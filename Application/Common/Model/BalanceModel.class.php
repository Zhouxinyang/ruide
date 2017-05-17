<?php 
namespace Common\Model;

/**
 * 资金流水modal
 * @author lanxuebao
 *
 */
class BalanceModel extends BaseModel{
    protected $tableName = 'member_balance';
    
    /**
     * 记录个人资金流水
     * @see \Think\Model::add()
     */
    public function add($record){
        $sql = "";
        if(!is_numeric($record['mid'])){
            E('积分会员ID不能为空');
        }
        
        if(empty($record['reason'])){
            E('积分原因不能为空');
        }
        
        if(empty($record['create_time'])){
            $record['create_time'] = date('Y-m-d H:i:s');
        }

        $record['add_balance'] = is_numeric($record['balance']) ? $record['balance'] : 0;
        $record['add_no_balance'] = is_numeric($record['no_balance']) ? $record['no_balance'] : 0;
        $record['money'] = $record['add_balance'] + $record['add_no_balance'];
        
        $this->execute("UPDATE member SET balance=balance+".$record['add_balance'].", no_balance=no_balance+".$record['add_no_balance']." WHERE id=".$record['mid']);
        $balance = $this->query("SELECT balance, no_balance FROM member WHERE id=".$record['mid']);
        $record['balance'] = $balance[0]['balance'];
        $record['no_balance'] = $balance[0]['no_balance'];
        return parent::add($record);
    }
    
    public function getAll($mid,$shopId = null){
        $where = array('mid' => $mid);
        
        $data = array('total' => 0, 'rows' => array());
        $data['total'] = $this->where($where)->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $data['rows'] = $this->where($where)->order('id DESC')->limit($offset, $limit)->select();
        foreach($data['rows'] as $k=>$v){
           $data['rows'][$k]['money'] = ($v['money']>0)?'+'.$v['money']:$v['money'];
        }
        return $data;
    }
    
    /**
     * 获取会员金子流水数据
     */
    public function getMyRecord($where, $offset = 50, $limit = 0){
        $list = $this->field("id, money, balance, no_balance, reason, type, create_time")->where($where)->limit($offset, $limit)->order("id desc")->select();
        
        $shortList = array(
            'agent_up'      => array('级', '#f90'),
            'agent_yjtj'    => array('荐', '#f90'),
            'agent_ejtj'    => array('荐', '#f90'),
            'agent_sjtj'    => array('荐', '#f90'),
            'agent_tj'      => array('荐', '#f90'),
            'agent_jjtj'    => array('荐', '#f90'),
            'order'         => array('订', '#9E9E9E'),
            'transfers'     => array('提', '#FF5722'),
            'order_balance' => array('差', '#f90'),
            'diff_profit'   => array('差', '#f90'),
            'order_refunded'=> array('退', '#00bffe'),
            'lower_cancel'  => array('退', '#00bffe'),
            'tjhy'          => array('赠', '#9E9E9E'),
            'gszs'          => array('赠', '#9E9E9E'),
            'sign'          => array('签', '#f90'),
            'groupon'       => array('筹', '#f90'),
        );
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $theDayBeforeYesterday = date('Y-m-d', strtotime('-2 day'));
        $weekarray = array("周日","周一","周二","周三","周四","周五","周六");
        foreach ($list as &$record){
            $timespan = strtotime($record['create_time']);
            $record['short'] = $shortList[$record['type']][0];
            $record['color'] = $shortList[$record['type']][1];
            $ymd = date('Y-m-d', $timespan);
            
            if($today == $ymd){
                $record['date'] = '今天';
                $record['time'] = date('H:i', $timespan);
            }else if($yesterday == $ymd){
                $record['date'] = '昨天';
                $record['time'] = date('H:i', $timespan);
            }else if($theDayBeforeYesterday == $ymd){
                $record['date'] = '前天';
                $record['time'] = date('H:i', $timespan);
            }else{
                $week = date("w", $timespan);
                $record['date'] = $weekarray[$week];
                $record['time'] = date('m-d', $timespan);
            }
        }
        return $list;
    }
    
    /**
     * 获取所有人员的资金流水
     */
    public function getAllBalance($where = array()){
        $balacne_type = $this->balacne_type();
        $data = array('total' => 0, 'rows' => array());
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        
        $data['total'] = $this->alias('b')
                              ->field('b.*')
                              ->join('member AS m ON b.mid=m.id')
                              ->where($where)
                              ->order('b.id DESC')
                              ->limit($offset, $limit)
                              ->count();
        
        if($data['total'] == 0){
            return $data;
        }
        
        $data['rows'] = $this->alias('b')
                              ->field('b.*,m.nickname')
                              ->join('member AS m ON b.mid=m.id')
                              ->where($where)
                              ->order('b.id DESC')
                              ->limit($offset, $limit)
                              ->select();
        foreach($data['rows'] as $k=>$v){
            $data['rows'][$k]['type'] = $balacne_type[$v['type']];
        }
        
        return $data;
    }
    
    /**
     * 资金流水类型
     */
    function balacne_type($key = null){
        $list = array(
            'agent_up' => '代理升级',
            'agent_yjtj' => '推荐代理',
            'agent_ejtj' => '二级推荐代理',
            'agent_sjtj' => '三级推荐代理',
            'agent_tj' => '推荐代理(历史)',   // 已废弃
            'agent_jjtj' => '间接推荐代理(历史)',   // 已废弃
            'order' => '订单',
            'transfers' => '提现',
            'order_balance' => '订单结算收益',
            'order_refunded' => '订单退款',
            'lower_cancel'   => '下级代理取消订单',
            'tjhy'      => '推荐赠送',
            'gszs'      => '公司赠送',
            'sign'      => '每日签到',
            'handbag_express' => '手包派件签收'
        );
        
        if($key){
            return $list[$key];
        }
        
        return $list;
    }
}
?>