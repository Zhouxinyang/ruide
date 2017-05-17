<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 店铺设置
 * 
 * @author lanxuebao
 *        
 */
class ShopController extends CommonController
{
    private $Model;
    
    function __construct(){
        parent::__construct();
        $this->Model = new \Common\Model\ShopModel();
    }
    
    /**
     * 店铺设置--店铺信息
     */
    public function index(){
        $this->edit();
    }
    
    public function all(){
        if(IS_AJAX){
            $data = $this->Model->getAll(0);
            $this->ajaxReturn($data);
        }
        
        $allType = \Common\Model\StaticModel::shopType();
        $this->assign('allType', $allType);
        $this->display();
    }
    
    /**
     * 添加
     */
    public function add(){
        if(!IS_POST){
            $this->assign('data', array('logo' => C('CDN').'/img/logo.jpg'));
            $this->display('edit');
        }
        
        $user = $_POST['user'];
        if($user['password'] != $user['password2']){
            $this->error('两次密码不一致！');
        }
        
        $userModel = M('users');
        $exists = $userModel->where("username='{$user['username']}'")->count();
        if(!empty($exists)){
            $this->error('该账号已存在！');
        }
        
        //保存店铺信息
        $shop = $_POST['data'];
        $shop['created'] = date('Y-m-d H:i:s');
        $result = $this->Model->add($shop);
        if($result <= 0){
            $this->error('保存失败！');
        }
        
        //保存登录账号信息
        $user['password'] = md5($user['password']);
        $user['shop_id'] = $result;
        $user['status'] = 1;
        
        $userModel->add($user);
        $this->success('保存成功！');
    }
    
    public function edit(){
        $id = is_numeric($_GET['id']) ? $_GET['id'] : $this->user('shop_id');
        
        if(IS_AJAX){
            $up = $_POST;
            $result = $this->Model->where('id='.$id)->save($up);
            if($result === false){
                $this->error('修改失败！');
            }
            $this->success('修改成功！');
        }
        $data = $this->Model->find($id);
        if(empty($data)){
            $this->error('店铺不存在');
        }
        if(empty($data['logo'])){
            $data['logo'] = C('CDN').'/img/logo.jpg';
        }
        $this->assign('data', $data);
        $this->display('edit');
    }
    
    public function delete(){
        $id = I("post.id");
        $res = $this->Model->where("id IN ({$id})")->delete();
        $this->success('删除成功！');
    }
}

?>