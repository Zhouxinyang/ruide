<?php
namespace H5\Controller;
use Common\Common\CommonController;

/**
 * 个人中心
 * @author lanxuebao
 *
 */
class PersonalController extends CommonController
{
    public function index(){
        $user = $this->user('wx.headimgurl, member.nickname, member.mobile, member.id, member.balance + member.no_balance AS total_balance, member.agent_level, 
                member.sex, member.province_id, member.city_id, member.county_id, member.address AS detail');
        $agent = $this->agentLevel($user["agent_level"]);
        $user['agent_title'] = $agent['title'];
        
        // 获取订单总数
        $order = $this->getOrderCount($user);
        
        // 个人中心广告
        $advs = $this->advs($user);
        
        $this->assign(array(
            'user'  => $user,
            'order' => $order,
            'agent' => $agent,
            'advs'  => $advs
        ));
        $this->display();
    }
    
    /**
     * 获取订单总数
     * @param unknown $user
     */
    private function getOrderCount($user){
        // 待付款订单
        $sql = "SELECT `status`, COUNT(*) AS num
                FROM mall_trade
                WHERE buyer_id = {$user['id']}
                AND `status` IN ('topay', 'tosend', 'toout')
                GROUP BY `status`
                UNION(
                SELECT refund_state AS `status`, COUNT(*) AS num
                FROM mall_trade
                WHERE buyer_id = {$user['id']}
                AND refund_state IN ('partial_refunding','full_refunding')
                )";
        
        $orderCount = M()->query($sql);
        $order = array('topay' => 0, 'tosend' => 0, 'torefund' => 0, 'total' => '+');
        foreach ($orderCount as $item){
            if($item['status'] == 'topay'){
                $order['topay'] += $item['num'];
            }else if($item['status'] == 'tosend' || $item['status'] == 'toout'){
                $order['tosend'] += $item['num'];
            }else if($item['status'] == 'partial_refunding' || $item['status'] == 'full_refunding'){
                $order['torefund'] += $item['num'];
            }
        }
        foreach ($order as &$num){
            if($num >= 100){
                $num = '+';
            }
        }
        
        return $order;
    }
    
    /**
     * 个人中心广告
     */
    private function advs(){
        $list = M('mall_banner')
                ->field("title, url")
                ->where("personal=1 AND is_show=1")
                ->order("sort DESC")
                ->limit(4)
                ->select();
        
        return $list;
    }
    
    /**
     * 保存个人资料
     */
    public function save(){
        $post = $_POST['data'];
        
        $data = array(
            "id"            => $post['id'],
            'sex'           => $post['sex'],
            "nickname"      => $post['nickname'],
            "mobile"        => $post['mobile'],
            "checknum"        => $post['checknum'],
            "province_id"   => $post['province_id'],
            "city_id"       => $post['city_id'],
            "county_id"     => $post['county_id'],
            "address"       => $post['detail']
        );
        
        #判断如果手机号没改，就不走验证码流程
        $user = $this->user('id, mobile');
        $Model = D("Member");
        
        if ($user['mobile'] != $data['mobile']) {
            $check = session('check');
            
            if(!is_array($check) || $check['num'] != $data['checknum'] || $check['phone'] != $data['mobile']){
                $this->error('验证码错误');
            }
        }
        
        $res = $Model->edit($data);
        if($res > -1){
            session('check', null);
            $this->success("保存成功");
        }
        $this->error('存储失败');
    }
    
    /**
     * 手机号短信验证
     */
    public function check(){
        $phone = I('phone','','/^\d+$/');
        #号码非数字报错
        if (strlen($phone) == 0) {
            $this->error('请输入手机号');
        }
         
        // 判断上次验证码是否未过60秒
        $check = session("check");
        $now = time();
        if (is_array($check) && $now < $check['time']) {
            $this->success('验证码已发送');
        }
        
        $data = $this->user();
        #生成验证码，并存储
        $checknum = rand(100000,999999);
        
        #发送短信验证码
        vendor('TopSDK.SMSSend');
        $res = FcSmsNumSend($data, $phone, $checknum);
        if (isset($res->result)) {
            $smssend = array(
                'uid'   =>  $data['id'],
                'status'    =>  1,
                'remark'    =>  $res->request_id.$phone
            );
            $Model = M('sms_send');
            $Model->add($smssend);

            session("check",array('phone'=>$phone, "num" =>$checknum,"time" =>$now + 60));
            $this->success('已发送');
        } else {
            $smssend = array(
                'uid'   =>  $data['id'],
                'status'    =>  0,
                'remark'    =>  $res->request_id.$phone.$res->sub_msg
            );
            $Model = M('sms_send');
            $Model->add($smssend);
            if ( $res->code == 15 )
                $msg = '发送验证码过于频繁';
            else
                $msg = '发送失败';
            $this->error($msg);
        }
    }
   
    /**
     * 退出登录
     */
    public function login_out(){
        session('[destroy]');
        redirect('/h5/mall');
    }
    
    /**
     * 签到领积分
     */
    public function sign(){
        $mid = $this->user('id');
        $Model = D('Sign');
        $sign = $Model->sign($mid);
        if($sign < 1){
            $this->error($Model->getError());
        }
        
        $this->ajaxReturn($sign);
    }
    
    /**
     * 我的好友
     */
    public function friends(){
        $agents = $this->agentLevel();
           
        if(!IS_AJAX){
            $my = $this->user("member.id, member.nickname AS `name`, member.mobile, member.agent_level, wx.nickname, wx.headimgurl,wx.mid");
            $my['agent_title'] = $agents[$my['agent_level']]['title'];
            $this->assign('my',$my);
            $this->display();
        }
        
        $offset = is_numeric($_GET['offset']) ? $_GET['offset'] : 0;
        $size = is_numeric($_GET['size']) ? $_GET['size'] : 20;
        $pid = $this->user('id');
        $where = "member.pid = $pid";
        
        if(strlen($_GET['kw']) > 0){
            $where .= " AND member.nickname like '%".$_GET['kw']."%' OR wx.nickname like '%".$_GET['kw']."%'";
            if(preg_match('/^1[34578]\d{9}$/', $_GET['kw'])){
                $where .= " OR member.mobile='".$_GET['kw']."'";
            }
        }
        
        $sql = "SELECT * FROM
                (
                    SELECT mbr.*, wx_user.nickname, wx_user.headimgurl
                    FROM
                    (
                		SELECT id, nickname AS `name`, mobile, agent_level, IF(agent_level = 0, 99, agent_level) AS sort
                        FROM member
                        WHERE {$where}
                        ORDER BY sort
                        LIMIT {$offset}, {$size}
                	) AS mbr
                	INNER JOIN wx_user ON wx_user.mid=mbr.id
                	ORDER BY wx_user.last_login desc
                ) AS wx_mbr
                GROUP BY id
                ORDER BY sort";
        $Model = M();
        $data = $Model->query($sql);
        foreach($data as $i=>$item){
            $data[$i]['agent_title'] = $agents[$item['agent_level']]['title'];
        }
        
        $this->ajaxReturn($data);
    }
}
?>