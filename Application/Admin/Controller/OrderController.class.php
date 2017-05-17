<?php
namespace Admin\Controller;
use Common\Common\CommonController;

/**
 * 订单管理
 * @author lanxuebao
 *
 */
class OrderController extends CommonController
{
    private $Model;
    private $allShop = false;
    
    function __construct(){
        parent::__construct();
        $this->allShop = \Common\Common\Auth::get()->validated('admin','shop','all');
    }
    
    /**
     * 我的订单
     */
    public function index(){
        $shopId = $this->user('shop_id');

        // 全部店铺的权限
        if($this->allShop){
            $shops = $this->shops();
            $this->assign('shops', $shops);
            
            if(is_numeric($_GET['shop_id']) || $_GET['shop_id'] == 'all'){
                $shopId = $_GET['shop_id'];
            }
        }
        
        $this->getOrderList($shopId);
    }
    
    private function getOrderList($shopId = 'all'){
        $tid = I('get.tid', '');//订单号
        $start_date = I('get.start_date', date('Y-m-d 00:00:00', strtotime('-1 day')));//下单时间  开始
        $end_date = I('get.end_date', date('Y-m-d 23:59:59'));//下单时间  结束
        $pay_start_date = I('get.pay_start_date');//付款时间  开始
        $pay_end_date = I('get.pay_end_date');//付款时间  开始
        $receiver_name = I('get.receiver_name');//收货人姓名
        $receiver_mobile = I('get.receiver_mobile');//收货人手机号
        $status = I('get.status', $shopId == 'all' ? '' : 'tosend');//订单状态
        $buyer_mobile = I('get.buyer_mobile');
        $title = I('get.title');

        $Static = D('Common/Static');
        $refundedState   = $Static->refundedState();
        if(!IS_AJAX){
            $order_status    = $Static->orderStatus();
            //验证是否有打印订单的权限
            $access = \Common\Common\Auth::get()->validated('admin','order','print_and_send');
            
            $this->assign(array(
                'order_status'     => $order_status,
                'refundedState'    => $refundedState,
                'access'           => $access,
                "search"           => array(
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'receiver_mobile' => $receiver_mobile,
                    'status' => $status
                ),
                'currentShopId' => $shopId
            ));
            $this->display('index');
        }
        
        $this->Model = new \Common\Model\OrderModel();
        
        $page = I('get.page/d', 1);
        $offset = 10;
        $limit = ($page - 1) * 10;
        $total = 0;
        $list = array();
         
        $where = array();
        if(is_numeric($buyer_mobile)){
            $mids = $this->Model->query("SELECT GROUP_CONCAT(id) AS ids FROM member WHERE mobile='{$buyer_mobile}'");
            $mids = $mids[0]['ids'];
            if(empty($mids)){
                $this->error('下单人手机号无对应会员信息');
            }
            
            if(strpos($mids, ',')){
                $where[] = "trade.buyer_id IN({$mids})";
            }else{
                $where[] = "trade.buyer_id={$mids}";
            }
        }
        
        if(is_numeric($_GET['buyer_id'])){
            $where[] = "trade.buyer_id={$_GET['buyer_id']}";
        }
        
        if(is_numeric($tid)){
            if(strlen($tid) == 13){ // 本系统
                $where[] = "trade.tid='{$tid}'";
            }else if(strlen($tid) == 16){ // 1688订单
                $tids = $this->Model->query("SELECT GROUP_CONCAT(tid) AS tid FROM alibaba_trade WHERE out_tid='{$tid}'");
                $tids = $tids[0]['tid'];
                
                if(empty($tids)){
                    $this->error('外部订单号不存在');
                }
                $where[] = "trade.tid IN ({$tids})";
            }else{
                $this->error('订单号格式未识别');
            }
        }
        if(is_numeric($shopId)) $where[] = "trade.seller_id='{$shopId}'";
        
        if($start_date != ''){
            $where[] = "trade.tid>'".(str_replace('-', '', substr($start_date, 0, 10)).'00000')."'";
            $where[] = "trade.created>='".$start_date."'";
        }
        if($end_date != ''){
            $where[] = "trade.tid<'".(str_replace('-', '', substr($end_date, 0, 10)).'99999')."'";
            $where[] = "trade.created<='".$end_date."'";
        }
        
        if($pay_start_date != '') $where[] = "trade.pay_time>='".$pay_start_date."'";
        if($pay_end_date != '') $where[] = "trade.pay_time<='".$pay_end_date."'";
        if($receiver_name) $where[] = "trade.receiver_name='".$receiver_name."'";
        if(is_numeric($receiver_mobile)) $where[] = "trade.receiver_mobile='".$receiver_mobile."'";
        if($status == 'error1688'){
            $where[] = "alibaba.`status` IN ('toorder', 'error')";
        }else if($status != '') {
            $where[] = "trade.status='".addslashes($status)."'";
        }
        
        if($_GET['refund_state'] == ''){
            
        }else if($_GET['refund_state'] == 'refunding'){
            $where[] = "trade.refund_state IN ('partial_refunding','full_refunding')";
        }else if($_GET['refund_state'] == 'refunded'){
            $where[] = "trade.refund_state IN ('partial_refunded', 'partial_failed', 'full_refunded', 'full_failed')";
        }else{
            $where[] = "trade.refund_state='".addslashes($_GET['refund_state'])."'";
        }

        $join = " LEFT JOIN alibaba_trade AS alibaba ON alibaba.tid=trade.tid AND is_del=0";
        if($title != ''){
            $where[] = "`order`.title like '%".addslashes($title)."%'";
            $join .= " INNER JOIN mall_order AS `order` ON `order`.tid = trade.tid";
        }
        $where = "WHERE ".implode(" AND ", $where);
         
        $sql = "SELECT COUNT(DISTINCT trade.tid) AS total FROM mall_trade AS trade {$join} {$where}";
        $total = $this->Model->query($sql);
        $total = $total[0]['total'];
        
        if($total > 0){
            $sql = "SELECT trade.*, `order`.title, `order`.product_id, `order`.pay_type, `order`.num, `order`.price, `order`.goods_id, `order`.sku_json, `order`.pic_url
                    FROM (
                        SELECT trade.tid, trade.`status`, trade.created, trade.type, trade.pay_type, trade.adjust_fee,
                          trade.total_fee, trade.post_fee, trade.paid_balance, trade.paid_no_balance, trade.discount_fee, trade.payment, trade.paid_fee,
                          trade.receiver_name, trade.receiver_mobile,
                          trade.buyer_id, trade.buyer_nick, trade.buyer_remark, trade.seller_id, trade.seller_nick, trade.seller_remark,
                          trade.refund_state, trade.refunded_fee, trade.express_no, trade.pay_time, GROUP_CONCAT(alibaba.id) AS ali_id
                        FROM mall_trade AS trade
                        {$join}
                        {$where}
                        GROUP BY trade.tid
                        ORDER BY trade.tid DESC
                        LIMIT {$limit}, {$offset}
                    ) AS trade
                    INNER JOIN mall_order AS `order` ON `order`.tid = trade.tid
                    ORDER BY trade.tid DESC";
            
            $aliIdList = '';  // 1688订单id         
            $_list = $this->Model->query($sql);
            
            // 是否允许手动修改1688单号
            $accessSetOutTradeNo = \Common\Common\Auth::get()->validated('admin','order','setOutTradeNo');
            
            // 处理成主订单信息附加子订单信息
            foreach($_list as $trade){
                if(!isset($list[$trade['tid']])){
                    $this->Model->handle($trade);
                    
                    //验证订单是否可以取消
                    $can_cancel = $this->Model->can_cancel($trade);
                    
                    $refunded_desc = '';
                    if($trade['refund_state'] != 'no_refund'){
                        $refunded_desc = $refundedState[$trade['refund_state']];
                        if($trade['refunded_fee'] > 0){
                             if($trade['refund_state'] == 'partial_refunded' || $trade['refund_state'] == 'full_refunded'){
                                 
                             }else{
                                 $refunded_desc .= '<br>已退款';
                             }
                             $refunded_desc .= '<br>'.$trade['refunded_fee'];
                        }
                    }
                    
                    $list[$trade['tid']] = array(
                        'tid'              => $trade['tid'],
                        'type'             => $trade['type'],
                        'pay_type'         => $trade['pay_type'],
                        'status'           => $trade['status'],
                        'status_str'       => $trade['status_str'],
                        'created'          => $trade['created'],
                        'buyer_id'         => $trade['buyer_id'],
                        'buyer_nick'       => $trade['buyer_nick'],
                        'receiver_name'    => $trade['receiver_name'],
                        'receiver_mobile'  => $trade['receiver_mobile'],
                        'payment'          => $trade['payment'],
                        'seller_nick'      => $trade['seller_nick'],
                        'buyer_remark'     => $trade['buyer_remark'],
                        'seller_remark'    => $trade['seller_remark'],
                        'pay_time'         => $trade['pay_time'],
                        'paid_fee'         => $trade['paid_fee'],
                        'total_fee'        => $trade['total_fee'],
                        'post_fee'         => $trade['post_fee'],
                        'sum_fee'          => $trade['sum_fee'],
                        'adjust_fee'       => $trade['adjust_fee'],
                        'paid_balance'     => $trade['paid_balance'],
                        'paid_no_balance'  => $trade['paid_no_balance'],
                        'express_no'       => $trade['express_no'],
                        'refund_state'     => $trade['refund_state'],
                        'refunded_fee'     => $trade['refunded_fee'],
                        'refunded_desc'    => $refunded_desc,
                        'out_trade_no'     => $trade['out_trade_no'],
                        'can_cancel'       => $can_cancel,
                        'express'          => array(),
                        'orders'           => array(),
                        'alibaba'          => array(),
                        'edit_out_tid'     => $trade['status'] == 'toout' && $accessSetOutTradeNo
                    );
                   
                    if(!empty($trade['express_no'])){
                        $express = explode(";", $trade['express_no']);
                        foreach($express as $k=>$v){
                            $list[$trade['tid']]['express'][] = explode(":", $v);
                        }
                    }
                    
                    if(!empty($trade['ali_id'])){
                        $aliIdList .= $aliIdList == '' ? $trade['ali_id'] : ','.$trade['ali_id'];
                    }
                }

                $list[$trade['tid']]['orders'][$trade['product_id']] = array(
                    'goods_id'         => $trade['goods_id'],
                    'product_id'       => $trade['product_id'],
                    'pay_type'         => $trade['pay_type'],
                    'title'            => $trade['title'],
                    'price'            => sprintf('%.2f', $trade['price']),
                    'original_price'   => $trade['original_price'],
                    'num'              => $trade['num'],
                    'pic_url'          => $trade['pic_url'],
                    'spec'             => get_spec_name($trade['sku_json']),
                    'errmsg'           => ''
                );
            }
            
            if($aliIdList != ''){
                $aliList = $this->Model->query("SELECT id, tid, out_tid, `status`, seller_nick, buyer_login_id, error_msg, products, type FROM alibaba_trade WHERE id IN ({$aliIdList})");
                foreach ($aliList as $item){
                    if($item['type'] == 1){
                        $list[$item['tid']]['sync1688'] = true;
                    }
                    $list[$item['tid']]['alibaba'][] = $item;
                    if(empty($item['products']) || strpos($item['products'], '{') === false){
                        continue;
                    }

                    $products = json_decode($item['products'], true);
                    foreach ($products as $productId=>$taoId){
                        if(!isset($list[$item['tid']]['orders'][$productId])){
                            continue;
                        }
                         
                        $errmsg = '';
                        if($item['status'] == 'error'){
                            $errmsg = $item['error_msg'];
                        }else if($item['status'] == 'toorder'){
                            $errmsg = '未下单';
                        }
                        $list[$item['tid']]['orders'][$productId]['errmsg'] = $errmsg;
                    }
                }
            }
        }
        
        $this->assign(array(
            'total' => $total,
            'page'  =>$page,
            'offset'=>$offset,
            'list'  => $list
        ));
        $this->display('list');
    }
    
