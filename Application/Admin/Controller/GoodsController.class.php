<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 商品
 *
 * @author 兰学宝
 */
class GoodsController extends CommonController
{
    private $shopId;
    private $all_shop;
    function __construct(){
        parent::__construct();
        $this->shopId = $this->user('shop_id');
        
        $this->all_shop = \Common\Common\Auth::get()->validated('admin','shop','all');
        
        if(!IS_AJAX && IS_GET){
            $sort_access = \Common\Common\Auth::get()->validated('admin','goods','saveSort');
            $this->assign('sort_access', $sort_access);
        }
    }
    
    /**
     * 出售中的商品
     *
     * @author lanxuebao
     */
    public function index(){
        $where = null;
        if(IS_AJAX){
            $where = array(
                'shop_id' => $this->all_shop == true ? $_GET['shop_id'] : $this->shopId,
                'tag' => I('get.tag'),
                'title' => $_GET['title'],
                'action'  => 'index',
                'sort'    => $_GET['sort'],
                'order'    => $_GET['order']
            );
        }
        $this->showGoodsList($where);
    }
    
    private function showGoodsList($_where){
        if(IS_AJAX){
            $Model = D('Goods');
            $data = $Model->getProductList($_where);
            $this->ajaxReturn($data);
        }
        
        //查询商品分组
        $Module = M("mall_tag");
        $goods_tag = $Module->select();
        
        $shop = $this->shops();
        $this->assign(array(
            'goods_tag' => $goods_tag,
            'shop'     => $shop,
            'all_shop' => $this->all_shop
        ));
        
        if(!empty($_GET['tag'])){
            $this->assign("tag", $_GET['tag']);
        }
        $this->display('index');
    }
    
    /**
     *  已售罄的商品
     *  @author lanxuebao
     */
    public function soldout(){
        $where = null;
        if(IS_AJAX){
            $where = array(
                'shop_id' => $this->all_shop == true ? $_GET['shop_id'] : $this->shopId,
                'tag' => I('get.tag'),
                'title' => $_GET['title'],
                'action'  => 'soldout',
                'sort'    => $_GET['sort'],
                'order'    => $_GET['order']
            );
        }
        $this->showGoodsList($where);
    }
    
    /**
     * 仓库中的商品
     * @author lanxuebao
     */
    public function no_display(){
        $where = null;
        if(IS_AJAX){
            $where = array(
                'shop_id' => $this->all_shop == true ? $_GET['shop_id'] : $this->shopId,
                'tag' => I('get.tag'),
                'title' => $_GET['title'],
                'action'  => 'no_display',
                'sort'    => $_GET['sort'],
                'order'    => $_GET['order']
            );
        }
        $this->showGoodsList($where);
    }
    
    /**
     * 删除
     */ 
    public function delete(){
        $id = I('post.id');
        $Model = D('Goods');
        $result = $Model->deleteById($id, $this->all_shop ? null : $this->shopId);
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success('删除成功！');
    }
    
    
    /**
     * 批量上架
     */ 
    public function takeUp(){
        $ids = I('post.ids');
        $Model = D('Goods');
        $result = $Model->takeUp($ids, $this->shopId);
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success('已上架');
    }
    
    /**
     * 批量下架
     * @author zhanghaipeng
     */ 
    public function takeDown(){
        $ids = I('post.ids');
        $Model = D('Goods');
        $result = $Model->takeDown($ids, $this->all_shop ? null : $this->shopId);
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success('已下架');
    }
    
    /**
     * 修改分组
     * @author lanxuebao
     */
    public function saveTag(){
        $goodsId = $_REQUEST['id'];
        if(!is_numeric($goodsId)){
            $this->error('请选择单个商品');
        }
        
        $Model = M('mall_goods');
        $goods = $Model->find($goodsId);
        if(empty($goods)){
            $this->error('商品ID不存在');
        }
        $goods['tag_id'] = explode(',', $goods['tag_id']);
        
        if(IS_POST){
            if(count($_POST['tag_id']) > 3){
                $this->error('最多可选3个分组');
            }
            
            $tagList = array();
            foreach ($goods['tag_id'] as $tagId){
                if($tagId < 10000){
                    $tagList[] = $tagId;
                }
            }
            foreach ($_POST['tag_id'] as $tagId){
                if(!in_array($tagId, $tagList) && is_numeric($tagId)){
                    $tagList[] = $tagId;
                }
            }
            
            $Model->execute("UPDATE mall_goods SET tag_id='".implode(',', $tagList)."' WHERE id=".$goods['id']);
            $this->success("修改分组成功");
        }
        
        //查询商品分组
        $tagList = $Model->query("SELECT * FROM mall_tag WHERE id>9999");
        $this->assign(array(
            'tagList' => $tagList,
            'goods'   => $goods
        ));
        $this->display();
    }
    
