<?php 
namespace Common\Model;

use Org\Wechat\WechatAuth;
/**
 * 订单处理modal
 * @author lanxuebao
 *
 */
class OrderModel extends BaseModel{
    protected $tableName = 'mall_trade';
    
    public function buyNum($goods_id, $buyer_id){
        $sql = "SELECT SUM(num) FROM mall_order_product WHERE buyer_id='{$buyer_id}' AND goods_id='{$goods_id}'";
        $result = $this->query($sql);
        $total = current($result[0]);
        return $total ? $total : 0;
    }
    
    /**
     * 生成微信支付(*total_fee单位为元，系统会自动转成分)
     * @param unknown $data
     * @param string $retry
     * @return \Org\WxPay\成功时返回，其他抛异常|\Org\WxPay\json数据，可直接填入js函数作为参数
     */
    public function createWxPayOrder($data, $retry = false){
        //下单结果通知url
        if(!isset($data['notify_url']))
	        $data['notify_url'] = U('/service/wxpaynotify', '', true, true);
	    
	    ini_set('date.timezone','Asia/Shanghai');
	    
	    $tools = new \Org\WxPay\JsApiPay();

	    //②、统一下单
	    $input = new \Org\WxPay\WxPayUnifiedOrder();
	    $input->SetBody($data['body']);
	    $input->SetDetail($data['detail']);
	    $input->SetAttach($data['attach']);
	    $input->SetOut_trade_no($data['order_no']);
	    $input->SetTotal_fee($data['total_fee'] * 100);
	    $input->SetTime_start($data['time_start']);
	    $input->SetTime_expire($data['time_expire']);
	    $input->SetGoods_tag($data['goods_tag']);
	    $input->SetNotify_url($data['notify_url']);
	    $input->SetSpbill_create_ip(get_client_ip());
	    $input->SetTrade_type(IS_APP ? 'APP' : 'JSAPI');
	    $input->SetOpenid($data['openid']);
	    $order = \Org\WxPay\WxPayApi::unifiedOrder($input);
	    
	    // 保存微信订单记录
	    $values = $input->GetValues();
	    $data = array_merge($values, $order);
	    M('wx_order')->add($data);
	    
	    if($order['result_code'] != 'SUCCESS'){
	        if($retry == true){
	            $this->createWxPayOrder($data, false);
	        }else{
	            return $order;
	        }
	    }
	    
	    return $tools->GetParameters($order);
    }
    
    /**
     * 订单支付超时处理
     * @param unknown $trade
     */
    public function timeout(&$trade){
        if($trade['status'] != 'topay' || $trade['pay_type'] == 'giveaway' || strtotime($trade['created']) + C('ORDER_TIME_OUT') > NOW_TIME){
            return;
        }
        
        $this->startTrans();
        
        // 退款
        $this->cancelTrade($trade, 'pay_timeout');
        
        $this->commit();
    }
    
    /**
     * 订单统一处理入口
     * @param unknown $trade
     */
    public function handle(&$trade){
        $static = D('Common/Static');
        //判定是否超时
        $this->timeout($trade);
        
        // 订单状态
        $statusList = $static->orderStatus();
        if($trade['pay_type'] == 'giveaway'){
            if($trade['status'] == 'topay'){
                $trade['status_str'] = '未领取';
            }else if($trade['status'] == 'cancel'){
                $trade['status_str'] = '已弃领';
            }else{
                $trade['status_str'] = $statusList[$trade['status']];
            }
        }else{
            $trade['status_str'] = $statusList[$trade['status']];
        }
        $aliStatuses = $static->aliStatus();
        $trade['ali_status_str'] = $aliStatuses[$trade['astatus']];
        $trade['pay_type_str'] = $static->payType($trade['pay_type']);
        
        $trade['sum_fee'] = sprintf('%.2f', $trade['total_fee'] + $trade['post_fee'] - $trade['discount_fee'] - $trade['paid_no_balance'] + $trade['adjust_fee']);
        
        // 退款状态
        $refundState = $static->refundedState();;
        foreach($trade['orders'] as $i=>$item){
            $trade['orders'][$i]['spec'] = get_spec_name($item['sku_json']);
            $trade['orders'][$i]['price'] = $item['price'];
            $trade['orders'][$i]['original_price'] = $item['original_price'] > 0 ? $item['original_price'] : '';
            $trade['orders'][$i]['refund_state_str'] = empty($item['refund_id']) ? '无退款' : $refundState[$item['refund_state']];
            $status_str = '';
            switch ($item['status']){
                case 'tosend':
                    $status_str = '待发货';
                    break;
                case 'send':
                    $status_str = '已发货';
                    break;
            }
            
            $trade['orders'][$i]['status_str'] = $status_str;
        }
    }
    
