<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 购物车
 * @author 兰学宝
 *
 */
class CartController extends CommonController
{
	/**
     * 购物车列表
     */
	public function index(){
	    $this->user('id');
	    $jsonList = $this->getList();
	    $this->assign('jsonList', json_encode($jsonList));
		$this->display();
	}
	
	private function getList($buyer){
	    $Model = D('Common/Cart');
	    return $Model->getAll($buyer);
	}

    /**
     * 加入购物车
     */
    public function add(){
        $data = array();
        $data['buyer_id']   = $this->user('id');
        $data['product_id'] = $_POST['id'];
        $data['num']        = $_POST['num'];
        
        $Model = D('Cart');
        $result = $Model->insert($data);
        if($result <= 0){
            $this->error($Model->getError());
        }
        
        $total = $Model->where("buyer_id={$data['buyer_id']}")->count();
        $this->success(array('total' => $total));
    }
    
    /**
     * 删除购物车
     */
    public function delete(){
        $buyerId = $this->user('id');
        $id = I('post.id');
        D('Cart')->where(array("id" => $id, 'buyer_id' => $buyerId))->delete();
        $this->success('已删除');
    }

    /**
     * 更新购物车
     */
    public function update(){
        $buyerId = $this->user('id');
        $id = I('post.id/d');
        $num = I('post.num/d');
        
        D('Cart')->setNum($id, $buyerId, $num);
        $this->success();
    }
    
    /**
     * 获取购物车中的数量
     */
    public function num(){
        $num = 0;
        $buyerId = $this->user('id', false);
        if(is_numeric($buyerId)){
            $num = D('Cart')->getBuyerNum($buyerId);
        }
        
        $this->ajaxReturn(array('num' => $num));
    }
}

?>