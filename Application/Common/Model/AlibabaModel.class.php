<?php
namespace Common\Model;

class AlibabaModel extends BaseModel
{
    protected $tableName = 'alibaba_goods';
    
    public function syncGoods($taoId, $goodsId = null){
        $result = array('error' => '', 'goods' => null);
        if(!is_numeric($taoId)){
            $result['error'] = '淘ID不能为空';
            return $result;
        }
        
        $goodsOld = $this->where("tao_id=".$taoId)->find();
        $type = is_null($goodsOld) ? 'add' : 'sync';
        
        // 微供商品接口
        $aop = new \Common\Model\AopModel();
        $taoData = $aop->getProduct($taoId);
        $images = $aop->getProductImg($taoId);
        
        if ( strlen($images['model']['__MAIN_IMAGES'])>0 )
            $images = '<img src="'.str_replace('|', '"/><img src="',$images['model']['__MAIN_IMAGES']).'"/>';
        else
            $images = '';
        $productInfo = $taoData['productInfo'];
        $goods = array(
            'tao_id'        => $productInfo['productID'],
            'seller_nick'   => $taoData['productOwnerId'],
            'subject'       => $productInfo['subject'],
            'last_sync'     => date('Y-m-d H:i:s'),
            'attributes'    => '',
            'images'        => '',
            'detail'        => $productInfo['description'].$images,
            'expire_time'   => strtotime(substr($taoData['expireTime'], 0, 14)) - 300,
            'min_order'     => $taoData['productInfo']['saleInfo']['minOrderQuantity'],
            'freight_tid'   => $productInfo['shippingInfo']['freightTemplateID'],
            'weight'        => $productInfo['shippingInfo']['unitWeight'] ? $productInfo['shippingInfo']['unitWeight'] : 0,
            'status'        => $productInfo['status'],
            'stock'         => $productInfo['saleInfo']['amountOnSale'],
            'sku_json'      => '',
            'type'          => $productInfo['productType'],
            'unit'          => $productInfo['saleInfo']['unit'],
            'price'         => $taoData['productInfo']['saleInfo']['priceRanges'][0]['price']
        );
        if ($OldAliGoods = M('alibaba_goods')->find($taoId)) {
            if ($OldAliGoods['last_update']) {
                $goods['last_update'] = $OldAliGoods['last_update'];
            }
        }
        
        // 商品图片
        $picHost = 'https://cbu01.alicdn.com/';
        $images = array();
        foreach($productInfo['image']['images'] as $src){
            $images[] = $picHost.$src;
        }
        $goods['images'] = json_encode($images);
        
        // 属性处理
        $attributes = array();
        foreach($productInfo['attributes'] as $item){
            if(!isset($attributes[$item['attributeID']])){
                $attributes[$item['attributeID']] = array('name' => $item['attributeName'], 'value' => $item['value']);
                $attrKs[] = $item['attributeName'];
            }else{
                $attributes[$item['attributeID']]['value'] .= ','.$item['value'];
            }
            $attrVs[] = $item['value'];
        }
        $goods['attributes'] = json_encode(array_values($attributes), JSON_UNESCAPED_UNICODE);
        
        // 产品信息
        $products = array();
        $skuJson = array();
        $newId = 0;
        $skuIdList = array();
        
        if ( $type == 'sync' )
        {
            $tmp = json_decode($goodsOld['products'], true);
            foreach( $tmp as $k => $v )
            {
                $productsOld[$v['spec_id']] = $v;
            }
        }
        
        foreach ($productInfo['skuInfos'] as $item){
            $skuInfo = array(
                'retail_price'  => $item['retailPrice'],
                'price'         => $item['price'],
                'spec_id'       => $item['specId'],
                'sku_id'        => $item['skuId'],
                'stock'         => ($item['amountOnSale'] - 10) > 0 ? $item['amountOnSale'] - 10 : 0,
                'sku_json'      => array()
            );
            if ($type == 'sync'){
                if ( $item['price']*1 != $productsOld[$item['specId']]['price']*1){
                    $result['error'] = '单品价格变更';
                }
            }
            
            foreach($item['attributes'] as $attrs){
                if(!isset($skuJson[$attrs['attributeID']])){
                    $skuJson[$attrs['attributeID']] = array(
                        'id'    => $attrs['attributeID'],
                        'text'  => $attributes[$attrs['attributeID']]['name'],
                        'items' => array()
                    );
                }
                
                $key = $attrs['attributeID'].$attrs['attributeValue'];
                if(!isset($skuIdList[$key])){
                    $newId++;
                    $skuIdList[$key] = $newId;
                    
                    $_data = array('id' => $skuIdList[$key], 'text' => $attrs['attributeValue']);
                    if(!empty($attrs['skuImageUrl'])){
                        $_data['img'] = $picHost.$attrs['skuImageUrl'];
                    }
                    $skuJson[$attrs['attributeID']]['items'][] = $_data;
                }
                
                $skuInfo['sku_json'][] = array(
                    'kid' => $skuJson[$attrs['attributeID']]['id'],
                    'vid' => $skuIdList[$key],
                    'k' => $skuJson[$attrs['attributeID']]['text'],
                    'v' => $attrs['attributeValue']
                );
            }

            $products[] = $skuInfo;
        }
        
        if(!empty($products)){
            $goods['products'] = json_encode($products);
        }
        if(!empty($skuJson)){
            $skuJson = array_values($skuJson);
            $goods['sku_json'] = json_encode($skuJson, JSON_UNESCAPED_UNICODE);
        }
        
        // 保存信息
        $this->add($goods, array(), true);
        
        // 过期时间
        if($goods['expire_time'] <= NOW_TIME){
            $result['error'] = '商品已过期';
        }else if($goods['type'] != 'wholesale'){
            $result['error'] = '不支持在线批发';
        }else if($goods['status'] != 'published'){
            $result['error'] = '商品未上架';
        }else if($goods['min_order'] > 1){
            $result['error'] ='最小起定量'.$productInfo['saleInfo']['minOrderQuantity'].$productInfo['saleInfo']['unit'];
        }
        
        if (isset($result['error']) && strlen($result['error'])>0) {
            if(is_numeric($goodsId)){
                $this->execute("UPDATE mall_goods SET is_display=0 WHERE id='{$goodsId}'");
            }else if(!empty($goodsOld)){
                $this->execute("UPDATE mall_goods SET is_display=0 WHERE tao_id='{$goodsOld['tao_id']}'");
            }
        }
        
        // 返回商品信息
        $goods['images']     = $images;
        $goods['attributes'] = $attributes;
        $goods['products']   = $products;
        
        $result['goods'] = $goods;
        return $result;
    }
    
