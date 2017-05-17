<?php 
namespace Common\Model;

class PayOrderModel extends OrderModel{
    
    /**
     * 下单解析
     * @param unknown $post
     * @param unknown $buyer
     */
    public function order($address, $postGroups, $login, $from = ''){
        // 校验收货地址
        if(empty($address['receiver_name'])){
            $this->error = '收货人不能为空';
        }else if(empty($address['receiver_mobile'])){
            $this->error = '收货人手机号不能为空';
        }else if(empty($address['receiver_province']) || empty($address['receiver_city'])){
            $this->error = '收货人城市不能为空';
        }else if(empty($address['receiver_detail'])){
            $this->error = '详细收货地址不能为空';
        }
        if($this->error != ''){ return; }
        
        // 获取下单产品id
        $orderProducts = '';
        foreach($postGroups as $sellerId=>$trades){
            foreach($trades as $freightTid=>$data){
                foreach($data['products'] as $id=>$product){
                    $orderProducts .= $id.',';
                }
            }
        }
        if($orderProducts == ''){
            $this->error = '订单产品不能为空';
            return;
        }else{
            $orderProducts = rtrim($orderProducts, ',');
        }
        
        // 获取买家信息
        $buyer = $this->getTradingBuyer($login);
        if(empty($buyer)){$this->error = '账号丢失，请重新下单'.__LINE__; return;}
        
        // 提交的产品信息列表
        $products = $this->getTradingProducts($orderProducts, $buyer);
        if(count($products) != substr_count($orderProducts, ',')+1){
            $this->error = '产品不存在，请重新下单'.__LINE__;
            return;
        }
        
        // 获取优惠信息
        $discountList = $this->getDiscountInfo($buyer, $products, $postGroups);
        if($this->error != ''){
            return;
        }
        
        // 本次交易订单，分组规则：店铺+运费模板相同则为同一个订单
        $groups = array();
        $quotaList = array();   // 限购列表
        $differenceList = array();// 订单差价
        $buyList = array(); // 用于计算邮费
        $tradeCount = 0;
        
        foreach($products as $product){
            $postTrade = $postGroups[$product['seller_id']][$product['freight_tid']];
            $post = $postTrade['products'][$product['product_id']];
            if(empty($post)){
                $this->error = '产品信息已变更，请重新下单'.__LINE__;
                return;
            }
            
            // 本次购买数量
            $product['buy_num'] = $post['num'];
        
            // 交易
            $group = isset($groups[$product['seller_id']]) ? $groups[$product['seller_id']] : array();
        
            // 交易运费模板
            $trade = null;
            if(!isset($group[$product['freight_tid']])){
                $tradeCount++;
                if($tradeCount > 20){
                    $this->error = '已超过20单，建议拆开结算';
                    return;
                }
                
                $expressId = $postTrade['express_id'];
                if(!is_numeric($expressId) || $expressId < 1){
                    $this->error = '订单异常：未选择配送方式';
                    return;
                }
                
                $trade = array(
                    'tid'               => '',
                    'status'            => 'topay',
                    'created'           => '',
                    'type'              => 'normal',
                    'receiver_name'     => $address['receiver_name'],
                    'receiver_mobile'   => $address['receiver_mobile'],
                    'receiver_province' => $address['receiver_province'],
                    'receiver_county'   => $address['receiver_county'],
                    'receiver_city'     => $address['receiver_city'],
                    'receiver_detail'   => $address['receiver_detail'],
                    'receiver_zip'      => $address['receiver_zip'],
                    'buyer_id'          => $buyer['id'],
                    'buyer_agent_level' => $buyer['agent_level'],
                    'buyer_nick'        => $buyer['nickname'],
                    'buyer_type'        => $login['login_type'],
                    'buyer_openid'      => $buyer['openid'],
                    'buyer_subscribe'   => $buyer['subscribe'],
                    'buyer_remark'      => $postTrade['remark'],
                    'seller_id'         => $product['seller_id'],
                    'seller_nick'       => $product['seller_nick'],
                    'kind'              => 0, // 购买了几种商品
                    'total_num'         => 0, // 商品数量的和
                    'total_fee'         => 0, // 商品数量*商品售价的和
                    'total_cost'        => 0, // 商品数量*商品成本的和
                    'total_weight'      => 0, // 商品数量*商品重量的和
                    'paid_fee'          => 0, // 已付金额
                    'payment'           => 0, // 需付金额
                    'discount_fee'      => 0, // 优惠总额
                    'paid_balance'      => 0, // 使用n元可提现金额
                    'paid_no_balance'   => 0, // 使用n元不可提现金额
                    'post_fee'          => 0, // 总运费
                    'pay_time'          => '', // 付款时间
                    'merge_pay'         => '', // 合并支付单号
                    'shipping_type'     => 'express', // 物流方式
                    'express_id'        => $expressId, // 运费id
                    'discount_details'  => array()  // 优惠详情
                );
            }else{
                $trade = $group[$product['freight_tid']]['trade'];
            }
            
            // 代理价
            $product['price']  = $this->getAgentPrice($buyer['agent_level'], $product);

            // 限时折扣限购
            foreach ($discountList['discount'] as $discount){
                if(in_array($product['goods_id'], $discount['goods'])){
                    if($discount['quota'] > 0){
                        $product['buy_quota'] = $discount['quota'];
                    }
                    break;
                }
            }
            
            // 校验数据
            $validate = $this->validOrder($buyer, $product, $quotaList, false);
            if($validate['error_code'] != 0){
                $this->error = $validate['error_msg'];
                return;
            }

            // 上级差价佣金
            if(!isset($product['commission'])){
                $commission = 0;
                $product['commission'] = array();
                if($buyer['parent_level'] > 0){
                    $myPrice = $this->getAgentPrice($buyer['agent_level'], $product);
                    if($buyer['agent_level'] == $buyer['parent_level']){
                        if($buyer['parent_level']-1 > 0){
                            $commission = $this->getAgentPrice($buyer['parent_level']-1, $product);
                            $commission = bcsub($myPrice, $commission, 2);
                            $commission = bcdiv($commission, 2, 2);
                        }
                    }else{
                        $parentPrice = $this->getAgentPrice($buyer['parent_level'], $product);
                        $commission = bcsub($myPrice, $parentPrice, 2);
                    }
                    if($commission > 0){
                        $product['commission'][$buyer['pid']] = $commission;
                    }
                }
            }
            foreach ($product['commission'] as $mid=>$amount){
                $differenceList[$product['product_id']][$mid] = array(
                    'buyer_id'     => $buyer['id'],
                    'num'          => $product['buy_num'],
                    'diff_price'   => $amount,
                    'total_fee'    => $product['buy_num'] * $amount
                );
            }
            
            // 合并默认优惠信息
            if(!empty($product['discount_details'])){
                $trade['discount_details'] = array_merge($trade['discount_details'], $product['discount_details']);
            }
            
            if(!is_array($product['discount_details'])){
                $product['discount_details'] = $post['discount_details'];
            }else if(!empty($post['discount_details'])){
                $product['discount_details'] = array_merge($product['discount_details'], $post['discount_details']);
            }
            
            // 订单详情项
            $order = array(
                'product_id'   => $product['product_id'],
                'goods_id'     => $product['goods_id'],
                'title'        => $product['title'],
                'cat_id'       => $product['cat_id'],
                'pic_url'      => empty($product['pic_url']) ? $product['goods_pic_url'] : $product['pic_url'],
                'pay_type'     => $product['pay_type'],
                'original_price'=>$product['original_price'],
                'price'        => $product['price'],
                'cost'         => $product['cost'],
                'weight'       => $product['weight'],
                'discount_fee' => 0,
                'total_fee'    => $product['buy_num'] * $product['price'],
                'sku_json'     => $product['sku_json'],
                'num'          => $product['buy_num'],
                'payment'      => $product['buy_num'] * $product['price'],
                'outer_id'     => $product['outer_id'],
                'shipping_type'=> $product['is_virtual'] == 1 ? 'virtual' : 'express',
                'send_time'    => '',
                'status'       => 'topay',
                'discount_details' => $product['discount_details'],
                'postage'      => $post['postage'], // 临时字段
                'score'        => $product['score'] // 临时字段
            );

            $group[$product['freight_tid']]['trade'] = $trade;
            $group[$product['freight_tid']]['orders'][] = $order;
            $groups[$product['seller_id']]  = $group;
            
            $buyList[$product['product_id']] = array(
                'num' => $product['buy_num'],
                'postage' => $post['postage'],
                'express_id' => $expressId,
                'freight_tid' => $product['freight_tid'],
                'attach_postage' => $product['attach_postage']
            );
        }
        
        // 获取运费
        $ExpressModel = new ExpressModel();
        $postFeeData  = $ExpressModel->getFreightFee($buyList, $address, true);
        $taoTradeList = $postFeeData['tao_list'];
        
        // 计算金额和优惠
        $couponList = array('id' => array(), 'vid' => array());
        foreach ($groups as $sellerId=>&$group){
            foreach ($group as $freightTid=>&$info){
                /*
                // 处理限时折扣
                $_return = $this->discountHandler($info, $discountList['discount']);
                if($this->error != ''){return;}
                if(is_array($_return)){
                    $couponList['id'] = array_merge($couponList['id'], $_return);
                }
                
                // 处理满减
                $_return = $this->promotionHandler($info, $discountList['promotion']);
                if($this->error != ''){return;}
                if(is_array($_return)){
                    $couponList['id'] = array_merge($couponList['id'], $_return);
                }
                */
                
                // 处理优惠券
                $_return = $this->couponHandler($info, $discountList['coupon'], $freightTid);
                if($this->error != ''){return;}
                if(is_array($_return)){
                    $couponList['id'] = array_merge($couponList['id'], $_return['id']);
                    $couponList['vid'] = array_merge($couponList['vid'], $_return['vid']);
                }
                
                // trade数据汇总
                $trade  = &$info['trade'];
                $orders = &$info['orders'];
                $info['taoList'] = array();

                foreach($orders as &$order){
                    $trade['discount_fee'] = bcadd($trade['discount_fee'], $order['discount_fee'], 2);
                    $trade['total_num']   += $order['num'];
                    $trade['total_fee']    = bcadd($trade['total_fee'], $order['total_fee'], 2);
                    // 先不计入成本，方便后面核算，否则无法核算出哪个应该计算成本
                    //$trade['total_cost']   = bcadd($trade['total_cost'], $order['cost'] * $order['num'], 2);
                    $trade['total_weight'] = bcadd($trade['total_weight'], $order['weight'] * $order['num'], 2);
                    
                    // 积分抵用
                    $discountScore = end($order['discount_details']);
                    if(!empty($discountScore) && $discountScore['id'] == 0){
                        if($buyer['no_balance'] <= 0){
                            $this->error = '积分余额不足：剩余'.$buyer['no_balance'];
                            return;
                        }

                        $score = bcmul($order['score'], $order['payment'], 2);
                        $score = bcmul($score, 0.01, 2)*1;
                        if($score > $buyer['no_balance']){
                            $score = $buyer['no_balance'];
                        }
                        
                        if($score != $discountScore['discount_fee']*1){
                            $this->error = '商品抵用积分不一致';
                            return;
                        }else if($score > $buyer['no_balance']){
                            $this->error = '积分余额不足：剩余'.$buyer['no_balance'];
                            return;
                        }
                        
                        $buyer['no_balance'] = bcsub($buyer['no_balance'], $score, 2);
                        $order['discount_fee'] = bcadd($order['discount_fee'], $score, 2);
                        $order['payment'] = bcsub($order['total_fee'], $order['discount_fee'], 2);
                        $trade['paid_no_balance'] = bcadd($trade['paid_no_balance'], $score, 2);
                    }
                    
                    // 1688订单
                    if(isset($taoTradeList[$order['product_id']])){
                        $taoTrade = &$taoTradeList[$order['product_id']];
                        if($taoTrade['has_error']){
                            $this->error = '失败：'.$taoTrade['error_msg'];
                            return;
                        }
                        $trade['type'] = '1688';
                        unset($taoTrade['has_error']);
                        unset($taoTrade['error_msg']);
                    
                        $taoTrade['tid'] = '';
                        $taoTrade['post_json']['senderInfo'] = $trade['buyer_remark'];
                        $taoTrade['post_json'] = json_encode($taoTrade['post_json'], JSON_UNESCAPED_UNICODE);
                        $info['taoList'][$order['product_id']] = $taoTrade;
                    }

                    unset($order['postage']);
                    unset($order['score']);
                }

                // 计算运费
                foreach ($postFeeData['system_list'][$sellerId][$freightTid] as $express){
                    if($express['id'] == $trade['express_id']){
                        if($express['has_error']){
                            $this->error = '收货地址异常：'.$express['error_msg'];
                            return;
                        }
                        $trade['post_fee'] = $express['money'];
                        break;
                    }
                }

                $trade['kind']      = count($orders);
                $trade['payment']   = bcadd($trade['total_fee'], $trade['post_fee'], 2);
                $trade['payment']   = bcsub($trade['payment'], $trade['discount_fee'], 2);
                $trade['payment']   = bcsub($trade['payment'], $trade['paid_no_balance'], 2);
                
                if($buyer['balance'] > 0){
                    $trade['paid_balance'] = $buyer['balance'] <= $trade['payment'] ? $buyer['balance'] : $trade['payment'];
                    if($trade['paid_balance'] > 0){
                        $trade['payment']      = bcsub($trade['payment'], $trade['paid_balance'], 2);
                        $buyer['balance']      = bcsub($buyer['balance'], $trade['paid_balance'], 2);
                    }
                }
                
                $trade['paid_fee'] = bcadd($trade['paid_no_balance'], $trade['paid_balance'], 2);
                
                // 与客户端提交的数据对比
                $postTrade = $postGroups[$sellerId][$freightTid];
                if($trade['discount_fee']*1 != $postTrade['discount_fee']* 1){
                    $this->error = '折扣总额不一致，请重新下单'.__LINE__;
                }else if($trade['paid_no_balance']*1 != $postTrade['paid_no_balance']*1){
                    $this->error = '积分折扣不一致，请重新下单';
                }else if($trade['post_fee']*1 != $postTrade['post_fee']*1){
                    $this->error = '总运费不一致，请重新下单';
                }else if($trade['paid_balance']*1 != $postTrade['paid_balance']*1){
                    $this->error = '积分抵扣已变化，请重新下单';
                }else if($trade['payment']*1 != $postTrade['payment']*1){
                    $this->error = '应付金额不一致，请重新下单';
                }
                
                if($this->error != ''){
                    return;
                }
            }
        }
        
        return $this->addTrade(array(
            'groups'        => $groups,
            'buyer'         => $buyer,
            'differenceList'=> $differenceList,
            'couponList'    => $couponList,
            'from'          => $from,
            'tradeCount'    => $tradeCount
        ));
    }
    