    /**
     * 订单详细
     */
    public function detail(){
        $order_no = $_GET['tid'];//订单号
        $Model = D("Order");
        $trade = $Model->getTradeByTid($order_no);
        if ( $trade['type'] )
        {
            $ali_trade = M('alibaba_trade')->where(array('tid'=>$trade['tid']))->select();
            $this->assign('ali_trade', $ali_trade);
        }
        
        $refunded_desc = '无退款';
        if($trade['refund_state'] != 'no_refund'){
            switch ($trade['refund_state']){
                case 'partial_refunded':
                    $refunded_desc = '已部分退款<br>'.sprintf('%.2f', $trade['refunded_fee']);
                    break;
                case 'partial_refunding':
                    $refunded_desc = '部分退款中'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
                case 'full_refunded':
                    $refunded_desc = '已全额退款&nbsp;'.$trade['refunded_fee'];
                    break;
                case 'full_refunding':
                    $refunded_desc = '全额退款中'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
                case 'partial_failed':
                    $refunded_desc = '部分退款失败'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
                case 'full_failed':
                    $refunded_desc = '额退款失败'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
            }
        }
        $trade['refunded_desc'] = $refunded_desc;
        
        if($trade['status'] == 'tosend' && $trade['pay_time']){
            $trade['status_str'] = '买家已付款，等待商家发货';
            $trade['status_desc'] = '买家已付款，请尽快发货，否则买家有权申请退款';
        }else if($trade['status'] == 'topay'){
            $trade['status_str'] = '商品已拍下，等待买家付款';
            $trade['status_desc'] = '如买家未在规定时间内付款，订单将按照设置逾期自动关闭';
        }else if($trade['status'] == 'send'){
            $trade['status_str'] = '商家已发货';
            $trade['status_desc'] = '商家已发货,等待买家签收,确认快递';
        }else if($trade['status'] == 'success'){
            $trade['status_str'] = '交易完成';
            $trade['status_desc'] = '交易完成';
        }else if($trade['status'] == 'cancel'){
            $trade['status_str'] = '订单关闭';
            $trade['status_desc'] = D('Common/Static')->orderEndType($trade['end_type']);
            
            $trade['status_desc'] .= $refunded_desc;
        }

        if(empty($trade['buyer_remark']))
            $trade['buyer_remark'] = '无留言内容';
        if(empty($trade['seller_remark']))
            $trade['seller_remark'] = '无备注内容';
        
        if($trade['shipping_type'] == 'virtual'){
            $trade['shipping_type_str'] = '无需物流';
        }else if($trade['shipping_type'] == 'selffetch'){
            $trade['shipping_type_str'] = '上门自提';
        }else{
            $trade['shipping_type_str'] = '快递配送';
        }
        
        
        // 已付款
        $payment = array();
        if($trade['paid_fee'] > 0)
            $payment[] = '¥'.sprintf('%.2f', $trade['paid_fee']);
        $payments = count($payment) > 0 ? implode(' + ', $payment) : '¥0.00';
        $this->assign("payment",$payments);


        // 应付款
        $payment_desc = '';
        $add = array();
        $reduce = array();
        if($trade['total_fee'] > 0)
            $add[] = '商品'.sprintf('%.2f', $trade['total_fee']).'元';
        if($trade['post_fee'] > 0)
            $add[] = '运费'.sprintf('%.2f', $trade['post_fee']).'元';
        if($trade['discount_fee'] > 0)
            $reduce[] = '优惠'.sprintf('%.2f', $trade['discount_fee']).'元';
        if($trade['paid_balance'] > 0)
            $reduce[] = '零钱'.sprintf('%.2f', $trade['paid_balance']).'元';
        if($trade['paid_no_balance'] > 0)
            $reduce[] = '积分'.sprintf('%.2f', $trade['paid_no_balance']).'元';
        /*
        if($trade['paid_balance'] + $trade['paid_no_balance'] > 0)
            $reduce[] = '钱包'.sprintf('%.2f', $trade['paid_balance'] + $trade['paid_no_balance']).'元';
        */
        if($trade['adjust_fee'] > 0)
            $add = '调价'.sprintf('%.2f', abs($trade['adjust_fee'])).'元';
        else if($trade['adjust_fee'] < 0)
            $reduce = '调价'.sprintf('%.2f', abs($trade['adjust_fee'])).'元';
        if(count($add) > 0)
            $payment_desc .= implode(' + ', $add);
        if(count($reduce) > 0)
            $payment_desc .= ' - '.implode(' - ', $reduce);
        $this->assign("payment_desc",$payment_desc.'='.$trade['payment'].'元');

        //判断主订单是否可以取消 $can_cancel = 1可以取消
        $can_cancel = $Model->can_cancel($trade);
        
        $this->assign(array(
            'data'         =>  $trade,
            'can_cancel'   => $can_cancel
        ));
        
        $this->display();
    }
    