    /**
     * 同步1688店铺
     * @param number $pid
     * @return number|\Think\false
     */
    public function syncShop(){
        $alibaba = new AopModel();
        $list = $alibaba->getSupplier();
        if(empty($list)){
            $this->error = $alibaba->getError();
            return -1;
        }
        
        $today = date('Y-m-d H:i:s');
        $sql = "INSERT IGNORE INTO alibaba_shop(`name`, created) VALUES";
        foreach($list as $name){
            $sql .= "('{$name}', '{$today}'),";
        }
        
        $sql = rtrim($sql, ',');
        $this->execute($sql);
        return $this->execute("ALTER TABLE `alibaba_shop` AUTO_INCREMENT=1");
    }
    
    /**
     * 获取阿里巴巴产品列表
     */
    public function getGoodsList($name, $type = '1688'){
        if(empty($name)){
            $this->error = '店铺名称不能为空';
            return;
        }
        
        // 查找1688店铺产品
        $aop = new AopModel();
        $i = 0;
        $temp = true;
        $offers = array();
        while ( $temp === true || count($temp)>=100 )
        {
            $temp = $aop->getGoodsList($name, $i*100, 100);
            if (!is_null($temp)) {
                $offers = array_merge($offers, $temp);
                $i++;
            }
        }
        
        // 查找本地已存储的产品
        $localList = $this->query("SELECT a.tao_id, a.expire_time, a.last_sync, 
            a.last_update, g.is_display, g.is_del, g.id
            FROM alibaba_goods AS a 
            LEFT JOIN  mall_goods AS g ON a.tao_id = g.tao_id 
            WHERE a.seller_nick='".addslashes($name)."'");

        $list = array();
        foreach($localList as $i=>$item){
            $item['sys_notice'] = '';
            if(!in_array($item['tao_id'], $offers)){
                $item['action'] = 'hidden';    // 下架
                $item['sys_notice'] = '已被淘家删除';
            }else if($item['expire_time'] <= NOW_TIME){
                $item['action'] = 'hidden';    // 下架
                $item['sys_notice'] = '已过期，建议下架';
            }else if($item['is_del'] == 0){
                $item['action'] = 'sync';    // 更新
            }else {
                $item['action'] = 'add';    // 添加
            }
            
            if(is_numeric($item['id'])){
                $_list = array(''.$item['tao_id'] => $item);
                $list = array_merge($_list, $list);
            }else{
                $list[''.$item['tao_id']] = $item;
            }
        }
        
        foreach($offers as $taoId){
            if(isset($list[''.$taoId])){
                continue;
            }
            
            $list[''.$taoId] = array(
                'action'        => 'add',   // 添加
                'tao_id'        => (string)$taoId,
                'sys_notice'    => ''
            );
        }
        
        return $list;
    }
    
    public function commitOrder ($tidList, $maxTid = null){
        if(empty($tidList)){
            return;
        }
        
        $where = "WHERE `status`='toorder'";
        if (is_numeric($tidList)){
            if(is_numeric($maxTid)){
                $where .= " AND tid BETWEEN '{$tidList}' AND '{$maxTid}'";
            }else{
                $where .= " AND tid=".$tidList;
            }
        }else if(is_array($tidList)){
            $where .= " AND tid IN(".implode(',', $tidList).")";
        }else{
            $where .= " AND tid IN({$tidList})";
        }

        ignore_user_abort(true);
        set_time_limit(0);
        $ali_order = $this->query("SELECT * FROM alibaba_trade {$where}");
        if(empty($ali_order) ){
            return false;
        }

        $orderTime = date('Y-m-d H:i:s');
        $this->startTrans();
        $AopList = array();
        foreach( $ali_order as $v_ ){
            if(!isset($AopList[$v_['buyer_login_id']])){
                $AopList[$v_['buyer_login_id']] = new AopModel($v_['buyer_login_id']);
            }
            $result = $AopList[$v_['buyer_login_id']]->openOrder($v_['post_json']);
            
            if($result['success']){
                $result = json_decode($result['model'][0], true, 512, JSON_BIGINT_AS_STRING);
                $this->execute("UPDATE alibaba_trade SET `status`='success', order_time='{$orderTime}', out_tid='{$result['orderId']}' WHERE id=".$v_['id']);
                $this->execute("UPDATE mall_trade SET `status`=IF(`status`='tosend', 'toout', `status`) WHERE tid = '{$v_['tid']}'");
            }else{
                $errmsg = mb_substr($result['msgInfo'],0,128,'utf-8');
                $errmsg = addslashes($errmsg);
                $this->execute("UPDATE alibaba_trade SET `status`='error', error_msg='{$errmsg}' WHERE id=".$v_['id']);
            }
        }
        $this->commit();
        return true;
    }

    public function getAliTrade($tids, $endTids = null){
        if(empty($tids)){
            return array();
        }
        
        $where = "WHERE alibaba_trade.is_del=0 AND ";
        if(is_numeric($tids)){
            if(is_numeric($endTids)){
                $where .= "alibaba_trade.tid BETWEEN {$tids} AND {$endTids}";
            }else{
                $where .= "alibaba_trade.tid=".$tids;
            }
        }else if(is_array($tids)){
            $where .= "alibaba_trade.tid IN(".implode(',', $tids).")";
        }else{
            $where .= "alibaba_trade.tid IN(".addslashes($tids).")";
        }

        $sql = "SELECT alibaba_trade.id, mall_trade.tid, alibaba_trade.out_tid, alibaba_trade.`status`, alibaba_trade.buyer_login_id, alibaba_trade.do_cost,
                    mall_trade.consign_time, mall_trade.express_no, alibaba_trade.payment, mall_trade.total_cost, mall_trade.express_no, alibaba_trade.type,
                    mall_trade.`status` AS trade_status, mall_trade.buyer_openid, wx_user.appid, mall_trade.receiver_name, mall_trade.receiver_mobile,
                    mall_trade.receiver_city, mall_trade.receiver_province, mall_trade.receiver_county, 
                    mall_trade.receiver_detail, wx_user.subscribe AS buyer_subscribe, alibaba_trade.pay_time
                FROM alibaba_trade
                INNER JOIN mall_trade ON alibaba_trade.tid=mall_trade.tid
                LEFT JOIN wx_user ON wx_user.openid=mall_trade.buyer_openid
                {$where}";

        $ali_trades = $this->query($sql);
        if(empty($ali_trades)){
            return array();
        }
        
        $Aop = null;
        $aopList = $updateList = array();
        $this->startTrans();
        foreach($ali_trades as $i=>$v){
            if(!isset($updateList[$v['tid']])){
                $updateList[$v['tid']] = array(
                    'appid'     => $v['appid'],
                    'consign_time' => $v['consign_time'],
                    'express_no' => array(),
                    'express_changed' => false,
                    'changed'    => false,
                    'receiver_name' => $v['receiver_name'],
                    'receiver_mobile' => $v['receiver_mobile'],
                    'receiver_province' => $v['receiver_province'],
                    'receiver_city' => $v['receiver_city'],
                    'receiver_county' => $v['receiver_county'],
                    'receiver_detail' => $v['receiver_detail'],
                    'kind'      => 1,
                    'sended'    => 0,
                    'trade_status' => $v['trade_status'],
                    'buyer_openid' => $v['buyer_openid'],
                    'buyer_subscribe' => $v['buyer_subscribe']
                );
            
                if(!empty($v['express_no'])){
                    $_express_no = explode(';', $v['express_no']);
                    foreach ($_express_no as $en){
                        $enInfo = explode(':', $en);
                        $updateList[$v['tid']]['express_no'][$enInfo[1]] = $enInfo[0];
                    }
                }
            }else{
                $updateList[$v['tid']]['kind']++;
            }
            
            if(!is_numeric($v['out_tid'])){
                continue;
            }
            
            // 已发货则不再请求
            if($v['status'] == 'end' || $v['type'] != 1){
                if($v['type'] == 1){     // 1688
                    $updateList[$v['tid']]['sended']++;
                }
                continue;
            }
            
            if(!isset($aopList[$v['buyer_login_id']])){
                $Aop = new \Common\Model\AopModel($v['buyer_login_id']);
                $aopList[$v['buyer_login_id']] = $Aop;
            }else{
                $Aop = $aopList[$v['buyer_login_id']];
            }
            
            $aorder = $Aop->getOrder($v['out_tid']);
            if(empty($aorder)){
                continue;
            }
            
            $aorder = $aorder['result']['toReturn'][0];

            // 付款时间
            if (!empty($aorder['gmtPayment'])){
                $payTime = strtotime(substr($aorder['gmtPayment'], 0, 14));
                $payTime = date('Y-m-d H:i:s', $payTime);
            }else{
                $payTime = null;
            }
            
            $payment = ($aorder['sumPayment'] + (isset($aorder['codFee']) ? $aorder['codFee'] : 0)) / 100;
            if (!empty($aorder['gmtGoodsSend'])){
                $updateList[$v['tid']]['sended']++;
                
                if(empty($updateList[$v['tid']]['consign_time']) && $updateList[$v['tid']]['consign_time'] != '0000-00-00 00:00:00'){
                    $sendTime = strtotime(substr($aorder['gmtGoodsSend'], 0, 14));
                    $sendTime = date('Y-m-d H:i:s', $sendTime);
                    
                    $updateList[$v['tid']]['changed'] = true;
                    $updateList[$v['tid']]['consign_time'] = $sendTime;
                }
                
                foreach($aorder['logistics'] as $logistic){
                    if($updateList[$v['tid']]['express_no'][$logistic['logisticsBillNo']] != $logistic['logisticsCompanyName']){
                        $updateList[$v['tid']]['express_no'][$logistic['logisticsBillNo']] = $logistic['logisticsCompanyName'];
                        $updateList[$v['tid']]['changed'] = $updateList[$v['tid']]['express_changed'] = true;
                    }
                }
                
                $this->execute("UPDATE alibaba_trade SET `status`='end', payment={$payment}, pay_time='{$payTime}' WHERE id=".$v['id']);
            }else if(($payment > $v['payment'] || $payment < $v['payment']) || (!is_null($payTime) && $payTime != $v['pay_time'])){
                $sqlSet = "payment={$payment}";
                if(!is_null($payTime)){
                    $sqlSet .= ",pay_time='{$payTime}'";
                }
                $this->execute("UPDATE alibaba_trade SET {$sqlSet} WHERE id=".$v['id']);
            }
        }
        
        $changed = array();
        $appList = array();
        foreach ($updateList as $tid=>$item){
            if($item['kind'] == $item['sended'] && $item['trade_status'] == 'toout'){
                $item['changed'] = true;
                $item['trade_status'] = 'send';
            }
            
            if(!$item['changed']){
                continue;
            }
            
            $express_no = $lastNo = $lastName = '';
            foreach ($item['express_no'] as $gid=>$name){
                $express_no .= ($express_no == '' ? '' : ';').$name.':'.$gid;
                $lastNo = $gid;
                $lastName = $name;
            }
            
            $this->execute("UPDATE mall_trade SET `status`='{$item['trade_status']}', consign_time='".($item['consign_time'] ? $item['consign_time'] : 'null')."', express_no='{$express_no}' WHERE tid=".$tid);
            $changed[$tid] = array(
                'consign_time' => $item['consign_time'],
                'express_no' => $express_no,
                'status' => $item['trade_status']
            );
            
            if($item['express_changed'] && $item['buyer_subscribe'] && !empty($updateList[$tid]['express_no'])){
                if(!isset($appList[$item['appid']])){
                    $config = get_wx_config($item['appid']);
                    $WechatAuth = new \Org\Wechat\WechatAuth($config['WEIXIN']);
                    $appList[$item['appid']] = array('config' => $config, 'wechat' => $WechatAuth);
                }else{
                    $config = $appList[$item['appid']]['config'];
                    $WechatAuth = $appList[$item['appid']]['wechat'];
                }
                
                $message = array(
                    'template_id' => $config['WX_TEMPLATE']['OPENTM200565259'],
                    'url' => $config['HOST'].'/h5/order/detail?tid='.$tid,
                    'data' => array(
                        "first"    => array("value" => '亲,您的订单已发货'),
                        "keyword1" => array("value" => $tid),
                        "keyword2" => array("value" => $lastName),
                        "keyword3" => array("value" => $lastNo),
                        "remark"   => array("value" => '收货地址：'."\t".$item["receiver_name"] ."\t".$item["receiver_mobile"]."\t".$item["receiver_province"]. $item["receiver_city"]. $item["receiver_county"]. $item["receiver_detail"])
                    )
                );
                $WechatAuth->sendTemplate($item['buyer_openid'], $message);
            }
        }
        $this->commit();
        return $changed;
    }
    
    /**
     * 批量同步
     * @param unknown $next
     */
    public function syncAllTrade($nextTid){
        $data = $this->getMinSyncTid($nextTid);
        if(empty($data)){
            return;
        }
        
        for($i=$data['min_tid']; $i<=$data['max_tid']; $i+=500){
            $this->getAliTrade($i, $i+499);
        }
        
        return $this->getMinSyncTid($nextTid);
    }
    
    public function getMinSyncTid($nextTid){
        $sql = "SELECT MIN(tid) AS min_tid, MAX(tid) AS max_tid FROM mall_trade WHERE tid>{$nextTid} AND `status`='toout'";
        $data = $this->query($sql);
        return $data[0];
    }
}
?>