    /**
     * 处理限时折扣
     * @param array $info trade和orders
     * @param unknown $discountList
     */
    private function discountHandler(&$info, $discountList){
        if(empty($discountList)){
           return; 
        }
        
        $discountDetails = array();
        foreach ($info['orders'] as $i=>&$order){
            if($order['single']){   // 只能参与一次优惠
                continue;
            }
            
            $discount = $used = null;
            foreach ($order['discount_details'] as $_used){
                if(isset($discountList[$_used['id']])){
                    $discount = $discountList[$_used['id']];
                    $used = $_used;
                    break;
                }
            }
            
            if(is_null($discount)){
                continue;
            }
            
            if(!isset($discount['goods'][$order['goods_id']])){
                $this->error = '商品【'.$order['goods_id'].'】未参与折扣【'.$discount['id'].'】';
                return;
            }
            
            if($discount['single'] && $order['discount_fee'] > 0){
                $this->error = '商品【'.$order['goods_id'].'】不能叠加折扣【'.$discount['id'].'】';
                return;
            }
            
            $originalPrice = $order['price'];
            $value = $discount['goods'][$order['goods_id']];
            // 折扣价
            $order['price'] = $value > 0 ? bcmul($order['price'], $value * 0.1, 2) : bcadd($order['price'], $value, 2);
            if($order['price'] <= 0){
                $this->error = '无效折扣【'.$discount['id'].'】商品【'.$order['goods_id'].'】';
                return;
            }
            
            // 折扣总额
            $discountFee = bcsub($originalPrice, $order['price'], 2) * $order['num'];
            if($discountFee > $used['discount_fee'] || $discountFee < $used['discount_fee']){
                $this->error = '折扣额异常【'.$order['product_id'].'-'.$discount['id'].'】';
                return;
            }
            
            $order['total_fee'] = bcmul($order['price'], $order['num'], 2);
            $order['payment'] = $order['total_fee'];
            if($discount['single']){
                $order['single'] = true;
            }
            
            // 记录折扣
            if(!isset($discountDetails[$discount['id']])){
                $discountDetails[$discount['id']] = array(
                    'id'           => $discount['id'],
                    'type'         => $discount['type'],
                    'title'        => $discount['title'],
                    'discount_fee' => $discountFee,
                    'single'       => $discount['single']
                );
            }else{
                $discountDetails[$discount['id']]['discount_fee'] = bcadd($discountDetails[$discount['id']]['discount_fee'], $discountFee, 2);
            }
        }
        
        $returnList = array();
        foreach ($discountDetails as $discount){
            $info['trade']['discount_details'][] = $discount;
            $returnList[] = $discount['id'];
        }
        return $returnList;
    }
    
