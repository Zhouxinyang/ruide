<?php 
namespace Common\Model;

class StaticModel{
    public function orderEndType($id = null){
        $list = array(
            'pay_timeout'  => '超时未付款',
            'buyer_cancel' => '买家主动取消',
            'quota'        => '超出限购',
            'no_stock'     => '已经缺货无法交易',
            'lost_buyer'   => '无法联系上买家',
            '10' => '无法联系上买家',
            '11' => '买家误拍或重拍了',
            '12' => '买家无诚意完成交易',
            '13' => '已通过银行线下汇款',
            '14' => '已通过同城见面交易',
            '15' => '已通过货到付款交易',
            '16' => '已通过网上银行直接汇款',
            '17' => '已经缺货无法交易'
        );
        
        if(!is_null($id)){
            return $list[$id];
        }
        return $list;
    }
    
    /**
     * 订单状态
     */
    public function orderStatus($status = null){
        $data = array(
            'topay'     => '待付款',
            'tosend'    => '待发货',
            'toout'     => '出库中',
            'sendpart'  => '部分发货',
            'send'      => '已发货',
            'success'   => '已完成',
            'cancel'    => '已关闭'
        );
        
        if(!is_null($status)){
           return $data[$status];
        }
        
        return $data;
    }
    
    /**
     * 1688订单状态
     */
    public function aliStatus($status = null){
        $data = array(
            'toorder'     => '待下单',
            'success'     => '下单成功',
            'end'         => '同步成功',
            'error'       => '订单异常',
            'cancel'      => '已关闭'
        );
        
        if(!is_null($status)){
           return $data[$status];
        }
        
        return $data;
    }
    
    /**
     * 支付方式
     * @param string $id
     * @return Ambigous <string>|multitype:string
     */
    public function payType($id = null){
        $list = array(
            'score'     => '积分兑换',
            'umpay'     => '银行卡支付',
            'aliwap'    => '支付宝付款',
            'codpay'    => '货到付款/到店付款',
            'peerpay'   => '找人代付',
            'presentpay'=> '领取赠品',
            'couponpay' => '优惠兑换',
            'cash'      => '现金结算',
            'wxpay'     => '微信支付',
            'balance'   => '钱包支付'
        );
        
        if(!is_null($id)){
            return $list[$id];
        }
        return $list;
    }
    
    /**
     * 退款原因
     */
    public function refundedReason($id = null){
        $list = array(
             8=> '有瑕疵换货',
             9=> '7天无理由退换货',
            10=> '误拍/重拍',
            11=> '退运费',
            12=> '做工问题',
            13=> '缩水/褪色',
            14=> '大小/尺寸与商品描述不符',
            15=> '颜色/图案/款式与商品描述不符',
            16=> '材质面料与商品描述不符',
            17=> '少件/漏发',
            18=> '卖家发错货',
            19=> '包装/商品破损/污渍',
            20=> '假冒品牌',
            21=> '未按约定时间发货'
        );
        
        if(is_numeric($id)){
            return $list[$id];
        }
        return $list;
    }
    
    /**
     * 退款状态
     */
    public function refundedState($id = 'all'){
        $list = array(
            'no_refund'         => '无退款',
			'refunding'         => '处理中',
			'refundend'         => '已处理',
            'partial_refunding' => '部分退款中',
            'partial_refunded'  => '已部分退款',
            'partial_failed'    => '部分退款失败',
            'full_refunding'    => '全额退款中',
            'full_refunded'     => '已全额退款',
            'full_failed'       => '全额退款失败',
            '0'                 => '无退款',
            '1'                 => '退款申请中',
            '2'                 => '待上传单号',
            '2.1'               => '等待退款',
            '3'                 => '已退款',
            '4'                 => '拒绝退款',
            '5'                 => '已取消退款'
        );
        
        if($id == 'all'){
            return $list;
        }else if(isset($list[$id])){
            return $list[$id];
        }
    }
    
    /**
     * 快递公司
     */
    public function express($onlyExpress = false, $key = ''){
        $_list = array();

        if($onlyExpress == false){
            $_list[] = array('name' => '上门自提', 'code' => 'selffetch');
            $_list[] = array('name' => '无需物流', 'code' => 'virtual');
        }
        
        $_list = include COMMON_PATH.'Conf/express.php';
        
        if($key != ''){
            $list = array();
            foreach($_list as $item){
                $list[$item[$key]] = $item;
            }
            return $list;
        }
        
        return  $_list;
    }
    
