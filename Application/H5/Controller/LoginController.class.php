<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 扫码登陆
 * 
 * @author lanxuebao
 *        
 */
class LoginController extends CommonController
{
    public function index(){
        $mobile = cookie('auth_mobile');
        $redirect = $_GET['redirect'] ? $_GET['redirect']: '/h5/mall';
        $this->assign(array(
            'appid'    => C('WEIXIN.appid'),
            'mobile'   => $mobile,
            'redirect' => $redirect
        ));
        $this->display();
    }
    
    /**
     * 获取验证码
     */
    public function code(){
        $mobile = $_POST['mobile'];
        if(!is_numeric($mobile) || strlen($mobile) != 11){
            $this->error('请输入11位手机号');
        }
        $session = session('user_auth_login');
        if(!empty($session) && !empty($session[$mobile]) && $session[$mobile]['start'] + 60 > NOW_TIME){
            $this->success('验证码已发送');
        }
        $code = rand(100000, 999999);
        
        //发送短信验证码
        vendor('TopSDK.SMSSend');
        $res = FcSmsNumSend(null, $mobile, $code);
        if (isset($res->result)) {
           M('sms_send')->add(array(
                'uid'   =>  0,
                'status'    =>  1,
                'remark'    =>  $res->request_id.$mobile
            ));
        }else{
            $this->error('验证码发送失败，请稍候再试！');
        }
        
        if(empty($session)){
            $session = array();
        }
        $session[$mobile] = array('start' => time(), 'code' => $code);
        session('user_auth_login', $session);
        $this->success('验证码已发送，请注意查收');
    }
    
    /**
     * 校验手机号
     */
    public function exists(){
        $mobile = $_POST['mobile'];
        if(!is_numeric($mobile) || strlen($mobile) != 11){
            $this->error('请输入手机号');
        }
        
        $data = array('errmsg' => '', 'errcode' => 0);
        $sql = "SELECT id, password FROM member WHERE member.mobile='{$mobile}' LIMIT 1";
        $Model = M('member');
        $member = $Model->field("id, password")->where("mobile='{$mobile}'")->find();
        if(empty($member) || empty($member['password'])){
            $data['errcode'] = 1; // 输入验证码
            $this->ajaxReturn($data);
        }
        $this->ajaxReturn($data);
    }
    
    /**
     * 执行登录APP
     */
    public function auth(){
        $mobile = $_POST['mobile'];
        if(!is_numeric($mobile) || strlen($mobile) != 11){
            $this->error('请输入手机号');
        }
        
        if(strlen($_POST['password']) < 6){
            $this->error('密码错误');
        }
        $password = md5($_POST['password']);
        
        $data = array('errmsg' => '', 'errcode' => 0, 'mobile' => $mobile);
        $Model = M();
        $list = $Model->query("SELECT id, mobile, agent_level, nickname, balance, no_balance, password, sex, head_img, wxid FROM member WHERE mobile='{$mobile}' ORDER BY balance DESC");
        
        // 校验验证码
        if(empty($list[0]['password'])){
            $authCode = session('user_auth_login');
            if(empty($_POST['code']) || $authCode[$mobile]['code'] != $_POST['code']){
                $data['errcode'] = 1;
                $data['errmsg'] = '验证码错误';
                $this->ajaxReturn($data);
            }
        }else if($list[0]['password'] != $password){
            $data['errcode'] = 2;
            $data['errmsg'] = '密码错误';
            $this->ajaxReturn($data);
        }
        
        session('user_auth_login', null);
        
        if(empty($list)){   // 添加手机账户
            $member = array(
                'agent_level'=> 0,
                'nickname'   => substr($mobile, 0, 3).'****'.substr($mobile, -4),
                'mobile'     => $mobile,
                'balance'    => 0,
                'no_balance' => 0,
                'sex'        => 0,
                'head_img'   => '',
                'wxid'       => '',
                'first_from' => 'app',
                'from'       => 'app',
                'reg_time'   => NOW_TIME
            );
            
            $sql = "INSERT INTO member SET ";
            foreach ($member as $field=>$value){
                $sql .= "`{$field}`='{$value}',";
            }
            $Model->execute(rtrim($sql, ','));
            $member['id'] = $Model->getLastInsID();
            $list[] = $member;
        }else if(empty($list[0]['password'])){  // 设置密码
            $Model->execute("UPDATE member SET password='{$password}' WHERE mobile='{$mobile}'");
        }
            
        $agents = $this->agentLevel();
        $idList = array();
        foreach($list as $i=>&$item){
            unset($item['password']);
            $item['agent_title'] = $agents[$item['agent_level']]['title'];
            $item['head_txt'] = mb_substr($item['nickname'], -2, null, 'utf8');
            if(empty($item['wxid'])){
                $data['errcode'] = 3;   // 绑定微信
            }
        }
        $data['list'] = $list;
        
        // 直接保存session，登录成功
        if(count($list) == 1 && !empty($list[0]['wxid'])){
            $this->setSession($list[0]);
            $this->ajaxReturn($data);
        }
        
        session('user_waitting_login', $list);
        $this->ajaxReturn($data);
    }
    
