<?php 
namespace Common\Model;

use Think\Model;
class BaseModel extends Model{
    private static $agentList = null;
    protected $autoCheckFields = true;
    
    function __construct($name='',$tablePrefix='',$connection=''){
        if(empty($name) || get_class($this) == 'Common\Model\BaseModel'){
            $this->autoCheckFields = false;
        }
        parent::__construct($name, $tablePrefix, $connection);
    }

    /**
     * 获取交易中的用户信息
     * @param array $login 登录session标记
     */
    public function getTradingBuyer($login = null){
        if(is_null($login)){
            $login = session('user');
        }
        
        if(!is_numeric($login['id']) || empty($login['openid'])){
            E('登陆账号异常，请重新关注后再试！');
        }
        
        $sql = "SELECT wx_member.*, IF(ISNULL(parent.agent_level), 0, parent.agent_level) AS parent_level, IF(ISNULL(parent2.id), 0, parent2.id) AS pid2, IF(ISNULL(parent2.agent_level), 0, parent2.agent_level) AS parent2_level
                FROM (
	               SELECT member.id, member.nickname, wx.openid, wx.subscribe, member.sex, member.mobile,
		              member.province_id, member.city_id, member.county_id, member.address AS detail,
                      member.balance, member.no_balance, member.agent_level, member.pid
                   FROM member, wx_user AS wx
                   WHERE member.id = {$login['id']} AND wx.openid = '{$login['openid']}'
                   LIMIT 1
                ) AS wx_member
                LEFT JOIN member AS parent ON parent.id=wx_member.pid
                LEFT JOIN member AS parent2 ON parent2.id=parent.pid";
        $buyer = $this->query($sql);
        if(empty($buyer)){
            session('[destroy]');
        }
        return $buyer[0];
    }
    
    /**
     * 下单时代理价
     * @param unknown $level
     * @param unknown $product
     * @return unknown
     */
    public function getAgentPrice($level, $product){
        if(isset($product['agents'][$level])){
            return $product['agents'][$level]['price'];
        }
        
        $field = 'price';
        if($level == 1){
            $field = $product['agent1_price'] > 0 ? 'agent1_price' : 'agent2_price';
        }else if($level > 1){
            $field = 'agent'.$level.'_price';
        }
        
        if(isset($product[$field])){
            return $product[$field];
        }
        
        E('商品价格不存在：'.$level);
    }
    
    /*代理级别*/
    public function agentLevel($level = ''){
        if(is_null(self::$agentList)){
            $list = $this->query("SELECT id, `level`, title, price_title FROM agent ORDER BY `level`");
            self::$agentList = array();
            foreach($list as $k=>$v){
                switch ($v['level']){
                    case 1:
                    case 2:
                        $v['price_field'] = 'agent2_price';
                        break;
                    case 3:
                        $v['price_field'] = 'agent3_price';
                        break;
                    case 0:
                    default:
                        $v['price_field'] = 'price';
                        break;
                }
                self::$agentList[$v['level']] = $v;
            }
        }
    
        if($level !== ''){
            return self::$agentList[$level];
        }
        return self::$agentList;
    }
    
