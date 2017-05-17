<?php 
namespace Common\Model;

class ExpressModel extends BaseModel{
    protected $tableName = 'shop_freight_template';
    
    public function getAllExpress(){
        return include COMMON_PATH.'Conf/express.php';
    }
    
    /**
     * 计算运费
     */
    public function getFreightFee($products, $address, $returnAll = false){
        $pids = array_keys($products);
        $pids = implode(',', $pids);
    
        $sql = "SELECT goods.shop_id, goods.id AS goods_id, product.id AS product_id,goods.tao_id,
                    goods.freight_tid, product.weight, product.sku_json, goods.remote_area
                FROM mall_product AS product
                INNER JOIN mall_goods AS goods ON goods.id=product.goods_id
                WHERE product.id IN ({$pids})";
        $productsInfo = $this->query($sql);
    
        $tao = array('tao_ids' => '', 'seller_spec' => array(), 'seller_freight' => array(), 'address' => $address);
        $localList = array();   // 本系统订单
        $taoBuyNum = array(); // 淘系模板运费
        // 读取淘系商品信息
        foreach($productsInfo as $i=>$item){
            if(empty($products[$item['product_id']]['freight_tid'])){
                E('运费模板不能为空');
            }
            $productsInfo[$i]['freight_tid'] = $products[$item['product_id']]['freight_tid'];
            $productsInfo[$i]['attach_postage'] = $products[$item['product_id']]['attach_postage'];
            if(empty($item['tao_id'])){
               continue; 
            }
            
            if(!in_array($item['tao_id'], $tao['tao_ids'])){
                $tao['tao_ids'][] = $item['tao_id'];
            }
            
            $taoKey = $item['tao_id'];
            if($item['sku_json'] != '' && $item['sku_json'] != '[]'){
                $skuArray = json_decode($item['sku_json'], true);
                $temp = array();
                foreach($skuArray as $sku){
                    $temp[] = $sku['k'];
                    $temp[] = $sku['v'];
                }
                sort($temp);
                $taoKey .= implode('', $temp);
                $taoKey = md5($taoKey);
            }
            
            $taoBuyNum[$item['shop_id']][$taoKey][$item['product_id']] = $products[$item['product_id']]['num'];
        }

        // 按照1688订单计算运费
        $taoTemplate = array();
        if(!empty($tao['tao_ids'])){
            $ids = implode(',', $tao['tao_ids']);
            $list = $this->query("SELECT tao_id, products, freight_tid, seller_nick, sku_json,price FROM alibaba_goods WHERE tao_id IN ({$ids})");
            
            foreach($list as $goods){
                if($goods['products'] != '' && $goods['products'] != '[]'){
                    $skuInfos = json_decode($goods['products'], true);
                    foreach($skuInfos as $product){
                        $temp = array();
                        foreach($product['sku_json'] as $sku){
                            $temp[] = $sku['k'];
                            $temp[] = $sku['v'];
                        }
                        sort($temp);
                        $taoKey = $goods['tao_id'].implode('', $temp);
                        $taoKey = md5($taoKey);
                        
                        foreach($taoBuyNum as $sellerId=>$template){
                            foreach($template[$taoKey] as $productId=>$buyNum){
                                $taoTemplate[$sellerId][$goods['freight_tid']][$productId] = array(
                                    'skuId'       => $product['sku_id'],
                                    'specId'      => $product['spec_id'],
                                    'quantity'    => $buyNum,
                                    'unitPrice'   => $product['price'],
                                    'freightId'   => $goods['freight_tid'],
                                    'offerId'     => $goods['tao_id'],

                                    'seller_id'   => $sellerId,
                                    'seller_nick' => $goods['seller_nick'],
                                    'unit'        => $goods['unit'],
                                );
                            }
                        }
                    }
                }else{
                    $taoKey = $goods['tao_id'];
                    foreach($taoBuyNum as $sellerId=>$template){
                        foreach($template[$taoKey] as $productId=>$buyNum){
                            $taoTemplate[$sellerId][$goods['freight_tid']][$productId] = array(
                                'skuId'       => '',
                                'specId'      => '',
                                'quantity'    => $buyNum,
                                'unitPrice'   => $goods['price'],
                                'freightId'   => $goods['freight_tid'],
                                'offerId'     => $goods['tao_id'],

                                'seller_id'   => $sellerId,
                                'seller_nick' => $goods['seller_nick'],
                                'unit'        => $goods['unit'],
                            );
                        }
                    }
                }
            }
        }

        // 按照本系统计算1688运费
        $myTaoTemplate = array();
        $alibabaList = array();
        foreach($productsInfo as $item){
            $buyNum = $products[$item['product_id']]['num'];
            $isTao = !empty($item['tao_id']);
            $isTaoTpl = false;

            $taoKey = $item['tao_id'];
            if($isTao){
                $isTaoTpl = stripos($item['freight_tid'], 'T') === 0;
                if($item['sku_json'] != '' && $item['sku_json'] != '[]'){
                    $skuArray = json_decode($item['sku_json'], true);
                    $temp = array();
                    foreach($skuArray as $sku){
                        $temp[] = $sku['k'];
                        $temp[] = $sku['v'];
                    }
                    sort($temp);
                    $taoKey .= implode('', $temp);
                    $taoKey = md5($taoKey);
                }
                
                $sellerId = $item['shop_id'];
                foreach($taoTemplate[$sellerId] as $freightTid=>$productArray){
                    foreach($productArray as $productId=>$product){
                        if($productId == $item['product_id']){
                            $alibabaList[$sellerId][$product['seller_nick']][$item['freight_tid']][$productId] = $product;
                            break;
                        }
                    }
                }
            }
            
            if($isTaoTpl){   // 走淘系模板
                $freightTid = substr($item['freight_tid'], 1);
                $data = $taoTemplate[$item['shop_id']][$freightTid][$item['product_id']];
                if($products[$item['product_id']]['postage']){ // 如果是包邮
                    $data['postage'] = 1;
                }
                $myTaoTemplate[$item['shop_id']][$freightTid][$item['product_id']] = $data;
            }else{ // 走系统模板
                $template = isset($localList[$item['shop_id']][$item['freight_tid']])
                            ? $localList[$item['shop_id']][$item['freight_tid']]
                            : array(
                                'freight_tid' => $item['freight_tid'],
                                'num'         => 0,
                                'weight'      => 0,
                                'size'        => 0,
                                'money'       => 0,
                                'remote'      => false,
                                'total_weight'=> 0,
                                'total_num'   => 0,
                                'attach_postage' => 0,
                                'tao'         => array()
                            );

                $weight = $buyNum * $item['weight'];
                $template['total_weight'] += $weight;
                $template['total_num']    += $buyNum;
                $template['attach_postage'] += $item['attach_postage']*$buyNum;
                
                if(!$products[$item['product_id']]['postage']){ // 如果包邮
                    $template['weight'] += $weight;
                    $template['num']    += $buyNum;
                }
                
                
                if(!empty($item['remote_area'])){
                    if(preg_match("/({$address['province_code']})/", $item['remote_area'])){
                        $template['remote'] = true;
                    }
                }
                $localList[$item['shop_id']][$item['freight_tid']] = $template;
            }
        }
        
        // 计算本系统产品运费
        $expressList = $this->getSystemGoodsFreight($localList, $address);
    
        // 计算淘系运费
        if(!empty($myTaoTemplate)){
            if(empty($tao['address'])){ // 此地区不支持配送
                foreach ($myTaoTemplate as $sellerId=>$freight){
                    foreach($freight as $freightTid=>$specs){
                        $expressList[$sellerId]['T'.$freightTid] = array(array('id' => 10, 'name' => '商家自配', 'money' => 0, 'has_error' => 1, 'error_msg' => '暂不支持配送，请联系客服'));
                    }
                }
            }else{
                $taoResult = $this->getTaoGoodsFreight($myTaoTemplate, $tao['address']);
                foreach ($taoResult as $sellerId=>$array){
                    foreach($array as $freightTid=>$list){
                        $expressList[$sellerId][$freightTid] = $list;
                    }
                }
            }
        }
    
        if($returnAll){
            $taoResult = $this->getAliTradeList($alibabaList, $tao['address']);
            return array('system_list' => $expressList, 'tao_list' => $taoResult);
        }
        return $expressList;
    }
    