    /**
     * 会员折扣
     * @author lanxuebao
     */
    public function discount(){
        $id = I('post.id');
        $join = I('post.join');
        $Model = D('Goods');
        $result = $Model->discount($id, $join);
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success($join ? '参与会员折扣成功' : '已取消参与会员折扣');
    }
    
    /**
     * 入库出库
     */
    public function storage(){
        $Model = D('Goods');
        if(IS_POST){
            $products = $_POST['products'];
            $goodsId = $_POST['id'];
            $stock = $Model->updateProducts($goodsId, $products);
            $this->success(array('stock' => $stock));
        }
        $id = I('get.id');
        $goods = $Model->getById($id);
        unset($goods['detail']);
        $this->assign('goods', $goods);
        $this->display();
    }
    
    /**
     * 复制商品 (复制商品  分组及商品下产品的数据)
     * @author lanxuebao
     */
    public function copy(){
        $id = I('post.id');
        if(empty($id)){
            $this->error("商品ID错误");
        }
        $Model = D('Goods');
        $result = $Model->copy($id);
        if($result < 0){
            $this->error($Model->getError());
        }
        
        $this->success("复制成功");
    }
    
    /**
     * 商品入库
     */
    public function editStocks(){
        $model_product = M("mall_product");
        $model_goods = M("mall_goods");
        if(IS_POST){
            $data_type = $_POST['data_type'];
            if($data_type == 1){
                $stocks = $_POST['stocks'];
                if(empty($stocks)){
                    $this->error('商品库存未填写');
                }else if(!is_numeric($stocks)){
                    $this->error('商品库存格式不对,请重新输入');
                }
                $product_id = $_POST['product_id'];
                $goods_id = $_POST['goods_id'];
                
                $sql_product = "update mall_product set stock=IF(stock + {$stocks} > 0, stock + {$stocks}, 0)where id={$product_id};";
                $model_product->execute($sql_product);
                $sql_goods = "update mall_goods set stock=IF(stock + {$stocks} > 0, stock + {$stocks}, 0)where id={$goods_id};";
                $model_goods->execute($sql_goods);
                
                $this->success();
            }else if($data_type == 2){
                $goods_id = $_POST['goods_id'];
                $product_ = $_POST['products'];
                //处理	每个产品的库存的加减
                foreach($product_ as $key => $value){
                    if(is_numeric($value['stock']) && $value['stock'] != 0){
                        $product_id = $value['id'];
                        $stocks = $value['stock'];
                        
                        $sql_product = "update mall_product set stock=IF(stock + {$stocks} > 0, stock + {$stocks}, 0)where id={$product_id};";
                        $model_product->execute($sql_product);
                    }
                }
                //处理	商品的库存的加减
                $goods_stock = $model_product->where("goods_id = %d",$goods_id)->sum("stock");//所有商品下的产品的库存和
                $data['stock'] = $goods_stock;
                $model_goods->where("id = %d",$goods_id)->save($data);
                
                $this->success();
            }
        }
        //查询商品下面的产品是否含有商品规格
        $id = I('get.goods_id');
        if(empty($id)){
            $this->error("商品ID错误");
        }
        $list = $model_product->where("goods_id = %d",$id)->find();
        if(empty($list['sku_json'])){
            //查询商品信息
            $goods['data_type'] = 1;//代表无规格
            $goods['stock'] = $list['stock'];//库存
            $goods['price'] = $list['price'];//价格
            $goods['outer_id'] = $list['outer_id'];//库存
            $goods['goods_id'] = $id;
            $goods['product_id'] = $list['id'];
            $this->ajaxReturn($goods);
        }else{
            $data = $this->Model->find($id);
            $products = $this->Model->query("SELECT * FROM mall_product WHERE goods_id={$id} ORDER BY id");
            $goods = array(
                'sku_json'  => array(), 
                'cat_id'    => $data['cat_id']
            );
            $goods['data_type'] = 2;//代表有规格
            $goods['sku_json'] = json_decode($data['sku_json'], true);
            $goods['product']['products'] = $products;
            $goods['goods_id'] = $id;
            $goods['pay_type'] = $data['pay_type'];
            $this->ajaxReturn($goods);
        }
    }
    