    public function goodsHandler($goods, $buyer){
        $goods["agent_level"] = $myLevel = $buyer['agent_level'];
        $agentList = $this->agentLevel();
        end($agentList);
        $lastLevel = key($agentList);
        
        // 列表中显示的价格
        $price_prefix = '¥';
        $price_suffix = '';
        
        // 活动标记
        $activePriceTitle = '';
        if(is_numeric($goods['active_id'])){
            if($goods['active_type'] == 1001){ // groupon拼团
                $activePriceTitle = $goods['active_price_title'].'价';
                $goods['progress'] = bcdiv($goods['active_sold'], $goods['active_total'], 2) * 100;
                $goods['link'] = '/h5/goods?id='.$goods['id'].'&from=1001';
                $goods["view_price"] = array(
                    array('title' => $activePriceTitle,'price' => $this->getAgentPrice($myLevel, $goods), 'price_prefix' => $price_prefix,'price_suffix' => $price_suffix),
                    array('title' => $agentList[0]['price_title'],'price' => $goods['original_price'], 'price_prefix' => $price_prefix,'price_suffix' => $price_suffix)
                );
                unset($goods['active_price_title']);
            }else if($goods['active_type'] == 1002){ // zero零元购
                $activePriceTitle = $goods['active_price_title'];
                $goods['progress'] = bcdiv($goods['active_sold'], $goods['active_total'], 2) * 100;
                $goods['link'] = '/h5/goods?id='.$goods['id'].'&from=1002';
                $goods["view_price"] = array(
                    array('title' => $goods['active_price_title'], 'price' => $goods['price']),
                    array('title' => '原　价', 'price' => $goods['original_price'])
                );
                
                unset($goods['active_type']);
                unset($goods['active_price_title']);
            }
        }else{
            $goods['link'] = '/h5/goods?id='.$goods['id'];
            
            // 打标签
            if(isset($goods['tag_id'])){
                if(!is_array($goods['tag_id'])){
                    $goods['tag_id'] = explode(',', $goods['tag_id']);
                }
            
                if(count($goods['tag_id']) > 0){
                    if(in_array(1000, $goods['tag_id'])){
                        $goods['tags'][] = '包邮';
                    }
                }
            }
        }
        
        // 显示价格
        if(IS_GET && !isset($goods["view_price"])){
            if($myLevel == 0){
                $goods["view_price"] = array(
                    array('title' => $agentList[0]['price_title'],'price' => $this->getAgentPrice(0, $goods),'price_prefix' => $price_prefix,'price_suffix' => $price_suffix),
                    array('title' => $agentList[$lastLevel]['price_title'],'price' => '神秘','price_prefix' => '','price_suffix' => '')
                );
            }else{
                $goods["view_price"] = array(
                    array('title' => $agentList[$myLevel]['price_title'], 'price' => $this->getAgentPrice($myLevel, $goods),'price_prefix' => $price_prefix,'price_suffix' => $price_suffix),
                    array('title' => $agentList[0]['price_title'],'price' => $this->getAgentPrice(0, $goods),'price_prefix' => $price_prefix,'price_suffix' => $price_suffix)
                );
            }
        }
        
        // 代理价格
        $goods['agents'] = array();
        foreach($agentList as $level=>$agent){
            $shenmi = $myLevel == 0 && $level==$lastLevel;
            $visible = $activePriceTitle != '' && $level != $lastLevel ? false : (($myLevel == 0 && $level == $lastLevel) || ($myLevel > 0 && $level >= $myLevel));
            $goods['agents'][$level] = array(
                'level'         => $level,
                'title'         => $agent['title'],
                'price'         => $shenmi ? '神秘' : $this->getAgentPrice($level, $goods),
                'price_title'   => $level == 0 || $activePriceTitle == '' ? $agent['price_title'] : $activePriceTitle,
                'visible'       => $visible,
                'price_prefix'  => $shenmi ? '' : '¥',
                'price_suffix'  => ''
            );
        }
        unset($goods['agent2_price']);
        unset($goods['agent3_price']);
        unset($goods['agent4_price']);

        if(is_string($goods["images"])){
            $goods["images"] = json_decode($goods["images"], true);
        }
        if(!empty($goods['sku_json']) && is_string($goods['sku_json'])){
            $goods['sku_json'] = json_decode($goods["sku_json"], true);
        }
        
        // 获取产品信息
        if(isset($goods['products'])){
            $retailPrice = $myRetailPrice = array(-1, 0);
            foreach($goods['products'] as $i=>$product){
                $priceList = isset($product['return']) ? $product['return'] : $product;
                $product['agents'] = array();
                foreach($agentList as $level=>$agent){
                    $shenmi = $myLevel == 0 && $level==$lastLevel;
                    $price = $shenmi ? '神秘' : $this->getAgentPrice($level, $product);
                    
                    // 差价佣金
                    $commission = 0;
                    if($myLevel > 0){
                        if($myLevel == $level){ // 咱俩等级相同
                            if(isset($agentList[$myLevel - 1])){
                                $commission1 = $this->getAgentPrice($myLevel, $priceList);
                                $commission2 = $this->getAgentPrice($myLevel - 1, $priceList);
                                $commission = bcsub($commission1, $commission2, 2);
                                $commission = bcdiv($commission, 2, 2);
                            }
                        }else{
                            $commission1 = $this->getAgentPrice($level, $priceList);
                            $commission2 = $this->getAgentPrice($myLevel, $priceList);
                            $commission = bcsub($commission1, $commission2, 2);
                        }
                    }
                    
                    $product['agents'][$level] = array(
                        'level'         => $level,
                        'title'         => $agent['title'],
                        'price'         => $price,
                        'price_title'   => $level == 0 || $activePriceTitle == '' ? $agent['price_title'] : $activePriceTitle,
                        'price_prefix'  => $shenmi ? '' : '¥',
                        'price_suffix'  => '',
                        'commission'    => $commission    // 佣金
                    );
                }
                unset($product['return']);
                unset($product['agent2_price']);
                unset($product['agent3_price']);
                unset($product['agent4_price']);
                $goods['products'][$i] = $product;
                
                // 最低零售价
                if($retailPrice[0] < 0 || $product['price'] < $retailPrice[0]){
                    $retailPrice[0] = $product['price'];
                }
                // 最高零售价
                if($product['price'] > $retailPrice[1]){
                    $retailPrice[1] = $product['price'];
                }
                
                // 我的最低采购价
                $myPrice = $product['agents'][$myLevel]['price'];
                if($myRetailPrice[0] < 0 || $myPrice < $myRetailPrice[0]){
                    $myRetailPrice[0] = $myPrice;
                }
                // 我的最高采购价
                if($myPrice > $myRetailPrice[1]){
                    $myRetailPrice[1] = $myPrice;
                }
            }
            
            $goods['agents'][0]['price'] = $retailPrice[0] != $retailPrice[1] ? sprintf('%.2f', $retailPrice[0]).'-'.sprintf('%.2f', $retailPrice[1]) : sprintf('%.2f', $retailPrice[0]);
            $goods['agents'][$myLevel]['price'] = $myRetailPrice[0] != $myRetailPrice[1] ? sprintf('%.2f', $myRetailPrice[0]).'-'.sprintf('%.2f', $myRetailPrice[1]) : sprintf('%.2f', $myRetailPrice[0]);
        }
        
        if($goods['quota']*1 === 0){
            $goods['quota'] = $goods['stock'];
        }
        if(isset($goods['buy_quota'])){
            if($goods['buy_quota'] > 0 && $goods['buy_quota'] < $goods['quota']){
                $goods['quota'] = $goods['buy_quota'];
            }
            
            if($goods['day_quota'] > 0 && $goods['day_quota'] < $goods['quota']){
                $goods['quota'] = $goods['day_quota'];
            }
            
            if($goods['every_quota'] > 0 && $goods['every_quota'] < $goods['quota']){
                $goods['quota'] = $goods['every_quota'];
            }
        }
        
        return $goods;
    }
    