    /**
     * 获取淘宝省市区地址
    private function getTaoAddress($address){
        $result = array(
            'receiver_name'   => $address['receiver_name'],
            'receiver_mobile' => $address['receiver_mobile'],
            'province_name' => '',
            'city_name'     => '',
            'county_name'   => '',
            'province_code' => '',
            'city_code'     => '',
            'county_code'   => '',
            'detail'        => $address['detail'],
            'zip'           => $address['zip'],
            'customer'      => 0
        );
    
        $sql = "SELECT * FROM alibaba_city
                WHERE id IN ({$address['province_code']}, {$address['city_code']}, {$address['county_code']})
                UNION
                (SELECT * FROM alibaba_city WHERE pcode={$address['city_code']} ORDER BY id LIMIT 1)";
        $list = $this->query($sql);
    
        if(count($list) < 3){
            return array();
        }
    
        // 第三级对不上取第三级的第一个
        foreach($list as $item){
            if($item['id'] == $address['province_code']){
                $result['province_name'] = $item['name'];
                $result['province_code'] = $item['id'];
            }else if($item['id'] == $address['city_code']){
                $result['city_name'] = $item['name'];
                $result['city_code'] = $item['id'];
            }else if($item['id'] == $address['county_code']){
                $result['county_name'] = $item['name'];
                $result['county_code'] = $item['id'];
                $result['customer'] = 0;
            }else if($address['county_code'] == ''){
                $result['county_name'] = $item['name'];
                $result['county_code'] = $item['id'];
                $result['customer'] = 1;
            }
        }
    
        if($address['customer'] == 1){
            $result['detail'] = $address['county_name'].$result['detail'];
        }
    
        return $address;
    }
    */
    
