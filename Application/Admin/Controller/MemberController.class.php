<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Org\Wechat\WechatAuth;

/**
 * 会员管理
 * @author lanxuebao
 *
 */
class MemberController extends CommonController
{
    public function index(){
        if(IS_AJAX){
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 50);
            $wx_config = C('WEIXIN');
            $where = "";
            if($_GET['mid'])//ID
                $where .= " AND m.id IN (".addslashes($_GET['mid']).")";
            if(!empty($_GET['appid'])){
                $where .= " AND w_u.appid='".addslashes($_GET['appid'])."'";
            }
            if($_GET['name'])//姓名
                $where .= " AND m.nickname like '%".addslashes($_GET['name'])."%'";
            if($_GET['nickname'])//昵称
                $where .= " AND w_u.nickname like '%".addslashes($_GET['nickname'])."%'";
            if(is_numeric($_GET['mobile']))//联系电话
                $where .= " AND m.mobile='".addslashes($_GET['mobile'])."'";
            if(is_numeric($_GET['agent_level']))//代理级别
                $where .= " AND m.agent_level =".$_GET['agent_level'];
            if(is_numeric($_GET['city_id'])){//城市
                $where .= " AND m.city_id ='".$_GET['city_id']."'";
            }else if(is_numeric($_GET['province_id'])){
                $where .= " AND m.province_id ='".$_GET['province_id']."'";
            }
            
            if($where != ''){
                $where = "WHERE ".ltrim($where, ' AND');
            }

            $Model = M();
            $rowstotal =$Model->query("SELECT count(*) AS count
                                     FROM member AS m
                                     INNER JOIN wx_user AS w_u ON m.id=w_u.mid ".$where);
            $total = $rowstotal[0]["count"];
            
            if($total == 0){
                $this->ajaxReturn(array(
                    "total" => $total,
                    "rows" => array()
                ));
            }
            
            $rows = $Model->query("SELECT 
                                    m.id,m.nickname as name,w_u.nickname,w_u.province,w_u.city,w_u.country,
                                    FROM_UNIXTIME(m.reg_time, '%Y-%m-%d %H:%i') as reg_time,
                                    m.nickname AS name,m.sex,m.mobile, w_u.wxno,
                                    m.balance,m.agent_level,m.pid,m.is_employee
                                FROM member AS m
                                INNER JOIN wx_user AS w_u ON m.id=w_u.mid
                                {$where}
                                ORDER BY w_u.mid DESC
                                LIMIT {$offset},{$limit}");
            
            $MCity = D('City');
            $agentLevel = $this->agentLevel();
            foreach($rows as $k=>$v){
                $level = $agentLevel[$v['agent_level']];
                $rows[$k]['agent_level'] = ($v['agent_level'] !== '')?$level['title']:'';
                $rows[$k]['name'] = $v['name'];
                $rows[$k]['is_employee'] = ($v['is_employee'] == 1)?"是":"否";
                $rows[$k]['city_name'] = $MCity->find($v['city_id']);
            }
            
            $this->ajaxReturn(array(
                "total" => $total,
                "rows" => $rows
            ));
        }
        
        if($_GET['mid']){
            $this->assign('mid',$_GET['mid']);
        }

        $this->assign("wxlist",  C('WXLIST'));
        $levels = $this->agentLevel();
        $this->assign("levels",$levels);
        $this->display();
    }
    
    /**
     * 显示下级代理
     * @author zw
     */
    public function show_cm(){
        if($_GET['data'] != 'json'){
            $this->display();
        }

        $pid = I("get.mid" ,'', int);
        if (!$pid) //上级代理id
            $this->error('错误的用户编号！');
        $rowstotal = M()->query("select nickname,'上级代理' as pid,agent_level,mobile from member where id =
                                        (select pid from member where id  = ".$pid.")
                                UNION
                                select nickname,'上二级代理' as pid,agent_level,mobile from member where id =
                                        (select pid from member where id =
                                        (select pid from member where id  = ".$pid."))
                                UNION
                                select nickname,'上三级代理' as pid,agent_level,mobile from member where id =
                                        (select pid from member where id =
                                        (select pid from member where id =
                                        (select pid from member where id  = ".$pid.")))
                                UNION
                                select nickname,'一级代理' as pid,agent_level,mobile from member where pid  = ".$pid."
                                UNION
                                select nickname,'二级代理' as pid,agent_level,mobile from member where pid in
                                		(select id from member where pid  = ".$pid.")
                                UNION
                                select nickname,'三级代理' as pid,agent_level,mobile from member where pid in
                                	(select id from member where pid in
                                			(select id from member where pid  = ".$pid."))");
        $total = count($rowstotal);
        if($total == 0){
            $this->ajaxReturn(array(
                "total" => $total,
                "rows" => array()
            ));
        }
        
        $allLevle = $this->agentLevel();
        foreach ($rowstotal as $k=>$v) {
            $level = $allLevle[$v['agent_level']];
            $rowstotal[$k]['agent_level'] = ($v['agent_level'] !== '')?$level['title']:'';
        }
        
        $this->ajaxReturn(array(
            "total" => $total,
            "rows" => $rowstotal
        ));
    }
    
    
    /**
     * 资金流水
     * @author wangjing
     */
    public function balance_list(){
        $this->display();
    }
    
    /**
     * 修改等级
     * @author wangjing
     */
    public function change_level($id = 0){
        if(empty($id)){
            $this->error('修改项不能为空！');
        }
        $Model = M('member');
        if(IS_POST){
            $data = I("post.");
            if($data['agent_level'] == ''){
                $this->error('请选择代理级别!');
            }
            //查询代理信息
            $member = $Model->field('id,agent_level')->where("id IN(".$id.")")->select();
            if(empty($member)){
                $this->error('请选择代理！');
            }
            //修改等级
            $result = $Model->where("id IN(".$id.")")->save($data);
            if($result === false){
                $this->error("修改失败！");
            }
            
            //保存修改日志
            $uid = $this->user('id');
            $levelModel = D('LevelChange');
            $result = $levelModel->add($member, $uid, $data['agent_level'], 1);
            if($result < 0){
                $this->error($levelModel->getError());
            }
            $this->success('操作成功');
        }
        
        $levels = $this->agentLevel();
        $this->assign(array(
            'ids' => $id,
            'levels' => $levels
        ));
        $this->display();
    }
    
    /**
     * 设置为公司员工
     * @author wangjing
     */
    public function employee($id = 0){
        if(empty($id)){
            $this->error('修改项不能为空！');
        }
        $Model = M('member');
        if(IS_POST){
            $data = I("post.");
            $result = $Model->where("id IN(".$id.")")->save($data);
            if($result >= 0){
                $this->success("设置成功！");
            }else{
                $this->error("设置失败！");
            }
        }
        $this->assign(array(
            'ids' => $id
        ));
        $this->display();
    }
    
    /**
     * 员工关系调整页面
     */
    public function member_out(){
        $change_mid = $_GET['change_mid'];
        if(IS_GET){
            $this->assign('change_mid',$change_mid);
            $this->display();
        }
        
        if($_POST['type'] == 'change_pid'){
            $this->change_pid();
        }else if($_POST['type'] == 'leave_out'){
            $this->leave_out();
        }
        
        $this->error('未知操作');
    }
    
    /**
     * 修改下级的pid
     */
    private function change_pid(){
        $Model = M('member');
        if(!is_numeric($_POST['id']) || !is_numeric($_POST['change_mid'])){
            $this->error('非法数据！');
        }else if($_POST['id'] == $_POST['change_mid']){
            $this->error('不能把自己设为上级！');
        }
        
        //修改人
        $sourceMember = $Model->find($_POST['change_mid']);
        if(empty($sourceMember)){
            $this->error('数据不存在！');
        }
        
        if($sourceMember['pid'] == $_POST['id']){
            $this->success('修改成功');
        }
        
        //选中的父级
        $targetMember = $Model->find($_POST['id']);
        if(empty($targetMember)){
            $this->error('数据不存在！');
        }
        
        //验证 选中的父级 是否在 修改人的下线中 
        $is_my_child = $Model->query("SELECT isMyChild({$targetMember['id']},{$sourceMember['id']}) AS result");
        if($is_my_child[0]['result'] != 0){
            $this->error('调整的代理为选中的代理的下级，无法修改！');
        }
        
        //把修改人的pid 改成 选中的父级
        $Model->execute("UPDATE member SET pid=".$targetMember['id']." WHERE id=".$sourceMember['id']);
        
        $this->success("修改成功！");
    }
    
    /**
     * 离职
     */
    private function leave_out(){
        if(!is_numeric($_POST['id']) || !is_numeric($_POST['change_mid'])){
            $this->error('非法数据！');
        }else if($_POST['id'] == $_POST['change_mid']){
            $this->error('自己不能接管自己，请选择其他人！');
        }
        
        $Model = M('member');
        //离职的代理
        $sourceMember = $Model->find($_POST['change_mid']);
        if(empty($sourceMember)){
            $this->error('离职代理不存在！');
        }
        
        //接管的代理
        $targetMember = $Model->find($_POST['id']);
        if(empty($targetMember)){
            $this->error('接管代理不存在！');
        }
        
        //判定接管的代理是否在离职代理的下面
        $is_my_child = $Model->query("SELECT isMyChild({$targetMember['id']},{$sourceMember['id']}) AS result");
        
        if($is_my_child[0]['result'] != 0){
            $this->error('接管代理为离职代理的下线，无法更改！');
        }
        //把离职代理 下级 的pid 改成 接管代理的id
        $Model->execute("UPDATE member SET pid=".$targetMember['id']." WHERE pid=".$sourceMember['id']);
        //修改离职代理的等级
        if($sourceMember['agent_level'] > 0){
            $Model->execute("UPDATE member SET agent_level=3 WHERE id=".$sourceMember['id']);
        }
        $this->success("修改成功！");
    }
    
    /*
     * 补发积分
     */
    public function reissue_score($id = 0){
        if(IS_POST){
           $this->reissue();
        }
        
        $this->assign('id',$id);
        $this->display();
    }
    
    /**
     * 添加积分调用
     */
    public function reissue(){
        $uid = $this->user('id');
        $post = array(
            'mid'        =>$_POST['mid'],
            'type'       =>$_POST['type'],
            'reason'     =>$_POST['reason'],
            'balance'    =>$_POST['balance'],
            'no_balance' =>$_POST['no_balance'],
        );
        
        if(!is_numeric($post['mid'])){
            $this->error('会员ID不能为空');
        }
        if(empty($post['reason'])){
            $this->error('原因不能为空');
        }
        if(!is_numeric($post['balance']) || !is_numeric($post['no_balance'])){
            $this->error('请输入有效的金额');
        }
        if($post['balance']==0 && $post['no_balance']==0){
            $this->error('金额不能都为0');
        }
        if($post['balance'] > 200 || $post['no_balance'] > 200){
            $this->error('金额不能大于200');
        }
        
        $start_time = date("Y-m-d 00:00:00");
        $end_time = date("Y-m-d 23:59:59");
        $Model = new \Common\Model\BalanceModel();
        $exists = $Model->where("create_time BETWEEN '".$start_time."' AND '".$end_time."' AND mid=".$post['mid'])
                        ->join('member_reissued AS m on m.bid = id','inner')
                        ->find();
        if(!empty($exists)){
            $this->error('一天只能补发一次积分,请明天在添加');
        }

        $id = $Model->add($post);
        $Model->execute("insert into member_reissued set bid={$id}, uid={$uid},balance={$post['balance']}, no_balance={$post['no_balance']}");
        
        $wxUser = $Model->getWXUserConfig($post['mid']);
        if(!empty($wxUser['config'])){
            $money = bcadd($post['balance'], $post['no_balance'], 2);
            $WechatAuth = new WechatAuth($wxUser['config']['WEIXIN']);
            $message = array(
                'template_id' => $wxUser['config']['WX_TEMPLATE']['TM00335'],
                'url' => $wxUser['config']['HOST'].'/h5/balance#record',
                'data' => array(
                    'first'    => array('value' => '您有新积分'.($money>0 ? '到账' : '扣款').'，详情如下。', 'color' => '#173177'),
                    'account'  => array('value' => '当前账号'),
                    'time'     => array('value' => date('Y年m月d日 H:i')),
                    'type'     => array('value' => $Model->balacne_type($post['type'])),
                    'creditChange' => array('value' => $money>0 ? '增加' : '减少'),
                    'number'       => array('value' => $money),
                    'creditName'   => array('value' => '积分'),
                    'amount'       => array('value' => '***'),
                    'remark'       => array('value' => '人工补发：'.$post['reason'])
                )
            );
            
            $WechatAuth->sendTemplate($wxUser['openid'], $message);
        }
        $this->success("保存成功");
    }
        
}
?>