    /**
     * 取消订单
     */
    public function cancel(){
        $tid = I('post.tid');
        $reason   = I('post.reason', '');
        if(empty($reason))
            $this->error('请选择取消原因！');
        
        if(empty($tid))
            $this->error('订单编号不能为空');
        
        $Model = new \Common\Model\OrderModel();
        $trade = $Model->where("tid='{$tid}'")->find();
        
        if(empty($trade))
            $this->error('订单不存在');
        if($trade['refunded_fee'] > 0)
            $this->error('订单已退款'.$trade['refunded_fee'].'元，请使用退款功能');

        //验证订单是否可以取消
        $can_cancel = $Model->can_cancel($trade);
        if($can_cancel < 0){
            $this->error($Model->getError());
        }
        
        // 返还已付款金额
        $Model->cancelTrade($trade, $reason, true);
        $this->success();
    }
    
    /**
     * 订单备注
     */
    public function remark(){
        $tid = I('post.tid');
        $remark   = I('post.remark', '');
        if(empty($tid))
            $this->error('订单编号不能为空');
        
        M("mall_trade")->where("tid='%s'", array($tid))->save(array(
            'seller_remark'    => $remark,
            'modified'         => date('Y-m-d H:i:s')
        ));
        $this->success();
    }
    
    /**
     * 订单发货
     */
    public function send(){
        $tid = I('request.tid');
        $Model  = D('Order');
        $trade = $Model->getTradeByTid($tid);
        $express_list = D('Common/Static')->express();
        
        $orderCount = count( $trade["orders"]);
        $sendCount = 0; // 已发送订单数量
        $sended = array();
        $nosended = array();
        
        foreach($trade["orders"] as $k=>$v){
            if($v['shipping_type'] == 'selffetch'){
                $trade["orders"][$k]['express_name'] = '上门自提';
            }else if($v['shipping_type'] == 'virtual'){
                $trade["orders"][$k]['express_name'] = '无需物流';
            }
            
            if($v['status'] == 'send'){
                $sendCount++;
            }
        
            foreach($trade["logistics"] as $key=>$val){
                if($v["product_id"] == $val["product_id"]){
                    $v["num"] = $val["num"];
                    $v["express_id"] = $val["express_id"];
                    $v["express_no"] = $val["express_no"];
                    $v["express_name"] = $express_list[$val["express_id"]]['name'];
                    
                    $v["status"] = "send";
                    $v["status_str"] = "已发货";
                    $sended[] = $v;
        
                    $trade["orders"][$k]["num"] -= $val["num"];
                    if($trade["orders"][$k]["num"] <= 0){
                        unset($trade["orders"][$k]);
                    }
                }
            }
        }
        
        $nosended = $trade["orders"];
        if($_POST){
            $now = date("Y-m-d H:i:s");
            $logisticsList = array();
            $update = array();
            
            $add = array(
                "tid" => $tid,
                "product_id" => 0,
                "num" => 0,
                "express_id" => '',
                "express_no" => '',
                "consign_time" => $now,
            );
            
            if($_POST["shipping_type"] == 'express'){
                $add['express_id'] = $_POST["express_id"];
                $add['express_no'] = $_POST["express_no"];
            }else if($_POST["shipping_type"] == 'selffetch'){
                $add['express_id'] = 1;
                $add['express_no'] = 'selffetch';
            }else if($_POST["shipping_type"] == 'virtual'){
                $add['express_id'] = 2;
                $add['express_no'] = 'virtual';
            }
            
            foreach($nosended as $item){
                if(!isset($_POST['products'][$item['oid']]) || $item['send']){
                    continue;
                }
                
                $num = $_POST['products'][$item['oid']];
                if($item['num'] < $num){
                    $this->error('发货产品数量充裕');
                }

                $add['product_id'] = $item["product_id"];
                $add['num'] = $num;
                $logisticsList[] = $add;
                $update[] = "UPDATE mall_order SET shipping_type='{$_POST["shipping_type"]}'".($item['num'] == $num ? ",status='send'" : "")." WHERE oid='{$item['oid']}'";
                if($item['num'] == $num){
                    $sendCount++;
                }
            }
            
            M("mall_logistics")->addAll($logisticsList);
            $status = '';
            if($orderCount == $sendCount){
                $status = 'send';
            }else if($trade['status'] == 'tosend'){
                $status = 'sendpart';
            }
            
            if($status != ''){
                $sql = "UPDATE mall_trade SET status='{$status}'";
                if(!$trade['consign_time']){
                    $sql .= ",consign_time='{$now}'";
                }
                $update[] = $sql." WHERE tid='{$tid}'";
            }else{
                $status = $trade['status'];
            }
            
            foreach ($update as $sql){
                $Model->execute($sql);
            }
            
            // 计算代理收益
            if($status == 'send'){
                
            }
            $this->success(array('status' => $status));
        }

        $this->assign(array(
            'express_list' => $express_list,
            'trade'        => $trade,
            'orders'   => array_merge($sended, $nosended)
        ));
        $this->display();
    }
    