    /**
     * 每件产品减少多少钱
     */
    private function manjian($jian, $tradeGoods){
        arsort($tradeGoods);
        $list = $result = array();
        $total = $totalDiscount = $discount = 0;
        
        $last = 0;
        foreach($tradeGoods as $i=>$payment){
            $last = $i;
            $total += $payment;
        }
    
        $prec = bcdiv($jian, $total, 6);
        
        foreach($tradeGoods as $i=>$payment){
            if($last == $i){
                $discount = bcsub($jian, $totalDiscount, 2);
            }else{
                $discount = bcmul($payment, $prec, 2);
            }
            $result[$i] = $discount;
            $totalDiscount = bcadd($totalDiscount, $discount, 2);
        }
    
        return $result;
    }
    
    /**
     * 处理满减
     * @param unknown $groups
     * @param unknown $discountList
     */
    private function promotionHandler(&$tradeInfo, $discountList){
        if(empty($discountList)){
            return;
        }
    
        // 一个产品只能参加一种满减
        $discountGroup = $pushList = array();
        foreach ($tradeInfo['orders'] as $i=>$order){
            if($order['single'] || $order['payment'] <= 0){   // 只能参与一次优惠
                continue;
            }
            
            foreach ($order['discount_details'] as $usedIndex=>$used){
                if(!isset($discountList[$used['id']])){
                    continue;
                }
                
                $discount = $discountList[$used['id']];
                if(!in_array($order['goods_id'], $discount['goods'])){
                    $this->error = '满减无效：商品未参与满减活动';
                    return;
                }else if(in_array($order['product_id'], $pushList)){
                    $this->error = '满减无效：商品不能重复参与满减';
                    return;
                }else if($discount['single'] && $order['discount_fee'] > 0){
                    $this->error = '满减无效：商品不能参与其他优惠';
                    return;
                }
                
                if(!isset($discountGroup[$discount['id']])){
                    $discountGroup[$discount['id']] = array('total_fee' => 0, 'payment' => array(), 'detail' => array());
                }
                $discountGroup[$discount['id']]['total_fee'] = bcadd($discountGroup[$discount['id']]['total_fee'], $order['payment'], 2);
                $discountGroup[$discount['id']]['payment'][$i] = $order['payment'];
                $discountGroup[$discount['id']]['detail'][$i] = $usedIndex;
            }
        }
        
        // 判断金额是否达到满减条件
        $returnList = array();
        foreach($discountGroup as $discountId=>$data){
            $discount = $discountList[$discountId];
            if($data['total_fee'] < $discount['meet']){ // 未达到最低满减条件
                $this->error = '满减无效：未达到最低满减金额';
                return;
            }
            
            $info = null; // 优惠详情
            $meet = 0;
            foreach ($discount['value'] as $meet=>$discountInfo){
                if($data['total_fee'] >= $meet){
                    $info = $discountInfo;
                }
            }
            
            if(is_null($info)){
                $this->error = '【'.$discountId.'】满减内容丢失';
                return;
            }

            $result = $info['money'] > 0 ? $this->manjian($info['money'], $data['payment']) : null;
            foreach ($data['payment'] as $i=>$payment){
                $order = &$tradeInfo['orders'][$i];
                // 减现金
                if($info['money'] > 0){
                    $discountFee = $result[$i];
                    $submitFee = $order['discount_details'][$data['detail'][$i]]['discount_fee'];
                    if($submitFee > $discountFee || $submitFee < $discountFee){
                        $this->error = '满减无效：优惠金额不一致';
                        return false;
                    }

                    $order['discount_fee'] = bcadd($order['discount_fee'], $discountFee, 2);
                    $order['payment'] = bcsub($order['total_fee'], $order['discount_fee'], 2);
                }
                
                // 包邮
                if($order['postage'] != $info['postage']){
                    $this->error = '满减无效：包邮不一致';
                    return false;
                }
            }

            $tradeInfo['trade']['discount_details'][] = array(
                'id'           => $discount['id'],
                'type'         => $discount['type'],
                'title'        => $discount['title'],
                'discount_fee' => $info['money'],
                'postage'      => $info['postage'],
                'single'       => $discount['single'],
                'meet'         => $meet
            );
            
            $returnList[] = $discount['id'];
        }
        
        return $returnList;
    }
    