    /**
     * 获取微信用户信息
     */
    public function getWXUserConfig($mid, $openid = ''){
        $sql = "SELECT wx_user.mid, wx_user.openid, wx_user.appid, wx_user.subscribe, wx_user.nickname, member.nickname AS `name`, member.pid, member.id
                FROM member
                INNER JOIN wx_user ON member.id=wx_user.mid
                WHERE member.id='{$mid}' 
                ORDER BY subscribe DESC, last_login DESC";
        $list = $this->query($sql);
        if(count($list) == 0){
            return;
        }
        $wxUser = $list[0];
        
        // 找当前openid
        if(!empty($openid) && count($list) > 1){
            foreach($list AS $item){
                if($item['openid'] == $openid){
                    if($item['subscribe'] == 1){
                        $wxUser = $item;
                    }
                    break;
                }
            }
        }

        if($wxUser['subscribe'] != 1){
            return $wxUser;
        }
        
        $wxUser['config'] = get_wx_config($wxUser['appid']);
        return $wxUser;
    }
    
    /**
     * 获取店铺信息
     */
    public function getAllShop(){
        return $this->query("SELECT id, `name` FROM shop");
    }
    
    /**
     * 批量接续商品 - 列表
     * @param unknown $list
     * @param unknown $myLevel
     */
    public function goodsListHandler($_list, $buyer){
        // 活动标记
        $activeTag = $list = array();
        $key = isset($_list[0]['product_id']) ? product_id : 'id';
        foreach($_list as $index=>$goods){
            if(!isset($goods['active_id'])){
                $goods['tag_id'] = explode(',', $goods['tag_id']);
                if(in_array(1001, $goods['tag_id'])){// 众筹拼团
                    $activeTag[1001][$goods[$key]] = $goods;
                }else if(in_array(1002, $goods['tag_id'])){ // 零元购
                    $activeTag[1002][$goods[$key]] = $goods;
                }else{
                    $list[$goods[$key]] = $this->goodsHandler($goods, $buyer);
                }
            }else{
                $list[$goods[$key]] = $this->goodsHandler($goods, $buyer);
            }
        }

        if(isset($activeTag[1001])){  // 众筹拼团
            $resultList = $this->parseGrouponList($activeTag[1001], $buyer);
            foreach ($resultList as $key=>$goods){
                $list[$goods[$key]] = $this->goodsHandler($goods, $buyer);
            }
        }

        if(isset($activeTag[1002])){  // 零元购
            $resultList = $this->parseZeroList($activeTag[1002], $buyer);
            foreach ($resultList as $key=>$goods){
                $list[$goods[$key]] = $this->goodsHandler($goods, $buyer);
            }
        }

        return array_values($list);
    }
    