    /**
     * 订单修改价格--获取要修该的订单信息
     */
    public function get_change_order(){
        $this->Model = M("mall_order");
        $order_no = I('get.order_no');
        if($order_no == ""){
            $this->error("订单号不能为空！");
        }
        $order = $this->Model
                 ->where("order_no='%s'",$order_no)
                 ->field("order_no,status,total_price,total_fee,total_postage,adjust_fee,address_user_name,address_province,address_city,address_county,address_detail")
                 ->find();
        
        if(empty($order)){
            $this->error("订单不存在！");
        }
        if($order['status'] != "topay"){
            $this->error("订单状态已更新，无法改价！");
        }
        
        $order['products'] = $this->Model->query("SELECT * FROM mall_order_product WHERE order_id='{$order['order_no']}'");
        if(empty($order['products'])){
            $this->error("订单不存在！");
        }
        
        //数据处理
        foreach($order['products'] as $k=>$v){
            $sku_json = "";
            $v['sku_json'] = json_decode($v['sku_json']);
            foreach($v['sku_json'] as $key=>$val){
                $sku_json .= $val->sku_text.":".$val->text."&nbsp;&nbsp;";
            }
            $order['products'][$k]['sku_json'] = $sku_json;
        }
        
        $this->ajaxReturn($order);
    }
    