    public function getShortList($where, $limit = 0, $offset = 20){
        $list = $this->alias("trade")
                ->field("trade.tid, trade.paid_fee, trade.created, trade.kind, trade.status, trade.pay_type, trade.type, trade.post_fee, trade.payment, trade.paid_no_balance, `order`.oid, `order`.goods_id, trade.buyer_id,
	                    `order`.title, `order`.pic_url, `order`.price, `order`.original_price, `order`.pay_type AS ppt, `order`.num, `order`.sku_json")
               ->join("mall_order AS `order` ON `order`.oid=trade.tid")
               ->where($where)
               ->order("trade.created DESC")
               ->limit($limit, $offset)
               ->select();
        $data = array();

        // 字段处理
        foreach($list as $index=>$item){
            if(!isset($data[$item['tid']])){
                $this->handle($item);
                $data[$item['tid']] = array(
                    'tid'       => $item['tid'],
                    'type'      => $item['type'],
                    'created'   => $item['created'],
                    'status'    => $item['status'],
                    'status_str'=> $item['status_str'],
                    'post_fee'  => $item['post_fee'],
                    //'receiver_name'   => $item['receiver_name'],
                    //'receiver_mobile'   => $item['receiver_mobile'],
                    'kind'      => $item['kind'],
                    'payment'   => $item['payment'],
                    'pay_type'  => $item['pay_type'],
                    'orders'    => array()
                );
            }

            $data[$item['tid']]['orders'][] = array(
                'oid' => $item['oid'],
                'goods_id'  => $item['goods_id'],
                'product_id'=> $item['product_id'],
                'title' => $item['title'],
                'num' => $item['num'],
                'original_price' => $item['original_price'],
                'price' => $item['price'],
                'pic_url' => $item['pic_url'],
                'pay_type' => $item['ppt'],
                'spec' => get_spec_name($item['sku_json'])
            );
        }
        
        return array_values($data);
    }
    
    /**
     * 取消订单
     * @param unknown $tid 订单号
     */
    public function cancel($tid, $buyer_id){
        $result = array('error' => 0, 'msg' => '已退款');
        
        $trade = $this->where("tid='%s'", $tid)->find();
        if(empty($trade) || $buyer_id != $trade['buyer_id']){
            $result['error'] = 1;
            $result['msg'] = '订单不存在';
        }

        $this->handle($trade);
        if($trade['status'] == 'cancel'){
            return $result;
        }
        
        if($trade['status'] != 'topay'){
            $result['error'] = 1;
            $result['msg'] = '订单已确认，无法取消';
            return $result;
        }
        
        // 退款
        $this->cancelTrade($trade, 'buyer_cancel', true);
        return $result;
    }
    
    /**
     * 订单取消后 扣除受益人差价提成
     */
    private function backDiff($trade, $sendMsg = false){
        $diff = $this->query("SELECT SUM(profit.total_fee) AS total_fee, profit.mid
                              FROM mall_trade_difference AS profit
                              WHERE profit.tid = '{$trade['tid']}' AND profit.checkout=1
                              GROUP BY tid");
        if(empty($diff)){
            return 0;
        }
        $diff = $diff[0];
        
        // 扣除上级的差价收益
        D('Balance')->add(array(
            'mid'       => $diff['mid'],
            'reason'    => '好友取消订单['.$trade['tid'].']',
            'balance'   => -$diff['total_fee'],
            'link'      => '/h5/order/detail?tid='.$trade['tid'],
            'type'      => 'lower_cancel'
        ));

        // 发送消息
        if($sendMsg){
            return $diff['total_fee'];
        }

        $myWX = $this->getWXUserConfig($diff['mid']);
        if(empty($myWX['config'])){
            return $diff['total_fee'];
        }

        ignore_user_abort(true);
        set_time_limit(0);
        $url = '/h5/order/detail?tid='.$trade['tid'];
        //发送模板消息
        $WechatAuth = new WechatAuth($myWX['config']['WEIXIN']);
        $WechatAuth->sendTemplate($myWX['openid'], array(
            'template_id' => $myWX['config']['WX_TEMPLATE']['TM00335'],
            'url' => $myWX['config']['HOST'].$url,
            'data' => array(
                'first'    => array('value' => '您有新积分扣除，详情如下。', 'color' => '#173177'),
                'account'  => array('value' => '当前账户'),
                'time'     => array('value' => date('Y年m月d日 H:i')),
                'type'     => array('value' => '好友订单被取消'),
                'creditChange' => array('value' => '扣除'),
                'number'       => array('value' => sprintf('%.2f', $diff['total_fee'])),
                'creditName'   => array('value' => '积分'),
                'amount'       => array('value' => '***'),
                'remark'       => array('value' => '好友订单'.$trade['tid'].'已售后退款，故积分应返还给好友')
            )
        ));
        
        return $diff['total_fee'];
    }
    
    /**
     * 返还订单金额
     * @param unknown $trade
     * @param unknown $reason
     */
    public function cancelTrade(&$trade, $reason, $sendMsg = false){
        ignore_user_abort(true);
        set_time_limit(0);
        
        $now = date('Y-m-d H:i:s');
        $trade['status'] = 'cancel';
        $trade['end_type'] = $reason;
        $trade['end_time'] = $now;
        $trade['modified'] = $now;
        
        // 退款折扣率
        $rate = 1;
        if($reason == 'buyer_cancel'){
            $rate = 0.99;
        }
        
        if($reason != 'pay_timeout'){
            $sendMsg = true;
        }
        
        // 返还金额
        $refundedFee = sprintf('%.2f', $trade['paid_fee'] * $rate);
        $refundState = 'no_refund';
        
        // 更新订单信息
        $sql = "UPDATE mall_trade SET status='cancel', end_time='".$now."', end_type='{$reason}'";
        if($trade['paid_fee'] > 0){
            $refundState = $refundedFee == $trade['paid_fee'] ? 'full_refunded' : 'partial_refunded';
            $sql .= ", refund_state='{$refundState}', refunded_fee='{$refundedFee}'";
        }
        $sql .= " WHERE tid='{$trade['tid']}'";
        $this->execute($sql);
        
        // 删除退款申请
        if($trade['paid_fee'] > 0){
            $this->execute("DELETE FROM mall_trade_refund WHERE refund_id IN (SELECT oid FROM mall_order WHERE tid={$trade['tid']})");
        }
        
        // 将钱退回到个人钱包
        if($refundedFee > 0){
            $noBalance = $trade['paid_no_balance'] >= $refundedFee ? $refundedFee : $trade['paid_no_balance'];
            D('Balance')->add(array(
                'mid'       => $trade['buyer_id'],
                'reason'    => '订单退款-'.$trade['tid'],
                'no_balance'=> $noBalance,
                'balance'   => $refundedFee - $noBalance,
                'link'      => '/h5/order/detail?tid='.$trade['tid'],
                'type'      => 'order_refunded'
            ));
            
            if($sendMsg){
                $myWX = $this->getWXUserConfig($trade['buyer_id']);
                if(!empty($myWX['config'])){
                    $url = '/h5/order/detail?tid='.$trade['tid'];
                    //发送模板消息
                    $WechatAuth = new WechatAuth($myWX['config']['WEIXIN']);
                    $WechatAuth->sendTemplate($myWX['openid'], array(
                        'template_id' => $myWX['config']['WX_TEMPLATE']['TM00335'],
                        'url' => $myWX['config']['HOST'].$url,
                        'data' => array(
                            'first'    => array('value' => '您有新积分到账，详情如下。', 'color' => '#173177'),
                            'account'  => array('value' => empty($trade['buyer_nick']) ? '当前账户' : $trade['buyer_nick']),
                            'time'     => array('value' => date('Y年m月d日 H:i')),
                            'type'     => array('value' => '订单退款'),
                            'creditChange' => array('value' => '增加'),
                            'number'       => array('value' => $refundedFee),
                            'creditName'   => array('value' => '积分'),
                            'amount'       => array('value' => '***'),
                            'remark'       => array('value' => '订单'.$trade['tid'].'已取消')
                        )
                    ));
                }
            }
        }
        
        //订单取消后 扣除受益人 和 平台获取的利益
        $this->backDiff($trade, $sendMsg);
        
        $trade['refund_state'] = $refundState;
        $trade['refunded_fee'] = $refundedFee;
    }
    
    /**
     * 确认收货(签收)
     * @param unknown $tid
     * @param unknown $buyer
     * @return multitype:number string
     */
    public function sign($tid, $buyer_id){
        if(!is_numeric($tid)){
            $this->error = '订单号不存在';
            return -1;
        }
        
        $trade = $this->where("tid='{$tid}'")->find();
        if(empty($trade) || $buyer_id != $trade['buyer_id']){
            $this->error = '订单不存在';
            return -1;
        }
    
        //$this->handle($trade);
        
        if($trade['status'] != 'send'){
            $this->error = '未发货不能签收：'.$trade['status'];
            return -1;
        }else if(empty($trade['consign_time']) || $trade['consign_time'] == '0000-00-00 00:00:00'){
            $this->error = '异常订单：发货时间为空';
            return -1;
        }else if($trade['refund_state'] == 'partial_refunding' || $trade['refund_state'] == 'full_refunding'){ // 退款中不能确认收货
            $this->error = '退款中不能确认收货';
            return -1;
        }

        $consignTime = strtotime($trade['consign_time']);
        if($consignTime + 259200 > NOW_TIME){
            $this->error = '发货三天后可确认收货，剩余：';
            $leftsecond = $consignTime + 259200 - NOW_TIME;
            
            $day = floor($leftsecond/(60*60*24));
            $hour=floor(($leftsecond-$day*24*60*60)/3600);
            $minute=floor(($leftsecond-$day*24*60*60-$hour*3600)/60);
            $second=floor($leftsecond-$day*24*60*60-$hour*3600-$minute*60);

            if($day > 0){$this->error .= $day.'天';}
            if($hour > 0){$this->error .= $hour.'小时';}
            if($minute > 0){$this->error .= $minute.'分';}
            if($second > 0){$this->error .= $second.'秒';}
            return -1;
        }
    
        $now = date('Y-m-d H:i:s');
        $result = $this->where("tid='{$tid}'")->save(array(
            'status'    => 'success',
            'sign_time' => $now,
            'end_time'  => $now,
            'modified'  => $now
        ));
        
        if($result < 1){
            $this->error = '确认收货失败';
            return -1;
        }
        
        $sql = "SELECT mall_order.oid, mall_order.goods_id, mall_order.product_id,
                    mall_order.title, mall_order.sku_json,
                    mall_order.price, mall_order.payment, mall_order.discount_fee,
                    mall_order.num, IF(ISNULL(refund_num), 0, refund_num) AS refund_num
                FROM mall_order
                LEFT JOIN mall_trade_refund AS trade_refund ON trade_refund.refund_id=mall_order.oid AND refund_state='3'";
        $orders = $this->query("SELECT * FROM mall_order WHERE tid='{$tid}'");
        $this->backGrouponDiffFee($trade, $orders);
        return 1;
    }
    
    /**
     * 返还众筹拼团差价
     */
    private function backGrouponDiffFee($trade, $orders){
        $backList = $goodsId = $productList = array();
        foreach ($orders as $order){
            if($order['num'] == $order['refund_num']){
                continue;
            }
            
            $productList[$order['product_id']] = $order;
            if(!in_array($order['goods_id'], $goodsId)){
                $goodsId[] = $order['goods_id'];
            }
        }
        
        // 创建时间和付款时间 都在众筹拼团活动时间才返点
        $paytime = strtotime($trade['pay_time'])-1;
        $createTime = strtotime($trade['created'])+1;
        $sql = "SELECT * FROM mall_groupon
                WHERE goods_id IN(".implode(',', $goodsId).")
                    AND {$createTime}>start_time AND {$paytime}<end_time";
        $grouponList = $this->query($sql);
        if(count($grouponList) == 0){
            return;
        }

        foreach ($grouponList as $groupon){
            $priceRange = json_decode($groupon['price_range'], true);
            foreach ($priceRange AS $productId=>$item){
                if(!isset($productList[$productId])){
                    continue;
                }
                
                // 找到最后成交的价格
                foreach ($item['range'] as $num=>$price){
                    if($item['sold'] <= $num){
                        break;
                    }
                }
                
                // 折扣后单价
                $discountPrice = bcdiv($productList[$productId]['payment'], $productList[$productId]['num'], 2);
                if($discountPrice < $price){
                    continue;
                }
                
                $backNum = $productList[$productId]['num'] - $productList[$productId]['refund_num'];
                if($backNum <= 0){ continue; }
                $diff = bcsub($discountPrice, $price, 2);
                $backTotal = bcmul($diff, $backNum, 2);
                
                $backList[] = array(
                    'oid'        => $productList[$productId]['oid'],
                    'groupon_id' => $groupon['id'],
                    'back_num'   => $backNum,
                    'buy_price'  => $discountPrice,
                    'back_price' => $price,
                    'back_fee'   => $backTotal,
                    'back_time'  => NOW_TIME,
                    'title'      => $productList[$productId]['title'].get_spec_name($productList[$productId]['sku_json'])
                );
            }
        }
        
        if(count($backList) == 0){
            return;
        }
        
        // 增加积分
        $totalBack = 0;
        $this->startTrans();
        $balanceModel = new BalanceModel();
        // 返点记录
        $sql = "INSERT INTO mall_groupon_back(oid, groupon_id, back_num, buy_price, back_price, back_fee, back_time) VALUES";
        foreach ($backList as $item){
            $balanceModel->add(array(
                'mid'     => $trade['buyer_id'],
                'balance' => $item['back_fee'],
                'type'    => 'groupon',
                'reason'  => '众筹拼团返款-'.$trade['tid']
            ));
            $totalBack = bcadd($totalBack, $item['back_fee'], 2);
            $sql .= "({$item['oid']}, {$item['groupon_id']}, {$item['back_num']}, {$item['buy_price']}, {$item['back_price']}, {$item['back_fee']}, {$item['back_time']}),";
            
            // 增加子订单折扣金额
            $this->execute("UPDATE mall_order SET discount_fee=discount_fee+{$item['back_fee']}, payment=payment-{$item['back_fee']} WHERE oid=".$item['oid']);
            // 累加活动返点金额
            $this->execute("UPDATE mall_groupon SET back_fee=back_fee+{$item['back_fee']} WHERE id=".$item['groupon_id']);
        }
        $sql = rtrim($sql, ',');
        $this->execute($sql);
        $this->commit();
        
        
        // 发送消息
        $wxUser = $this->query("SELECT openid, appid, subscribe FROM wx_user WHERE openid='{$trade['buyer_openid']}'");
        $wxUser = $wxUser[0];
        if($wxUser['subscribe'] != 1){
            return;
        }

        $config = get_wx_config($wxUser['appid']);
        $wechatAuth = new WechatAuth($config['WEIXIN']);
        $wechatAuth->sendTemplate($wxUser['openid'], array(
            'template_id' => $config['WX_TEMPLATE']['TM00335'],
            'url' => $config['HOST'].'/h5/balance#record',
            'data' => array(
                'first'    => array('value' => '您有新积分到账，详情如下。', 'color' => '#173177'),
                'account'  => array('value' => '当前账户'),
                'time'     => array('value' => date('Y年m月d日 H:i')),
                'type'     => array('value' => '众筹拼团返款'),
                'creditChange' => array('value' => '返款'),
                'number'       => array('value' => $totalBack),
                'creditName'   => array('value' => '积分'),
                'amount'       => array('value' => '***'),
                'remark'       => array('value' => '订单'.$trade['tid'].'众筹返款，点击详情查看积分记录')
            )
        ));
    }
    
    public function delete($tid, $buyer_id){
        $this->execute("UPDATE ".$this->tableName." SET buyer_del=1 WHERE tid='%s' AND buyer_id='%d'", array($tid, $buyer_id));
    }
    
    public function getTradeByTid($tid){
        $trade = $this->where("tid='%s'", $tid)->find();
        if(empty($trade)){
            $this->error = '订单不存在';
            return null;
        }
        
        // 订单详情
        $trade['orders'] = 
            $this->query("SELECT `order`.*, refund.*, difference.checkout, SUM(difference.total_fee) AS total_fee, GROUP_CONCAT(difference.mid) AS mid
                     FROM mall_order AS `order`
                     LEFT JOIN mall_trade_refund AS refund ON refund.refund_id=`order`.oid
                     LEFT JOIN mall_trade_difference AS difference ON difference.oid=`order`.oid
                     WHERE `order`.tid='{$tid}'
                     GROUP BY `order`.oid");
        
        // 查找物流信息
        $trade['express'] = array();
        $staticM = D('Common/Static');
        if($trade['express_no'] == ''){
            $express = $staticM->express(false, 'id');
            $trade['express_name'] = $express[$trade['express_id']]['name'];
        }else if(strpos($trade['express_no'], ':')){  // 快递公司名称:运单号
            $express = $staticM->express(false, 'name');
            $expressList = explode(';', $trade['express_no']);
            foreach($expressList as $item){
                $detail = explode(':', $item);
                $trade['express'][] = array('name' => $detail[0], 'code' => $express[$detail[0]]["code"], 'no' => $detail[1]);
            }
        }else{   // 直接运单号
            $express = $staticM->express();
            foreach($express as $k=>$v){
                if($trade['express_id'] == $v["id"]){
                    $trade['express'][] = array('name' => $v["name"], 'code' => $v["code"], 'no' => $trade['express_no']);
                    break;
                }
            }
        }
        
        $this->handle($trade);
        return $trade;
    }
    
    /**
     * 获取销售数量 - 根据买家ID
     * @param unknown $goodsId
     * @param unknown $buyerId
     * @return Ambigous <number, mixed>
     */
    public function getSoldNumByBuyer($goodsId, $buyerId, $startTime = null, $endTime = null){
        if(!is_numeric($startTime)){
            $startTime = strtotime('-1 month');
        }
        if(!is_numeric($endTime)){
            $endTime = NOW_TIME;
        }
        
        $startTid = date('Ymd', $startTime).'00000';
        $endTid = date('Ymd', $endTime).'99999';
        $startDate = date('Y-m-d H:i:s', $startTime);
        $endDate = date('Y-m-d H:i:s', $endTime);
        
        $sql = "SELECT SUM(o.num) FROM mall_order AS o
                INNER JOIN mall_trade AS t ON t.tid=o.tid
                WHERE t.tid BETWEEN '{$startTid}' AND '{$endTid}'
                  AND t.buyer_id='{$buyerId}' AND o.goods_id='{$goodsId}'
                  AND t.pay_time BETWEEN '{$startDate}' AND '{$endDate}'";
        
        $result = $this->query($sql);
        $total = current($result[0]);
        return $total ? $total : 0;
    }
    
    /**
     * 获取销售数量 - 根据时间
     * @param unknown $goodsId
     * @param unknown $startTime
     * @param unknown $endTime
     * @return Ambigous <number, mixed>
     */
    public function getSoldNumByTime($goodsId, $startTime, $endTime){
        $startTid = date('Ymd', strtotime($startTime)).'00000';
        $endTid = date('Ymd', strtotime($endTime)).'99999';
        
        $sql = "SELECT SUM(o.num) FROM mall_order AS o
                INNER JOIN mall_trade AS t ON t.tid=o.tid
                WHERE t.tid BETWEEN '{$startTid}' AND '{$endTid}'
                AND o.goods_id='{$goodsId}'
                AND t.pay_time BETWEEN '{$startTime}' AND '{$endTime}'";
        
        $result = $this->query($sql);
        $total = current($result[0]);
        return $total ? $total : 0;
    }
    
    /**
     * 获取销售数量 - 根据买家时间
     * @param unknown $goodsId
     * @param unknown $buyerId
     * @param unknown $startTime
     * @param unknown $endTime
     * @return Ambigous <number, mixed>
     */
    public function getSoldNumByBuyerTime($goodsId, $buyerId, $startTime, $endTime){
        $startTid = date('Ymd', strtotime($startTime)).'00000';
        $endTid = date('Ymd', strtotime($endTime)).'99999';
        
        $sql = "SELECT SUM(o.num) FROM mall_order AS o
                INNER JOIN mall_trade AS t ON t.tid=o.tid
                WHERE t.tid BETWEEN '{$startTid}' AND '{$endTid}' 
                    AND t.buyer_id='{$buyerId}' AND o.goods_id='{$goodsId}'
                    AND t.pay_time BETWEEN '{$startTime}' AND '{$endTime}'";
        
        $result = $this->query($sql);
        $total = current($result[0]);
        return $total ? $total : 0;
    }
    
    /**
     * 设置收货地址
     * @param unknown $param
     */
    public function setReceiver($tid, $address) {
          $this->where("tid='{$tid}'")->save($address);
    }
    
    /**
     * 验证订单是否可以取消(取消主订单)
     * @param unknown $trade 订单
     * @param unknown $time 当前时间
     */
    public function can_cancel($trade){
        if($trade['status'] == 'cancel'){
            $this->error = '当前状态不可取消！';
            return -1;
        }
        
        //是否有取消订单的权限
        $auth = \Common\Common\Auth::get();
        $accessCancel = $auth->validated('admin','order','cancel');
        
        if(!$accessCancel){
            $this->error = '无权限！';
            return -1;
        }
        
        //取消其他店铺的权限
        if($trade['seller_id'] != session('user.shop_id')){
            $accessAll = $auth->validated('admin','shop','all');
            if(!$accessAll){
                $this->error = '无权限！';
                return -1;
            }
        }
        
        if(($trade['status'] == 'topay' || $trade['status'] == 'tosend' || $trade['status'] == 'toout') && $trade['refunded_fee'] == 0){
            return 1;
        }
        
        // 是否有退款权限
        $access_cancel = $auth->validated('admin','refund','agree');
        if($access_cancel){
            return 2;
        }
        
        return -1;
    }
    
    /**
     * 校验产品
     */
    protected function validOrder($buyer, &$product, &$quotaList, $autoChange = false){
        $result = array('error_code' => 1, 'error_msg' => '');
    
        if($product['is_del']){
            $result['error_msg'] = '产品已被删除';
        }else if($product['is_display'] == 0){
            $result['error_msg'] = '产品已下架';
        }else if($product['sold_time'] > 0 && $product['sold_time'] > time()){
            $result['error_msg'] = date('Y-m-d H:i:s', $product['sold_time']).'开售';
        }else if($product['stock'] == 0){
            $result['error_msg'] = '已售罄';
        }else if($product['day_quota'] > 0 && !empty($quotaList[$product['goods_id']]['today_sold']) && $quotaList[$product['goods_id']]['today_sold'] >= $product['day_quota']){
            $result['error_msg'] = '今日已售罄';
        }else if($buyer['agent_level'] == 0 && $product['visitors_quota'] != 1){
            $result['error_msg'] = '非会员不可购买';
        }
    
        if($result['error_msg'] != ''){
            return $result;
        }
    
        if(intval($product['buy_num']) < 1){
            $result['error_msg'] = '不能小于1件';
            if(!$autoChange){ return $result; }
            $product['buy_num'] = 1;
        }
    
        if($product['buy_num'] > $product['stock']){
            $result['error_msg'] = '仅剩'.$product['stock'].'件';
            if(!$autoChange){ return $result; }
            $product['buy_num'] = $product['stock'];
        }
    
        $startTime = date('Y-m-d').' 00:00:00';
        $endTime = date('Y-m-d').' 23:59:59';
        $goodsId = $product['goods_id'];
    
        if(!isset($quotaList[$goodsId])){
            //$quotaList['商品id'] = array('今日卖出数量' => 0, '今日购买数量' => 0, '累计购买数量' => 0 );
            $quotaList[$goodsId] = array('today_sold' => -1, 'today_buy' => -1, 'buy_num' => -1);
        }
    
        // 每人每日最多可购买
        if($product['every_quota'] > 0){
            if($quotaList[$goodsId]['today_buy'] == -1){
                $quotaList[$goodsId]['today_buy'] = $this->getSoldNumByBuyerTime($goodsId, $buyer['id'], $startTime, $endTime);
            }
            $canBuy = $product['every_quota'] - $quotaList[$goodsId]['today_buy'];
    
            if($product['buy_num'] > $canBuy){
                $result['error_msg'] = '日限购'.$product['every_quota'].'件';
                if($canBuy > 0){
                    $result['error_msg'] .= '(可购'.$canBuy.'件)';
                    if(!$autoChange){ return $result; }
                    $product['buy_num'] = $canBuy;
                }else{
                    return $result;
                }
            }
            $quotaList[$goodsId]['today_buy'] += $product['buy_num'];
        }
    
        // 每日最多可售卖数量限制
        if($product['day_quota'] > 0){
            // 获取今日此商品卖出数量
            if($quotaList[$goodsId]['today_sold'] == -1){
                $quotaList[$goodsId]['today_sold'] = $this->getSoldNumByTime($goodsId, $startTime, $endTime);
            }
            $canSold = $product['day_quota'] - $quotaList[$goodsId]['today_sold'];
    
            if($product['buy_num'] > $canSold){
                $result['error_msg'] = '日限卖'.$product['day_quota'].'件';
                if($canSold > 0){
                    $result['error_msg'] .= '(可购'.$canSold.'件)';
                    if(!$autoChange){ return $result; }
                    $product['buy_num'] = $canSold;
                }else{
                    return $result;
                }
            }
            $quotaList[$goodsId]['today_sold'] += $product['buy_num'];
            $product['stock'] -= $product['buy_num'];
        }
    
        // 每人最多可购买数量
        if($product['active']['quota'] > 0){    // 活动限购
            if($quotaList[$goodsId]['buy_num'] == -1){
                $quotaList[$goodsId]['buy_num'] = $this->getSoldNumByBuyer($product['goods_id'], $buyer['id'], $product['active']['start_time'], $product['active']['end_time']);
            }
            $quota = $product['active']['quota'];
            $canBuy = $quota - $quotaList[$goodsId]['buy_num'];
            
            if($product['buy_num'] > $canBuy){
                $result['error_msg'] = '每人限购'.$quota.'件';
                if($canBuy > 0){
                    $result['error_msg'] .= '(可购'.$canBuy.'件)';
                    if(!$autoChange){ return $result; }
                    $product['buy_num'] = $canBuy;
                }else{
                    return $result;
                }
            }
            $quotaList[$goodsId]['buy_num'] += $product['buy_num'];
        }else if($product['buy_quota'] > 0){
            if($quotaList[$goodsId]['buy_num'] == -1){
                $quotaList[$goodsId]['buy_num'] = $this->getSoldNumByBuyer($product['goods_id'], $buyer['id']);
            }
            $canBuy = $product['buy_quota'] - $quotaList[$goodsId]['buy_num'];
    
            if($product['buy_num'] > $canBuy){
                $result['error_msg'] = '每人限购'.$product['buy_quota'].'件';
                if($canBuy > 0){
                    $result['error_msg'] .= '(可购'.$canBuy.'件)';
                    if(!$autoChange){ return $result; }
                    $product['buy_num'] = $canBuy;
                }else{
                    return $result;
                }
            }
            $quotaList[$goodsId]['buy_num'] += $product['buy_num'];
        }
    
        if($product['buy_num'] > $product['stock']){
            $result['error_msg'] = '仅剩'.$product['stock'].'件';
            if(!$autoChange){ return $result; }
            $product['buy_num'] = $product['stock'];
        }
    
        $result['error_code'] = 0;
        return $result;
    }
    
        
    /**
     * 订单备注
     */
    public function sendOne($data){
        $Model = M();
        $sql = "SELECT
                    mall_trade.tid,
                    mall_trade.receiver_name,
                    mall_trade.receiver_mobile,
                    mall_trade.receiver_province,
                    mall_trade.receiver_city,
                    mall_trade.receiver_county,
                    mall_trade.receiver_detail,
                    mall_trade.buyer_openid,
                    mall_trade.`status`,
                    mall_trade.consign_time,
                    mall_trade.express_id,
                    mall_trade.express_no,
                    wx_user.appid,
                    wx_user.subscribe
                FROM mall_trade
                LEFT JOIN wx_user ON wx_user.openid = mall_trade.buyer_openid
                WHERE mall_trade.tid =".$data['tid'];
        $trade = $Model->query($sql);
        $trade = $trade[0];
        if(empty($trade)){
            $this->error = '订单不存在';
            return -1;
        }

        $date = date("Y-m-d H:i:s");
        if($trade['status'] == 'toout'){
            $trade['status'] = 'send';
        }
        if(!strtotime($trade['consign_time'])){
            $trade['consign_time'] = $date;
        }
        
        // 解析
        $expressList = array();
        $info = explode(';', $data['send']);
        $express = null;
        foreach ($info as $item){
            $express = explode(':', $item);
            
            if(!isset($express[1]) || empty($express[0]) || empty($express[1])){
                $this->error = '运单异常';
                return -1;
            }else if(isset($expressList[$item[1]])){
                $this->error = '运单号重复';
                return -1;
            }
        }
        
        $Model->execute("UPDATE mall_trade SET `status`='{$trade['status']}',express_no='{$data['send']}',`consign_time`='{$trade['consign_time']}' WHERE tid='{$data['tid']}'");
        
        $config = get_wx_config($trade['appid']);
        $WechatAuth = new WechatAuth($config['WEIXIN']);
        $message = array(
            'template_id' => $config['WX_TEMPLATE']['OPENTM200565259'],
            'url' => $config['HOST'].'/h5/order/detail?tid='.$trade['tid'],
            'data' => array(
                "first"    => array("value" => '您的订单已发货，请留意快递包裹'),
                "keyword1" => array("value" => $trade['tid']),
                "keyword2" => array("value" => $express[0]),
                "keyword3" => array("value" => $express[1]),
                "remark"   => array("value" => '收货信息：'.$trade["receiver_name"] ." ".$trade["receiver_mobile"]." ".$trade["receiver_province"]." ".$trade["receiver_city"]." ".$trade["receiver_county"]." ".$trade["receiver_detail"])
            )
        );
        $WechatAuth->sendTemplate($trade['buyer_openid'], $message);
    }

    /**
     * 获取交易中的用户信息
     * @param array $login 登录session标记
     */
    public function getTradingProducts($id, $buyer){
        $sql = "SELECT
                    product.id AS product_id, product.goods_id, goods.score, goods.points, goods.tag_id,
                    goods.title, IF(product.pic_url='' OR ISNULL(product.pic_url), goods.pic_url, product.pic_url) AS pic_url,
                    product.price,goods.points, goods.original_price,
                    goods.pay_type, goods.is_virtual, goods.cat_id, goods.buy_quota, goods.day_quota, goods.every_quota,
                    product.stock, product.outer_id, product.sku_json, product.weight, product.cost,
                    goods.sold_time, goods.is_del, goods.is_display, goods.visitors_quota, goods.freight_tid,
                    product.agent2_price, product.agent3_price, goods.shop_id AS seller_id, shop.name AS seller_nick
                FROM
                    mall_product AS product
                INNER JOIN mall_goods AS goods ON goods.id = product.goods_id
                INNER JOIN shop ON shop.id = goods.shop_id
                WHERE product.id IN(".rtrim($id, ',').")
                ORDER BY product.id";
        
        $list = $this->query($sql);
        $list = $this->goodsListHandler($list, $buyer);
        return $list;
    }
}
?>