    /**
     * 处理优惠券
     * @param unknown $tradeInfo
     * @param unknown $discountList
     */
    private function couponHandler(&$tradeInfo, $discountList){
        if(empty($discountList)){
            return;
        }
    
        // 一个产品只能使用一种优惠券
        $discountGroup = $pushList = array();
        foreach ($tradeInfo['orders'] as $i=>$order){
            if($order['single'] || $order['payment'] <= 0){   // 只能参与一次优惠
                continue;
            }
            
            foreach ($order['discount_details'] as $usedIndex=>$used){
                if(!isset($discountList[$used['id']])){
                    continue;
                }
                
                $discount = $discountList[$used['id']];
                if(!in_array($order['goods_id'], $discount['goods'])){
                    $this->error = '商品与优惠券不匹配';
                    return;
                }else if(in_array($order['product_id'], $pushList)){
                    $this->error = '每种产品只可用一张优惠券';
                    return;
                }else if($discount['single'] && $order['discount_fee'] > 0){
                    $this->error = '此优惠券不能和其他优惠一起使用';
                    return;
                }
                
                if(!isset($discountGroup[$discount['id']])){
                    $discountGroup[$discount['id']] = array('total_fee' => 0, 'payment' => array(), 'detail' => array(), 'vid' => $used['vid'], 'discount_fee' => $discount['value'][$used['vid']]);
                    unset($discount['value'][$used['vid']]);    // 销毁此优惠券
                }else if(!isset($discount['value'][$used['vid']])){
                    $this->error = '优惠券已使用或不存在';
                    return;
                }else if($discountGroup[$discount['id']]['vid'] != $used['vid']){
                    $this->error = '您的优惠券id异常，无法使用';
                    return;
                }
                
                $discountGroup[$discount['id']]['total_fee'] = bcadd($discountGroup[$discount['id']]['total_fee'], $order['payment'], 2);
                $discountGroup[$discount['id']]['payment'][$i] = $order['payment'];
                $discountGroup[$discount['id']]['detail'][$i] = $usedIndex;
            }
        }
        
        $returnList = array('id' => array(), 'vid' => array());
        foreach($discountGroup as $discountId=>$data){
            if($data['discount_fee'] > $data['total_fee']){
                $this->error = '优惠券优惠金额不能超出商品支付总额';
                return;
            }
            
            $discount = $discountList[$discountId];
            if($discount['meet'] > 0 && $data['total_fee'] < $discount['meet']){
                $this->error = '满'.$discount['meet'].'才能使用此优惠券';
                return;
            }
            
            $result = $this->manjian($data['discount_fee'], $data['payment']);
            foreach ($data['payment'] as $i=>$payment){
                $order = &$tradeInfo['orders'][$i];
                
                // 折扣
                $discountFee = $result[$i];
                $submitFee = $order['discount_details'][$data['detail'][$i]]['discount_fee'];
                if($submitFee > $discountFee || $submitFee < $discountFee){
                    $this->error = '商品与优惠金额不匹配';
                    return false;
                }
                $order['discount_fee'] = bcadd($order['discount_fee'], $discountFee, 2);
                $order['payment'] = bcsub($order['total_fee'], $order['discount_fee'], 2);
            }
            
            $tradeInfo['trade']['discount_details'][] = array(
                    'id'           => $discount['id'],
                    'vid'          => $data['vid'],
                    'type'         => $discount['type'],
                    'title'        => $discount['title'],
                    'discount_fee' => $data['discount_fee'],
                    'single'       => $discount['single'],
                    'meet'         => $discount['meet']
                );

            $returnList['id'][]  = $discount['id'];
            $returnList['vid'][] = $data['vid'];
        }
        
        return $returnList;
    }
    