    /**
     * 订单修改价格
     */
    public function change_price(){
        $this->Model = M("mall_order");
        $order = array();
        $pay_time_out = C('ORDER_TIME_OUT');
        $order = I("post.order");
        
        if($order['order_no'] == ""){
            $this->error("订单号不能为空！");
        }
        
        $data = $this->Model->where("order_no='%s'",$order['order_no'])->find();
        
        //验证订单是否可以改价
        if(empty($data)){
            $this->error("订单不存在！");
        }
        if($data['status'] != "topay"){
            $this->error("订单状态已更新，无法改价！");
        }
        if($pay_time_out > 0 && strtotime($data['create_time']) + $pay_time_out <= time()){
            $order['status'] = 'cancel';
            $order['end_time'] = time();
            $order['end_type'] = 'timeout';
            M()->execute("UPDATE mall_order SET status='{$order['status']}', end_time='{$order['end_time']}', end_type='{$order['end_type']}' WHERE order_no='{$data['order_no']}'");
            $this->error("订单已超时未付款，无法改价！");
        }

        //计算总金额的值
        $order['total_fee'] = bcsub($data['total_fee'], (bcsub($data['adjust_fee'], $order['adjust_fee'], 2) + bcsub($data['total_postage'], $order['total_postage'], 2)), 2);
        
        if($order['total_fee'] <= 0){
            $this->error("修改价格不能小于总价格！");
        }
        
        $this->Model->where("order_no='{$data["order_no"]}'")->save($order);
        $this->ajaxReturn($order);
    }
    
