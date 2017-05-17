<?php
namespace H5\Controller;

use Common\Common\CommonController;
/**
 * 首页
 * @author lanxuebao
 *
 */
class MallController extends CommonController
{
	/**
     * 商城首页
     */
    public function index(){
        $user = $this->user('id, subscribe');
        $Model = M('mall_banner');
        // 顶部轮播图
        $banners = $Model
                ->where("home !=0 AND is_show = 1")
                ->order("sort desc, id")
                ->cache(true, 600)
                ->select();
        
        $topbanners = $bottombanners = array();
        foreach($banners as $item){
            if($item['home'] == 1){
                $topbanners[] = $item;
            }else{
                $bottombanners[] = $item;
            }
        }
        
        $this->assign(array(
            'user'          => $user,
            'topbanners'   => $topbanners,
            'bottombanners'=> $bottombanners,
            'show_watch_weixin' => IS_WEIXIN && $user['subscribe'] == 0
        ));
        $this->display();
    }
    
    /**
     * 猜你喜欢
     */
    public function like(){
        $userId = $this->user('id');
        $goodsModel = D('Common/Goods');
        $goods_list = $goodsModel->getLikeGoods();
        $this->ajaxReturn($goods_list);
    }
    
    public function login_out(){
        session_start();
        session_destroy();
        session_write_close();
        exit('<script>alert("已注销登录");</script>');
    }
}
?>