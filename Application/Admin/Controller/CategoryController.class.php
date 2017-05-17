<?php
namespace Admin\Controller;

use Common\Common\CommonController;
/**
 * 商品类目
 * 
 * @author lanxuebao
 *        
 */
class CategoryController extends CommonController
{
    /**
     * 列表
     */
    public function index(){
        if(IS_AJAX){
            $sql = "SELECT id, `name`, pid, icon, sort, created,
                    (select count(*) from mall_goods where mall_category.id=mall_goods.cat_id AND mall_goods.is_del=0) AS goods_num
                    FROM mall_category
                    ORDER BY sort DESC";
            $list = M()->query($sql);
            
            $rows = array();
            foreach ($list as $item){
                $rows[$item['pid']][] = $item;
            }
            
            foreach ($rows[0] as $i=>$item){
                $rows[0][$i]['goods_num'] += $this->getGoodsNum($rows, $item['id']);
            }
            
            $list = array();
            foreach ($rows as $item){
                foreach ($item as $v){
                    $list[] = $v;
                }
            }
            
            $this->ajaxReturn($list);
        }
        $this->display();
    }
    
    private function getGoodsNum(&$list, $pid){
        $num = 0;
        foreach ($list[$pid] as $i=>&$item){
            $item['goods_num'] += $this->getGoodsNum($list, $item['id']);
            $num += $item['goods_num'];
        }
        
        return $num;
    }
    
    /**
     * 添加
     */
    public function add(){
        $Model = M('mall_category');
        $da = $Model->field('id,name')->where('pid = 0')->select();
        if(IS_POST){
            $eid = addslashes($_POST['eid']);
            $data['icon'] = addslashes($_POST['icon']);
            $data['pid'] = addslashes($_POST['pid']);
            $data['name'] = addslashes($_POST['name']);
            $data['sort'] = addslashes($_POST['sort']);
            $data['created'] = date("Y-m-d h:i:s");
            
            if(is_numeric($eid)){
                $data['pid'] = $eid;
            }
            if(!is_numeric($data['pid'])){
                $this->error('类目不能为空');
            }
            if(empty($data['name'])){
                $this->error('类目名称不能为空');
            }
            if(!is_numeric($data['sort'])){
                $this->error('排序不能为空');
            }
           
            $result = $Model->add($data);
            if($result > 0){
                $this->success('添加成功');
            }
            $this->error('添加失败，请稍后再试.');
        }
        
        $da['pid'] = 0;
        $showParent=1;
        $this->assign(array(
            'showParent' =>$showParent,
            'data' =>$da
        ));
        $this->display();
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
        $Model = D('Category');
        if(IS_POST){
            $id = I('post.id/d');
            $eid = $_POST['eid'];
            if(!is_numeric($_POST['pid'])){
                $this->error('PID不能为空');
            } 
            if(!is_numeric($id)){
                $this->error('编辑ID不能为空');
            }
            if(empty($_POST['name'])){
                $this->error('类目名称不能为空');
            }
            if(!is_numeric($_POST['sort'])){
                $this->error('排序不能为空');
            }
            
            $data = array(
                'pid'       => addslashes($_POST['pid']),
                'name'      => addslashes($_POST['name']),
                'icon'      => addslashes($_POST['icon']),
                'sort'      => I('post.sort/d')
            );
            
            if(is_numeric($eid)){
                $data['pid'] = $eid;
            }
            
            $Model->update($id, $data);
            $this->success('已保存');
        }
        
        $da = $Model->where("id=".$id)->find();
        $data = $Model->field('id,name')->where('pid = 0')->select();
        $eid = $_GET['eid'];
        if(is_numeric($eid)){
            $er = $Model->field('id,name')->where('pid ='.$eid)->select();
            $this->ajaxReturn(json_encode($er));
        }
        $showParent = 1;
       // if($da['pid']==0){
            $pi = $Model->where('pid='.$id)->find();
            if(!empty($pi)){
                $showParent = 0;
            }
       // }
        
        $this->assign(array(
            'showParent' =>$showParent,
            'da' =>$da,
            'data' =>$data
        ));
        $this->display('add');
        
        
    }
    
    /**
     * 删除菜单
     */
    public function delete(){
        $Model = D('Category');
        $id = I('post.id');
        if(empty($id)){
            $this->error('删除项不能为空！');
        }
        $exists = $Model->field('pid')->where("id=".$id)->find();
        if($exists['pid']==0){
            $pi = $Model->where('pid='.$id)->find();
            if($pi){
                $this->error('存在子分类,无法删除');
            }
        }
        
        D('Category')->delete($id, $this->shop['id']);
        $this->success('删除成功！');
    }
    
    
   
}

?>