    /**
     * 将分组分割成单个订单
     * @param unknown $groups
     * @param unknown $buyer
     */
    private function addTrade($parameters){
        $groups         = $parameters['groups'];
        $buyer          = $parameters['buyer'];
        $differenceList = $parameters['differenceList'];
        $couponList     = $parameters['couponList'];
        $from           = $parameters['from'];
        
        $pay = array(
            'total_fee' => 0,
            'trades' => array(),
            'order_no' => null,
            'openid'   => $buyer['openid'],
            'attach'   => $buyer['id'],
            'body' => '',
            'detail' => array('goods_detail' => array())
        );
        $today = date('Y-m-d H:i:s');
        
        // 买家ip定位
        $ipLocation = new \Org\Net\IpLocation();
        $location = $ipLocation->getlocation();
        
        $idwork = new \Org\IdWork();    // 自增id
        // 插入交易表 - 插入交易详情表 - 1688订单 - 1688产品id对应所在数组
        $tradeList = $orderList = $taoTradeList = $grouponList = array();
        
        foreach($groups as $sellerId=>&$group){
            foreach($group as $freightTid=>&$info){
                $trade  = $info['trade'];
                $orders = $info['orders'];
                
                $trade['tid']        = $idwork->nextId();
                $trade['created']    = $today;
                $trade['pay_type']   = 'wxpay';
                $trade['buyer_ip']   = $location['ip'];
                $trade['buyer_area'] = $location['country'];
                
                // 计入支付
                $pay['trades'][] = $trade['tid'];
                if($trade['payment'] > 0){
                    if(is_null($pay['order_no'])){
                        $pay['order_no'] = $parameters['tradeCount'] == 1 ? $trade['tid'] : $idwork->nextId();
                        $spec = get_spec_name($orders[0]['sku_json']);
                        $body = $orders[0]['title'].($spec == '' ? '' : ' '.$spec);
                        $pay['body'] = mb_substr($body,  0, 32, 'utf8');
                    }
                    
                    $trade['merge_pay'] = $pay['order_no'];
                    $pay['total_fee'] = bcadd($pay['total_fee'], $trade['payment'], 2);
                }else{
                    $trade['status']    = 'tosend';
                    $trade['pay_type']  = 'balance';
                    $trade['pay_time']  = $today;
                    $trade['merge_pay'] = $trade['tid'];
                }

                $sendedCount = 0;
                foreach($orders as $i=>$order){
                    $order['tid']    = $trade['tid'];
                    $order['oid']    = $i == 0 ? $trade['tid'] : $idwork->nextId();
                    
                    if(is_array($order['sku_json'])){
                        $order['sku_json'] = json_encode($order['sku_json'], JSON_UNESCAPED_UNICODE);
                    }
                    
                    $order['discount_details'] = empty($order['discount_details']) ? '' : json_encode($order['discount_details'], JSON_UNESCAPED_UNICODE);
                    if($trade['status'] == 'tosend' && $order['shipping_type'] == 'virtual'){
                        $trade['status'] = 'send';
                        $order['send_time'] = $today;
                        $sendedCount++;
                    }else{
                        $order['status'] = $trade['status'];
                    }
                    
                    // 支付信息
                    if($trade['payment'] > 0){
                        $spec = get_spec_name($order['sku_json']);
                        $pay['detail']['goods_detail'][] = array(
                            'goods_id'       => $order['goods_id'],
                            'wxpay_goods_id' => $order['product_id'],
                            'goods_name'     => $order['title'].(empty($spec) ? '' : ' '.$spec),
                            'goods_num'      => $order['num'],
                            'price'          => $order['price'] * 100
                        );
                    }
                    $orderList[] = $order;
                    
                    // 订单差价收益(准备插入数据库)
                    if(isset($differenceList[$order['product_id']])){
                        foreach($differenceList[$order['product_id']] as $mid=>$item){
                            $differenceList[$order['product_id']][$mid]['tid'] = $trade['tid'];
                            $differenceList[$order['product_id']][$mid]['oid'] = $order['oid'];
                        }
                    }
                    
                    // 1688订单(准备插入数据库)
                    if(isset($info['taoList'][$order['product_id']])){
                        $info['taoList'][$order['product_id']]['tid'] = $trade['tid'];
                        $taoTradeList[] = $info['taoList'][$order['product_id']];
                    }
                }
                
                if($sendedCount == $trade['kind']){
                    $trade['status'] = 'send';
                }
                
                $trade['discount_details'] = !empty($trade['discount_details']) ? json_encode($trade['discount_details'], JSON_UNESCAPED_UNICODE) : '';
                $tradeList[] = $trade;
            }
        }
        
        // 判断是否需要使用支付功能
        if($pay['total_fee'] > 0){
            $pay['time_start']  = date('YmdHis');
            $pay['time_expire'] = date('YmdHis', NOW_TIME + C('ORDER_TIME_OUT'));
            $pay['detail'] = json_encode($pay['detail'], JSON_UNESCAPED_UNICODE);
            
            $payResult = $this->createWxPayOrder($pay);
            if(empty($payResult) || $payResult['result_code'] == 'FAIL' || $payResult['return_code'] == 'FAIL'){
                $this->error = '创建支付失败：'.$payResult['return_msg'];
                return;
            }
            $pay['parameters'] = $payResult;
        }
        
        // 插入数据
        $this->startTrans();
        $this->addAll($tradeList);
        M('mall_order')->addAll($orderList);
        if(count($differenceList) > 0){
            $sql = "INSERT INTO mall_trade_difference(oid, tid, product_id, mid, buyer_id, diff_price, num, total_fee, checkout) VALUES";
            foreach($differenceList as $productId=>$items){
                foreach ($items as $mid=>$item){
                    $sql .= "('{$item['oid']}', '{$item['tid']}', '{$productId}', '{$mid}', '{$item['buyer_id']}', '{$item['diff_price']}', '{$item['num']}', '{$item['total_fee']}', 0),";
                }
            }
            $sql = rtrim($sql, ',');
            $this->execute($sql);
        }
        
        if(!empty($taoTradeList)){
            $taoTradeList = array_values($taoTradeList);
            M('alibaba_trade')->addAll($taoTradeList);
        }
        
        // 后续处理
        $BalanceModel = D('Balance');
        foreach($tradeList as $trade){
            // 扣除用户积分
            if($trade['paid_balance'] > 0 || $trade['paid_no_balance'] > 0){
                $BalanceModel->add(array(
                    'mid'       => $trade['buyer_id'],
                    'reason'    => '订单支付-'.$trade['tid'],
                    'balance'   => -$trade['paid_balance'],
                    'no_balance'=> -$trade['paid_no_balance'],
                    'link'      => '/h5/order/detail?tid='.$trade['tid'],
                    'type'      => 'order'
                ));
            }
            
            // 已支付
            if($trade['status'] != 'topay'){
                $this->diffProfitTransferred($trade['tid']);
            }
        }
        
        // 优惠券核销
        if(count($couponList['vid'])){
            $this->execute("UPDATE member_coupon SET `status`=1, used_time=".NOW_TIME." WHERE id IN (".implode(',', $couponList['vid']).")");
        }
        
        //减少库存、增加销售量
        foreach($orderList as $order){
           if($order['status'] != 'topay'){
               $this->execute("UPDATE mall_goods SET stock=stock-{$order['num']}, sold_num=sold_num+{$order['num']} WHERE id={$order['goods_id']}");
               $this->execute("UPDATE mall_product SET stock=stock-{$order['num']}, sold_num=sold_num+{$order['num']} WHERE id={$order['product_id']}");
           }

           // 从购物车删除
           if($from == 'shopping_cart'){
               $this->execute("UPDATE mall_cart SET num=num-{$order['num']} WHERE buyer_id={$buyer['id']} AND product_id={$order['product_id']}");
           }
        }
        $this->commit();

        $this->startTrans();
        // 清空购物车
        if($from == 'shopping_cart'){
            $this->execute("DELETE FROM mall_cart WHERE buyer_id={$buyer['id']} AND num<=0");
        }
        
        // 累计优惠信息
        if(count($couponList['id']) > 0){
            $couponList['id'] = array_count_values($couponList['id']);
            foreach($couponList['id'] as $couponId=>$couponNum){
                $this->execute("UPDATE mall_coupon SET used=used+{$couponNum} WHERE id =".$couponId);
            }
        }
        $this->commit();
        
        return $pay;
    }
    
