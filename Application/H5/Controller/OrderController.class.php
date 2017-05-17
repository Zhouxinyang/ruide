<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 订单
 * @author lanxuebao
 *
 */
class OrderController extends CommonController
{
	public function index(){
	    $status = in_array($_GET['status'], array('all', 'topay', 'tosend', 'send', 'success' ,'cancel','refund')) ? $_GET['status'] : 'all';

	    $mid = $this->user('id');
	    if(IS_AJAX){
	        $limit = I('get.size/d', 0);
	        $offset = I('get.offset/d', 20);
	        
	        $where = array();
	        $where['trade.buyer_id'] = $mid;
	        $where['trade.buyer_del'] = 0;
	        if($status == 'refund')
	            $where['trade.refund_state']  = array('neq','no_refund');
	        else if($status == 'tosend')
	            $where['trade.status'] = array('IN', "tosend,toout");
	        else if($status != 'all')
	           $where['trade.status'] = $status;
	        
	        $Model = D("Common/Order");
	        $data = $Model->getShortList($where, $limit, $offset);
	       
	        $this->ajaxReturn($data);
	    }
	    
	    $this->assign('status', $status);
		$this->display();
	}

	/**
	 * 取消订单
	 */
	public function cancel(){
	    $buyer_id = $this->user('id');
	    $tid = $_POST['tid'];
	    $result = D('Order')->cancel($tid, $buyer_id);
	    if($result['error'] > 0){
	        $this->error($result['msg']);
	    }
	    $this->success($result['msg']);
	}
	
	public function delete(){
	    $buyer_id = $this->user('id');
	    $tid = $_POST['tid'];
	    $result = D('Order')->delete($tid, $buyer_id);
	    $this->success('订单已删除');
	}
	
	/**
	 * 确认收货
	 */
	public function sign(){
	    $buyer_id = $this->user('id');
	    $tid = $_POST['tid'];
	    
	    $Model = new \Common\Model\OrderModel();
	    $result = $Model->sign($tid, $buyer_id);
	    if($result <= 0){
	        $this->error($Model->getError());
	    }
	    $this->success();
	}
	
	/**
	 * 订单详情
	 */
	public function detail(){
        $userId = $this->user("id");
        
	    $tid = $_GET['tid'];
	    $Model = new \Common\Model\OrderModel();
	    $trade = $Model->getTradeByTid($tid);
	    if($trade['status'] == 'topay'){
	        $this->redirect('/h5/pay/'.$tid);
	    }
	    
	    /*
	    if($userId != $trade["buyer_id"]){
	        $this->error("暂无订单信息！");
	    }
	    */
        
		$this->suporrtRefund($trade);
	    $this->assign(array(
	        'trade' => $trade,
	        'login_id' => $userId
	    ));
	    $this->display();
	}
	
	/**
	 * 是否支持退款
	 * @param unknown $trade
	 */
	private function suporrtRefund(&$trade){
	    if($trade['status'] == 'topay'
	        || ($trade['refund_state'] == 'no_refund' && $trade['status'] == 'cancel')
	        || ($trade['status'] != 'cancel' && strtotime($trade['pay_time']) + 864000 <= NOW_TIME)){
	        return;
	    }
	    
	    // 要申请退款
	    $rule = M('shop_refund')->find($trade['seller_id']);
	    if(empty($rule)){
	        return;
	    }
        
	    $canRefund = false;
		// 未发货
		if($trade['status'] == 'tosend' || $trade['status'] == 'toout'){
		    if(empty($rule['not_received'])){
		        return;
		    }
		    
		    $canRefund = true;
		    $parameters = explode(',', $rule['not_received']);
		    if($trade['status'] != 'send' && $trade['status'] != 'success'){
		        $key = array_search(17, $parameters);
		        if($key !== false && count($parameters) == 1){
		            $canRefund = false;
		        }
		    }
		}else if(!empty($rule['received'])){ // 已发货
			$canRefund = true;
		}
		
		foreach($trade['orders'] as &$order){
			$order['can_refund'] = $order['refund_id'] != '' || $canRefund;
		}
	}
	
	/**
	 * 最近下单记录
	 */
	public function nearest(){
	    if(APP_DEBUG){
			$this->ajaxReturn(array('errcode' => 1));
		}
	    $Model = M();
	    $sql = "SELECT nickname, headimgurl
                FROM wx_user
                INNER JOIN 
                (
                	SELECT ROUND(max_id * RAND()) AS mid FROM (select max(mid) as max_id from wx_user) AS t2
                ) AS t3
                WHERE wx_user.mid >t3.mid
                LIMIT 10";
	    $list = $Model->query($sql, false);
	    shuffle($list);
	    
	    $data = $list[0];
	    $data['time'] = mt_rand(1, 6).'分钟';
	    $this->ajaxReturn($data);
	}
}
?>