    private function assingEdit(&$goods){
        //获取商品类目信息
        $Model = M('mall_category');
        $_categorys = $Model->order("sort DESC")->select();
        $categorys = array();
        foreach ($_categorys as $item){
            $categorys[$item['pid']][] = $item;
        }
        $this->assign('categorys', $categorys);
        
        // 代理
        $Model = new \Common\Model\BaseModel();
        $allAgentList = $Model->agentLevel();
        $agentList = array('price' => $allAgentList[0]);
        for($i=count($allAgentList)-1; $i>0; $i--){
            if(isset($agentList[$allAgentList[$i]['price_field']])){
                $agentList[$allAgentList[$i]['price_field']]['title'] .= '/ '.$allAgentList[$i]['title'];
                continue;
            }
            $agentList[$allAgentList[$i]['price_field']] = $allAgentList[$i];
        }
        $this->assign('allAgentList', $allAgentList);
        $this->assign('agentList', array_values($agentList));
        
        // 不是淘系商品
        if(empty($goods['tao_id'])){
            $list = D('Sku')->getAll();
            $this->assign('sku_list', $list);
        }

        $ExpressModel = new \Common\Model\ExpressModel();
        $freightTemplate = $ExpressModel->getShopFreightTemplates($goods['shop_id'], $goods['freight_tid']);
        
        // 运费模板
        $freightList = $this->get('freightList');
        if (!empty($freightList)){
            $freightTemplate = array_merge($freightList, $freightTemplate);
        }
        $this->assign('freightList', $freightTemplate);
    }
    
    /**
     * 添加商品
     */
    public function add(){
        $myShopId = $this->user('shop_id');
        if(IS_POST){   
            $goods = $this->postGoods();
            if(!is_numeric($goods['tao_id'])){
                M('alibaba_goods')->save(array('tao_id' => $goods['tao_id'], 'last_update' => date('Y-m-d H:i:s')));
            }
            $Model = D('Goods');
            $result = $Model->insert($goods);
            
            if($result > 0) 
                $this->success('添加成功');
            
            $this->error('添加失败：'.$Model->getError());
        }

        $data = array(
            'tao_id'    => '',
            'cat_id'     => '',
            'pay_type'   => 1,
            'is_virtual' => 0,
            'points'     => 0,
            'total_stock' => 0,
            'post_fee' => 0,
            'invoice' => 0,
            'warranty' => 0,
            'returns' => 0,
            'pay_type' => 1,
            'buy_quota' => 0,
            'day_quota' => 0,
            'every_quota' => 0,
            'sold_time' => 0,
            'tag_id'   => '',
            'weight'    => '',
            'visitors_quota' => 1,
            'sku_json'  => array(),
            'products'  => array(),
        );
        $tao_id = I('get.tao_id', null);
        if(is_numeric($tao_id)){
            $tao = $this->getTaoGoods($tao_id);
            $data = array_merge($data, $tao);
        }
        
        $data['shop_id'] = $myShopId;
        $this->assingEdit($data);
        $this->assign('data', $data);
        $this->display('edit');
    }
    
    /**
     * 添加淘宝商品
     */
    private function getTaoGoods($taoId){
        $Model = new \Common\Model\AlibabaModel();
        $Model->syncGoods($taoId);
        
         $data = M('alibaba_goods')->find($taoId);
         if(empty($data)){
             $this->error('此淘ID不符合本系统零售商品规则，不建议添加');
         }else if($data['expire_time'] <= NOW_TIME){
             $this->error('商品已过期');
         }else if($data['type'] != 'wholesale'){
             $this->error('此商品不支持在线批发');
         }else if($data['status'] != 'published'){
             $this->error('商品未上架');
         }else if($data['min_order'] > 1){
             $this->error('最小起定量'.$data['min_order'].$data['unit']);
         }

         $data['sku_json'] = json_decode($data['sku_json'], true);
         $data['images'] = json_decode($data['images'], true);
         $data['attributes'] = json_decode($data['attributes'], true);
         
         if(!empty($data['products'])){
             $products = json_decode($data['products'], true);
             foreach($products as $i=>$product){
                 $products[$i]['weight'] = $data['weight'];
                 $products[$i]['cost'] = $product['price'];
                 $products[$i]['price'] = empty($product['retail_price']) ? $product['price'] * 1.4 : $product['retail_price'];
                 $products[$i]['agent2_price'] = $product['price'] * 1.16;
                 $products[$i]['agent3_price'] = $product['price'] * 1.2;
                 $products[$i]['agent4_price'] = '';
                 $products[$i]['original_price'] = '';
                 $products[$i]['outer_id'] = '';
                 $products[$i]['id'] = '';
                 
                $temp = array();
                foreach($product['sku_json'] as $sku){
                    $temp[] = $sku['k'];
                    $temp[] = $sku['v'];
                }
                sort($temp);
                $products[$i]['taokey'] = md5(implode('', $temp));
             }
         }else{
             $data['weight'] = $data['weight'];
             $data['cost'] = $data['price'];
             $data['price'] = '';
             $data['agent2_price'] = '';
             $data['agent3_price'] = '';
             $data['agent4_price'] = '';
             $data['original_price'] = '';
             $data['outer_id'] = '';
         }
         $data['products'] = $products;
         $data['title'] = $data['subject'];
         $data['freight_tid'] = 'T'.$data['freight_tid'];
         
         $freightTemplate = array(
            array('id' => $data['freight_tid'], 'name' => '淘系'.($data['freight_tid'] == 'T1' ? '包邮' : '模板'))
         );
         $this->assign('freightList', $freightTemplate);
         return $data;
    }
    
