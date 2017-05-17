<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Org\Wechat\WechatAuth;
use Common\Model\BaseModel;
use Common\Model\PayOrderModel;

/**
 * 下单
 * @author lanxuebao
 *
 */
class PayController extends CommonController
{
    /**
     * 订单确认VIEW
     */
    public function confirm(){
        if(empty($_POST['products'])){
            exit('<script>window.location.href="/h5/mall";</script>');
        }
        
        $login = $this->user();
        $Model = D('PayConfirm');
        $data = $Model->confirm($_POST, $login);
        
        if(empty($data)){
            $this->error($Model->getError());
        }
        
        $assign = array(
            'has_error'    => $data['has_error'], 
            'error_msg'    => $data['error_msg'],
            'groups'       => $data['groups']
        );
        
        $this->assign($assign);
        $this->assign('json_data', json_encode($data));
        
        $this->display();
    }
    
    /**
     * 计算运费
     */
    public function freightFee(){
        $Model = new \Common\Model\ExpressModel();
        $data = $Model->getFreightFee($_POST['products'], $_POST['address']);
        $this->ajaxReturn($data);
    }
    
    /**
     * 提交订单
     */
    public function order(){
        $login = $this->user();
        $Model = D('PayOrder');
        $data = $Model->order($_POST['address'], $_POST['groups'], $login, $_POST['from']);
        if(empty($data)){
            $this->error($Model->getError());
        }
        
        $this->ajaxReturn($data);
    }
    
    /**
     * 订单二次支付
     * @param unknown $tid
     */
    public function _empty($tid){
        $Model = new PayOrderModel();
        $trade = $Model->getTradeByTid($tid);
        
        if(empty($trade)){
            $this->error($Model->getError());
        }else if($trade['status'] != 'topay'){
            redirect('/h5/order/detail?tid='.$trade['tid'], 0);
        }
        
        $login = $this->user();
        $data = $Model->getToPay($trade);
        $trade = $data['trade'];

        if(IS_POST){
            if($data['has_error']){
                $this->error($data['error_msg']);
            }
            
            // 领取赠品 - 是否需要收货地址
            if(empty($trade['receiver_mobile'])){
                if(empty($_POST['address']['receiver_name']) || empty($_POST['address']['receiver_mobile']) || empty($_POST['address']['receiver_city']) || empty($_POST['address']['receiver_detail'])){
                    $this->error('收货地址不能为空');
                }else{
                    $Model->setReceiver($tid, $_POST['address']);
                }
            }
            
            // 如果需要支付
            if($trade['payment'] > 0){
                $wxpay = $Model->wxpay($trade, $login);
                if($wxpay['return_code'] == 'FAIL' || $wxpay['result_code'] == 'FAIL'){
                    $this->error('支付失败:'.$wxpay['return_msg']);
                }else{
                    $this->success($wxpay);
                } 
            }else{
                $Model->pay($trade);
            }
            
            $this->success();
        }
        
        if(empty($trade['receiver_name'])){
            $address = D('Address')->getDefault($trade['buyer_id']);
            $this->assign('address', $address);
        }

        $this->assign(array(
            'trade' => $trade,
            'has_error' => $data['has_error']
        ));
        
        $this->display('index');
    }
    
    /**
     * 积分兑换微信红包
     */
    public function redpack(){
        $buyer = $this->user("id, nickname, openid, subscribe");
        $Model = D('PayRedPack');
        $trade = $Model->exchange($buyer, $this->shop, $_POST['product_id']);
        if(empty($trade)){
            $this->error($Model->getError());
        }
        $this->ajaxReturn($trade);
    }
    
