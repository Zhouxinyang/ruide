<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 商城
 * 
 * @author lanxuebao
 *        
 */
class ListController extends CommonController
{
    public function index(){
        $user = $this->user('id');
        $tag = '';
        
        $where = "home=1";
        if(is_numeric($_GET['tag'])){
            $tag = $_GET['tag'];
            $where = "FIND_IN_SET({$tag}, seat)>0";
        }
        
        // 顶部轮播图
        $banners = M("mall_banner")
            ->where($where." AND is_show = 1")
            ->order("sort desc, id")
            ->limit("0, 7")
            ->select();
        
        $this->assign(array(
            'banners'      => $banners,
            'title'         => $_GET['title']
        ));
        
        if($_GET['tag_id'] == 1001){
            $this->display('1001');
        }else if($_GET['tag_id'] == 1002){
            $this->display('1002');
        }else{
            $this->display();
        }
    }

    /**
     * 商城首页产品列表
     */
    public function search(){
        $myLevel = $this->user('agent_level');
        $goodsModel = D('Common/Goods');
        $goods_list = $goodsModel->getGoodsList($myLevel);
        $this->ajaxReturn($goods_list);
    }
}
?>