    /**
     * 导出并发货
     */
    public function print_and_send(){
        $Model = D('ExportSendOrder');
        $shopId = $this->user('shop_id');
        if(is_numeric($_GET['shop_id']) && $shopId != $_GET['shop_id']){
            if(!$this->allShop){
                $this->error('您无权导出其他店铺订单');
            }
            $shopId = $_GET['shop_id'];
        }

        $uid = $this->user('id');
        $data = $Model->sendAndExport($shopId, $uid);
        if($data === false){
            $this->error($Model->getError());
        }
        exit();
    }
    
    /**
     * 导出订单
     */
    public function printOrder(){
        $Model = D('ExportSendOrder');
        $shopId = $this->allShop && is_numeric($_GET['shop_id']) ? $_GET['shop_id'] : $this->user('shop_id');
        $data = $Model->printOrder($shopId);
        if($data === false){
            $this->error($Model->getError());
        }
        die;
    }
    
    public function import(){
        $sellerId = $this->user('shop_id');
        if(IS_GET){
            $static = D('Static');
            $list = $static->express(true);
            $this->assign('express_list', $list);
            $this->display();
        }
        
        if(empty($_FILES) || !preg_match('/\.xl(s|sx)$/', $_FILES['file_stu']['name'], $match)){
            $this->error("请上传excel文件！");
        }
         
        if($_FILES["file_stu"]['error'] > 0){
            $this->error("文件格式错误！");
        }
         
        $folder = '../Upload/import/'.date('Y-m');
        if(!@is_dir($folder)){
            mkdir ($folder, 0777, true);
        }
        
        $filename = $folder.'/'.date("Y-m-d His").'_'.$sellerId.$match[0];
        move_uploaded_file($_FILES["file_stu"]["tmp_name"], $filename);
        $Model = D('ImportOrderExpress');
        $Model->import($filename, $sellerId);
    }
    
