<?php 
namespace Common\Model;

class GoodsModel extends BaseModel{
    protected $tableName = 'mall_goods';
    
    /**
     * 添加商品
     * @param Goods $data
     * @return number
     */
    public function insert($data){
        // 处理系统tag
        foreach ($data['tag_id'] as $i=>$tagId){
            if($tagId < 10000){
                unset($data['tag_id'][$i]);
            }
        }
        // 包邮标记
        $express = new \Common\Model\ExpressModel();
        $expressFee = $express->getRangeFee($data['freight_tid'], $data['weight']);
        if($expressFee['baoyou']){
            array_unshift($data['tag_id'], 1000);
        }else{
            $i = array_search(1000, $data['tag_id']);
            if($i > -1){unset($data['tag_id'][$i]);}
        }
        $data['tag_id'] = array_unique($data['tag_id']);
        $data['tag_id'] = implode(',', $data['tag_id']);
        
        $products = $data['products'];
        unset($data['products']);
        $goods = $data;
        $goods['pic_url'] = $data['images'][0];
        $goods['images'] = json_encode($data['images']);
        $goods['sold_time'] = empty($data['sold_time']) ? 0 : strtotime($data['sold_time']);
        $goods['created'] = date('Y-m-d H:i:s');
        $goods['sku_json'] = is_array($data['sku_json']) ? json_encode($_POST['sku_json'], JSON_UNESCAPED_UNICODE) : '[]';
        if($data['cat_id'] == 2){
            $goods['pay_type'] = 4; // 拼手气红包
        }else if($data['cat_id'] == 3){
            $goods['pay_type'] = 5; // 裂变红包
        }
        
        if($goods['pay_type'] == 4 || $goods['pay_type'] == 5){
            $goods['returns'] = 0;
            $goods['original_price'] = '';
        }
        
        // 添加商品
        $goodsId = $this->add($goods);
        if($goodsId < 1)
            return 0;
        $goods['id'] = $goodsId;
        
        // 产品处理
        if(is_array($products) && count($products) > 0){
            foreach($products as $i=>$item){
                $products[$i]['goods_id'] = $goodsId;
                $products[$i]['pic_url'] = '';
                
                // 处理产品图片
                foreach($data['sku_json'] as $psku){
                    foreach($psku['items'] as $vsku){
                        if(!empty($vsku['img'])){
                            foreach($item['sku_json'] as $pcsku){
                                if($pcsku['vid'] == $vsku['id']){
                                    $products[$i]['pic_url'] = $vsku['img'];
                                    goto end;
                                }
                            }
                        }
                    }
                }
                end:
            }
        }else{
            $products = array(
                'goods_id'      => $goodsId,
                'stock'         => $goods['stock'],
                'price'         => $goods['price'],
                'outer_id'      => $goods['outer_id'],
                'original_price'=> $goods['original_price'],
                'pic_url'       => $goods['pic_url'],
                'weight'        => $goods['weight'],
                'agent2_price'  => $goods['agent2_price'],
                'agent3_price'  => $goods['agent3_price'],
                'cost'          => $goods['cost']
            );
        }
        $this->addProduct($products);
        
        $this->updateGoodsSort($goods);
        return 1;
    }
    
    /**
     * 添加产品/多个产品
     * @param unknown $products
     */
    public function addProduct($products){
        $Model = M('mall_product');
        $today = date('Y-m-d H:i:s');
        if(isset($products[0])){
            $list = array();
            foreach($products as $item){
                $item['created'] = $today;
                unset($item["id"]);
                if(is_array($item['sku_json'])){
                    $item['sku_json'] = json_encode($item['sku_json'], JSON_UNESCAPED_UNICODE);
                }
                $list[] = $item;
            }
            return $Model->addAll($list);
        }else{
            $products['created'] = $today;
            if(is_array($products['sku_json'])){
                $products['sku_json'] = json_encode($products['sku_json'], JSON_UNESCAPED_UNICODE);
            }
            return $Model->add($products);
        }
    }
    