    /**
     * 代理规则
     */
    public function rule(){
        $user = $this->user('wx.headimgurl, member.nickname, member.mobile, member.id, member.balance,(member.balance-member.no_balance) AS can_balance, member.agent_level,
                member.sex, member.province_id, member.city_id, member.county_id, member.address AS detail');
        
        $agent_level = $user['agent_level'];
        $agent = M('agent')->where("level=3")->find();
        
        $xiaoshipin = M()->query("SELECT qrcode FROM kf_list WHERE type=2 AND enabled=1");
        shuffle($xiaoshipin);
        
        $this->assign('agent_level',$agent_level);
        $this->assign('agent',$agent);
        $this->assign('user',$user);
        $this->assign('xiaoshipin', $xiaoshipin[0]['qrcode']);
        $this->display();
    }
    
    /**
     * 代理商充值
     */
    public function payagent(){
        $my = $this->user('id, pid, agent_level, openid');
        if($my['agent_level'] > 0){
            $this->error('您已成为会员，无需充值！');
        }
        
        //获取代理商支付信息
        $agent = M('agent')->where('level=3')->find();
        if(empty($agent)){
            $this->error('暂无数据！');
        }
        
        //代理商充值订单数据
        $idwork = new \Org\IdWork();
        $recharge = array(
            'tid'            => $idwork->nextId(),
            'status'         => 'topay',
            'buyer_id'       => $my['id'],
            'buyer_openid'   => $my['openid'],
            'agent_level'    => $agent['level'],
            'once_amount'    => $agent['once_amount'],
            'created'        => date('Y-m-d H:i:s')
        );
        
        //数据整理 拉去微信支付功能
        $wxpay = D('Order')->createWxPayOrder(array(
            'body'       => '升级为'.$agent['title'],
            'detail'     => '升级为'.$agent['title'],
            'order_no'   => $recharge['tid'],
            'openid'     => $recharge['buyer_openid'],
            'attach'     => $my['id'],
            'time_start' => date('YmdHis'),
            'time_expire'=> date('YmdHis', strtotime('+3 hours')),
            'total_fee'  => $agent['once_amount'],
            'notify_url' => U('/service/Wxagentnotify', '', true, true),
        ));
        
        if($wxpay['result_code'] == 'FAIL' || $wxpay['return_code'] == 'FAIL'){
            $this->error('生成微信支付失败');
        }

        //查询登录人信息
        $MemberModel = M('member');
        //推荐费用记录
        $agentDetail = array(
            '0' => array(
                'id'          => $my['id'],
                'agent_level' => $my['agent_level'],
                'money'       => $agent['self_amount']
           ));
        
        // 查找上级推荐人
        $pid = $my['pid'];
        $level = 1;
        while ($pid > 0 && isset($agent['parent'.$level.'_amount'])){
            $parent = $MemberModel->field("id, pid, agent_level")->find($pid);
            if(empty($parent)){
                $pid = 0;
                break;
            }
            
            $money = $agent['parent'.$level.'_amount'];
            $agentDetail[$level] = array(
                "id"          => $parent['id'],
                "agent_level" => $parent['agent_level'],
                'money'       => ($money > 0 && $parent['agent_level'] > 0) ? $money : 0
            );
            $pid = $parent['pid'];
            $level++;
        }
        
        //保存代理商充值数据
        $recharge['detail'] = json_encode($agentDetail);
        $result = M('member_recharge')->add($recharge);
        if($result <= 0){
            $this->error('操作失败！');
        }

        $wxpay['tid'] = $recharge['tid'];
        $this->success($wxpay);
    }
    
    /**
     * 充值成功后发送提醒消息
     */
    public function agetnMessage(){
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();

        $tid = addslashes($_REQUEST['tid']);
        if(empty($tid)){
            return;
        }

        $Model = new \Common\Model\BaseModel();
        
        //获取充值订单信息
        $recharge = $Model->query("SELECT * FROM member_recharge WHERE tid='{$tid}'");
        if(empty($recharge)){
            return;
        }
        $recharge = $recharge[0];

        // 人员关系
        $list = json_decode($recharge['detail'], true);
        $msgDate = date('Y年m月d日 H:i');
        $message = null;
        $wechatAuthList = array();
        foreach($list as $level=>$item){
            if($item['money'] <= 0 && $item['id'] != $recharge['buyer_id']){ // 充值人
                continue;
            }
            
            $wxUser = $Model->getWXUserConfig($item['id']);
            $config = $wxUser['config'];
            
            if($item['id'] == $recharge['buyer_id']){ // 充值人
                $agent = $Model->agentLevel();
                $first = '恭喜您成为本平台'.$agent[$recharge['agent_level']]['title'].'！';
                $remark = '';
                if($item['money'] > 0){
                    $remark .= '系统赠送您'.sprintf('%.2f', $item['money']).'积分，';
                }
                $remark .= '赶紧去商城享受'.$agent[$recharge['agent_level']]['price_title'].'优惠价吧！';
                $message = array(
                    'template_id' => $config['WX_TEMPLATE']['TM00891'],
                    'url'  => $config['HOST'].'/h5/mall',
                    'data' => array(
                        'first'   => array('value' => $first, 'color' => '#173177'),
                        'grade1'  => array('value' => $agent[$item['agent_level']]['title']),
                        'grade2'  => array('value' => $agent[$recharge['agent_level']]['title']),
                        'time'    => array('value' => $msgDate),
                        'remark'  => array('value' => $remark)
                    )
                );
            }else{
                $message = array(
                    'template_id' => $config['WX_TEMPLATE']['TM00335'],
                    'url' => $config['HOST'].'/h5/balance',
                    'data' => array(
                        'first'        => array('value' => '您有新积分到账，详情如下。', 'color' => '#173177'),
                        'account'      => array('value' => $wxUser['name']),
                        'time'         => array('value' => $msgDate),
                        'type'         => array('value' => $level.'级好友冲值升级'),
                        'creditChange' => array('value' => '到账'),
                        'number'       => array('value' => sprintf('%.2f', $item['money']).'积分'),
                        'creditName'   => array('value' => '积分'),
                        'amount'       => array('value' => '***'),
                        'remark'       => array('value' => '积分已到账，点击查看详情。')
                    )
                );
            }
            
            $wechatAuth = isset($wechatAuthList[$config['WEIXIN']['appid']]) ? $wechatAuthList[$config['WEIXIN']['appid']] : new WechatAuth($config['WEIXIN']);
            $wechatAuthList[$config['WEIXIN']['appid']] = $wechatAuth;
            $result = $wechatAuth->sendTemplate($wxUser['openid'], $message);
        }
    }
    
    /**
     * 下单支付成功后发送消息
     */
    public function order_notify(){
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header('Connection: close');
        header('HTTP/1.1 200 OK');
        ob_end_flush();
        flush();
        set_time_limit(180);

        $where = '';
        $tidStr = addslashes($_POST['trades']);
        if(!empty($tidStr)){
            $where = "trade.tid IN (".$tidStr.")";
        }else{
            return;
        }

        $Model = new BaseModel();
        $exists = $Model->query("SELECT 1 FROM mall_pay_notify WHERE tid IN({$tidStr}) LIMIT 1");
        if(count($exists) > 0){
            exit('订单号已经通知过');
        }
        $loginId = $this->user('id', false);
        
        $sql = "SELECT trade.tid, trade.created, trade.`status`, trade.buyer_id, trade.buyer_nick, trade.buyer_openid,
                  trade.paid_balance, trade.payment, trade.pay_time,
                  trade.seller_id, shop.mid AS seller_mid, trade.seller_nick, trade.receiver_name, trade.receiver_mobile,
                  trade.receiver_province, trade.receiver_city, trade.receiver_county, trade.receiver_detail,
                  mall_order.oid, mall_order.discount_details, mall_order.num AS order_num, mall_order.price AS order_price, 
                  mall_order.payment AS order_payment, mall_order.product_id, mall_order.goods_id
                FROM mall_trade AS trade
                INNER JOIN mall_order ON mall_order.tid=trade.tid
                LEFT JOIN shop ON shop.id=trade.seller_id
                WHERE {$where}";
        $list = $Model->query($sql);
        if(count($list) == 0){
            exit('');
        }
        
        // 订单支付超时时间
        $timeOutSeconds = NOW_TIME - C('ORDER_TIME_OUT') - 60;
        
        // 1688下单
        $alibaba = new \Common\Model\AlibabaModel();
        $alibaba->commitOrder($tidStr);

        $today = date('Y-m-d H:i:s');
        $notifyRecord = "INSERT INTO mall_pay_notify(tid, `status`, created, client_ip) VALUES";
        $clientIP = get_client_ip();
        
        $tradeList = array();
        foreach ($list as $item){
            if(!isset($tradeList[$item['tid']])){
                $tradeList[$item['tid']] = array(
                    'tid'    => $item['tid'],
                    'created'    => $item['created'],
                    'pay_time'    => $item['pay_time'],
                    'status'    => $item['status'],
                    'buyer_id'    => $item['buyer_id'],
                    'buyer_nick'    => $item['buyer_nick'],
                    'buyer_openid'    => $item['buyer_openid'],
                    'paid_balance'    => $item['paid_balance'],
                    'payment'    => $item['payment'],
                    'seller_id'    => $item['seller_id'],
                    'seller_nick'    => $item['seller_nick'],
                    'seller_mid'    => $item['seller_mid'],
                    'receiver_name'    => $item['receiver_name'],
                    'receiver_mobile'    => $item['receiver_mobile'],
                    'receiver_province'    => $item['receiver_province'],
                    'receiver_city'    => $item['receiver_city'],
                    'receiver_county'    => $item['receiver_county'],
                    'receiver_detail'    => $item['receiver_detail'],
                    'orders' => array(),
                    'profit' => array()
                );
                
                if(is_numeric($loginId) && $item['buyer_id'] != $loginId){
                    E('支付通知：下单人与登录人不一致');
                }else if(strtotime($item['created']) < $timeOutSeconds){
                    E('虚假通知：支付超时');
                }
                $notifyRecord .= "({$item['tid']}, '{$item['status']}', '{$today}', '{$clientIP}'),";
            }
            
            $tradeList[$item['tid']]['orders'][] = array(
                'oid' => $item['oid'],
                'goods_id' => $item['goods_id'],
                'product_id' => $item['product_id'],
                'num' => $item['order_num'],
                'price' => $item['order_price'],
                'payment' => $item['order_payment'],
                'discount_details' => json_decode($item['discount_details'], true)
            );
        }

        // 保存通知记录
        $notifyRecord = trim($notifyRecord, ',');
        $Model->execute($notifyRecord);
        
        // 查找差价佣金
        $profitList = $Model->query("SELECT tid, mid, sum(total_fee) AS total_fee FROM mall_trade_difference WHERE tid IN({$tidStr}) GROUP BY tid, mid");
        foreach ($profitList as $item){
            $tradeList[$item['tid']]['profit'][$item['mid']] = $item['total_fee'];
        }        
        
        // 活动处理
        $this->discountDetails($Model, $tradeList);
        
        // 赠送积分优惠券
        $this->giveCouponORScore($Model, $tradeList);
        
        // 差价佣金消息提醒
        $this->msgToParent($Model, $tradeList);
        
        // 向店铺发送支付成功通知
        $this->sendMsgToShop($Model, $tradeList);
    }
    
    /**
     * 向推荐人发送佣金收入消息
     * @param BaseModel $Model
     * @param unknown $list
     */
    private function msgToParent(BaseModel $Model, $tradeList){
        $midList = $wechatAuthList = array();
        foreach ($tradeList as $trade){
            foreach ($trade['profit'] as $mid=>$amount){
                if($amount == 0){
                    continue;
                }
                
                if(!isset($midList[$mid])){
                    $wxUser = $Model->getWXUserConfig($mid);
                    $midList[$mid] = $wxUser;
                }else{
                    $wxUser = $midList[$mid];
                }
                
                if(empty($wxUser['config'])){
                    continue;
                }
                
                $appid = $wxUser['config']['WEIXIN']['appid'];
                if(!isset($wechatAuthList[$appid])){
                    $wechatAuth = new WechatAuth($wxUser['config']['WEIXIN']);
                    $wechatAuthList[$appid] = $wechatAuth;
                }else{
                    $wechatAuth = $wechatAuthList[$appid];
                }
                
                $message = array(
                    'template_id' => $wxUser['config']['WX_TEMPLATE']['TM00335'],
                    'url' => $wxUser['config']['HOST'].'/h5/order/detail?tid='.$trade['tid'],
                    'data' => array(
                        'first'    => array('value' => '您有新积分到账，详情如下。', 'color' => '#173177'),
                        'account'  => array('value' => '当前账户'),
                        'time'     => array('value' => date('Y年m月d日 H:i')),
                        'type'     => array('value' => '好友下单赠送'),
                        'creditChange' => array('value' => '赠送'),
                        'number'       => array('value' => $amount),
                        'creditName'   => array('value' => '积分'),
                        'amount'       => array('value' => '***'),
                        'remark'       => array('value' => '好友【'.$trade['buyer_nick'].'】下单赠送，点击查看详情。消息仅是辅助通知，请以实际收到积分为准！')
                    )
                );
                
                $result[] = $wechatAuth->sendTemplate($wxUser['openid'], $message);
            }
        }
    }
    
    /**
     * 赠送积分或优惠券
     * @param unknown $list
     */
    private function giveCouponORScore(BaseModel $Model, $list){
    }
    
    /**
     * 支付订单后向店铺推送消息
     * @param unknown $list
     */
    private function sendMsgToShop(BaseModel $Model, $tradeList){
        $shopTrade = $midList = array();
        foreach ($tradeList as $tid=>$trade){
            $midArray = explode(',', $trade['seller_mid']);
            foreach ($midArray as $mid){
                $shopTrade[$mid][] = $tid;
                if(!in_array($mid, $midList)){
                    $midList[] = $mid;
                }
            }
        }
        
        if(count($midList) == 0){
            return false;
        }
        
        // 查找客服
        $sql = "SELECT * FROM (
                    SELECT mid, openid, appid FROM wx_user
                    WHERE mid IN (".implode(',', $midList).")
                    ORDER BY last_login DESC
                ) AS wx_member
                GROUP BY mid";
        
        $users = $Model->query($sql);
        if(count($users) == 0){
            return;
        }
        
        $message = array(
            'template_id' => null,
            'url' => '',
            'data' => array(
                'first'     => array('value' => '您的店铺有新的订单生成，请按时发货哦', 'color' => '#173177'),
                'keyword1'  => array('value' => '店铺名称'),
                'keyword2'  => array('value' => '商品名称'),
                'keyword3'  => array('value' => '下单时间'),
                'keyword4'  => array('value' => '下单金额'),
                'keyword5'  => array('value' => '已付款'),
                'remark'    => array('value' => '')
            )
        );
        
        $wechatAuth = array();
        $configList = array();
        foreach ($users as $user){
            foreach ($shopTrade as $mid=>$tidList){
                if($user['mid'] != $mid){
                    continue;
                }
            
                foreach ($tidList as $tid){
                    $trade = $tradeList[$tid];
                    if(!isset($wechatAuth[$user['appid']])){
                        $config = get_wx_config($user['appid']);
                        if(empty($config) || empty($config['WX_TEMPLATE']['OPENTM200750297'])){
                            continue;
                        }
                    
                        $configList[$user['appid']] = $config;
                        $wechatAuth[$user['appid']] = new WechatAuth($config['WEIXIN']);
                    }
                    
                    $message['template_id'] = $configList[$user['appid']]['WX_TEMPLATE']['OPENTM200750297'];
                    $message['url'] = $configList[$user['appid']]['HOST'].'/h5/order/detail?tid='.$trade['tid'];
                    $message['data']['keyword1']['value'] = $trade['seller_nick'];
                    $message['data']['keyword2']['value'] = '******';
                    $message['data']['keyword3']['value'] = $trade['created'];
                    $message['data']['keyword4']['value'] = bcadd($trade['paid_balance'], $trade['payment'], 2).'元';
                    $message['data']['remark']['value'] = '收货地址：'.$trade['receiver_name'].' '.$trade['receiver_mobile'].' '.$trade['receiver_province'].$trade['receiver_city'].$trade['receiver_county'].$trade['receiver_detail'];
                    $wechatAuth[$user['appid']]->sendTemplate($user['openid'], $message);
                }
            }
        }
    }
    
