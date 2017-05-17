<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 商城
 * @author lanxuebao
 *
 */
 
class GoodsController extends CommonController
{
    /**
     * 商品列表
     */
    public function index(){
        $user = $this->user('id, member.nickname, agent_level, pid');
        
        //如果通过分享页面进入，则需要把登录人和分享人进行绑定
        if(is_numeric($_GET['share_mid']) && $_GET['share_mid'] != $user['id'] && $user['agent_level'] == 0 && $user['pid'] == 0){
            $GuanzhuModel = D('Common/Guanzhu');
            $GuanzhuModel->shareGuanzhu($user, $_GET['share_mid']);
        }
        
        $Model = new \Common\Model\GoodsModel();
        $goods = $Model->getDetail($_GET['id']);
        if(empty($goods) || $goods['is_del'] == 1){
            $this->error('商品不存在或已被删除');
        }
        
        // 虚假销量
        $goods['sold_num'] = intval(($goods['sold_num'] + date('ym') + date('hi')) / 30);
        
        if(IS_WEIXIN){
            $userId = $this->user("id");
            // 分享文案
            $shareData = array(
                "title" => $goods['title'],
                "desc" => empty($goods['digest']) ? $goods['title'] : $goods['digest'],
                "link" =>  'http://'.$_SERVER['HTTP_HOST'].'/h5/goods?id='.$goods['id'].'&share_mid='.$userId,
                "imgUrl" => $goods['pic_url']
            );
            
            //获取签名
            $WechatAuth = new \Org\Wechat\WechatAuth();
            $sign = $WechatAuth->getSignPackage();
            
            $this->assign(array('sign' => json_encode($sign), 'share_data' => json_encode($shareData),));
        }
        
        $this->assign(array(
            'user'      => $user,
            'data'      => $goods,
            'other'     => $this->getNum($user['id'], $goods['id'])
        ));
        
        $this->display('index');
    }
    
    /**
     * 获取购物车中产品的数量
     * @param unknown $buyerId
     */
    private function getNum($buyerId, $goodsId){
        $data = array('cart_num' => 0, 'is_collection' => 0);
        $Model = M();
        $cartNum = $Model->query("SELECT COUNT(*) AS total FROM mall_cart WHERE buyer_id=".$buyerId);
        $data['cart_num'] = $cartNum[0]['total'];
        
        $isCoollection = $Model->query("SELECT COUNT(*) AS total FROM member_collection WHERE mid={$buyerId} AND goods_id=".$goodsId);
        $data['is_collection'] = $isCoollection[0]['total'];
        
        return $data;
    }
    
    /**
     * 获取商品sku信息
     * @param unknown $id
     */
    public function skudata($id){
        $userId = $this->user('id');

        $Model = new \Common\Model\GoodsModel();
        $goods = $Model->getSKU($id);
        if(empty($goods) || $goods['is_del'] == 1){
            $this->error('商品不存在或已被删除');
        }
        
        $goods['other'] = $this->getNum($userId, $goods['id']);
        $this->ajaxReturn($goods);
    }
    
    /**
     * 商品详商 和 最近下单记录 
     */
    public function detail(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            exit('商品ID异常');
        }

        $Model = M("mall_goods");
        $detail = $Model->getFieldById($id,'detail');
        exit($detail);
    }
    
    /**
     * 众筹拼团
     */
    public function groupon(){
        $this->index();
    }
}
?>