    /**
     * 更新产品七日上架时间(排序)
     */
    private function updateGoodsSort($goods){
        $goodsId     = $goods['id'];
        $displayTime = $goods['sold_time'] == 0 ? time() : $goods['sold_time'];
        $sort = is_numeric($goods['sort']) ? $goods['sort'] : 0;
        
        $now = NOW_TIME;
        $this->execute("INSERT INTO mall_goods_sort(goods_id, sort, display_time) VALUES({$goodsId}, {$sort}, {$displayTime}) 
                        ON DUPLICATE KEY UPDATE sort=VALUES(sort), display_time={$displayTime}");
    
        // 更新CDN域名BUG
        $cdn = C('CDN');
        $sql = "update mall_goods set detail=replace(detail,'src=\"/upload','src=\"{$cdn}/upload') WHERE id=".$goodsId;
        $this->execute($sql);
    }
    
    /**
     * 复制商品
     * @author Liucong
     */
    public function copy($id){
        $data = $this->getById($id);
        $data['pv'] = 0;
        $data['uv'] = 0;
        $data['sold_num'] = 0;
        $data['order_num'] = 0;
        $data['sku_json'] = is_array($data['sku_json']) ? json_encode($data['sku_json'], JSON_UNESCAPED_UNICODE) : '';
        if(empty($data['weight'])){
                unset($data['weight']);
        }
        unset($data['id']);
        foreach($data['products'] as $key => $value){
                unset($data['products'][$key]['id']);
                if(empty($value['modified'])){
                        unset($data['products'][$key]['modified']);
                }
                $data['products'][$key]['sold_num'] = 0;
                $data['products'][$key]['order_num'] = 0;
                $data['products'][$key]['pv'] = 0;
                $data['products'][$key]['uv'] = 0;
        }
        $result = $this->insert($data);
        return $result;
    }
    
    /**
     * 根据id获取商品信息
     * @param unknown $id
     * @return NULL|Ambigous <mixed, boolean, NULL, string, unknown, multitype:, object>
     */
    public function getById($id){
        $goods = $this
                 ->alias("goods")
                 ->field("goods.id, goods.sold_time, goods.buy_quota, goods.cat_id, goods.tag_id, goods.pay_type, 
                     goods.title, goods.agent2_price, goods.agent3_price, goods.agent4_price, goods.price, 
                     goods.original_price, goods.score, goods.stock, goods.hide_stock, goods.outer_id, 
                     goods.pic_url, goods.images, goods.digest, goods.day_quota, goods.every_quota, 
                     goods.agent_quota, goods.is_virtual, goods.freight_tid, 
                     goods.member_discount, goods.points, goods.is_display, goods.sold_num, goods.order_num,
                     goods.invoice, goods.warranty, goods.`returns`, goods.created, goods.sku_json, 
                     goods.template_id, goods.is_del, goods.weight, goods.shop_id, goods.tao_id, goods.parameters,
                     shop.name AS shop_name")
                 ->join("shop ON shop.id=goods.shop_id")
                 ->where("goods.id=".$id)
                 ->find();
        
        if(empty($goods)){
            $this->error = '商品不存在';
            return null;
        }
        
        return $this->formatter($goods);
    }
    
    private function formatter($goods){
        // 格式化数据
        $goods['price'] = sprintf("%.2f", $goods['price']);
        if($goods['original_price'] > $goods['price']){
            $goods['original_price'] = sprintf("%.2f", $goods['original_price']);
        }else{
            $goods['original_price'] = '';
        }
        $goods['images'] = json_decode($goods['images'], true);
        
        // sku组合
        $goods['sku_json'] = $goods['sku_json'] ? json_decode($goods['sku_json'], true) : array();
    
        // 参数
        if(isset($goods['parameters'])){
            $goods['parameters'] = json_decode($goods['parameters'], true);
        }
    
        // 产品
        $products = $this->query("SELECT * FROM mall_product WHERE goods_id='{$goods['id']}'");
        foreach($products as $i=>$item){
            $products[$i]['sku_json'] = ($item['sku_json'] && $item['sku_json'] != '[]') ? json_decode($item['sku_json'], true) : array();
        }
        $goods['products'] = $products;
        
        /************************************/
        // 限购处理
        $limit = array();
        $goods['quota'] = $goods['stock'];
        $todayStart     = date('Y-m-d').' 00:00:00';
        $todayEnd       = date('Y-m-d').' 23:59:59';
        
        // 日限售处理(同时处理库存)
        if($goods['day_quota'] > 0){
            $limit[] = '每日限售'.$goods['day_quota'].'件';
            
            $OrderModel = new OrderModel();
            $soldNum = $OrderModel->getSoldNumByTime($goods['id'], $todayStart, $todayEnd);
            if($soldNum >= $goods['day_quota']){
                $goods['stock'] = 0;
            }else{
                $stock = $goods['day_quota'] - $soldNum;
                $goods['stock'] = $goods['stock'] < $stock ? $goods['stock'] : $stock;
            }
        
            $goods['quota'] = $goods['stock'];
        }
        
        // 每人每日限购处理
        if($goods['every_quota'] > 0){
            $limit[] = '每日限购'.$goods['every_quota'].'件';
            if($goods['quota'] > 0){
                $quota = $goods['stock'] < $goods['every_quota'] ? $goods['stock'] : $goods['every_quota'];
                if($quota < $goods['quota']){
                    $goods['quota'] = $quota;
                }  
            }
        }
        
        // 每人最多能购买
        if($goods['buy_quota'] > 0){
            $limit[] = '每人限购'.$goods['buy_quota'].'件';
            if($goods['quota'] > 0){
                $quota = $goods['stock'] < $goods['buy_quota'] ? $goods['stock'] : $goods['buy_quota'];
                if($quota < $goods['quota']){
                    $goods['quota'] = $quota;
                }
            }
        }
        
        if($goods['quota'] > 0 && ($goods['pay_type'] == 4 || $goods['pay_type'] == 5)){
            $goods['quota'] = 1;
        }
        $goods['quota_str'] = count($limit) > 0 ? implode(',', $limit) : '';
        /*************************************/
        
        // 发货地
        if(isset($goods['send_place'])){
            if($goods['send_place'] > 0){
                $city = StaticModel::getCityList($goods['send_place']);
                $province = StaticModel::getCityList($city['pcode']);
                $goods['send_place'] = $province['sname'].' '.$city['sname'];
            }else{
                $goods['send_place'] = '';
            }
        }
        
        // 偏远地区
        if(!empty($goods['remote_area'])){
            $remoteAreas = explode(',', $goods['remote_area']);
            $remoteArea = '';
            foreach ($remoteAreas as $code){
                $city = StaticModel::getCityList($code);
                $remoteArea .= $remoteArea == '' ? $city['sname'] : '、'.$city['sname'];
            }
            $goods['remote_area'] = $remoteArea;
        }
        
        $goods['status'] = 'onsale';
        if($goods['sold_time'] > NOW_TIME){
            //$goods['status'] = 'countdown';
            //$goods['errmsg'] = '未开售';
            $goods['countdown'] = array('txt' => '距开售剩余', 'start' => NOW_TIME, 'end' => $goods['sold_time']);
            $goods['action'] = array(
                array('id' => 'addCart', 'txt' => '加入购物车', 'disabled' => 0, 'class' => 'btn-orange'),
                array('id' => 'buyNow', 'txt' => '立即购买', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($goods['is_display'] == 0){
            //$goods['status'] = 'no_display';
            //$goods['errmsg'] = '已下架';
            $goods['action'] = array(
                array('id' => 'buyNow', 'txt' => '已下架', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($goods['stock'] <= 0){
            //$goods['status'] = 'sold_out';
            //$goods['errmsg'] = '已售罄';
            $goods['action'] = array(
                array('id' => 'buyNow', 'txt' => '已售罄', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($goods['quota'] == 0){
            //$goods['status'] = 'quota';
            //$goods['errmsg'] = '已限购';
            $goods['action'] = array(
                array('id' => 'buyNow', 'txt' => '已超限购', 'disabled' => 1, 'class' => 'disabled')
            );
        }else{
            $goods['action'] = array(
                array('id' => 'addCart', 'txt' => '加入购物车', 'disabled' => 0, 'class' => 'btn-orange'),
                array('id' => 'buyNow', 'txt' => '立即购买', 'disabled' => 0, 'class' => 'btn-orange-dark'),
            );
        }
        
        return $goods;
    }
    
    /**
     * 更新商品信息
     * @param unknown $goods
     */
    public function update($data){
        $goodsId = $data['id'];
        if(!is_numeric($goodsId)){
            $this->error = '商品id不能为空';
            return -1;
        }
        
        $old = $this->getById($goodsId);
        
        // 处理系统tag
        foreach ($data['tag_id'] as $i=>$tagId){
            if($tagId < 10000){
                unset($data['tag_id'][$i]);
            }
        }
        // 合并旧标记
        if(!empty($old['tag_id'])){
            $tags = explode(',', $old['tag_id']);
            foreach ($tags as $tagId){
                if($tagId < 10000){
                    array_unshift($data['tag_id'], $tagId);
                }
            }
        }
        
        // 包邮标记
        $express = new \Common\Model\ExpressModel();
        $expressFee = $express->getRangeFee($data['freight_tid'], $data['weight']);
        if($expressFee['baoyou']){
            array_unshift($data['tag_id'], 1000);
        }else{
            $i = array_search(1000, $data['tag_id']);
            if($i > -1){unset($data['tag_id'][$i]);}
        }
        $data['tag_id'] = array_unique($data['tag_id']);
        $data['tag_id'] = implode(',', $data['tag_id']);
        
        $products = $data['products'];
        unset($data['products']);
        $goods = $data;
        $goods['pic_url'] = $data['images'][0];
        $goods['images'] = json_encode($data['images']);
        $goods['sold_time'] = empty($data['sold_time']) ? 0 : strtotime($data['sold_time']);
        $goods['sku_json'] = is_array($data['sku_json']) ? json_encode($_POST['sku_json'], JSON_UNESCAPED_UNICODE) : '[]';
        if($data['cat_id'] == 2){
            $goods['pay_type'] = 4; // 拼手气红包
        }else if($data['cat_id'] == 3){
            $goods['pay_type'] = 5; // 裂变红包
        }
        
        if($goods['pay_type'] == 4 || $goods['pay_type'] == 5){
            $goods['returns'] = 0;
            $goods['original_price'] = '';
        }
        
        // 保存GOODS
        $this->where("id=".$goodsId)->save($goods);
        
        // 保存PRODUCT
        if(is_array($products) && count($products) > 0){
            $existsIds = array();
            foreach($products as $product){
                // 处理产品图片
                $product['pic_url'] = '';
                foreach($data['sku_json'] as $psku){
                    foreach($psku['items'] as $vsku){
                        if(!empty($vsku['img'])){
                            foreach($product['sku_json'] as $pcsku){
                                if($pcsku['vid'] == $vsku['id']){
                                    $product['pic_url'] = $vsku['img'];
                                    goto end;
                                }
                            }
                        }
                    }
                }
                end:
                
                if(is_numeric($product['id'])){
                    $this->updateProduct($product);
                    $existsIds[] = $product['id'];
                }else{
                    $product['goods_id'] = $goodsId;
                    $this->addProduct($product);
                }
            }

            // 取出已被摒弃的产品ID
            $deletedIds = array();
            foreach($old['products'] as $product){
                if(!in_array($product['id'], $existsIds)){
                    $deletedIds[] = $product['id'];
                }
            }
            
            // 删除摒弃的产品
            if(count($deletedIds) > 0){
                $sql = "DELETE FROM mall_product WHERE id IN (".implode(',', $deletedIds).")";
                $this->execute($sql);
            }
        }else{
            $product = array(
                'goods_id'      => $goodsId,
                'stock'         => $goods['stock'],
                'price'         => $goods['price'],
                'pic_url'       => '',
                'outer_id'      => $goods['outer_id'],
                'pic_url'       => $goods['pic_url'],
                'weight'        => $goods['weight'],
                'agent2_price'  => $goods['agent2_price'],
                'agent3_price'  => $goods['agent3_price'],
                'cost'          => $goods['cost']
            );
            
            if(empty($old['sku_json'])){
                $product['id'] = $old['products'][0]['id'];
                $this->updateProduct($product);
            }else{
                $this->execute("DELETE FROM mall_product WHERE goods_id=".$goodsId);
                $this->addProduct($product);
            }
        }
        
        $this->updateGoodsSort($goods);
        return 1;
    }
    
    /**
     * 更新单个产品(不更新goods表库存)
     * @param unknown $product
     * @return number|boolean
     */
    public function updateProduct($product){
        if(!is_numeric($product['id'])){
            $this->error = '产品ID不能为空';
            return -1;
        }
        
        $Model = M('mall_product');
        if(is_array($product['sku_json'])){
            $product['sku_json'] = json_encode($product['sku_json'], JSON_UNESCAPED_UNICODE);
        }
        $Model->where("id=%d", $product['id'])->save($product);
    }
    
    /**
     * 批量更新产品(自动更新goods表库存)
     * @param unknown $products
     */
    public function updateProducts($goodsId, $products){
        $Model = M('mall_product');
        $minPrice = -1;
        foreach($products as $id=>$product){
            $Model->where("id=".$id)->save($product);
            
            if($minPrice < 0 || $product['price'] < $minPrice){
                $minPrice = $product['price'];
            }
        }
        
        $this->execute("UPDATE mall_goods SET price='{$minPrice}', stock=(SELECT SUM(stock) FROM mall_product WHERE goods_id={$goodsId}) WHERE id={$goodsId}");
    }
    
    /**
     * 根据id删除商品
     * @param unknown $goodsIds
     * @param string $shopId
     * @return number
     */
    public function deleteById($goodsIds, $shopId = null){
        if(empty($goodsIds)){ 
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $sql = "UPDATE ".$this->tableName." SET is_del=1 WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= "  AND shop_id=".$shopId;
        }
        
        $result = $this->execute($sql);
        if($result > 0){
            $this->execute("DELETE FROM mall_goods_sort WHERE goods_id IN ({$goodsIds})");
        }
        return $result;
    }
    
    /**
     * 批量下架
     * @param unknown $goodsIds
     * @param string $shopId
     * @return number|\Think\false
     */
    public function takeDown($goodsIds, $shopId = null){
        if(empty($goodsIds)){ 
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $sql = "UPDATE ".$this->tableName." SET is_display=0 WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= " AND shop_id=".$shopId;
        }
        return $this->execute($sql);
    }
    
    /**
     * 批量上架
     * @param unknown $goodsIds
     * @param string $shopId
     * @return number|\Think\false
     */
    public function takeUp($goodsIds, $shopId = null){
        if(empty($goodsIds)){ 
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $sql = "UPDATE ".$this->tableName." SET is_display=1 WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= " AND shop_id=".$shopId;
        }
        return $this->execute($sql);
    }
    
    /**
     * 
     * @param unknown $goodsIds
     * @param unknown $join
     * @param string $shopId
     * @return number|\Think\false
     */
    public function discount($goodsIds, $join, $shopId = null){
        if(empty($goodsIds)){
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $sql = "UPDATE ".$this->tableName." SET member_discount=".($join ? 1 : 0)." WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= " AND shop_id=".$shopId;
        }
        return $this->execute($sql);
    }
    
    /**
     * 获取首页展示的商品
     * @param unknown $shopId
     */
    public function getGoodsList(){
        $offset = is_numeric($_GET['offset']) ? $_GET['offset'] : 0;
        $size = is_numeric($_GET['size']) ? $_GET['size'] : 38;
        $title = $_GET['title'];
        $sort = $_GET['sort'];
        
        $where = array('goods.is_display=1', 'goods.is_del=0');
        $order = '';
        switch ($sort){
            case 'newest':
                $order = 'goods_sort.display_time DESC';
                break;
            case 'sales':
                $order = 'goods_sort.threeday DESC';
                break;
            case 'hot':
                $order = 'goods_sort.uv DESC';
                break;
            default:
                $order = 'goods_sort.sort DESC, goods_sort.display_time DESC, goods_sort.uv DESC';
                break;
        }
        
        $join = "";
        if($sort == 'rand'){
            $join .= " JOIN ( SELECT ROUND( RAND() * (SELECT MAX(id) - MIN(id) FROM mall_goods) + (SELECT MIN(id) FROM mall_goods) ) AS id) AS rand2";
            $where[] = "goods.id >rand2.id";
        }
        
        if($title != ''){
            $where[] = "goods.title like '%".addslashes($title)."%'";
        }
        
        if(is_numeric($_GET['shop_id'])){
            $where[] = "goods.shop_id ='".$_GET['shop_id']."'";
        }

        if(is_numeric($_GET['cat_id'])){
            $where[] = "goods.cat_id ='".$_GET['cat_id']."'";
        }

        if(is_numeric($_GET['tag_id'])){
            if($_GET['tag_id'] == 1001){ // 众筹团购
                $order = '';
                if($_GET['status'] == 'waiting'){  // 待开团
                    $where[] = "active.start_time>".NOW_TIME;
                }else if($_GET['status'] == 'history'){  // 往期回顾
                    $where[] = NOW_TIME.">active.end_time";
                    $order = "ORDER BY end_time DESC";
                }else{
                    $where[] = NOW_TIME." BETWEEN active.start_time AND active.end_time";
                }
            }else if($_GET['tag_id'] == 1002){
                $order = '';
                if($_GET['status'] == 'waiting'){  // 待开抢
                    $where[] = "active.start_time>".NOW_TIME;
                }else{
                    $where[] = NOW_TIME." BETWEEN active.start_time AND active.end_time";
                }
            }else{
                $where[] = "MATCH (goods.tag_id) AGAINST ({$_GET['tag_id']} IN BOOLEAN MODE)";
            }
        }else if($_GET['tag_id'] == 'score'){
            $where[] = "goods.score>0";
        }
        
        $where   = "WHERE ".implode(' AND ', $where);
        $field = "goods.id, goods.title, goods.price, goods.original_price, goods.pic_url, goods.stock,
                  goods.images, goods.agent2_price, goods.agent3_price, goods.visitors_quota, goods.weight,
                  goods.cat_id, goods.tag_id";
        if($_GET['tag_id'] == 'jinribaokuan'){   // 今日爆款
            $sql = "SELECT {$field}
                    FROM (
                    	SELECT goods_sort.*
                    	FROM mall_goods AS goods
                    	INNER JOIN mall_goods_sort AS goods_sort ON goods_sort.goods_id=goods.id
                    	WHERE goods.is_display=1
                    	ORDER BY goods_sort.yesterday DESC
                    	LIMIT 114
                    ) AS goods_sort
                    LEFT JOIN mall_goods AS goods ON goods.id=goods_sort.goods_id
                    {$where}
                    ORDER BY {$order} 
                    LIMIT {$offset}, {$size}";
        }else if($_GET['tag_id'] == 1001){ // 众筹拼团
            $sql = "SELECT goods.id, goods.price AS original_price, active.title, active.price, IF(active.pic_url='' OR ISNULL(active.pic_url), goods.pic_url, active.pic_url) AS pic_url, goods.stock,
                      goods.images, active.price AS agent2_price, active.price AS agent3_price, goods.visitors_quota, goods.weight,
                      goods.cat_id, goods.tag_id, active.id AS active_id, active.sold AS active_sold, active.total AS active_total,
                      active.tag_short AS active_price_title, 1001 AS active_type
                    FROM mall_groupon AS active
                    INNER JOIN mall_goods AS goods ON goods.id=active.goods_id
                    {$where}
                    {$order}
                    LIMIT {$offset}, {$size}";
        }else if($_GET['tag_id'] == 1002){ // 零元购
            $sql = "SELECT goods.id, active.title, active.price, goods.price AS original_price, IF(active.pic_url='' OR ISNULL(active.pic_url), goods.pic_url, active.pic_url) AS pic_url, goods.stock, goods.images,
                    	goods.visitors_quota, goods.weight, goods.cat_id, goods.tag_id,
                    	active.id AS active_id, active.price AS agent2_price, active.price AS agent3_price,
                    	active.price_title AS active_price_title, 1002 AS active_type, active.sold AS active_sold, active.total AS active_total
                    FROM mall_zero AS active
                    INNER JOIN mall_goods AS goods ON goods.id = active.goods_id
                    {$join}
                    {$where}
                    LIMIT {$offset}, {$size}";
        }else{
            $sql = "SELECT {$field}
                    FROM mall_goods AS goods
                    INNER JOIN mall_goods_sort AS goods_sort ON goods_sort.goods_id=goods.id
                    {$join}
                    {$where}
                    ORDER BY {$order}
                    LIMIT {$offset}, {$size}";
        }
        
        $list = $this->query($sql);

        // 页面数据显示处理
        if(count($list) > 0){
            $buyer = $this->getTradingBuyer();
            $list = $this->goodsListHandler($list, $buyer);
        
            // 保存搜索历史记录
            if($title != ''){
                $search = cookie('search_goods');
                $searchList = !empty($search) ? explode(';', $search) : array();
                $key = array_search($title, $searchList);
                if($key !== false){
                    array_splice($searchList, $key, 1);
                }
                array_unshift($searchList, $title);
                if(count($searchList) > 20){
                    array_splice($searchList, 20);
                }
                $search = implode(';', $searchList);
                cookie('search_goods', $search, 2592000);
            }
        }
        
        return $list;
    }
    
    /**
     * 猜你喜欢
     */
    public function getLikeGoods(){
        $buyer = $this->getTradingBuyer();
        $myLevel = $user['agent_level'];
        
        // 查找浏览历史记录
        $sql = "SELECT GROUP_CONCAT(DISTINCT mall_goods.cat_id) AS cat
                FROM mall_goods_uv AS uv 
                INNER JOIN mall_goods ON mall_goods.id = uv.goods_id 
                WHERE user_id = {$buyer['id']}
                ORDER BY date DESC
                LIMIT 10";
        $earchResult = $this->query($sql);
        $cat = $earchResult[0]['cat'];
        
        $sql = '';
        if($cat != ''){
            $sql = "SELECT id, title, price, original_price, pic_url, images, agent2_price, agent3_price, visitors_quota, weight, cat_id, tag_id
                    FROM mall_goods
                    WHERE cat_id IN ({$cat}) AND is_display = 1 AND is_del = 0
                    ORDER BY RAND()
                    LIMIT 57";
        }else{
            $sql = "SELECT mall_goods.id, title, price, original_price, pic_url, images, agent2_price, agent3_price, visitors_quota, weight, cat_id, tag_id
                    FROM mall_goods
                    JOIN ( SELECT ROUND( RAND() * (SELECT MAX(id) - MIN(id) FROM mall_goods) + (SELECT MIN(id) FROM mall_goods) ) AS id) AS t2
                    WHERE mall_goods.id>=t2.id
                        AND mall_goods.is_display=1 AND is_del=0
                    LIMIT 57";
        }
        
        $list = $this->query($sql);
        $list = $this->goodsListHandler($list, $buyer);
        return $list;
    }
    
    /**
     * 获取商品的sku
     * @param unknown $goodsId
     */
    public function getSKU($goodsId){
        if(!is_numeric($goodsId)){
            $this->error = '商品ID不能为空';
            return null;
        }
    
        $goods = $this->getById($goodsId);
        if(empty($goods) || $goods['is_del']){
            $this->error = '商品不存在';
            return null;
        }
        unset($goods['images']);
        
        // 如果是套系商品，每隔5分钟更新一次信息
        if(!empty($goods['tao_id'])){
            $alibabaModel = new AlibabaModel();
            $syncResult = $alibabaModel->syncGoods($goods['tao_id'], $goods['id']);
            if(!empty($syncResult['error'])){
                $goods['is_display'] = 0;
            }
        }

        $buyer = $this->getTradingBuyer();
        $goods['tag_id'] = explode(',', $goods['tag_id']);
        if($_GET['from'] == 1001 || in_array(1001, $goods['tag_id'])){ // groupon拼团
            $goods = $this->parseGroupon($goods, $buyer);
        }else if($_GET['from'] == 1002 || in_array(1002, $goods['tag_id'])){  // ero零元购
            $goods = $this->parseZero($goods, $buyer);
        }

        // 快递费
        $goods['freight_fee'] = $this->getFreightFee($goods);
        $goods = $this->goodsHandler($goods, $buyer);
        $this->savePV($goods['id'], $buyer['id']);
        return $goods;
    }
    
    /**
     * 获取运费
     * @param unknown $goods
     */
    private function getFreightFee($goods){
        if(isset($goods['freight_fee'])){
            return $goods['freight_fee'];
        }
        
        $EM = new \Common\Model\ExpressModel();
        return $EM->getRangeFee($goods['freight_tid'], $goods['weight']);
    }
    
    /**
     * 获取商品详情
     * @return NULL|Ambigous <number, mixed, multitype:unknown number , unknown>
     */
    public function getDetail($goodsId){
        if(!is_numeric($goodsId)){
            $this->error = '商品ID不能为空';
            return null;
        }
        
        $goods = $this
                 ->alias("goods")
                 ->field("goods.id, goods.sold_time, goods.buy_quota, goods.cat_id, goods.pay_type,
                     goods.title, goods.agent2_price, goods.agent3_price, goods.agent4_price, goods.price,
                     goods.original_price, goods.score, goods.stock, goods.hide_stock, goods.outer_id,
                     goods.pic_url, goods.images, goods.digest, goods.day_quota, goods.every_quota,
                     goods.agent_quota, goods.is_virtual, goods.freight_tid, goods.tag_id, goods.detail,
                     goods.member_discount, goods.points, goods.is_display, goods.sold_num, goods.order_num,
                     goods.invoice, goods.warranty, goods.`returns`, goods.created, goods.sku_json,
                     goods.template_id, goods.is_del, goods.weight, goods.shop_id, goods.tao_id, goods.parameters,
                     shop.`name` AS shop_name, goods.send_place, goods.remote_area")
                 ->join("shop ON shop.id=goods.shop_id")
                 ->where("goods.id=".$goodsId)
                 ->find();
        
        if(empty($goods) || $goods['is_del']){
            $this->error = '商品不存在';
            return null;
        }
        
        $buyer = $this->getTradingBuyer();
        $goods = $this->formatter($goods);

        // 众筹拼团 
        $goods['tag_id'] = explode(',', $goods['tag_id']);
        if($_GET['from'] == 1001 || in_array(1001, $goods['tag_id'])){
            $goods = $this->parseGroupon($goods, $buyer);
        }else if($_GET['from'] == 1002 || in_array(1002, $goods['tag_id'])){  // 零元购
            $goods = $this->parseZero($goods, $buyer);
        } 

        // 快递费
        $goods['freight_fee'] = $this->getFreightFee($goods);
        $goods = $this->goodsHandler($goods, $buyer);
        // 保存浏览量
        $this->savePV($goods['id'], $buyer['id']);
        
        return $goods;
    }
    
    /**
     * 保存浏览量
     * @param unknown $goodsId
     * @param unknown $buyerId
     */
    public function savePV($goodsId, $buyerId){
        // 保存浏览量
        $date = date('Y-m-d');
        $existsUv = $this->query("SELECT id FROM mall_goods_uv WHERE user_id={$buyerId} AND `date`='{$date}' AND goods_id={$goodsId} LIMIT 1");
        if(count($existsUv) > 0){
            $this->execute("UPDATE mall_goods_uv SET times=times+1 WHERE id=".$existsUv[0]['id']);
            $this->execute("UPDATE mall_goods SET pv=pv+1 WHERE id=".$goodsId);
        }else{
            $this->execute("INSERT INTO mall_goods_uv SET `date`='{$date}', user_id={$buyerId}, goods_id={$goodsId}");
            $this->execute("UPDATE mall_goods SET pv=pv+1, uv=uv+1 WHERE id=".$goodsId);
        }
    }

    /**
     * 众筹拼团处理
     */
    private function parseGroupon($goods){
        $active = $this->query("SELECT * FROM mall_groupon WHERE goods_id={$goods['id']} LIMIT 1");
        $active = $active[0];
        if(empty($active)){ // 无效活动
            $active['status'] = 'error';
            $active['errmsg'] = '活动不存在';
        }else if(NOW_TIME < $active['start_time']){
            $active['status'] = 'countdown';
            $active['errmsg'] = '活动未开始';
            $active['countdown'] = array('txt' => '距开抢剩余', 'start' => time()+1, 'end' => $active['start_time']);
        }else if(NOW_TIME > $active['end_time']){
            $index = array_search(1001, $goods['tag_id']);
            if($index > -1){
                unset($goods['tag_id'][$index]);
                $this->execute("UPDATE mall_goods SET tag_id='".implode(',', $goods['tag_id'])."' WHERE id={$goods['id']}");
            }
            
            $active['status'] = 'ended';
            $active['errmsg'] = '活动已结束';
            $active['countdown'] = array('txt' => '活动已结束', 'start' => 0, 'end' => 0);
        }else{ 
            $active['status'] = 'onsale';
            $active['countdown'] = array('txt' => '距结束剩余', 'start' => time()+1, 'end' => $active['end_time']);
        }

        if($active['status'] != 'onsale' && $_GET['from'] != 1001){  // 显示合并内容
            if($active['status'] == 'countdown'){
                $goods['active_link'] = '/h5/goods?id='.$goods['id'].'&from=1001';
            }
            return $goods;
        }
        
        $goods['active'] = array(
            'id'        => $active['id'],
            'type'      => 1001,
            'start_time'=> $active['start_time'],
            'end_time'  => $active['end_time'],
            'status'=> $active['status'],
            'errmsg' => $active['errmsg'],
            'tag_name' => $active['tag_name'],
            'icon'  => '/img/mall/icon_tuan.png',
            'rule_url' => '/article/groupon.html',
            'sold'     => $active['sold']
        );
        $goods['countdown'] = $active['countdown'];
        $goods['title'] = $active['title'];
        if(isset($goods['detail']) && !empty($active['detail'])){$goods['detail'] = $active['detail'];}
        if($active['quota'] > $goods['quota']){$goods['quota'] = $active['quota'];}
        
        // 计算价格区间
        $rangePrice = array();
        $priceList = json_decode($active['price_range'], true);
        foreach($priceList as $pid=>$numPrice){
            foreach ($numPrice['range'] as $num=>$price){
                if(!isset($rangePrice[$num])){
                    $rangePrice[$num] = array($price, $price);
                    continue;
                }
    
                if($price < $rangePrice[$num][0]){
                    $rangePrice[$num][0] = $price;
                }
                if($price > $rangePrice[$num][1]){
                    $rangePrice[$num][1] = $price;
                }
            }
        }
    
        $start = 1;
        foreach ($rangePrice as $num=>$price){
            unset($rangePrice[$num]);
            $rangePrice[$start.' ~ '.$num] = $price[0] == $price[1] ? $price[0] : $price[0].' - '.$price[1];
            $start = $num+1;
        }
        $goods['range_price'] = $rangePrice;
    
        $nowMinPrice = -1;
        $totalStock = 0;
        foreach($goods['products'] as $index=>$product){
            if(!isset($priceList[$product['id']])){  // 商品信息已变更则不参与众筹拼团
                E('商品规格已变更');
            }
    
            $product['quota'] = 0;  // 当前可购买数量
            $data = $priceList[$product['id']];
            end($data['range']);
            $maxStock = key($data['range']);
            if($data['sold'] >= $maxStock){ // 超卖了
                $price = current($data['range']);
                $product['quota'] = $product['stock'] = 0;
            }else{
                foreach ($data['range'] as $num=>$price){
                    if($data['sold'] < $num){
                        $product['quota'] = $num - $data['sold'];
                        break;
                    }
                }
                
                // 总库存
                $num = $maxStock - $data['sold'];
                if($product['stock'] > $num){
                    $product['stock'] = $num;
                }
            }
            
            // 使用活动限购
            if($active['quota'] > 0){
                if($active['quota'] < $product['quota']){
                    $product['quota'] = $active['quota'];
                }
            }else if($goods['quota'] > 0){
                if($goods['quota'] < $product['quota']){
                    $product['quota'] = $goods['quota'];
                }
            }
            
            $totalStock += $product['stock'];

            // 当前价格
            $product['original_price'] = $product['price'];
            $product['price'] = $product['agent2_price'] = $product['agent3_price'] = $product['agent4_price'] = $price;
            $goods['products'][$index] = $product;
            
            if($nowMinPrice < 0 || $price < $nowMinPrice){
                $nowMinPrice = $price;
            }
        }

        $goods['active_sold'] = $active['sold'];
        $goods['tag_name'] = $active['tag_name'];
        $goods['active_price_title'] = $active['tag_short'];
        $goods['stock'] = $totalStock;
        $goods['original_price'] = $goods['price'];
        $goods['price'] = $goods['agent2_price'] = $goods['agent3_price'] = $goods['agent4_price'] = $nowMinPrice;
        $goods['stock'] = $active['total'] - $active['sold'];
        $goods['process'] = bcdiv($active['sold'], $active['total'], 4) * 100;
        if($goods['process'] == 100){
            $active['status'] = 'sold_out';
            $active['errmsg'] = '活动已售罄';
        }

        if($active['status'] == 'countdown'){
            $goods['action'] = array(
                array('id' => 'addCart','txt' => '加入购物车', 'disabled' => 0, 'class' => 'btn-orange'),
                array('id' => 'buyNow','txt' => '立即购买', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($active['status'] == 'ended'){
            $goods['action'] = array(
                array('id' => 'buyNow','txt' => '活动已结束', 'disabled' => 1, 'class' => 'disabled')
            );
        }
        
        if($active['single'] == 0){ // 不用任何优惠
            $goods['single'] = 1;
            $goods['score'] = 0;
        }else if($active['single'] == 1){ // 仅可使用积分
            $goods['single'] = 1;
        }
        
        return $goods;
    }
    
    public function parseZero($goods, $buyer){
        $active = $this->query("SELECT * FROM mall_zero WHERE goods_id={$goods['id']} ORDER BY id DESC LIMIT 1");
        $active = $active[0];
        if(empty($active)){
            $active['status'] = 'error';
            $active['errmsg'] = '活动不存在';
        }else if(NOW_TIME < $active['start_time']){
            $active['status'] = 'countdown';
            $active['errmsg'] = '活动未开始';
            $active['countdown'] = array('txt' => '距开抢剩余', 'start' => time()+1, 'end' => $active['start_time']);
        }else if(NOW_TIME > $active['end_time']){  // 卸载活动标记
            $index = array_search(1002, $goods['tag_id']);
            if($index > -1){ 
                unset($goods['tag_id'][$index]);
                $this->execute("UPDATE mall_goods SET tag_id='".implode(',', $goods['tag_id'])."' WHERE id={$goods['id']}");
            }
        
            $active['status'] = 'ended';
            $active['errmsg'] = '活动已结束';
            $active['countdown'] = array('txt' => '活动已结束', 'start' => 0, 'end' => 0);
        }else{
            $active['status'] = 'onsale';
            $active['countdown'] = array('txt' => '距结束剩余', 'start' => time()+1, 'end' => $active['end_time']);
        }
        
        if($active['status'] != 'onsale' && $_GET['from'] != 1002){  // 显示合并内容
            if($active['status'] == 'countdown'){
                $goods['active_link'] = '/h5/goods?id='.$goods['id'].'&from=1001';
            }
            return $goods;
        }
        
        $active['products'] = json_decode($active['products'], true);
        $goods['process'] = bcdiv($active['sold'], $active['total'], 4) * 100;
        $goods['title'] = $active['title'];
        $goods['single'] = 1;   // 不参与其他优惠
        $goods['score'] = 0;    // 不可使用积分
        $goods['original_price'] = $goods['price'];
        $goods['price'] = $goods['agent2_price'] = $goods['agent3_price'] = $active['price'];
        if($active['quota'] > 0){$goods['quota'] = $active['quota'];}
        if($active['pic_url']){$goods['pic_url'] = $active['pic_url'];}
        if(isset($goods['detail']) && !empty($active['detail'])){$goods['detail'] = $active['detail'];}
        
        $minPrice = -1;
        $maxPrice = 0;
        foreach ($goods['products'] as $i=>$product){
            $product['original_price'] = $product['price'];
            $product['price'] = $product['agent2_price'] = $product['agent3_price'] = $active['price'];
            $product['return'] = $active['products'][$product['id']];
            
            $goods['products'][$i] = $product;
            $price = $this->getAgentPrice($buyer['agent_level'], $active['products'][$product['id']]);
            if($minPrice == -1 || $price < $minPrice){
                $minPrice = $price;
            }
            
            if($price > $maxPrice){
                $maxPrice = $price;
            }
        }

        // 重新获取运费
        $goods['freight_tid'] = $active['freight_tid'];
        $goods['freight_fee'] = $this->getFreightFee($goods);
        $goods['freight_fee']['baoyou'] = 0;
        $goods['freight_fee']['min'] += $minPrice;
        $goods['freight_fee']['max'] += $maxPrice;
        if($goods['freight_fee']['min'] < $goods['freight_fee']['max']){
            $goods['freight_fee']['msg'] = $goods['freight_fee']['min'].' - '.$goods['freight_fee']['max'].'元';
        }else{
            $goods['freight_fee']['msg'] = $goods['freight_fee']['max'].'元';
        }
        
        $goods['active'] = array(
            'id'        => $active['id'],
            'type'      => 1002,
            'start_time'=> $active['start_time'],
            'end_time'  => $active['end_time'],
            'status'=> $active['status'],
            'errmsg' => $active['errmsg'],
            'tag_name' => $active['tag_name'],
            'icon'  => '/img/mall/icon_zero.png',
            'rule_url' => '/article/zero.html',
            'sold'     => $active['sold']
        );
        $goods['countdown'] = $active['countdown'];
        
        if($active['status'] == 'countdown'){
            $goods['action'] = array(
                array('id' => 'addCart','txt' => '加入购物车', 'disabled' => 0, 'class' => 'btn-orange'),
                array('id' => 'buyNow','txt' => '立即购买', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($active['status'] == 'ended'){
            $goods['action'] = array(
                array('id' => 'buyNow','txt' => '活动已结束', 'disabled' => 1, 'class' => 'disabled')
            );
        }
        
        return $goods;
    }
    
    /**
     * 获取产品
     */
    public function getProduct($shopId){
        $data = array('total' => 0, 'rows' => array());
        
        $where = "goods.is_del=0";
        if(!empty($_GET['title']))
            $where .= "AND goods.title LIKE '%".addslashes($_GET['title'])."%'";
        
        $data['total'] = $this->alias("goods")
                ->join("mall_product AS product ON product.goods_id=goods.id", "INNER")
                ->where($where)
                ->count();
        if($data['total'] == 0)
            return $data;
        

        $offset = I('get.offset/d', 0);
        $limit = I('get.limit/d', 50);
        
        $list = $this->alias("goods")
                ->field("product.id, product.sku_json, goods.post_fee, product.stock, goods.title")
                ->join("mall_product AS product ON product.goods_id=goods.id", "INNER")
                ->where($where)
                ->order("goods.id DESC")
                ->limit($offset, $limit)
                ->select();
        
        foreach($list as $i=>$item){
            $list[$i]['spec'] = $this->getSpec($item['sku_json']);
            unset($list[$i]['sku_json']);
        }
        
        $data['rows'] = $list;
        return $data;
    }
    
    /**
     * 获取产品信息
     */
    public function getProductList($_where){
        $data = array('total' => 0, 'rows' => array());
        $where = array();
        $offset = I('get.offset/d', 0);
        $limit = I('get.limit/d', 50);

        $where[] = "goods.is_del=0";
        if($_where['action'] == 'index'){
            $where[] = "goods.is_display=1 AND goods.stock>0";
        }else if($_where['action'] == 'soldout'){
            $where[] = "product.stock<=0";
        }else if($_where['action'] == 'no_display'){
            $where[] = "goods.is_display=0";
        }else if($_where['action'] == 'visitors'){
            $where[] = "goods.visitors_quota=1";
        }
        
        if(is_numeric($_where['shop_id'])){
            $where[] = "goods.shop_id=".$_where['shop_id'];
        }
        
        if(!empty($_where['title'])){
            $title = addslashes($_where['title']);
            $where[] = "(goods.title LIKE '%{$title}%' OR goods.outer_id like '%{$title}%')";
        }
        
        $innerJoin = " INNER JOIN mall_goods_sort AS goods_sort ON goods_sort.goods_id=goods.id";
        if(is_numeric($_where['tag'])){
            $where[] = "MATCH (goods.tag_id) AGAINST ({$_where['tag']} IN BOOLEAN MODE)";
        }
        
        if($_where['action'] == 'soldout'){
            $innerJoin .= " INNER JOIN mall_product AS product ON product.goods_id=goods.id";
        }
        
        $where = count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "";
        
        // 计算总数
        $_total = $this->query("SELECT COUNT(distinct goods.id) FROM mall_goods AS goods".$innerJoin.$where);
        $data['total'] = current($_total[0]);
        if($data['total'] == 0){
            return $data;
        }
        
        $groupBy = $_where['action'] == 'soldout' ? " GROUP BY goods.id" : "";
        
        $orderBy = "";
        switch ($_where['sort']){
            case 'title':
                $orderBy = "goods.title";
                break;
            case 'stock':
                $orderBy = "goods.stock";
                break;
            case 'sold_num':
                $orderBy = "goods.sold_num";
                break;
            case 'created':
                $orderBy = "goods.id";
                break;
            case 'sort':
                $orderBy = "goods_sort.sort";
                break;
        
        }
        
        if(empty($orderBy)){
            $orderBy = " ORDER BY goods_sort.sort DESC, goods_sort.display_time DESC, goods_sort.uv DESC";
        }else{
            $orderBy = " ORDER BY ".$orderBy." ".$_where['order'];
        }
        
        $sql = "SELECT goods.id, goods.title, goods.price, goods.pic_url, goods.stock, sort, goods.pay_type, goods.tao_id,
                       goods.sold_num, goods.pv, goods.uv, goods.created, goods.agent2_price, goods.agent3_price, goods.shop_id,goods.cost,
                       goods_sort.yesterday,goods_sort.sevenday
                FROM mall_goods AS goods
                {$innerJoin}
                {$where}
                {$groupBy}
                {$orderBy}
                LIMIT {$offset}, {$limit}";

        $data['rows'] = $this->query($sql);
        if($_where['action'] == 'soldout'){
            foreach ($data['rows'] as $i=>$item){
                $data['rows'][$i]['spec'] = get_spec_name($item['sku_json']);
                unset($data['rows'][$i]['sku_json']);
            }
        }
        
        return $data;
    }
    
    
    /**
     * 导出产品信息
     */
    public function export($_where){
        set_time_limit(0);
        
        $where = array("goods.is_del=0");
        if($_where['action'] == 'index'){
            $where[] = "goods.is_display=1 AND goods.stock>0";
        }else if($_where['action'] == 'soldout'){
            $where[] = "product.stock<=0";
        }else if($_where['action'] == 'no_display'){
            $where[] = "goods.is_display=0";
        }else if($_where['action'] == 'visitors'){
            $where[] = "goods.visitors_quota=1";
        }
        
        if(is_numeric($_where['shop_id'])){
            $where[] = "goods.shop_id=".$_where['shop_id'];
        }
        
        if(!empty($_where['title'])){
            $title = addslashes($_where['title']);
            $where[] = "(goods.title LIKE '%{$title}%' OR goods.outer_id like '%{$title}%')";
        }
        
        $innerJoin = " LEFT JOIN mall_goods_sort AS goods_sort ON goods_sort.goods_id=goods.id";
        $innerJoin .= " INNER JOIN mall_product AS product ON product.goods_id=goods.id";
        if(is_numeric($_where['tag'])){
            $where[] = "MATCH (goods.tag_id) AGAINST ({$_where['tag']} IN BOOLEAN MODE)";
        }
        
        $where = count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "";
        
        $orderBy = "";
        switch ($_where['sort']){
            case 'title':
                $orderBy = "goods.title";
                break;
            case 'stock':
                $orderBy = "goods.stock";
                break;
            case 'sold_num':
                $orderBy = "goods.sold_num";
                break;
            case 'created':
                $orderBy = "goods.id";
                break;
            case 'sort':
                $orderBy = "goods_sort.sort";
                break;
        }
        
        if(empty($orderBy)){
            $orderBy = " ORDER BY goods_sort.sort DESC, goods_sort.display_time DESC, goods_sort.uv DESC";
        }else{
            $orderBy = " ORDER BY ".$orderBy." ".$_where['order'];
        }
        
        $sql = "SELECT goods.id, goods.title, goods.pv,goods.uv,goods.stock AS goods_stock, goods.sold_num AS goods_sold_num, goods.created AS goods_created, goods_sort.sort,goods_sort.yesterday,goods_sort.threeday,
                       goods_sort.sevenday,goods_sort.thiryday,product.id AS product_id,product.sku_json, product.price, product.agent2_price, product.agent3_price, product.outer_id, product.stock, product.sold_num,
                       product.created,product.cost, goods.outer_id AS goods_outer_id
                FROM mall_goods AS goods
                {$innerJoin}
                {$where}
                {$orderBy}";
                
        $list = $this->query($sql);
        $goodsList = array();
        foreach($list as $item){
            if(!isset($goodsList[$item['id']])){
                $goodsList[$item['id']] = array(
                    'outer_id' => $item['goods_outer_id'],
                    'title' => $item['title'],
                    'pv' => $item['pv'],
                    'uv' => $item['uv'],
                    'stock' => $item['goods_stock'],
                    'sold_num' => $item['goods_sold_num'],
                    'created' => $item['goods_created'],
                    'sort' => $item['sort'],
                    'yesterday' => $item['yesterday'],
                    'threeday' => $item['threeday'],
                    'sevenday' => $item['sevenday'],
                    'thiryday' => $item['thiryday'],
                    'products' => array()
                );
            }
            
            $goodsList[$item['id']]['products'][] = array(
                'id' => $item['product_id'],
                'outer_id' => $item['outer_id'],
                'spec' => get_spec_name($item['sku_json']),
                'price' => $item['price'],
                'agent2_price' => $item['agent2_price'],
                'agent3_price' => $item['agent3_price'],
                'stock' => $item['stock'],
                'sold_num' => $item['sold_num'],
                'created' => $item['created'],
                'cost' => $item['cost']
            );
        }
        
        $date = date('Y-m-d H:i:s');
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        
        // 读取第一个工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('产品信息');
        
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, 'ID')
        ->setCellValue('B'.$i, '商品名称')
        ->setCellValue('C'.$i, '商家编码')
        ->setCellValue('D'.$i, 'SKU')
        ->setCellValue('E'.$i, '员工价')
        ->setCellValue('F'.$i, '会员价')
        ->setCellValue('G'.$i, '零售价')
        ->setCellValue('H'.$i, '成本')
        ->setCellValue('I'.$i, '产品销量')
        ->setCellValue('J'.$i, '产品库存')
        ->setCellValue('K'.$i, '产品货号')
        ->setCellValue('L'.$i, '创建时间')
        ->setCellValue('M'.$i, '商品销量')
        ->setCellValue('N'.$i, '商品库存')
        ->setCellValue('O'.$i, '商品PV')
        ->setCellValue('P'.$i, '商品UV')
        ->setCellValue('Q'.$i, '昨日销量')
        ->setCellValue('R'.$i, '三日销量')
        ->setCellValue('S'.$i, '七日销量')
        ->setCellValue('T'.$i, '月销量');
        
        foreach($goodsList AS $goodsId=>$goods){
            $i++;
            $worksheet
            ->setCellValue('A'.$i, $goodsId)
            ->setCellValue('B'.$i, $goods['title'])
            ->setCellValue('C'.$i, $goods['outer_id'])
            ->setCellValue('M'.$i, $item['sold_num'])
            ->setCellValue('N'.$i, $item['stock'])
            ->setCellValue('O'.$i, $item['pv'])
            ->setCellValue('P'.$i, $item['uv'])
            ->setCellValue('Q'.$i, $item['yesterday'])
            ->setCellValue('R'.$i, $item['threeday'])
            ->setCellValue('S'.$i, $item['sevenday'])
            ->setCellValue('T'.$i, $item['thiryday']);
            
            // 合并单元格
            $productCount = count($goods['products']);
            if($productCount > 1){
                $mergeLine = $productCount + $i - 1;
                
                $worksheet
                ->mergeCells("A{$i}:A{$mergeLine}")
                ->mergeCells("B{$i}:B{$mergeLine}")
                ->mergeCells("C{$i}:C{$mergeLine}")
                ->mergeCells("M{$i}:M{$mergeLine}")
                ->mergeCells("N{$i}:N{$mergeLine}")
                ->mergeCells("O{$i}:O{$mergeLine}")
                ->mergeCells("P{$i}:P{$mergeLine}")
                ->mergeCells("Q{$i}:Q{$mergeLine}")
                ->mergeCells("R{$i}:R{$mergeLine}")
                ->mergeCells("S{$i}:S{$mergeLine}")
                ->mergeCells("T{$i}:T{$mergeLine}");
            }
            
            foreach ($goods['products'] as $index=>$product){
                if($index > 0){
                    $i++;
                }
                $worksheet->setCellValue('D'.$i, $product['spec'])
                ->setCellValueExplicit('E'.$i, $product['agent2_price'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValueExplicit('F'.$i, $product['agent3_price'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValueExplicit('G'.$i, $product['price'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValueExplicit('H'.$i, $product['cost'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValue('I'.$i, $product['sold_num'])
                ->setCellValue('J'.$i, $product['stock'])
                ->setCellValue('K'.$i, $product['outer_id'])
                ->setCellValue('L'.$i, $product['created']);
            }
        }
        
        $worksheet->getStyle('A1:T'.(count($list)+1))
        ->getAlignment()
        ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        
        // Redirect output to a client’s web browser (Excel2007)
        $text = iconv('UTF-8', 'GB2312', '产品信息');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
?>