    /**
     * 获取优惠信息
     */
    private function getDiscountInfo($buyer, $products, $postGroups){
        // 整理优惠信息
        $buyList = array();
        $couponList = array('id' => array(), 'vid' => array());
        foreach ($products as $product){
            if($product['single']){ // 不参与任何优惠
                continue;
            }
            
            $sellerId = $product['seller_id'];
            $post = $postGroups[$sellerId][$product['freight_tid']]['products'][$product['product_id']];
            if(empty($post)){
                $this->error = '产品信息已变更，请重新下单'.__LINE__;
                return;
            }else if(empty($post['discount_details'])){
                continue;
            }
            
            if(!isset($buyList[$sellerId])){
                $buyList[$sellerId] = array();
            }
            $buyList[$sellerId][$product['goods_id']] = array(
                'cat' => $product['cat_id'],
                'tag' => explode(',', $product['tag_id'])
            );
            
            foreach ($post['discount_details'] as $discount){
                if(!in_array($discount['id'], $couponList['id'])){
                    $couponList['id'][] = $discount['id'];
                }
                
                if(is_numeric($discount['vid'])){
                    if(!in_array($discount['vid'], $couponList['vid'])){
                        $couponList['vid'][] = $discount['vid'];
                    }
                }
            }
        }
        
        if(empty($couponList['id'])){
            return;
        }

        $couponList['id'] = implode(',', $couponList['id']);
        $couponList['vid'] = implode(',', $couponList['vid']);
        
        $coupon = new CouponModel();
        $list = $coupon->beforePaying($buyer, $buyList, $couponList);
        if(empty($list)){
            $this->error = '优惠活动已失效，请重新下单';
            return;
        }
        
        return $list;
    }
    
