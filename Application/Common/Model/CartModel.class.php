<?php 
namespace Common\Model;

class CartModel extends BaseModel{
    protected $tableName = 'mall_cart';
    
    /**
     * 获取购物车中的产品
     * @param unknown $buyerId
     * @return multitype:
     */
    public function getAll(){
        $buyer = $this->getTradingBuyer();
        $where = array('cart.buyer_id' => $buyer['id']);
        
        $list = $this
                ->field("cart.id, product.id AS product_id, cart.num, goods.title, product.goods_id, goods.pic_url AS gpic_url, product.pic_url,
        		         product.stock, product.price, product.sku_json, goods.sold_time, goods.is_del,
        	             goods.original_price, goods.is_display, goods.pay_type, goods.buy_quota, goods.day_quota, goods.visitors_quota,
                         product.agent2_price,product.agent3_price, goods.tag_id,
                         shop.id AS seller_id, shop.name AS seller_nick")
                ->alias("cart")
                ->join("mall_product AS product ON product.id=cart.product_id")
                ->join("mall_goods AS goods ON goods.id=product.goods_id")
                ->join("shop ON shop.id=goods.shop_id")
                ->where($where)
                ->order("cart.id DESC")
                ->select();

        if(empty($list)){
            return array();
        }
        
        $shops = array();
        $products = array();

        $list = $this->goodsListHandler($list, $buyer);
        foreach($list as $index=>$item){
            $item['disabled'] = 0;
            $item['error'] = '';
             
            // 商品不存在则自动删除
            if(empty($item['id']) || empty($item['goods_id'])){
                $this->delete($item['cart_id']);
                continue;
            }
            
            if($item['is_del']){
                $item['error'] = '商品已被删除';
                $item['disabled'] = 1;
            }else if($buyer['agent_level'] == 0 && $item['visitors_quota'] !=1){
                $item['error'] = '非代理禁止购买';
                $item['disabled'] = 1;
            }else if($item['is_display'] == 0){
                $item['error'] = '商品已下架';
                $item['disabled'] = 1;
            }else if($item['sold_time'] > time()){
                $item['error'] = date('Y-m-d H:i:s', $item['sold_time']).'开售';
                $item['disabled'] = 1;
            }else if($item['stock'] < $item['num'] || $item['stock'] < 5){
                $item['error'] = '仅剩'.$item['stock'].'件';
                if($item['stock'] == 0){
                    $item['disabled'] = 1;
                }
            }
            
            if($item['quota'] <= 20){
                $item['error'] = '限购'.$item['quota'].'件';
            }
            
            if($item['buy_quota'] > 0){
                $item['error'] = '限购'.$item['buy_quota'].'件';
                if($item['buy_quota'] < $item['quota']){
                    $item['quota'] = $item['buy_quota'];
                }
            }
            
            if($item['every_quota'] > 0){
                $item['error'] = '日限买'.$item['every_quota'].'件';
                if($item['every_quota'] < $item['quota']){
                    $item['quota'] = $item['buy_quota'];
                }
            }
            
            if($item['day_quota'] > 0 && $item['day_quota'] < 10){
                $item['error'] = '日限卖'.$item['day_quota'].'件';
                if($item['day_quota'] < $item['quota']){
                    $item['quota'] = $item['day_quota'];
                }
            }
             
            if(!isset($shops[$item['seller_id']])){
                $shops[$item['seller_id']] = array(
                    'id' => $item['seller_id'],
                    'name' => $item['seller_nick'],
                    'products' => array()
                );
            }
            
            $shops[$item['seller_id']]['products'][] = array(
                'id'           => $item['id'],
                'product_id'   => $item['product_id'],
                'title'        => $item['title'],
                'price'        => $item['agents'][$buyer['agent_level']]['price'],
                'price_prefix' => $item['agents'][$buyer['agent_level']]['price_prefix'],
                'price_suffix' => $item['agents'][$buyer['agent_level']]['price_suffix'],
                'pic_url'      => ($item['pic_url'] ? $item['pic_url'] : $item['gpic_url']),
                'quota'        => $item['quota'],
                'disabled'     => $item['disabled'],
                'error'        => $item['error'],
                'goods_id'     => $item['goods_id'],
                'num'          => $item['num'],
                'original_price' => $item['original_price'],
                'pay_type'     => $item['pay_type'],
                'spec'         => get_spec_name($item["sku_json"]),
                'stock'        => $item['stock'],
                'url'          => '/h5/goods?id='.$item['goods_id']
            );
        }
        
        return array_values($shops);
    }
    
    /**
     * 加入购物车
     * @param unknown $product
     */
    public function insert($product){
        // 购物车数量限制
        $list = $this->field("id, product_id, num")->where("buyer_id=".$product['buyer_id'])->select();
        $total = count($list);
        
        foreach($list as $item){
            if($item['product_id'] == $product['product_id']){
                $this->execute("UPDATE {$this->tableName} SET num=num+{$product['num']} WHERE id=".$item['id']);
                return $total;
            }
        }
        
        if($total >= 20){
            $this->error = "购物车已达上限20种产品";
            return 0;
        }
        
        $product['created'] = time();
        $this->add($product);
        
        return $total+1;
    }
    
    /**
     * 设置数量
     * @param unknown $id
     * @param unknown $buyerId
     * @param unknown $num
     */
    public function setNum($id, $buyerId, $num){
        $sql = "UPDATE ".$this->getTableName()." SET num=%d WHERE id=%d AND buyer_id=%d";
        return $this->execute($sql, array($num, $id, $buyerId));
    }
    
    /**
     * 获取买家购物车中的数量
     * @param unknown $buyerId
     * @param string $sellerId
     */
    public function getBuyerNum($buyerId){
        $where = array("buyer_id" => $buyerId);
        return $this->where($where)->count();
    }
}
?>