<?php 
namespace Common\Model;

class PayConfirmModel extends OrderModel{
    /**
     * 下单解析
     * @param unknown $post
     * @param unknown $buyer
     */
    public function confirm($postData, $login){
        $post = $postData['products'];
        if(!is_array($post) || count($post) == 0){
            $this->error ='下单产品不能为空';
            return null;
        }
        
        // 提交的产品信息
        $postProducts = array();
        foreach($post as $item){
            if(!is_numeric($item['id'])){
                $this->error = "产品ID为空，请刷新页面后重试";
                return null;
            }
            
            $postProducts[$item['id']] = $item['num'];
        }
        
        // 获取买家信息
        $buyer = $this->getTradingBuyer($login);
        if(empty($buyer)){
            $this->error = '登陆账号异常，请重新关注后再试！';
            return null;
        }
         
        // 提交的产品信息列表
        $products = $this->getTradingProducts(implode(',', array_keys($postProducts)), $buyer);
        if(count($products) != count($postProducts)){
            $this->error = '某产品不存在，请刷新页面后重试';
            return null;
        }
        
        // 本次交易订单，分组规则：店铺+运费模板相同则为同一个订单
        $groups = array();
        // 限购列表
        $quotaList = array();
        $hasError = 0;  // 是否有异常
        $autoChange = 0;    // 1已自动调整，0没有自动调整
        
        /*********************  读取优惠信息 - 开始  ***************************/
        $promotionList = array('discount' => array(), 'promotion' => array(), 'coupon' => array());
        $sellerGoods = array();
        foreach($products as $product){
            if($product['single']){ // 不再参与其他任何优惠
                continue;
            }
            
            if(!isset($sellerGoods[$product['seller_id']])){
                $sellerGoods[$product['seller_id']] = array();
            }
            $sellerGoods[$product['seller_id']][$product['goods_id']] = array('cat' => $product['cat_id'], 'tag' => explode(',', $product['tag_id']));
        }
        if(count($sellerGoods) > 0){
            $couponModel = new CouponModel();
            $_data = $couponModel->beforePaying($buyer, $sellerGoods);
            foreach($_data as $type=>$_list){
                $promotionList[$type] = array_values($_list);
            }
        }
        /*********************  读取优惠信息 - 结束  ***************************/

        foreach($products as $product){
            // 本次购买数量
            $buyNum = $postProducts[$product['product_id']];
            $product['buy_num'] = $buyNum;
            
            // 交易
            $group = null;
            if(!isset($groups[$product['seller_id']])){
                $group = array(
                    'buyer_agent_level'=> $buyer['agent_level'],
                    'buyer_id'         => $buyer['id'],
                    'buyer_nick'       => $buyer['nickname'],
                    'buyer_type'       => 1,
                    'buyer_openid'     => $buyer['openid'],
                    'buyer_subscribe'  => $buyer['subscribe'],
                    'buyer_remark'     => '',
                    'seller_id'        => $product['seller_id'],   // 发货地
                    'seller_nick'      => $product['seller_nick'],   // 发货地
                    'type'             => 'normal'
                );
            }else{
                $group = $groups[$product['seller_id']];
            }

            // 交易运费模板
            $trade = null;
            if(!isset($group['trades'][$product['freight_tid']])){
                $trade = array(
                    'kind'              => 0, // 购买了几种商品
                    'total_num'         => 0, // 商品数量的和
                    'total_fee'         => 0, // 商品数量*商品售价的和
                    'total_weight'      => 0, // 商品数量*商品重量的和
                    'post_fee'          => 0, // 商品数量*商品邮费的和
                    'paid_balance'      => 0, // 使用n元可提现金额
                    'paid_no_balance'   => 0, // 使用n元不可提现金额
                    'paid_fee'          => 0, // 已付金额
                    'discount_fee'      => 0, // 优惠总额
                    'payment'           => 0, // 需付金额
                    'shipping_type'     => 'express', // 物流方式
                    'orders'            => array(), // 产品信息
                    'discount_details'  => array(), // 优惠活动促销信息
                    'attach_postage'    => 0    // 附加邮费
                );
            }else{
                $trade = $group['trades'][$product['freight_tid']];
            }

            // 优先使用限时折扣
            foreach($promotionList['discount'] as $discount){
                if(array_key_exists($product['goods_id'], $discount['goods'])){
                    if($discount['quota'] > 0){ // 限购
                        $product['buy_quota'] = $discount['quota'];
                    }
                    break;
                }
            }
            
            // 校验数据
            $validate = $this->validOrder($buyer, $product, $quotaList, true);
            if($validate['error_code'] != 0){
                $hasError = 1;
            }else if($product['buy_num'] != $buyNum){
                $autoChange = 1;
            }
            
            // 代理价
            $product['price']  = $this->getAgentPrice($buyer['agent_level'], $product);
            $order = array(
                'error_msg'    => $validate['error_msg'],
                'product_id'   => $product['product_id'],
                'goods_id'     => $product['goods_id'],
                'title'        => $product['title'],
                'spec'         => get_spec_name($product['sku_json']),
                'pic_url'      => empty($product['pic_url']) ? $product['goods_pic_url'] : $product['pic_url'],
                'pay_type'     => $product['pay_type'],
                'original_price'=>$product['original_price'],
                'price'        => $product['price'],
                'discount_price'=> $product['price'],
                'weight'       => $product['weight'],
                'total_fee'    => $product['price'] * $product['buy_num'],
                'num'          => $product['buy_num'],
                'discount_fee' => 0,
                'payment'      => $product['price'] * $product['buy_num'],
                'postage'      => 0,    // 包邮
                'sku_json'     => $product['sku_json'],
                'outer_id'     => $product['outer_id'],
                'shipping_type'=> $product['is_virtual'] == 1 ? 'virtual' : 'express',
                'score'        => $product['score'],
                'freight_tid'  => $product['freight_tid'],
                'attach_postage'=> $product['attach_postage']
            );
            
            $totalWeight = $order['weight'] * $order['num'];
            $trade['orders'][] = $order;
            $trade['kind']++;
            $trade['total_num']     += $order['num'];
            $trade['total_fee']     += $order['total_fee'];
            $trade['discount_fee']  += $order['discount_fee'];
            $trade['payment']       += $order['payment'];
            $trade['total_weight']  += $totalWeight;
            
            $group['trades'][$product['freight_tid']] = $trade;
            $groups[$product['seller_id']] = $group;
        }
        
        $errorMsg = '';
        if($hasError){
            $errorMsg = '抱歉，部分商品不符合下单条件。';
        }else if($autoChange){
            $errorMsg = '部分商品不符合下单条件，已自动调整。您也可以重新下单！';
        }

        // 对交易进行排序以便和js顺序一致
        ksort($groups);
        foreach ($groups as &$data){
            ksort($data['trades']);
        }

        // 计算优惠
        return array(
            'has_error'   => $hasError,
            'error_msg'   => $errorMsg,
            'buyer'       => $buyer,
            'from'        => $postData['from'],
            'groups'      => $groups,
            'promotionList'     => $promotionList,
            'address'     => array(
                'receiver_name' => '',
                'receiver_mobile' => '',
                'receiver_province' => '',
                'receiver_city' => '',
                'receiver_county' => '',
                'receiver_detail' => '',
                'receiver_zip' => ''
            )
        );
    }
}

?>