    /**
     * 支付步骤调用
     * @param unknown $trade
     */
    public function getToPay(&$trade){
        $data = array('has_error' => 0, 'trade' => $trade);
        
        if($trade['status'] != 'topay'){
            $data['has_error'] = 1;
            
            if($trade['end_type'] == 'time_out'){
                $data['trade']['status_str'] = '支付超时，请重新下单';
            }
            return $data;
        }
        
        // 提交的产品信息
        $productIds = '';
        $orders = array();
        foreach($trade['orders'] as $item){
            $productIds .= $item['product_id'].',';
            $orders[$item['product_id']] = $item;
        }
        $productIds = rtrim($productIds, ',');
        
        $sql = "SELECT 
                    product.id , product.goods_id, goods.buy_quota, goods.day_quota, goods.every_quota, product.stock, goods.is_del, goods.sold_time, goods.is_display, goods.visitors_quota
                FROM
                    mall_product AS product
                INNER JOIN mall_goods AS goods ON goods.id = product.goods_id
                WHERE product.id IN({$productIds})";
        // 提交的产品信息列表
        $products = $this->query($sql);
        
        // 限购
        $quotaList = array();
        $isQuota = 0;
        
        // 买家信息
        $buyer = $this->getTradingBuyer(array('id' => $trade['buyer_id'], 'openid' => $trade['buyer_openid']));

        // 校验数据
        $list = $orders;
        foreach($products as $product){
            $product['buy_num'] = $list[$product['id']]['num'];
            $validate = $this->validOrder($buyer, $product, $quotaList);
            if($validate['error_code'] != 0){
                $data['has_error'] = 1;
                $list[$product['id']]['error_msg'] = $validate['error_msg'];
            }
            unset($orders[$product['id']]);
        }
        
        foreach ($orders as $productId=>$item){
            $data['has_error'] = 1;
            $list[$productId]['error_msg'] = '商品无效';
        }
        
        $trade['orders'] = $list;
        $data['trade'] = $trade;
        return $data;
    }
    