    private function setSession($data){
        session('user_waitting_login', null);
        session('user', array(
            'id'         => $data['id'],
            'openid'     => $data['wxid'],
            'login_type' => 2
        ));
        
        cookie('auth_mobile', $data['mobile'], array(
            'expire'    =>  3600 * 24 * 3,
            'path'      =>  '/h5', // cookie 保存路径
        ));
    }
    
    public function confirm(){
        $mid = $_POST['mid'];
        $loginList = session('user_waitting_login');
        if(!is_numeric($mid) || empty($loginList)){
            $this->error('登录超时', '/h5/mall');
        }
        
        $member = null;
        foreach ($loginList as $item){
            if($item['id'] == $mid){
                $member = $item;
            }
        }
        
        if(empty($member)){
            $this->error('ID异常', '/h5/mall');
        }
        
        $this->setSession($member);
        $this->success();
    }
    
    /**
     * 
     */
    public function bind(){
        $loginList = session('user_waitting_login');
        if(empty($loginList)){
            $this->error('登录超时');
        }
        
        $wechatAuth = new \Org\Wechat\WechatAuth();
        $token = $wechatAuth->getAccessToken('code', $_POST['code']);
        if(empty($token)){
            $this->error('获取tokent失败');
        }
        
        $openid = $token['openid'];
        $userInfo = $wechatAuth->getUserInfo($token);
        
        if (isset($userInfo['errcode'])) {
            $this->error('获取用户信息失败：'. $userInfo['errmsg']);
        }
        
        $Model = M('wx_user');
        
        foreach ($loginList as &$info){
            if(empty($info['wxid'])){
                $Model->execute("UPDATE member SET wxid='{$openid}',head_img=IF(head_img='' OR ISNULL(head_img), '".addslashes($userInfo['headimgurl'])."', head_img) WHERE id=".$info['id']);
            }
            $info['wxid'] = $openid;
        }
        
        $wxUser = $Model->find($openid);
        if(empty($wxUser)){
            $Model->add(array(
                'mid'        => $loginList[0]['id'],
                'openid'     => $userInfo['openid'],
                'nickname'   => $userInfo['nickname'],
                'sex'        => $userInfo['sex'],
                'province'   => $userInfo['province'],
                'city'       => $userInfo['city'],
                'country'    => $userInfo['country'],
                'headimgurl' => $userInfo['headimgurl'],
                'unionid'    => $userInfo['unionid'],
                'appid'      => $wechatAuth->config['appid'],
                'created'    => NOW_TIME,
                'last_login' => NOW_TIME
            ));
        }
        
        if(count($loginList) == 1){
            $this->setSession($loginList[0]);
        }
        
        $this->success($loginList);
    }
}
?>