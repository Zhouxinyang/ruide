<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 收货地址api
 * @author lanxuebao
 *
 */
class AddressController extends CommonController
{
	public function json(){
		$Model = D('City');
		$pid = I('get.pid/d', 1);
		
		$list = $Model->select($pid);
		$this->ajaxReturn($list);
	}
	
	/**
	 * 我的收货地址列表
	 */
	public function my(){
		$mid = $this->user('id');
		$Model = D('City');
		$data = $Model->getMyCity($mid);

		$this->ajaxReturn($data);
	}
	
	/**
	 * 删除收货地址
	 */
	public function delete(){
		$id = I('post.id/d', 0);
		if($id > 0){
			$mid = $this->user('id');
			M('member_address')->where("mid='{$mid}'")->delete($id);
		}
		
		$this->success();
	}
	
	/**
	 * 编辑收货地址
	 */
	public function edit(){
		$data = $_POST;
		$data["mid"] = $this->user("id");
		$Model = D('City');
		
		$result = $Model->editCity($data);
		
		$this->success($result);
	}
}
?>