    public function wxpay($trade, $login){
        $wxpay  = array('body' => '', 'detail' => array('goods_detail' => array()));
        
        foreach($trade['orders'] as $index=>$order){
            if($wxpay['body'] == ''){
                $body = $order['title'].(empty($order['spec']) ? '' : ' '.$order['spec']);
                $wxpay['body'] = mb_substr($body,  0, 32, 'utf8');
            }
            $wxpay['detail']['goods_detail'][] = array(
                            'goods_id'       => $order['goods_id'],
                            'wxpay_goods_id' => $order['product_id'],
                            'goods_name'     => $order['title'].(empty($order['spec']) ? '' : ' '.$order['spec']),
                            'goods_num'      => $order['num'],
                            'price'          => $order['price'] * 100
                        );
        }
        $wxpay['detail'] = json_encode($wxpay['detail'], JSON_UNESCAPED_UNICODE);
        
        if($trade['merge_pay'] != $trade['tid']){
            $this->execute("UPDATE mall_trade SET merge_pay=tid WHERE tid='{$trade['tid']}'");
        }
        $now = strtotime($trade['created']);
        $wxpay['order_no']  = $trade['tid'];
        $wxpay['openid'] = $login['openid'];
        $wxpay['attach'] = $trade['buyer_id'];
        $wxpay['time_start'] = date('YmdHis', $now);
        $wxpay['time_expire'] = date('YmdHis', $now + C('ORDER_TIME_OUT'));
        $wxpay['detail'] = $wxpay['detail'];
        $wxpay['total_fee'] = $trade['payment'];
        
        $result = $this->createWxPayOrder($wxpay);

        $wxpay['trades'][]  = $trade['tid'];
        return $result;
    }  
    
    /**
     * 更改为已支付
     */
    public function pay($trade){
        $this->startTrans();
        $tid = $trade['tid'];
        $now = date('Y-m-d H:i:s');
        $orders = $trade['orders'];
        $data = array(
            'status' => 'tosend',
            'buyer_subscribe' => $trade['buyer_subscribe'],
            'pay_type' => $trade['pay_type'],
            'bank_type' => $trade['bank_type'],
            'pay_time' => empty($trade['pay_time']) ? $now : $trade['pay_time'],
            'paid_fee' => $trade['paid_fee'],
            'trade_no' => $trade['trade_no'],
            'modified' => $now
        );
        $sended = 0;
        
        // 获取订单产品信息
        foreach($orders as $product){
            if($product['shipping_type'] == 'virtual'){
                $this->execute("UPDATE mall_order SET `status`='send', send_time='{$now}' WHERE oid='".$product['oid']."'");
                $sended++;
            }else{
                $this->execute("UPDATE mall_order SET `status`='tosend' WHERE oid='".$product['oid']."'");
            }
            
            //减少库存、增加销售量
            $this->execute("UPDATE mall_goods SET stock=stock-{$product['num']}, sold_num=sold_num+{$product['num']} WHERE id={$product['goods_id']}");
            $this->execute("UPDATE mall_product SET stock=stock-{$product['num']}, sold_num=sold_num+{$product['num']} WHERE id={$product['product_id']}");
        }
        
        if($sended > 0){
            $data['consign_time'] = $now;
        
            if($sended == count($orders)){
                $data['status'] = 'send';
            } 
        }
        
        $this->where("tid='{$tid}'")->save($data);
        
        // 计算推荐人收益
        $this->diffProfitTransferred($trade['tid']);
        $this->commit();
        return 1;
    }
    
    /**
     * 支付异步通知处理
     * @param unknown $data
     * @return string|boolean
     */
    public function payNotify($data){
        set_time_limit(0);
        // 更新用户关注状态
        if($data['trade_type'] != 'APP'){
            $subscrib = $data['is_subscribe'] == 'Y' ? 1 : 0;
            $this->execute("UPDATE wx_user SET subscribe={$subscrib} WHERE openid='{$data['openid']}'");
        }
        
        // 处理订单
        $merge_pay = $data['out_trade_no'];
        $list = $this->where("merge_pay='{$merge_pay}'")->select();
        
        if(empty($list)){
            return '订单不存在';
        }
        
        foreach($list as $trade){
            if($trade['status'] != 'topay'){
                //return '订单状态：'.$trade['status'];
                continue;
            }
            
            // 获取订单产品信息
            $trade['orders'] = $this->query("SELECT * FROM mall_order WHERE tid='%s'", array($trade['tid']));
            $trade['buyer_subscribe'] = $subscrib;
            $trade['pay_type'] = 'wxpay';
            $trade['pay_time'] = $data['time_end'];
            //$trade['paid_fee'] += $trade['payment'];
            $trade['paid_fee'] += count($list) == 1 ? $data['cash_fee']/100 : $trade['payment'];
            
            $this->pay($trade);
        }
        return true;
    }
    
    /**
     * 差价到账
     * @param unknown $trade
     */
    private function diffProfitTransferred($tid){
        $list = $this->query("SELECT
                                p.mid, SUM(p.total_fee) as total_fee
                              FROM mall_trade_difference AS p
                              INNER JOIN member AS m ON p.mid=m.id
                              WHERE p.tid='{$tid}' AND p.checkout=0
                              GROUP BY mid");

        if(empty($list)){
            return;
        }

        $this->execute("UPDATE mall_trade_difference SET checkout=1 WHERE tid='{$tid}'");
        foreach ($list as $profit){
            if($profit['total_fee'] <= 0){
                continue;
            }
            
            //添加资金流水记录
            D('Balance')->add(array(
                'mid'       => $profit['mid'],
                'reason'    => '订单收益-'.$tid,
                'balance'   => $profit['total_fee'],
                'type'      => 'diff_profit'
            ));
        }
    }
}

?>