    /**
     * 众筹拼团处理 - 列表
     */
    private function parseGrouponList($goodsList){
        $first = current($goodsList);
        $key = 'goods_id';
        if(isset($first['product_id'])){ // 是产品
            $key = 'product_id';
            $_goodsId = array();
            foreach ($goodsList as $item){
                if(!in_array($item['goods_id'], $_goodsId)){
                    $_goodsId[] = $item['goods_id'];
                }
            }
        }else{
            $_goodsId = array_keys($goodsList);
        }
        $sql = "SELECT id, quota, title, goods_id, start_time, end_time, price, single, price_range, pic_url, tag_short
                FROM mall_groupon
                WHERE goods_id IN(".implode(',', $_goodsId).") AND end_time>".NOW_TIME;
        
        $activeList = $this->query($sql);
        foreach ($activeList as $active){
            if(NOW_TIME < $active['start_time']){
                if($key != 'product_id'){
                    $goodsList[$active['goods_id']]['tags'][] = $active['tag_short'];
                }
                continue;
            }
            
            // 商品列表
            if($key == 'goods_id'){
                $goods = $goodsList[$active['goods_id']];
                $goods['tags'][] = $active['tag_short'];
                $goods["view_price"] = array(
                    array('title' => $active['tag_short'].'价', 'price' => $active['price']),
                    array('title' => '零售价', 'price' => $goods['price'])
                );
                
                $goods['title'] = $active['title'];
                $goods['price'] = $goods['agent2_price'] = $goods['agent3_price'] = $active['price'];
                if($active['pic_url'] != ''){
                    $goods['pic_url'] = $active['pic_url'];
                }
                if($active['single'] == 0){ // 不用任何优惠
                    $goods['single'] = 1;
                    $goods['score'] = 0;
                }else if($active['single'] == 1){ // 仅可用积分
                    $goods['single'] = 1;
                }
                if($active['quota'] > 0){
                    $goods['quota'] = $active['quota'];
                }
                $goodsList[$active['goods_id']] = $goods;
                continue;
            }
    
            // 产品列表
            $priceList = json_decode($active['price_range'], true);
            foreach ($priceList as $productId=>$data){
                if(!isset($goodsList[$productId])){
                    continue;
                }
                $goods = $goodsList[$productId];
                
                end($data['range']);
                $maxStock = key($data['range']);
                if($data['sold'] >= $maxStock){ // 超卖了
                    $price = current($data['range']);
                    $goods['quota'] = 0;
                }else{
                    foreach ($data['range'] as $num=>$price){
                        if($data['sold'] < $num){
                            $goods['quota'] = $num - $data['sold'];
                            break;
                        }
                    } 
                }
                
                $goods['stock'] = $goods['quota'];
                $discountFee = bcsub($goods['agents'][$goods['agent_level']]['price'], $price, 2);
                $goods['active'] = array(
                    'id'    => $active['id'],
                    'type'  => 1001,
                    'name' => $active['title'],
                    'discount_fee' => $discountFee,
                    'single' => 1,
                    'start_time' => $active['start_time'],
                    'end_time'   => $active['end_time'],
                    'quota'        => $active['quota']
                );
                $goods['discount_details'][] = $goods['active'];
                
                if($active['single'] == 0){ // 不用任何优惠
                    $goods['single'] = 1;
                    $goods['score'] = 0;
                }else if($active['single'] == 1){ // 仅可用积分
                    $goods['single'] = 1;
                }
                
                if($active['quota'] > 0){
                    $goods['buy_quota'] = $active['quota'];
                    $goods['quota'] = $active['quota'];
                }
                
                $goods['title'] = $active['title'];
                $goods['price'] = $goods['agent2_price'] = $goods['agent3_price'] = $price;
                if($active['pic_url'] != ''){
                    $goods['pic_url'] = $active['pic_url'];
                }
                $goodsList[$productId] = $goods;
            }
        }

        return $goodsList;
    }
    
