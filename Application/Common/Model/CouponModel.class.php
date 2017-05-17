<?php
namespace Common\Model;

class CouponModel extends BaseModel
{
    protected $tableName = 'mall_coupon';
    
    /**
     * 获取优惠券信息(type < 4)
     */
    public function getCoupon($id, $member){
        $where = is_numeric($id) ? "id=".$id : "id IN ({$id})";
        //$where .= " AND type < 4";
        $list = $this->field("id, `code`, title, total, quota, pv, uv, `status`, start_time, end_time, type, meet, value, member_level, single, notice")->where($where)->select();
        
        if(empty($list)){
            $this->error = '优惠券不存在';
            return;
        }
        
        $agents = $this->agentLevel();
        foreach($list as $i=>&$coupon){
            $coupon['errcode'] = 0;
            $coupon['errmsg']  = '';
            
            // 使用条件
            $coupon['condition'] = $coupon['meet'] > 0 ? '商品满'.$coupon['meet'].'元可用' : '下单即可用';
            
            if(NOW_TIME < $coupon['start_time']){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '未开始';
                continue;
            }else if(NOW_TIME >= $coupon['end_time']){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '已过期';
                continue;
            }else if($coupon['status'] != 1){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '已失效';
                continue;
            }
            
            // 会员级别限制
            if($coupon['member_level'] !== ''){
                $allow = explode(',', $coupon['member_level']);
                if(!in_array($member['agent_level'], $allow)){
                    $coupon['errcode'] = 1;
                    $coupon['errmsg']  = $agents[$member['agent_level']]['title'].'不能领取';
                    continue;
                }
            }
            
            // 整体数量限制
            if($coupon['total'] > 0){
                if($coupon['pv'] >= $coupon['total']){
                    $coupon['stock'] = 0;
                }else{
                    $coupon['stock'] = $coupon['total'] - $coupon['pv'];
                }
            }else{
                $coupon['stock'] = 99999;
            }
            
            if($coupon['stock'] == 0){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '您来晚了，优惠券已发放完毕';
                continue;
            }
            
            $coupon['haved'] = 0;   // 用于标记是否增加uv
            // 限制领取张数
            if($coupon['quota'] > 0){
                $sql = "SELECT COUNT(*) AS total FROM member_coupon WHERE mid={$member['id']} AND coupon_id={$coupon['id']} LIMIT ".$coupon['quota'];

                $total = $this->query($sql);
                if(!empty($total)){
                    $coupon['haved'] = $total[0]['total'] > 0 ? 1 : 0;
                    if($total[0]['total'] >= $coupon['quota']){
                        $coupon['errcode'] = 1;
                        $coupon['errmsg']  = '每人最多领取'.$coupon['quota'].'张' ;
                        continue;
                    }
                }
            }else{  // 不限制数量时可用优惠券最多20张
                $sql = "SELECT id, `status` FROM member_coupon WHERE mid={$member['id']} AND coupon_id={$coupon['id']} ORDER BY `status` LIMIT 20";
                $valueList = $this->query($sql);
                if(!empty($valueList)){
                    $coupon['haved'] = 1;
                    $total = 0;
                    foreach ($total as $info){
                        if($info['status'] == 0){
                            $total++;
                        }
                    }
                    
                    if($total >= 20){
                        $coupon['errcode'] = 1;
                        $coupon['errmsg']  = '已达上限，请先使用几张后再来领取';
                        continue;
                    }
                }
            }
        }
        
        if(is_numeric($id)){
            return $list[0];
        }
        return $list;
    }
    
