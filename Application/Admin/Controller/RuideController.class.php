<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 阿里巴巴
 */
class RuideController extends CommonController
{
    private $Module;
    private $Module2;
    function __construct()
    {
        parent::__construct();
        $this->Module = M('wx_rduser');
        $this->Module2 = M('wx_power');
    }


    public function index(){
        if (IS_AJAX) {
            $where = array();
            if (strlen($_GET['wechat']) > 0) { $where['users.wechat'] = array( 'like', '%' . $_GET['wechat'] . '%' ); }
            if (strlen($_GET['username']) > 0) { $where['users.username'] = array( 'like', '%' . $_GET['username'] . '%'); }
            if(is_numeric($_GET['mobile'])){ $where['users.mobile'] = array( 'like', '%' . $_GET['mobile'] . '%' ); }
            if(is_numeric($_GET['card'])){ $where['users.card'] = array( 'like', '%' . $_GET['card'] . '%' ); }

            $rows = $this->Module
                ->alias('users')
                ->field("users.id, users.username, users.wechat, users.mobile,users.card")
                ->where($where)
                ->select();
            $this->ajaxReturn($rows);
        }
        $this->assign(array(
            'row'         => $rows
        ));
        $this->display();
    }


    public function add(){
        if(IS_POST){
            $data = $_POST['data'];
            $exists = $this->Module->where("card='{$data['card']}'")->count();
            $exists2 = $this->Module->where("wechat='{$data['wechat']}'")->count();
            $exists3 = $this->Module->where("mobile='{$data['mobile']}'")->count();

            if(!empty($exists)){
                $this->error('此人已存在！');
            }
            if(!empty($exists2)){
                $this->error('此微信号已注册！');
            }
            if(!empty($exists3)){
                $this->error('此手机号已注册！');
            }

            $result = $this->Module->add($data);
            if($result > 0){
                $this->success('添加成功！');
            }
            $this->error('添加失败！');
        }
        $this->display();
    }

    public function edit($id = 0){
        if(IS_POST){
            $data = $_POST['data'];
            $data['id'] = intval($data['id']);
            if($data['id'] <= 0){
                $this->error('数据ID异常！');
            }
            $result = $this->Module->save($data);
            if($result >= 0){
                $this->success('已修改！');
            }
            $this->error('修改失败！');
        }

        $data = $this->Module->find($id);
        if(empty($data)){
            $this->error('数据不存在或已被删除！');
        }
        $this->assign(array(
            'data'    => $data,
        ));
        $this->display();
    }


    public function delete($id = 0){
        if(empty($id)){
            $this->error('删除项不能为空！');
        }
        $result = $this->Module->delete($id);
        $where = array( 'rd_uid' => $id );
        $this->Module2->where($where)->delete();
        if($result > 0){
            $this->success('删除成功！');
        }
    }

    public function check($id = 0){
        if (IS_AJAX) {
            $where = array( 'rd_uid' => $id );
            $rows = $this->Module2
                ->alias('power')
                ->field("power.id, power.product, power.position, power.show,power.data")
                ->where($where)
                ->order("power.id desc")
                ->select();

            $total = count($rows);
            $data = array(
                'total' => $total,
                'rows' => $rows
            );
            $this->ajaxReturn($data);
        }
        $this->assign(array(
            "id"          => $id
        ));
        $this->display();

    }

    public function add_sq($uid = 0){
        if(IS_POST){
            $data = $_POST['data'];
            $result = $this->Module2->add($data);
            if($result > 0){
                $this->success('添加成功！',"/admin/ruide/check?id=".$data["rd_uid"]);
            }
            $this->error('添加失败！');
        }
        $this->assign(array(
            "id"          => $uid
        ));
        $this->display("add_sq");
    }

    public function edit_sq($id=0){
        if(IS_POST){
            $data = $_POST['data'];
            $data['id'] = intval($data['id']);
            if($data['id'] <= 0){
                $this->error('数据ID异常！');
            }

            $result = $this->Module2->save($data);
            if($result >= 0){
                $this->success('已修改！',"/admin/ruide/check?id=".$data['rd_uid']);
            }
            $this->error('修改失败！');
        }

        $data = $this->Module2->find($id);
        if(empty($data)){
            $this->error('数据不存在或已被删除！');
        }
        $this->assign(array(
            'data'    => $data,
        ));
        $this->display("edit_sq");
    }

    public function delete_sq($id = 0){
        if(empty($id)){
            $this->error('删除项不能为空！');
        }
        $result = $this->Module2->delete($id);
        if($result > 0){
            $this->success('删除成功！');
        }
    }


}
?>