    /**
     * 根据本系统的运费模板计算运费
     */
    private function getSystemGoodsFreight($list, $address){
        if(empty($list)){
            return array();
        }
        
        $allExpress = $this->getAllExpress();
        
        // 查找运费模板
        $ids = array();
        foreach($list as $sellerId=>$items){
            foreach ($items as $item){
                $ids[] = $item['freight_tid'];
            }
        }
        
        $tplItems = array();
        $tplList = $this->query("SELECT * FROM shop_freight_template WHERE id IN(".implode(',', $ids).")");
        if(count($ids) != count($tplList)){
            E('运费模板不存在');
        }
        
        foreach ($tplList as $tpl){
            $templates = json_decode($tpl['templates'], true);
    
            foreach($templates as $i=>$template){
                // 判断所选地区是否特殊地区
                $detail = $template['default'];
                foreach($template['specials'] as $item){
                    if(in_array($address['receiver_province'], $item['areas'])){
                        $detail = $item;
                        break;
                    }
                }
    
                $detail['checked'] = $tpl['checked'];
                $detail['express'] = $template['express'];
                $detail['type'] = $tpl['type'];
                $tplItems[$tpl['id']][] = $detail;
            }
        }
        
        // 组合运费结果
        $data = array();
        foreach($list as $sellerId=>$children){
            foreach($children as $freightTid=>$item){
                if($item['remote']){
                    $data[$sellerId][$freightTid][] = array(
                        'id'        => '',
                        'name'      => '',
                        'money'     => 0,
                        'checked'   => 1,
                        'has_error' => 1,
                        'error_msg' => '此地区不支持配送'
                    );
                    continue;
                }
        
                foreach($tplItems[$freightTid] as $template){
                    $money = $totalMoney = 0;
        
                    $key1 = $template['type'] == 0 ? 'weight' : 'num';
                    $money = $template['postage'];
                    if($item[$key1] > $template['start']){
                        $weight = bcsub($item[$key1], $template['start'], 2);
                        $money = bcadd($money, $template['postage_plus'] * ceil($weight/$template['plus']), 2);
                    }
        
                    $key2 = $template['type'] == 0 ? 'total_weight' : 'total_num';
                    $totalMoney = $template['postage'];
                    if($item[$key2] > $template['start']){
                        $weight = bcsub($item[$key2], $template['start'], 2);
                        $totalMoney = bcadd($totalMoney, $template['postage_plus'] * ceil($weight/$template['plus']), 2);
                    }
        
                    $money = $money + $item['attach_postage'];
                    $totalMoney = $totalMoney + $item['attach_postage'];
                    foreach($template['express'] as $expressId){
                        $data[$sellerId][$freightTid][] = array(
                            'id'      => $expressId,
                            'name'    => $allExpress[$expressId]['name'],
                            'money'   => $template['checked'] == $expressId ? $money : $totalMoney,
                            'checked' => $template['checked'] == $expressId ? 1 : 0
                        );
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 计算淘系商品运费
     */
    private function getTaoGoodsFreight($myTaoTemplate, $address){
        $expressList = array();
        
        // 向淘请求，计算运费
        $aop = new \Common\Model\AopModel();
        foreach($myTaoTemplate as $sellerId=>$array){
            foreach ($array as $freightTid=>$products){
                $models = array();
                foreach($products as $productId=>$product){
                    if($product['postage']){    // 如果是包邮
                        continue;
                    }
                    
                    $model = array(
                        'quantity'    => $product['quantity'],
                        'unitPrice'   => $product['unitPrice'],
                        'freightId'   => $product['freightId'],
                        'offerId'     => $product['offerId']
                    );
                    
                    if(!empty($product['skuId'])){
                        $model['skuId']  = $product['skuId'];
                        $model['specId'] = $product['specId'];
                    }
                    $models[] = $model;
                }
                

                $express = array('id' => 10, 'name' => '商家自配', 'money' => 0, 'has_error' => 0, 'error_msg' => '');
                if(count($models) > 0){
                    $result = $aop->freightFee(array(
                        'cityCode' => $address['city_code'],
                        'districtCode' => $address['county_code'],
                        'orderInputModel' => $models
                    ));

                    if($result['success'] == 1){
                        $express['money'] = $result['model']['totalFreightFee'];
                    }else{
                        $express['has_error'] = 1;
                        $express['error_msg'] = $result['msgInfo'];
                    }
                }
                $expressList[$sellerId]['T'.$freightTid] = array($express);
            }
        }
        
        return $expressList;
    }
    
    private function getAliTradeList($myTaoTemplate, $address){
        if(empty($myTaoTemplate)){
            return array();
        }
        $alibabaList = array();
        
        $created = date('Y-m-d H:i:s');
        $aop = new \Common\Model\AopModel();
        foreach($myTaoTemplate as $sellerId=>$aliArray){
            foreach($aliArray as $sellerNick=>$array){
                foreach ($array as $freightTid=>$products){
                    $trade = array(
                        'has_error'       => 0,
                        'error_msg'       => '',
                        'status'          => 'toorder',
                        'seller_nick'     => '',
                        'post_json'       => null,
                        'products'        => null,
                        'created'         => $created,
                        'buyer_login_id'  => $aop->loginId
                    );
                
                    $postJson = array(
                        'supplierLoginId'   => '',
                        'senderInfo'        => '',
                        'totalFreightFee'   => 0,
                        'totalProductPrice' => 0,
                        'addressInfoModel'  => array(
                            'personalName'  => $address['receiver_name'],
                            'mobileNO'      => $address['receiver_mobile'],
                            'province'      => $address['receiver_province'],
                            'city'          => $address['receiver_city'],
                            'district'      => $address['receiver_county'],
                            'cityCode'      => $address['city_code'],
                            'districtCode'  => $address['county_code'],
                            'areaCode'      => $address['county_code'],
                            'addressDetail' => $address['receiver_detail']
                        ),
                        'offerViewItems'    => array()
                    );
                
                    $tradeProducts = array();
                    $lastProductId = 0;
                    $models = array();
                
                    foreach($products as $productId=>$product){
                        $model = array(
                            'quantity'    => $product['quantity'],
                            'unitPrice'   => $product['unitPrice'],
                            'freightId'   => $product['freightId'],
                            'offerId'     => $product['offerId']
                        );
                        
                        if(!empty($product['skuId'])){
                            $model['skuId']  = $product['skuId'];
                            $model['specId'] = $product['specId'];
                        }
                        $models[] = $model;
                
                        $postJson['supplierLoginId'] = $product['seller_nick'];
                        $postJson['totalProductPrice'] += $product['unitPrice'];
                
                        $offerViewItem = array(
                            'offerId'       => $product['offerId'],
                            'buyDetails'    => array(
                                'type'      => 'OFFER',
                                'price'     => $product['unitPrice'],
                                'amount'    => $product['quantity'],
                                'unit'      => $product['unit'],
                            )
                        );
                
                        if(!empty($product['specId'])){
                            $offerViewItem['buyDetails']['type'] = 'SKU';
                            $offerViewItem['buyDetails']['skuId'] = $product['skuId'];
                            $offerViewItem['buyDetails']['specId'] = $product['specId'];
                        }
                        $postJson['offerViewItems'][] = $offerViewItem;
                        $tradeProducts[$productId] = $product['offerId'];
                        $lastProductId = $productId;
                    }
                
                    // 向1688发送请求计算运费
                    $result = $aop->freightFee(array(
                        'cityCode' => $address['city_code'],
                        'districtCode' => $address['county_code'],
                        'orderInputModel' => $models
                    ));
                
                    // 处理运费结果
                    if($result['success'] == 1){
                        $postJson['totalFreightFee'] = $result['model']['totalFreightFee'];
                    }else{
                        $trade['has_error'] = 1;
                        $trade['error_msg'] = $result['msgInfo'];
                    }
                
                    $trade['seller_nick']  = $postJson['supplierLoginId'];
                    $trade['post_json']    = $postJson;
                    $trade['products']     = json_encode($tradeProducts);
                    $alibabaList[$lastProductId] = $trade;
                }
            }
        }
        
        return $alibabaList;
    }
    
    /*
    * 是否包邮（判断首重里有0的就算）
    */
    public function getRangeFee($id, $weight = 0){
        $result = array('min' => 0, 'max' => 0, 'msg' => '包邮', 'baoyou' => false);
        if($id == 'T1'){
            $result['baoyou'] = true;
            return $result;
        }else if(strpos($id, 'T') === 0){
            $result['msg'] = '未知';
            return $result;
        }
        
        $result['min'] = 999999999;
        $template = $this->find($id);
        $array = json_decode($template['templates'], true);
        foreach($array as $template){
            $data = $template['default'];
            $money = $data['postage'];
            if($template['type'] == 0 && $weight > $data['start']){
                $weight = bcsub($weight, $data['start'], 2);
                $money += $data['postage_plus'] * ceil($weight/$data['plus']);
            }
            
            if($money < $result['min']){$result['min'] = $money;}
            if($money > $result['max']){$result['max'] = $money;}
            
            if(count($template['specials']) > 0){
                foreach($template['specials'] as $data){
                    $money2 = $data['postage'];
                    if($template['type'] == 0 && $weight > $data['start']){
                        $weight = bcsub($weight, $data['start'], 2);
                        $money2 += $data['postage_plus'] * ceil($weight/$data['plus']);
                    }
                    
                    if($money2 < $result['min']){$result['min'] = $money2;}
                    if($money2 > $result['max']){$result['max'] = $money2;}
                }
            }
        }
        
        $result['min'] *= 1;
        $result['max'] *= 1;
        if($result['max'] == 0){
            $result['baoyou'] = true;
            $result['msg'] = '包邮';
        }else if($result['min'] < $result['max']){
            $result['msg'] = $result['min'].' - '.$result['max'].'元';
        }else{
            $result['msg'] = $result['max'].'元';
        }
        
        return $result;
    }
    
    /**
     * 获取某店铺的运费模板
     * @param unknown $shopId
     */
    public function getShopFreightTemplates($shopId, $fid = null){
        $where = "shop_id IN ({$shopId}, 0)".(is_numeric($fid) ? " OR id=".$fid : "");
        $list = $this->where($where)->select();
        
        // 快递公司
        $expressList = include COMMON_PATH.'Conf/express.php';
        $cityModel = new \Common\Model\CityModel();
        $types = array(array('重', '公斤'), array('件', '件'));
        foreach ($list as $i=>$item){
            $templates = json_decode($item['templates'], true);
            unset($list[$i]['templates']);
            $describe = '';
            foreach ($templates as $ti=>$template){
                $describe .= '<div'.($ti>0 ? ' style="border-top: 1px dashed #e5e5e5;"' : '').'>';
        
                $expressName = '';
                foreach ($template['express'] AS $expressId){
                    $expressName .= '、'.$expressList[$expressId]['name'];
                }
                
                $type = $types[$item['type']];
                $describe .= '<span class="">'.ltrim($expressName, '、').'</span>：';
                $data = $template['default'];
                $describe .= "默认首{$type[0]}{$data['start']}{$type[1]}以内{$data['postage']}元";
                $describe .= "，每续{$type[0]}{$data['plus']}{$type[1]}增加{$data['postage_plus']}元；";
        
                // 指定地区
                foreach($template['specials'] as $data){
                    $describe .= '<br>'.implode('、', $data['areas']).'：';
                    $describe .= "首{$type[0]}{$data['start']}{$type[1]}首费{$data['postage']}元";
                    $describe .= "，每续{$type[0]}{$data['plus']}{$type[1]}增加{$data['postage_plus']}元；";
                }
                
                $describe .= '</div>';
            }
        
            $list[$i]['describe'] = $describe;
        }
        return $list;
    }
}
?>