    /**
     * 零元购 - 列表
     */
    private function parseZeroList($goodsList, $buyer){
        $first = current($goodsList);
        $key = 'id';
        if(isset($first['product_id'])){ // 是产品
            $key = 'product_id';
            $_goodsId = array();
            foreach ($goodsList as $item){
                if(!in_array($item['goods_id'], $_goodsId)){
                    $_goodsId[] = $item['goods_id'];
                }
            }
            
        }else if(isset($first['goods_id'])){
            $key = 'goods_id';
        }else{
            $_goodsId = array_keys($goodsList);
        }
        $sql = "SELECT * FROM mall_zero
                WHERE goods_id IN(".implode(',', $_goodsId).") AND end_time>".NOW_TIME;
    
        $activeList = $this->query($sql);
        if(count($activeList) == 0){
            return $goodsList;
        }

        $agentList = $this->agentLevel();
        foreach ($activeList as $active){
            if(NOW_TIME < $active['start_time']){
                if($key != 'product_id'){
                    $goodsList[$active['goods_id']]['tags'][] = $active['tag_name'];
                }
                continue;
            }
            
            // 商品列表
            if($key != 'product_id'){
                $goods = $goodsList[$active['goods_id']];
                $goods["view_price"] = array(
                    array('title' => $active['price_title'],'price' => $active['price']),
                    array('title' => '原　价', 'price' => $goods['price'])
                );
                
                $goods['tags'][] = $active['tag_name'];
                $goods['title'] = $active['title'];
                $goods['single'] = 1;   // 不参与其他优惠
                $goods['score'] = 0;    // 不可使用积分
                $goods['original_price'] = $goods['price'];
                $goods['price'] = $goods['agent2_price'] = $goods['agent3_price'] = $active['price'];
                if($active['pic_url'] != ''){$goods['pic_url'] = $active['pic_url'];}
                if($active['quota'] > 0){$goods['quota'] = $active['quota'];}
                $goodsList[$active['goods_id']] = $goods;
                continue;
            }
    
            $priceList = json_decode($active['products'], true);
            foreach ($priceList as $productId=>$data){
                if(!isset($goodsList[$productId])){
                    continue;
                }
                
                $goods = $goodsList[$productId];

                $myPrice = $this->getAgentPrice($buyer['agent_level'], $goods);
                $discountFee = bcsub($myPrice, $active['price'], 2);
                $goods['active'] = array(
                    'id'           => $active['id'],
                    'type'         => 1002,
                    'name'         => $active['title'],
                    'discount_fee' => $discountFee,
                    'single'       => 1,
                    'start_time'   => $active['start_time'],
                    'end_time'     => $active['end_time'],
                    'quota'        => $active['quota']
                );
                $goods['discount_details'][] = $goods['active'];

                // 覆盖原有数据
                $goods['freight_tid'] = $active['freight_tid'];
                $goods['title'] = $active['title'];
                $goods['single'] = 1;   // 不参与其他优惠
                $goods['score'] = 0;    // 不可使用积分
                $goods['original_price'] = $goods['price'];
                $goods['price'] = $goods['agent2_price'] = $goods['agent3_price'] = $active['price'];
                if($active['quota'] > 0){$goods['quota'] = $active['quota'];}
                $myPrice = $this->getAgentPrice($buyer['agent_level'], $data);
                $goods['attach_postage'] = $myPrice;   //附加邮费

                // 上级差价佣金
                $commission = 0;
                $goods['commission'] = array();
                if($buyer['parent_level'] > 0){
                    if($buyer['agent_level'] == $buyer['parent_level']){
                        if(isset($agentList[$buyer['agent_level'] - 1])){
                            $commission = $this->getAgentPrice($buyer['parent_level']-1, $data);
                            $commission = bcsub($myPrice, $commission, 2);
                            $commission = bcdiv($commission, 2, 2);
                        }
                    }else{
                        $parentPrice = $this->getAgentPrice($buyer['parent_level'], $data);
                        $commission = bcsub($myPrice, $parentPrice, 2);
                    }
                    if($commission > 0){
                        $goods['commission'][$buyer['pid']] = $commission;
                    }
                }
                
                // 游客和经理
                if($buyer['agent_level'] == 0 && $buyer['parent2_level'] == 1){ // 经理
                    if(isset($agentList[$buyer['parent2_level'] + 1])){
                        $commission1 = $this->getAgentPrice($buyer['parent2_level']+1, $data);
                        $commission2 = $this->getAgentPrice($buyer['parent2_level'], $data);
                        $commission = bcsub($commission1, $commission2, 2);
                        $commission = bcdiv($commission, 2, 2);
                    }
                    if($commission > 0){
                        $goods['commission'][$buyer['pid2']] = $commission;
                    }
                }
                
                $goodsList[$productId] = $goods;
            }
        }
        
        return $goodsList;
    }
}
?>