    private function parseCoupon($coupon, $member, $goodsList){
        // 会员级别限制
        if($coupon['member_level'] !== ''){
            $allow = explode(',', $coupon['member_level']);
            if(!in_array($member['agent_level'], $allow)){
                return null;
            }
        }

        $coupon['goods'] = array();
        if($coupon['type'] < 5){    // 优惠券、优惠码、代金券、满减
            if($coupon['range_type'] == 0){ // 全店铺通用
                $coupon['goods'] = array_keys($goodsList);
            }else{
                $ext = json_decode($coupon['range_ext'], true);
                foreach ($goodsList as $goodsId=>$goods){
                    // ["goods":[指定商品id], "cat":[指定类目id], "tag":[指定分组], "except":[指定商品不参与]]
            
                    if(in_array($goodsId, $ext['except'])){
                        continue;
                    }else if(in_array($goodsId, $ext['goods']) || in_array($goods['cat'], $ext['cat']) || !!array_intersect($goods['tag'], $ext['tag'])){
                        $coupon['goods'][] = $goodsId;
                    }
                }
            }
            
            if(count($coupon['goods']) == 0){
                return null;
            }
        }
        
        if($coupon['type'] < 4){    // 优惠券、优惠码、代金券
            
        }else if($coupon['type'] == 4){ // 满减
            $coupon['value'] = json_decode($coupon['value'], true);
        }else if($coupon['type'] == 5){// 限时折扣
            // [[A,B,C],[A,B,C]]
            // 数据结构   A:类型(0商品, 1分组,2类目)    B:表id    C:0不参与,大于0打折，小于0立减
            // 根据设置的顺序商品优先参与第一个类型
            $ext = json_decode($coupon['range_ext'], true);
            foreach ($goodsList as $goodsId=>$goods){
                $discount = null;
                foreach ($ext as $type=>$data){
                    if($data[2] == 0){
                        continue;
                    }else if($data[0] == 0){ // 指定商品
                        if($data[1] == $goodsId){
                            $discount = $data;
                            break;
                        }
                    }else if($data[0] == 1){ // 指定分组
                        if(in_array($data[1], $goods['tag'])){
                            $discount = $data;
                            break;
                        }
                    }else if($data[0] == 2){ // 指定分类
                        if($data[1] == $goods['cat']){
                            $discount = $data;
                            break;
                        }
                    }
                }
        
                if(!is_null($discount)){
                    $coupon['goods'][$goodsId] = $discount[2];
                }
            }
            
            if(count($coupon['goods']) == 0){
                return;
            }
        }
        
        return $coupon;
    }
    
    /**
     * 获取交易中的优惠
     * @param int $shipId
     * @param Member $member
     * @return List
     */
    public function beforePaying($member, $sellerGoods, $couponList = null){
        $shops = array_keys($sellerGoods);
        $shops = implode(',', $shops);
        
        $where = "";
        if(!empty($couponList['vid'])){
            $where = " AND member_coupon.id IN ({$couponList['vid']})";
        }
        
        // 先把这个人已领的优惠券查找出来
        $sql = "SELECT member_coupon.id AS vid, member_coupon.`value` AS vvalue, member_coupon.expire_time,
                    mall_coupon.id, mall_coupon.type, mall_coupon.title,
                    mall_coupon.total, mall_coupon.quota, mall_coupon.meet, mall_coupon.member_level, mall_coupon.single,
                    shop_coupon.shop_id, shop_coupon.range_type, shop_coupon.range_ext
                FROM member_coupon
                INNER JOIN mall_coupon ON mall_coupon.id=member_coupon.coupon_id
                INNER JOIN shop_coupon ON shop_coupon.coupon_id=mall_coupon.id
                WHERE member_coupon.mid={$member['id']} {$where}
                    AND member_coupon.`status`=0
                    AND ".NOW_TIME." BETWEEN mall_coupon.start_time AND member_coupon.expire_time
                    AND shop_coupon.shop_id IN({$shops})
                LIMIT 150";
        $myCouponList = $this->query($sql);

        $where = "sc.shop_id IN({$shops}) AND mc.type>3";
        if(is_array($couponList) && !empty($couponList['id'])){
            $where .= " AND sc.coupon_id IN ({$couponList['id']})";
        }
        
        // 获取店铺活动
        $sql = "SELECT mc.id, mc.type, mc.title, sc.shop_id, sc.range_type, sc.range_ext, 
                    mc.total, mc.quota, mc.meet, mc.`value`, mc.member_level, mc.single
                FROM shop_coupon AS sc
                INNER JOIN {$this->tableName} AS mc ON sc.coupon_id=mc.id
                WHERE {$where}
                    AND ".NOW_TIME." BETWEEN mc.start_time AND mc.end_time 
                    AND mc.`status`=1
                ORDER BY mc.end_time
                LIMIT 100";
        
        $list = $this->query($sql);
        if(!empty($myCouponList)){
            $list = array_merge($list, $myCouponList);
        }

        $data = array(
            'discount'       => array(),    // 限时折扣
            'promotion'      => array(),    // 满减
            'coupon'         => array(),    // 优惠券、优惠码、代金券
        );
        
        $myCoupon = array();
        foreach($list as $i=>$coupon){
            if(isset($myCoupon[$coupon['id']]) && count($myCoupon[$coupon['id']]) == 0){
                continue;
            }
            
            $result = $this->parseCoupon($coupon, $member, $sellerGoods[$coupon['shop_id']]);
            if(is_null($result)){
                continue;
            }
            
            $type = null;
            if($result['type'] < 4){
                $type = 'coupon';
            }else if($result['type'] == 4){
                $type = 'promotion';
            }else if($result['type'] == 5){
                $type = 'discount';
            }else{
                E('未知优惠类型');
            }
            
            if(!isset($data[$type][$result['id']])){
                $data[$type][$result['id']] = array(
                    'id'     => $result['id'],
                    'title'  => $result['title'],
                    'type'   => $result['type'],
                    'single' => $result['single'],
                    'meet'   => $result['meet'],
                    'goods'  => array()
                );
                
                if($result['type'] < 5){   // 优惠券、优惠码、代金券、满减
                    $data[$type][$result['id']]['value'] = $result['value'];
                }else{
                    $data[$type][$result['id']]['quota'] = $result['quota'];
                }
                
                if($result['type'] == 5){
                    $index = count($data[$type]) - 1;
                    foreach ($result['goods'] as $goodsId=>$goods){
                        $discountGoods[$goodsId] = $index;
                    }
                }
            }
            
            $data[$type][$result['id']]['goods'] = $result['type'] < 5 
                ? array_merge($data[$type][$result['id']]['goods'], $result['goods'])
                : $data[$type][$result['id']]['goods'] + $result['goods'];
        }
        
        // 查找优惠券领取资格
        if(count($myCouponList) > 0){
            $couponVList = array();
            foreach ($myCouponList as $item){
                if(!is_array($data['coupon'][$item['id']]['value'])){
                    $data['coupon'][$item['id']]['value'] = array();
                }
                $data['coupon'][$item['id']]['value'][$item['vid']] = $item['vvalue'];
            }
        }
        
        return $data;
    }
    
