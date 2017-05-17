<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 客服
 * @author wangjing
 *
 */
class KefuController extends CommonController
{
    /**
     * 根据类型获取二维码图片
     */
    public function qrcode(){
        // 首选客服
        $list = $rows = array();
        $sql = "SELECT id, nickname, weixin, qq, work_start, work_end, ticket, kf_account, qrcode
                FROM kf_list WHERE enabled=1";
        
        // 如果是商品专属客服
        if(is_numeric($_GET['goods'])){
            $where = " AND id IN(SELECT kf_id FROM kf_goods WHERE goods_id={$_GET['goods']})";
            $list = M()->query($sql.$where);
        }
        
        // 找指定店铺客服
        if(count($list) == 0 && is_numeric($_GET['shop'])){
            $where = " AND MATCH (shop_id) AGAINST ({$_GET['shop']} IN BOOLEAN MODE)";
            $list = M()->query($sql.$where);
        }

        $type = empty($_GET['type']) ? 1 : $_GET['type'];
        if($type == 1){ // 咨询
            $config = C('KEFU');
            if(count($list) == 0){
                $list[] = $config;
            }else{
                // 查找在线的客服
                $wechatAuth = new \Org\Wechat\WechatAuth($config['appid']);
                $onLineList = $wechatAuth->getOnlineKFList();
                
                foreach ($onLineList as $online){
                    foreach ($list as $i=>$item){
                        if($item['kf_account'] == $online['kf_account']){
                            $item['qrcode'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$item['ticket'];
                            unset($item['ticket']);
                            $item['online'] = 1;
                            $rows[] = $item;
                            unset($list[$i]);
                        }
                    }
                }
            }
        }
        
        
        // 根据类型找客服
        if(count($rows) == 0 && count($list) == 0){
            $list = M()->query($sql." AND type=".$type);
        }
        
        if(count($rows) == 0 && count($list) == 0){
            $this->error('暂无相关客服');
        }

        shuffle($rows);
        shuffle($list);
        foreach($list as $item){
            if($type != 2){ // 不是小视频
                $item['qrcode'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$item['ticket'];
            }
            unset($item['ticket']);
            $item['online'] = false;
            $rows[] = $item;
        }
        $this->ajaxReturn(array('connected' => 0, 'rows' => $rows));
    }
    
    /**
     * 评价客服
     */
    public function evaluate(){
        if(!is_numeric($_GET['id'])){
            $this->error('缺少评价ID');
        }
        
        $Model = M('kf_evaluate');
        $data = $Model->find($_GET['id']);
        if(empty($data)){
            $this->error('评价不存在');
        }
        
        if($data['attitude'] == 0 && is_numeric($_GET['attitude'])){
            $attitude = $_GET['attitude'];
            if($attitude < 1){
                $attitude = 1;
            }else if($attitude > 5){
                $attitude = 5;
            }
            
            $ip = get_client_ip();
            $Model->execute("UPDATE {$Model->getTableName()} SET attitude={$attitude}, ip='{$ip}' WHERE id=".$data['id']);
            $Model->execute("UPDATE kf_list SET amount=amount+1, score=score+{$attitude} WHERE id=".$data['kf_id']);
            $data['attitude'] = $attitude;
        }

        $kefu = M('kf_list')->find($data['kf_id']);
        $kefu['avg_score']=bcdiv($kefu['score'], $kefu['amount'],1);

        $this->assign(array(
            'data'    => $data,
            'kefu'     => $kefu,
            'canEdit' => empty($data['message']),
        ));
        $this->display();
    }
    
    /**
     * 保存意见反馈
     */
    public function feedback(){
        if(!is_numeric($_POST['id'])){
            $this->error('缺少反馈ID');
        }
        $Model = M('kf_evaluate');
        $data = $Model->find($_POST['id']);
        if(empty($data)){
            $this->error('评价不存在');
        }
        
        if(empty($data['message'])){
           $time = date("Y-m-d m:i:s");
           $attitude = addslashes($_POST['attitude']);
           $mobile = addslashes($_POST['mobile']);
           $message = addslashes($_POST['message']);
           $result = $Model->execute("UPDATE kf_evaluate SET attitude={$attitude},message='{$message}',mobile='{$mobile}',evaluate_time = '{$time}' WHERE id=".$_POST['id']);
           $this->success('反馈已提交');
       }
    }
}

?>