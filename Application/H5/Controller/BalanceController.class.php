<?php
namespace H5\Controller;
use Common\Common\CommonController;

/**
 * 资金流水记录
 * @author 兰学宝
 *
 */
class BalanceController extends CommonController
{
    private  $dayMax  = 500; // 每日最多提现金额
    private  $stepMin = 3600; // 提现间隔秒数
    
    public function index(){
        $user = $this->user("id, balance, no_balance");
        $user['total_balance'] = bcadd($user['balance'],$user['no_balance'], 2);
        $where = array('mid' => $user["id"]);
        $user['can_balance'] = $user['balance'] > 0 ? $user['balance'] : 0;
        
        // 每日最多可提现
        $today = date('Y-m-d');
        $todayAmount = M('wx_transfers')->where("mid='{$user['id']}' AND payment_time BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59' AND result_code='SUCCESS'")->sum('amount');
        $canTransfers = !is_numeric($todayAmount) ? $this->dayMax : $this->dayMax - $todayAmount;
        if($canTransfers <= 0){
            $user['can_balance'] = 0;
        }else if($canTransfers < $user['can_balance']){
            $user['can_balance'] = $canTransfers;
        }
        $user['can_balance'] = bcadd($user['can_balance'], 0, 2);
        
        if(IS_AJAX){
            $limit = I('get.size/d', 0);
            $offset = I('get.offset/d', 50);
        
            $Model = D("Balance");
            $list = $Model->getMyRecord($where, $offset, $limit);
            $result = array('user' => $user, 'rows' => $list);
            $this->ajaxReturn($result);
        }

        $this->assign(array(
            'user' => $user,
            'day_max' => $this->dayMax,
            'step_min' => $this->stepMin
        ));
        $this->display();
    }
    
    /**
     * 转账-提现
     */
    public function transfers(){
        // 数据校验
        $amount = $_POST['amount'];
        if(!is_numeric($amount) || $amount<1 || $amount>$this->dayMax){
            $this->error('单笔积分兑换应在1~'.$this->dayMax.'之间');
        }
        
        $user = $this->user("id,balance,no_balance,member.nickname, openid");
        if($user['balance'] < $amount){
            $this->error('您的积分不足，可兑换积分：'.$user['balance']);
        }
        
        $Model = M('wx_transfers');
        // 提现间隔分钟
        $prevTime = $Model->where("mid=".$user['id'])->max("payment_time");
        $prevTimespan = strtotime($prevTime);
        if($prevTimespan + $this->stepMin >= NOW_TIME){
            $this->error('操作频繁，请'.($this->stepMin - (NOW_TIME - $prevTimespan)).'秒后再试');
        }
        
        // 每日最多可提现
        $today = date('Y-m-d');
        $todayAmount = $Model->where("mid='{$user['id']}' AND payment_time BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59' AND result_code='SUCCESS'")->sum('amount');
        if(is_numeric($todayAmount)){
            if($todayAmount >= $this->dayMax){
                $this->error('每日兑换积分最多'.$this->dayMax);
            }else if($todayAmount + $amount > $this->dayMax){
                $this->error('您今日还可兑换'.sprintf('%.2f', $this->dayMax - $todayAmount).'积分');
            }
        }
        
        // 开始提现
        $wxTransfers = new \Org\WxPay\WXTransfers();
        $wxTransfers->setReUserName($user['nickname']);
        $wxTransfers->setDesc("余额提现");
        $result = $wxTransfers->transfers($user['openid'], $amount);

        // 保存微信提现结果
        $result['mid'] = $user['id'];
        $result['openid'] = $user['openid'];
        $result['amount'] = $amount;
        $result['balance'] = $user['balance'];
        $result['no_balance'] = $user['no_balance'];
        if(empty($result['payment_time'])){
            $result['payment_time'] = date('Y-m-d H:i:s');
        }
        $Model->add($result);
        
        if($result['result_code'] != 'SUCCESS'){
            if($result['error_code'] == 'NOTENOUGH'){
                $this->error('平台余额不足，请稍后重试或联系管理员！');
            }
            $this->error(empty($result['return_msg']) ? '系统繁忙，请稍后重试！' : $result['return_msg']);
        }
        
        // 扣除金额
        D('Common/Balance')->add(array(
            'mid'     => $user['id'],
            'balance' => -$amount,
            'reason'  => '提现扣款',
            'type'    => 'transfers'
        ));
        
        $this->success('已转至您的微信钱包，请注意查收！');
    }
}
?>