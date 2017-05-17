<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Org\Wechat\WechatAuth;

/**
 * 订单退款
 * @author 兰学宝
 *
 */
class RefundController extends CommonController
{
	public function index(){
	    $oid = $_GET['oid'];
	    
	    if(!is_numeric($oid)){
	        $this->error('订单号不能为空');
	    }
	    
	    $buyerId = $this->user('id');
	    $Model = new \Common\Model\BaseModel();
	    $order = $Model->query("SELECT
                                `order`.oid, `order`.tid, `order`.title, `order`.sku_json, `order`.num, `order`.price, truncate(`order`.payment / `order`.num, 2) AS discount_price,
                                trade.`status`, trade.buyer_id, trade.post_fee, refund.*, shop_refund.not_received, shop_refund.received
                                FROM mall_order AS `order`
                                INNER JOIN mall_trade AS trade ON trade.tid=`order`.tid
                                LEFT JOIN shop_refund ON shop_refund.id=trade.seller_id
                                LEFT JOIN mall_trade_refund AS refund ON refund.refund_id = `order`.oid
                                WHERE `order`.oid='{$oid}'");
        $order = $order[0];
	    if(empty($order)){
            $this->error('卖家暂不支持退换货');
        }else if(($order['status'] == 'topay' || $order['status'] == 'cancel') && empty($order['refund_id'])){
            $this->error('当前状态不能申请退款');
        }else if(empty($order['refund_id']) && $buyerId != $order['buyer_id']){
	        $this->error('您不是下单人，无权申请退款');
	    }
	    
	    // 退款原因
		$Static = D('Static');
		$reason = $Static->refundedReason();
		$received = array();
		
	    // 未收货处理方式
		if(!empty($order['not_received'])){
			$parameters = explode(',', $order['not_received']);
			$list = array();
			foreach($parameters as $rid){
				if($rid == 17 && $order['status'] != 'send' && $order['status'] != 'success'){
					continue;
				}
				$list[$rid] = $reason[$rid];
			}
			
			if(count($list) > 0){
				$received['not_received'] = $list;
			}
		}
		
		// 已收到货处理方式
		if($order['status'] != 'tosend' && $order['status'] != 'toout' && !empty($order['received'])){
			$parameters = explode(',', $order['received']);
			$received['received'] = array();
			foreach($parameters as $rid){
				$received['received'][$rid] = $reason[$rid];
			}
		}
		
		if(empty($received) && empty($order['refund_id'])){
		    $this->error('当前状态暂不支持退换货');
		}
	    $this->assign('received', $received);

	    $order['spec_name'] = get_spec_name($order['sku_json']);
	    $this->assign('show_action',  $buyerId == $order['buyer_id']);
	    
		if(empty($order['refund_id']) || $order['refund_state'] == 1){
	        if($order['status'] != 'tosend' && $order['status'] != 'toout'){
    	        //获取签名
    	        $WechatAuth = new WechatAuth();
    	        $sign = $WechatAuth->getSignPackage();
    	        $this->assign('wxconfig', json_encode($sign));
	        }
	    }else{
	        $order['refund_reason_str'] = $reason[$order['refund_reason']];
	        $order['refund_state_str'] = $Static->refundedState($order['refund_state']);
	    }
	    $order['refund_images'] = empty($order['refund_images']) ? array() : explode(';', $order['refund_images']);
	    
	    if(empty($order['refund_id'])){
	        $order['refund_num'] = $order['num'];
	    }
	    
	    $this->assign('order', $order);
	    if(empty($order['refund_id']) || $order['refund_state'] == 1){
	        $this->display();
	    }else{
	        $this->display('detail');
	    }
	}
	
	/**
	 * 保存退款记录
	 */
	public function add(){
	    $buyerId = $this->user('id');
	    if(!is_numeric($_POST['refund_id'])){
	        $this->error('订单号不能为空');
	    }
	    
	    $Model = new \Common\Model\BaseModel();
	    $order = $Model->query("SELECT
                                `order`.oid, `order`.tid, `order`.title, `order`.num, `order`.price, trade.status, trade.post_fee, truncate(`order`.payment / `order`.num, 2) AS discount_price,
                                refund.refund_id, refund.refund_state, trade.buyer_id, trade.refund_state AS trade_refund_state, trade.paid_fee
                                FROM mall_order AS `order`
                                INNER JOIN mall_trade AS trade ON trade.tid=`order`.tid
                                LEFT JOIN mall_trade_refund AS refund ON refund.refund_id = `order`.oid
                                WHERE `order`.oid='{$_POST['refund_id']}'");
	    if(empty($order)){
	        $this->error('订单号不存在');
	    }else if(!empty($order[0]['refund_id']) && $order[0]['refund_state'] != 1){
	        $this->error('每种商品只能申请退款一次');
	    }else if($order[0]['buyer_id'] != $buyerId){
	        $this->error('您不是下单人，无权申请退款');
	    }else{
	        $order = $order[0];
	    }
	    
	    $now = date('Y-m-d H:i:s');
	    $data = array(
	        'refund_id'        => $_POST['refund_id'],
	        'refund_state'     => 1,
	        'refund_fee'       => $_POST['refund_fee'],
	        'refund_num'       => $_POST['refund_num'],
	        'refund_remark'    => $_POST['refund_remark'],
	        'refund_reason'    => $_POST['refund_reason'],
	        'refund_created'   => $now,
	        'refund_type'      => ($order['status'] == 'tosend' || $order['status'] == 'toout') ? 1 : $_POST['refund_type'],
	        'refund_images'     => ''
	    );
	    
	    if(!is_numeric($data['refund_num']) || !is_numeric($data['refund_fee']) ||
	        empty($data['refund_remark']) || !is_numeric($data['refund_reason']) ||
	        $data['refund_num'] > $order['num']){
	        $this->error('非法操作');
	    }
	    
	    $max = $data['refund_num'] * $order['discount_price'];
        if($data['refund_fee'] > $max){
            $this->error('最多可退款'.$max.'元');
        }
	    
	    if(is_array($_POST['refund_images'])){
	        $data['refund_images'] = implode(';', $_POST['refund_images']);
	    }
	    
	    $result = M('mall_trade_refund')->add($data, null, true);
	    if(empty($result)){
	        $this->error('退款申请失败');
	    }
	    
	    // 更新订单退款状态状态
	    $this->updateStatus($order);
	    $this->success('已申请，请等待客服审核！');
	}
	
	/**
	 * 取消申请
	 */
	public function cancel(){
	    $buyerId = $this->user('id');
	    $refund_id = $_POST['refund_id'];
	    if(!is_numeric($refund_id)){
	        $this->error('退款编号不能为空');
	    }
	    
        $Model = M();
        $sql = "SELECT refund.refund_id, refund.refund_state, `order`.oid, `order`.tid, trade.refund_state AS trade_refund_state, trade.buyer_id, trade.paid_fee
                FROM mall_trade_refund AS refund
                INNER JOIN mall_order AS `order` ON `order`.oid=refund.refund_id
                INNER JOIN mall_trade AS trade ON trade.tid=`order`.tid
                WHERE refund.refund_id='{$refund_id}'";
        $refund = $Model->query($sql);

	    if(empty($refund)){
	        $this->error('退款编号不存在');
	    }else{
	        $refund = $refund[0];
	    }
	    
	    if($refund['buyer_id'] != $buyerId){
	        $this->error('您不是下单人不能取消退款');
	    }else if($refund['refund_state'] != 1){
	        $this->error('当前状态不允许取消申请');
	    }
	    
	    // 执行数据修改
	    $now = date('Y-m-d H:i:s');
	    $Model->execute("UPDATE mall_trade_refund SET refund_state='5' WHERE refund_id='{$refund['refund_id']}'");
	    
	    $this->updateStatus($refund);
	    $this->success('已取消退款申请');
	}
	
	/**
	 * 更新订单退款状态
	 * @param unknown $trade
	 */
	private function updateStatus($trade){
		$Model = M();
        // 计算是否还有退款，以便更改订单退款状态
        $all = array('doing' => 0, 'success' => 0, 'fail' => 0);
        $list = $Model->query("SELECT refund_state, SUM(refund_fee) + SUM(refund_post) AS total_fee FROM mall_trade_refund WHERE refund_id IN (SELECT oid FROM mall_order WHERE tid='{$trade['tid']}') GROUP BY refund_state");
		$refunding = 0;
		$refunded = 0;
		$refundFail = 0;
		foreach($list as $item){
            if($item['refund_state'] == 1 || $item['refund_state'] == 2 || $item['refund_state'] == 2.1){ // 申请中和退款中
                $all['doing'] += $item['total_fee'];
				$refunding++;
            }else if($item['refund_state'] == 3){
                $all['success'] += $item['total_fee'];
				$refunded++;
            }else if($item['refund_state'] == 4){
                $all['fail'] += $item['total_fee'];
				$refundFail++;
            }
        }
    
        $tradeRefundState = 'no_refund';
        if($all['doing'] > 0 || $refunding > 0){
            $tradeRefundState = $all['doing'] >= $trade['paid_fee'] ? 'full_refunding' : 'partial_refunding';
        }else if($all['success'] > 0 || $refunded > 0){
            $tradeRefundState = $all['success'] >= $trade['paid_fee'] ? 'full_refunded' : 'partial_refunded';
        }else if($all['fail'] > 0 || $refundFail > 0){
            $tradeRefundState = $all['fail'] >= $trade['paid_fee'] ? 'full_failed' : 'partial_failed';
        }
    
        if($trade['trade_refund_state'] != $tradeRefundState){
            $Model->execute("UPDATE mall_trade SET refund_state='{$tradeRefundState}' WHERE tid='{$trade['tid']}'");
        }
	}
	
	/**
	 * 保存快递单号
	 */
	public function express(){
	    $refund_id = $_REQUEST['refund_id'];
        $refund_express = $_REQUEST['refund_express'];
         
        if(!is_numeric($refund_id) || strlen($refund_express) < 10){
            $this->error('非法操作');
        }
         
        $Model = M();
        $Model->execute("UPDATE mall_trade_refund SET refund_express='".addslashes($refund_express)."', refund_state='2.1' WHERE refund_id={$refund_id} AND refund_state='2'");
        $this->success('已保存');
	}
}
?>