    /**
     * 编辑
     */
    public function edit($id){
        if(IS_GET){
            if(!is_numeric($id)){
                $this->error('商品ID不能为空');
            }
            
            $Model = D('Goods');
            $goods = $Model->find($id);
            if(empty($goods)){ $this->error('商品不存在');}
            
            // 产品
            $products = $Model->query("SELECT * FROM mall_product WHERE goods_id='%d'", $id);
            foreach($products as $i=>$item){
                $products[$i]['sku_json'] = ($item['sku_json'] && $item['sku_json'] != '[]') ? json_decode($item['sku_json'], true) : array();
            }
            $goods['products'] = $products;
            
            // 格式化数据
            if($goods['sold_time'] > 0){
                $goods['sold_time'] = date('Y-m-d H:i:s', $goods['sold_time']);
            }
            
            // 图片
            $goods['images'] = json_decode($goods['images'], true);
            
            // sku组合
            $goods['sku_json'] = $goods['sku_json'] ? json_decode($goods['sku_json'], true) : array();
            
            if(!empty($goods['tao_id'])){ // 如果是淘系商品，则重新获取淘系商品
                $new = $this->getTaoGoods($goods['tao_id']);
                
                // 同步规格
                $goods['sku_json'] = $new['sku_json'];
                $products = array();
                foreach($goods['products'] as $item){
                    $temp = array();
                    foreach($item['sku_json'] as $sku){
                        $temp[] = $sku['k'];
                        $temp[] = $sku['v'];
                    }
                    sort($temp);
                    $taoKey = md5(implode('', $temp));
                    $products[$taoKey] = $item;
                }
                
                foreach($new['products'] as $i=>$item){ 
                    if(isset($products[$item['taokey']])){
                        $new['products'][$i]['outer_id'] = $products[$item['taokey']]['outer_id'];
                        $new['products'][$i]['id'] = $products[$item['taokey']]['id'];
                    }
                }
                
                $goods['products'] = $new['products'];
                if (!strlen($goods['detail'])>0) {
                    $goods['detail'] = $new['detail'];
                }
            }
            $goods['parameters']=json_decode($goods['parameters'],true);
            $this->assingEdit($goods);
            $this->assign('data', $goods);
            $this->display('edit');
        }
        $goods = $this->postGoods();
        
        if (!is_null($goods['tao_id'])){
            M('alibaba_goods')->save(array('tao_id' => $goods['tao_id'], 'last_update' => date('Y-m-d H:i:s')));
        }
        $Model = D('Goods');
        $result = $Model->update($goods);
        
        if($result >= 0)
            $this->success('已保存');
        $this->error('保存失败：'.$Model->getError());
    }
    