    /**
     * 订单备注
     */
    public function sendOne(){
        if(!is_numeric($_POST['tid'])){
            $this->error('订单号不能为空');
        }
        $data['tid'] = $_POST['tid'];
        $data['send'] = str_replace(array("：","；","\n"),array(":",";",";"), addslashes(I('post.send', '')));
        
        D('Order')->sendOne($data);
        $this->success();
    }
    
    /**
     * 设置外部订单号
     */
    public function setOutTradeNo(){
        $tid = $_REQUEST['tid'];
        if(!preg_match('/^\d{13}$/', $tid)){
            $this->error('订单号不能为空');
        }
        
        $Model = M('mall_trade');
        $trade = $Model->field("seller_id, `status`, receiver_name, receiver_mobile")->find($tid);
        if(empty($trade)){
            $this->error('订单号不存在');
        }else if(!$this->allShop && $trade['seller_id'] != $this->user('shop_id')){
            $this->error('您无权查看其它店铺订单');
        }else if($trade['status'] != 'toout'){
            $this->error('不是出库中不能更改外部订单号');
        }
        
        $loginIdList = $Model->query("SELECT login_id FROM alibaba_token WHERE expires_in>".NOW_TIME);
        foreach ($loginIdList as $i=>$item){
            $loginIdList[$i] = $item['login_id'];
        }
        
        // 查看已存在的订单号
        $existsList = $Model->query("SELECT id, tid, out_tid, `status`, buyer_login_id, error_msg, type FROM alibaba_trade WHERE tid={$tid} AND is_del=0");

        if(IS_GET){
            $list = null;
            if(empty($existsList)){
                $list = array(array('login_id' => '','out_tid'  => '', 'status' => 'error'));
            }else{
                $list = $existsList;
            }

            $this->assign('list', $list);
            $this->assign('loginIdList', $loginIdList);
            $this->display();
        }
        
        if(empty($_POST['out_trade_no'])){
            $this->error('外部订单号不能为空');
        }
        
        //校验本地系统数据
        $outTidList = $_POST['out_trade_no'];
        $_temp = array_keys($outTidList);
        $_temp = implode(',', $_temp);
        
        $sql = "SELECT alibaba_trade.id, alibaba_trade.tid, alibaba_trade.buyer_login_id, alibaba_trade.out_tid, alibaba_trade.`status`,
                   mall_trade.receiver_name, mall_trade.receiver_mobile, alibaba_trade.type
                FROM alibaba_trade
                INNER JOIN mall_trade ON mall_trade.tid=alibaba_trade.tid
                WHERE alibaba_trade.out_tid IN({$_temp}) AND alibaba_trade.is_del=0";
        $exists = $Model->query($sql);
        $noDoCostList = array();   // 不二次计算成本
        foreach ($exists as $item){
            if($item['tid'] != $tid){
                if($item['buyer_login_id'] != $outTidList[$item['out_tid']]){
                    $this->error('订单'.$item['out_tid'].'已存在本系统，且与下单账号不匹配');
                }
                
                if($trade['receiver_name'] != $item['receiver_name'] || $trade['receiver_mobile'] != $item['receiver_mobile']){
                    $this->error('订单'.$item['out_tid'].'已存在本系统，且与'.$tid.'收货人信息不匹配');
                }
                $noDoCostList[] = $item['out_tid'];
            }
        }
        
        $parameters = array();
        foreach ($_POST['out_trade_no'] as $no=>$loginId){
            if(!is_numeric($no) || strlen($no) != 16){
                $this->error('订单号'.$no.'格式错误');
            }
            $parameters[$loginId][] = $no;
        }
        
        $updateList = array();   // 更新
        $delList = '';     // 删除
        foreach ($existsList as $item){
            if(!empty($item['out_tid'])){
                $updateList[$item['out_tid']] = $item['id'];
            }else{
                $delList .= ($delList == '' ? '' : ',').$item['id'];
            }
        }

        $totalCost = 0;
        $aopModelList = array();
        $aop = null;
        $created = date('Y-m-d H:i:s');
        $sqlList = array();
        
        // 1688订单
        foreach ($parameters as $loginId=>$noList){
            if(!isset($aopModelList[$loginId])){
                $aop = new \Common\Model\AopModel($loginId);
                $aopModelList[$loginId] = $aop;
            }else{
                $aop = $aopModelList[$loginId];
            }
            
            // 1688订单
            $list = $aop->getTradeListByTid($noList);
            $yaoliubaba = array();
            $loginId = addslashes($loginId);
            foreach ($list as $item){
                $outId = number_format($item['id'],0,'','');
                unset($_POST['out_trade_no'][$outId]);
                
                $yaoliubaba[] = $outId;
                $orderTime = substr($item['gmtCreate'], 0, 14);
                $payment = ($item['sumPayment'] + $item['codFee']) / 100;
                
                $doCost = 0;
                if(!in_array($outId, $noDoCostList)){
                    $doCost = 1;
                    $totalCost = bcadd($totalCost, $payment, 2);
                }
                
                if(array_key_exists($outId, $updateList)){
                    $sqlList[] = "UPDATE alibaba_trade SET `status`='success', seller_nick='{$item['sellerLoginId']}',buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='{$payment}', do_cost={$doCost}, type='1' WHERE id=".$updateList[$outId];
                    unset($updateList[$outId]);
                }else{
                    $sqlList[] = "INSERT INTO alibaba_trade SET tid={$tid}, out_tid='{$outId}', created='{$created}', `status`='success', seller_nick='{$item['sellerLoginId']}', buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='{$payment}', do_cost={$doCost}, type='1'";
                }
            }
            
            // 淘宝订单
            $diffList = array_diff($noList, $yaoliubaba);
            foreach ($diffList as $outId){
                $doCost = in_array($outId, $noDoCostList) ? 0 : 1;
                if(array_key_exists($outId, $updateList)){
                    $sqlList[] = "UPDATE alibaba_trade SET `status`='end', seller_nick='淘宝卖家',buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='0', do_cost={$doCost}, type='2' WHERE id=".$updateList[$outId];
                    unset($updateList[$outId]);
                }else{
                    $sqlList[] = "INSERT INTO alibaba_trade SET tid={$tid}, out_tid='{$outId}', created='{$created}', `status`='end', seller_nick='淘宝卖家', buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='0', do_cost={$doCost}, type='2'";
                }
                unset($_POST['out_trade_no'][$outId]);
            }
        }
        
        if(count($_POST['out_trade_no']) > 0){
            $this->error('订单号无效:'.implode(',', array_keys($_POST['out_trade_no'])));
        }
        
        // 更新成本
        //$sqlList[] = "UPDATE mall_trade SET total_cost={$totalCost} WHERE tid=".$tid;
        
        // 删除无用的
        if(count($updateList) > 0 || $delList != ''){
            foreach ($updateList as $id){
                $delList .= ($delList == '' ? '' : ',').$id;
            }
            $sqlList[] = "UPDATE alibaba_trade SET is_del=1 WHERE id IN ({$delList})";
        }
        
        // 执行修改
        $Model->startTrans();
        foreach($sqlList as $sql){
            $Model->execute($sql);
        }
        $Model->commit();
        $this->success();
    }
}
?>