    /**
     * 领取随机金额优惠券
     * @param unknown $rand
     * @param unknown $mid
     */
    public function getRandCouponValue($coupon, $rand, $member){
        if(empty($rand) || empty($member)){
            E('CouponModel.getRandCouponValue.Exception');
        }
        
        $remainSize   = $rand['num'] - $rand['send_num']; // 剩余数量
        if($remainSize <= 0){
            $this->error = '您来晚了，优惠券已被抢空！';
            return -1;
        }

        $remainMoney = bcsub($rand['total'], $rand['send_total'], 2); // 剩余金额
        // 剩余一张
        $fee = 0;
        if ($remainSize == 1) {
            $fee = $remainMoney;
        }else{
            $remainMoney *= 100;
            $min   = 1;
            $max   = bcdiv($remainMoney, $remainSize, 2) * 2;
            $money = bcmul(mt_rand(1, 99) * 0.01, $max, 2);
        
            $money = $money < $min ? $min : $money;

            $range = implode(',', $coupon['value']);
            $setMin = isset($range[1]) ? $range[0] : 0.01;
            $setMax = isset($range[1]) ? $range[1] : $range[0];
            if($money > $setMax){
                $money = $setMax;
            }
            $fee = bcdiv($money, 100, 2);
        }

        // 马上更新数据，占据位置
        $result = $this->execute("UPDATE mall_coupon_rand SET send_num=send_num+1, send_total=send_total+{$fee} WHERE id='{$rand['id']}' AND send_num+1<=num");
        if($result == 0){
            $this->error = '您手慢了，优惠券已被抢空了！';
            return -1;
        }
        
        $exists = $this->query("SELECT 1 FROM member_coupon WHERE mid={$member['id']} AND coupon_id={$coupon['id']} AND coupon_code='{$rand['id']}' LIMIT 1");
        $uv = count($exists) > 0 ? 0 : 1;
        $this->execute("UPDATE mall_coupon SET pv=pv+1, uv=uv+{$uv} WHERE id=".$coupon['id']);
        
        // 优惠券过期时间
        $expireTime = $rand['expire_day'] > 0 ? strtotime('+'.$rand['expire_day'].' day', NOW_TIME) : $coupon['end_time'];
        if($expireTime > $coupon['end_time']){
            $expireTime = $coupon['end_time'];
        }
        $this->execute("INSERT INTO member_coupon SET mid={$member['id']}, coupon_id={$coupon['id']}, coupon_code='{$rand['id']}', value={$fee}, created='".NOW_TIME."', status=0, expire_time={$expireTime}");
        return $fee;
    }
    
    public function existsMemberCoupon($mid, $couponId, $couponCode){
        $data = $this->query("SELECT * FROM member_coupon WHERE mid={$mid} AND coupon_id='{$couponId}' AND coupon_code='{$couponCode}' LIMIT 1");
        return $data[0];
    }
}
?>