    /**
     * 添加/编辑商品POST数据
     */
    private function postGoods(){
        $post = array(
            'id'            => $_GET['id'],
            'cat_id'        => $_POST['cat_id'],
            'tag_id'        => explode(',', $_POST['tag_id']),
            'is_virtual'    => $_POST['is_virtual'] ? 1 : 0,
            'visitors_quota'=> $_POST['visitors_quota'] ? 1 : 0,
            'pay_type'      => $_POST['pay_type'],
            'stock'         => $_POST['stock'],
            'weight'        => $_POST['weight'],
            'hide_stock'    => $_POST['hide_stock'] ? 1 : 0,
            'outer_id'      => $_POST['outer_id'],
            'title'         => $_POST['title'],
            'price'         => $_POST['price'],
            'original_price'=> $_POST['original_price'],
            'score'         => is_numeric($_POST['score']) && $_POST['score'] > 0 ? $_POST['score'] : 0,
            'points'        => is_numeric($_POST['points']) && $_POST['points'] > 0 ? $_POST['points'] : 0,
            'post_fee'      => $_POST['post_fee'],
            'buy_quota'     => $_POST['buy_quota'],
            'day_quota'     => $_POST['day_quota'],
            'every_quota'   => $_POST['every_quota'],
            'sold_time'     => $_POST['sold_time'],
            'member_discount'=> $_POST['member_discount'] ? 1 : 0,
            'invoice'       => $_POST['invoice'] ? 1 : 0,
            'warranty'      => $_POST['warranty'] ? 1 : 0,
            'returns'       => $_POST['returns'] ? 1 : 0,
            'detail'        => $_POST['detail'],
            'images'        => $_POST['images'],
            'is_display'    => $_POST['is_display'] ? 1 : 0,
            'template_id'   => $_POST['template_id'],
            'sku_json'      => $_POST['sku_json'],
            'products'      => $_POST['products'],
            'agent2_price'  => $_POST['agent2_price'],
            'agent3_price'  => $_POST['agent3_price'],
            'digest'        => $_POST['digest'],
            'tao_id'        => is_numeric($_POST['tao_id']) ? $_POST['tao_id'] : null,
            'shop_id'       => $_POST['shop_id'],
            'freight_tid'   => $_POST['freight_tid'],
            'remote_area'   => $_POST['remote_area'],
            'parameters'    => array(),
            'send_place'    => is_numeric($_POST['send_place']) ? $_POST['send_place'] : 0 ,
            'cost'          => $_POST['cost']
        );
        
        // 自定义参数
        foreach ($_POST['parameters'] as $value){
            if(empty($value['key']) || empty($value['value'])){
                continue;
            }
            
            $post['parameters'][] = array($value['key'], $value['value']);
        }
        $post['parameters'] = json_encode($post['parameters']);
        
        foreach ($post['tag_id'] as $i=>$tagId){
            if($tagId < 10000){
                unset($post['tag_id'][$i]);
            }
        }
        return $post;
    }
    
    /**
     * 保存排序
     */
    public function saveSort(){
        if(is_array($_POST['list']) && count($_POST['list']) > 0){
            $Model = M();
            foreach($_POST['list'] as $id=>$sort){
                $Model->execute("UPDATE mall_goods_sort SET sort='".$sort."' WHERE goods_id=".$id.";");
            }
        }
        $this->success('已保存排序！');
    }
    
    /**
     * 导出产品
     */
    public function export(){
        $_where = array(
            'shop_id' => $this->all_shop == true ? $_GET['shop_id'] : $this->shopId,
            'tag' => I('get.tag'),
            'title' => $_GET['title'],
            'action'  => $_GET['action'],
            'sort'    => $_GET['sort'],
            'order'    => $_GET['order']
        );
        
        $Model = D('Goods');
        $Model->export($_where);
    }
    
    /**
     * 商品返款信息
     */
    public function feedback(){
        $goods_id = $_REQUEST['goods_id'];
        $tid      = $_REQUEST['tid'];
        if(IS_GET){
            $this->assign(array(
                'goods_id' => $goods_id,
                'tid'      => $tid
            ));
            $this->display();
        }
        
        $add = array(
            'goods_id'  => $goods_id,
            'user_id'   => $this->user('id'),
            'question'  => $_POST['question'],
            'created'  => date('Y-m-d H:i:s'),
        );
        
        if(!empty($tid)){
            $add['tid'] = $tid;
        }
        
        M('mall_goods_feedback')->add($add);
        $this->success('已保存！');
    }
    
    /**
     * 专属客服
     */
    public function kefu(){
        $goods = $_REQUEST['goods'];
        if(empty($goods)){
            $this->error('请先选中要查看的商品');
        }
        
        $Model = new \Admin\Model\KeFuModel();
        if(IS_POST){
            $Model->saveGoods($goods, $_POST['list']);
            $this->success();
        }
        
        $groups = $Model->getAll(true);
        
        // 单个商品，则默认选中客服
        if(is_numeric($goods)){
            $selected = $Model->getGoodsKF($goods);
            foreach($selected as $sid){
                foreach ($groups as $type=>$item){
                    if(isset($item[$sid['kf_id']])){
                        $groups[$type][$sid['kf_id']]['checked'] = true;
                    }
                }
            }
        }
        
        $this->assign(array('groups' => $groups, 'goods' => $goods));
        $this->display();
    }
}
?>