    /**
     * 优惠活动处理
     */
    private function discountDetails(BaseModel $Model, $tradeList){
        $activeList = array();
        foreach ($tradeList as $trade){
            foreach ($trade['orders'] as $order){
                foreach ($order['discount_details'] as $detail){
                    if($detail['type'] == 1001){ // 众筹拼团
                        $activeList[1001][$detail['id']][$order['product_id']] = $order['num'];
                        break;
                    }else if($detail['type'] == 1002){ // 零元购
                        $activeList[1002][$detail['id']][$order['product_id']] = $order['num'];
                        break;
                    }
                }
            }
        }

        $this->grouponAfterPay($Model, $activeList[1001]);
        $this->zeroAfterPay($Model, $activeList[1002]);
    }
    
    /**
     * 团购付款后续处理
     */
    private function grouponAfterPay(BaseModel $Model, $info){
        if(empty($info)){
            return false;
        }
        
        $activeId = array_keys($info);
        $activeId = implode(',', $activeId);
        $list = $Model->query("SELECT * FROM mall_groupon WHERE id IN({$activeId})");
        foreach ($list as $active){
            $sold = 0;
            $products = json_decode($active['price_range'], true);
            foreach ($products as $productId=>$item){
                if(!isset($info[$active['id']][$productId])){
                    continue;
                }
        
                $products[$productId]['sold'] += $info[$active['id']][$productId];
                $sold += $info[$active['id']][$productId];
            }
            $products = json_encode($products, JSON_UNESCAPED_UNICODE);
            $Model->execute("UPDATE mall_groupon SET sold=sold+{$sold}, price_range='{$products}' WHERE id=".$active['id']);
        }
    }
    
    /**
     * 零元购付款后续处理
     */
    private function zeroAfterPay(BaseModel $Model, $info){
        if(empty($info)){
            return false;
        }

        $activeId = array_keys($info);
        $activeId = implode(',', $activeId);
        $list = $Model->query("SELECT * FROM mall_zero WHERE id IN({$activeId})");
        foreach ($list as $active){
            $sold = 0;
            $products = json_decode($active['products'], true);
            foreach ($products as $productId=>$item){
                if(!isset($info[$active['id']][$productId])){
                    continue;
                }
        
                $products[$productId]['sold'] += $info[$active['id']][$productId];
                $sold += $info[$active['id']][$productId];
            }
            $products = json_encode($products, JSON_UNESCAPED_UNICODE);
            $Model->execute("UPDATE mall_zero SET sold=sold+{$sold}, products='{$products}' WHERE id=".$active['id']);
        }
    }
}
?>