    public function shippingType(){
        return array(
            
        );
    }
    
    public function expressWeight($totalWeight, $seller_id = 1){
        $Model = M('shop_express');
        $data = $Model->where("shop_id=".$seller_id)->find();
        
        $templates = json_decode($data['template'], true);
        
        foreach($templates as $i=>$template){
            // 普通地区
            $templates[$i]['post_fee'] = $this->getExpressFee($totalWeight, $template);
            
            // 特殊地区
            foreach($template['special'] as $si=>$item){
                $templates[$i]['special'][$si]['post_fee'] = $this->getExpressFee($totalWeight, $item);
            }
        }
        
        // 快递运费模板
        $list = json_decode($data['express'], true);
        $expressList = $this->express(true, 'id');
        foreach($list as $i=>$item){
            $list[$i]['post_fee'] = 0;
            $list[$i]['name'] = $expressList[$item['id']]['name'];
        }
        
        $data = array(
            'express' => $list,
            'template' => $templates
        );
        return $data;
    }
    
    private function getExpressFee($totalWeight, $template){
        $fee = $totalWeight > 0 ? $template['postage'] : $template['post_fee'];
        if($totalWeight > $template['start']){
            $weight = bcsub($totalWeight, $template['start'], 2);
            $fee += $template['postage_plus'] * ceil($weight/$template['plus']);
        }
        return $fee;
    }
    
    /**
     * 获取订单运费
     * @param unknown $expressId
     * @param unknown $totalWeight
     * @param unknown $province
     * @param unknown $city
     * @return Ambigous <NULL, unknown>
     */
    public function getTradeExpress($expressId, $seller_id = 1, $totalWeight, $province, $city){
        $data = $this->expressWeight($totalWeight, $seller_id);
        $express = null;
        
        $template = null;
        foreach($data['express'] as $item){
            if($item['id'] == $expressId){
                $template = $data['template'][$item['template_id']];
                $express = $item;
                break;
            }
        }

        $fee = $template['post_fee'];
        foreach($template['special'] as $item){
            if(isset($item['areas'][$province])){
                if(count($item['areas'][$province]) == 0 || in_array($city, $item['areas'][$province])){
                    $fee = $item['post_fee'];
                }
                break;
            }
        }
        
        $express['post_fee'] = $fee;
        return $express;
    }
    
    
    /*
     * 店铺类别
     */
    public static function shopType($type = ''){
        $list = array(
            '0'  => '自营',
            '1'  => '代发',
            '2'  => '1688',
            '3'  => '京东'
        );
        
        if(!empty($type)){
            return $list[$type];
        }
        return $list;
    }
    
    public static function skuList(){
        return array(
            '1'     => '颜色',
            '2'     => '尺寸',
            '3'     => '尺码',
            '4'     => '规格',
            '5'     => '款式',
            '6'     => '净含量',
            '7'     => '种类',
            '8'     => '内存',
            '9'     => '版本',
            '10'    => '金重',
            '11'    => '套餐',
            '12'    => '容量',
            '13'    => '上市时间',
            '14'    => '系列',
            '15'    => '机芯',
            '16'    => '适用',
            '17'    => '包装',
            '18'    => '口味',
            '19'    => '产地',
            '20'    => '出行日期',
            '21'    => '出行人群',
            '22'    => '入住时段',
            '23'    => '房型',
            '24'    => '介质',
            '25'    => '开本',
            '26'    => '类型',
            '27'    => '有效期'
        );
    }
    
    /**
     * 获取城市列表
     * @return unknown
     */
    public static function getCityList($code = null){
        static $allCityList;
        if(is_null($allCityList)){
            $allCityList = include COMMON_PATH.'/Model/city.php';
        }
        if(is_numeric($code)){
            return $allCityList[$code];
        }
        return $allCityList;
    }
    
    /**
     * 客服类型
     */
    public static function getCustomerServiceType(){
        return array(
            '1' => '平台咨询',
            '2' => '产品小视频'
        );
    }
}
?>