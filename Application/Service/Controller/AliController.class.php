<?php
namespace Service\Controller;

use Common\Common\CommonController;
use Think\Model;
use Common\Model\AopModel;

/**
 * 阿里接口
 */
class AliController extends CommonController
{
    var $targer;
    var $shop_id;
    function __construct(){
        parent::__construct();
        $this->targer = 'wslm.hljwtlm3.com';
        $this->shop_id = 10;
    }

    // 10分钟同步阿里商品
    // tao_id 商品淘宝ID id 任务池ID
    public function syncGoods($tao_id)
    {
        if (!is_numeric($tao_id)) {
            $this->error('错误的编号');
        }

        $key = 'goods-'.$tao_id;
        $Model = new \Common\Model\AlibabaModel();
        $result = $Model->syncGoods($tao_id);
        /*
        M('web_mq_task')->add(array(
                'key'   => 'goods-'.$tao_id,
                'created'=> date('Y-m-d H:i:s'),
                'runtime'=> date('Y-m-d H:i:s', time()+600),
                'url'=> 'http://'.$this->targer.'/service/ali/syncGoods?tao_id='.$tao_id,
                'status'=> 'todo'
            ), array(), true);
        */
        $this->success('success');
    }

    // 10分钟同步订单状态
    public function syncOrder()
    {
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode(array('status' => 1, 'info' => 'success'));
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();
        set_time_limit(0);
        
        $nextTid = is_numeric($_GET['next']) ? $_GET['next'] : date('Ymd',strtotime('-10 day')).'00000';
        $alibaba = new \Common\Model\AlibabaModel();
        $result = $alibaba->syncAllTrade($nextTid);
        $nextTid = $result['min_tid'];
        
        // 晚20 ~ 早8点不更新
        $hour = date('H');
        if($hour >= 23){ // 晚11点以后第二天8点再开始更新信息
            $nextTime = date('Y-m-d', strtotime('+1 day')).' 08:00:00';
        }else{ // 10~30分钟更新一次
            $nextTime = date('Y-m-d H:i:s', time() + mt_rand(600, 1800));
        }
        
        M('web_mq_task')->add(array(
                'key'    => 'order',
                'created'=> date('Y-m-d H:i:s'),
                'runtime'=> $nextTime,
                'url'=> 'http://'.$this->targer.'/service/ali/syncOrder?next='.$nextTid,
                'status'=> 'todo'
            ), array(), true);
    }

    // 读取店铺、发布同步商品列表任务
    public function syncShop()
    {
        $AOP = new AopModel();
        $shops = $AOP->getSupplier();

        $data[] = array(
                'key'    => 'shop',
                'created'=> date('Y-m-d H:i:s'),
                'runtime'=> date('Y-m-d', time()+24*3600).' 01:00:00',
                'url'=> 'http://'.$this->targer.'/service/ali/syncShop',
                'status'=> 'todo'
            );
        foreach ($shops as $k => $v) {
            $data[] = array(
                'key'    => 'goods_list-'.$v,
                'created'=> date('Y-m-d H:i:s'),
                'runtime'=> date('Y-m-d H:i:s', time()),
                'url'=> 'http://'.$this->targer.'/service/ali/syncGoodsList?shop_id='.urlencode($v),
                'status'=> 'todo'
            );
        }

        M('web_mq_task')->addAll($data, array(), true);
        $this->success('success');
    }

    // 同步商品列表
    public function syncGoodsList()
    {
        $shop_id = I('shop_id');
        $aop = new AopModel();
        $shops = $aop->getSupplier();

        if (!in_array($shop_id, $shops)) {
            $this->error('error');
        }

        $i = 0;
        $temp = true;
        $offers = array();
        while ( $temp === true || count($temp)>=100 )
        {
            $temp = $aop->getGoodsList($shop_id, $i*100, 100);
            if (!is_null($temp)) {
                $offers = array_merge($offers, $temp);
                $i++;
            }
        }

        foreach ($offers as $v) {
            $data[] = array(
                'key'    => 'goods-'.$v,
                'created'=> date('Y-m-d H:i:s'),
                'runtime'=> date('Y-m-d H:i:s', time()),
                'url'=> 'http://'.$this->targer.'/service/ali/syncGoods?tao_id='.urlencode($v),
                'status'=> 'todo'
            );
        }

        M('web_mq_task')->addAll($data, array(), true);
        $this->success('success');
    }

    // 获取买家订单
    public function getBuyerTrade()
    {
        $Aop = new \Common\Model\AopModel();
        $data = $Aop->getOrder('2632091465258131');
        print_data($data);
        
        $tid = '[2632091465258131]';
        $aop = new \Common\Model\AopModel();
        $list = $aop->getBuyerTrade($tid, 'b2b-1109393181');
        print_data($list);
        
        
        $tid = '2510164916157595,2512738091257595';
        $aop = new \Common\Model\AopModel();
        $list = $aop->getTradeListByTid($tid);
        
        $sumPayment = 0;
        foreach ($list as $item){
            $sumPayment += $item['sumPayment'];
        }
        echo $sumPayment / 100;
        print_data($list);
    }
}