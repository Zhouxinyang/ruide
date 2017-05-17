<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 阿里巴巴
 */
class AlibabaController extends CommonController
{
    private $shopId = 10;
    public $authRelation = array(
        'goods'       => 'index',
        'syncgoods'   => 'sync',
        'synctrade'   => 'sync',
        'syncorder'   => 'sync',
        'detail'      => 'index',
        'goodses'     => 'index'
    );
    function __construct(){
        parent::__construct();
        $shopId = $this->user('shop_id');
        $isAdmin = $this->user('is_admin');
        if($shopId != $this->shopId && !$isAdmin){
            //$this->error('您无权限查看');
        }
    }
    
    /**
     * 店铺列表
     */
    public function index(){
        if(IS_AJAX){
            $where = strlen($_GET['name']) > 1 ? "`name` LIKE '%".addslashes($_GET['name'])."%'" : '';
            $list = M('alibaba_shop')->where($where)->select();
            $this->ajaxReturn($list);
        }
        $this->display();
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
        $Model = M('alibaba_shop');
        $data = $Model->find($_GET['id']);
        if(IS_POST){
            $id = I('post.id/d');
            if(!is_numeric($id)){
                $this->error('编辑ID不能为空');
            }
            if(empty($_POST['receiver_name'])){
                $this->error('退货联系人不能为空');
            }
            if(!is_numeric($_POST['receiver_province'])){
                $this->error('退货省份不能为空');
            }
            if(!is_numeric($_POST['receiver_city'])){
                $this->error('退货城市不能为空');
            }
            if(!is_numeric($_POST['receiver_county'])){
                $this->error('退货区/县不能为空');
            }
            if(empty($_POST['receiver_detail'])){
                $this->error('退货详细地址不能为空');
            }
            if(!is_numeric($_POST['receiver_mobile'])){
                $this->error('退货电话不能为空');
            }
            if(!is_numeric($_POST['receiver_zip'])){
                $this->error('退货邮编不能为空');
            }
        
            $data = array(
                'id'                 => addslashes($_POST['id']),
                'receiver_name'      => addslashes($_POST['receiver_name']),
                'receiver_mobile'    => addslashes($_POST['receiver_mobile']),
                'receiver_zip'       => addslashes($_POST['receiver_zip']),
                'receiver_province'  => addslashes($_POST['receiver_province']),
                'receiver_city'      => addslashes($_POST['receiver_city']),
                'receiver_county'    => addslashes($_POST['receiver_county']),
                'receiver_detail'    => addslashes($_POST['receiver_detail'])
            );
            $Model->save($data);
            $this->success('已保存');
        }
        $this->assign('data',$data);
        $this->display();
    }
    
    /**
     * 产品
     */
    public function goods(){
        $Model = new \Common\Model\AlibabaModel();
        $list = $Model->getGoodsList($_GET['shop']);
        
        if(empty($list)){
            $this->error('暂无商品信息');
        }
        
        $this->assign(array(
            'list' => $list,
            'shopName' => $_GET['shop']
        ));
        $this->display();
    }
    
    /**
     * 同步1688店铺
     * lanxuebao
     */
    public function sync(){
        $Model = new \Common\Model\AlibabaModel();
        $result = $Model->syncShop();
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success('已同步');
    }
    
    /**
     * 同步淘系产品
     * @return multitype:unknown Ambigous <>
     */
    public function syncGoods(){
        $result = $this->dosyncGoods($_GET['tao_id']);
        if($result['error'] != ''){
            $this->error($result['error']);
        }
        $this->success('已同步');
    }
    
    private function dosyncGoods($taoId){
        if (!is_numeric($taoId)) {
            $this->error('错误的淘宝商品ID。');
        }
        
        $Model = new \Common\Model\AlibabaModel();
        return $Model->syncGoods($taoId);
    }
    
    /**
     * 同步淘系订单
     * @return multitype:unknown Ambigous <>
     */
    public function syncTrade(){
        $tid = $_GET['tid'];
        if (!is_numeric($tid)) {
            $this->error('错误的订单ID。');
        }
        $result = D('Alibaba')->getAliTrade($tid);

        if(!empty($result)){
            $this->success('已同步');
        }
        
        $this->success('无更新内容');
    }
    
    /*
    * 批量同步1688订单
    */
    public function syncOrder(){
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode(array('status' => 1, 'info' => '已开始自动更新，您可以干点别的，请勿短时间内重复更新'));
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();
        set_time_limit(0);
        
        $shop_id = $this->user('shop_id');
        $tid = is_numeric($_GET['tid']) && strlen($_GET['tid']) == 13 ? $_GET['tid'] : date('Ymd', strtotime('-2 day')).'00000';
        D('Alibaba')->syncAllTrade($tid, $shop_id);
    }

    // 商品列表
    public function goodses(){
        if(IS_AJAX){
            # 查询处理
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 10);
            $seller_nick = I('get.name', 0);
            $key = I('get.key', 0);
            $tao_id = I('get.tao_id', 0);
            $where = array();

            if (strlen($seller_nick)>0) 
                $where['a.seller_nick'] = array('LIKE','%'.addslashes($seller_nick).'%');
            if (strlen($key)>0) 
                $where['a.subject'] = array('LIKE','%'.addslashes($key).'%');
            if (is_numeric($tao_id)) $where['a.tao_id'] = $tao_id;

            # 分页
            $total = M('alibaba_goods')
                ->alias('a')
                ->where($where)
                ->count();
            if($total == 0){
                $this->ajaxReturn(array(
                    "total" => $total,
                    "rows" => array()
                ));
            }

            # 读取数据
            $rows = M('alibaba_goods')
                ->alias('a')
                ->where($where)
                ->order('last_update DESC')
                ->limit($offset,$limit)
                ->select();
            // 根据tao_id集合查询平台商品
            $tmp = array();
            $tao_ids = array();
            foreach ($rows as $k=>$v) {
                $tmp[] = $v['tao_id'];
                $tao_ids[$v['tao_id']] = $k;
            }
            $mallGoodses = M('mall_goods')
                ->field('is_display,title,id,tao_id')
                ->where(array(
                    'tao_id' => array('IN', implode(',', $tmp)),
                    'is_del' => 0
                ))->select();
            # 数据处理
            foreach ($mallGoodses as $k => $v) {
                if (strlen($rows[$tao_ids[$v['tao_id']]]['title'])>0) {
                    $rows[] = array_merge($rows[$tao_ids[$v['tao_id']]],$v);
                }else{
                    $rows[$tao_ids[$v['tao_id']]] 
                        = array_merge($rows[$tao_ids[$v['tao_id']]],$v);
                }
            }

            # 投放页面
            $this->ajaxReturn(array(
                "total" => $total,
                "rows" => $rows
            ));
        }
        # 访问
        $this->display();
    }

    //商品详情
    public function detail(){
        $this->dosyncGoods($_GET['tao_id']);
        $data = M('alibaba_goods')->find($_GET['tao_id']);
        $this->assign('data', $data);
        $this->display();
    }
}
?>