<?php
namespace Admin\Controller;

use Common\Common\CommonController;

use Think\Controller;
/**
 * 商品分组
 * 
 * @author 兰学宝
 *        
 */
class TagController extends CommonController
{
    /**
     * 列表
     */
    public function index()
    {
        if (IS_AJAX) {
            $sql = "SELECT
                mall_tag.id,
                mall_tag.`name`,
                mall_tag.create_time
                FROM mall_tag";
            $Model = M();
            $rows = $Model->query($sql);
            foreach ($rows as $i=>$item){
                $result = $Model->query("select count(*) AS total from mall_goods where MATCH (mall_goods.tag_id) AGAINST ({$item['id']} IN BOOLEAN MODE) AND mall_goods.is_del=0");
                $rows[$i]['goods_num'] = $result[0]['total'];
                if($item['id'] < 10000){
                    $rows[$i]['state'] = array('disabled' => 1, 'selected' => 1);
                }
            }
            $this->ajaxReturn($rows);
        }
        
        $this->assign(array(
            'code' => session('shop.code'),
        ));
        $this->display();
    }
    
    /**
     * 添加分组
     */
    public function add(){
        if(IS_POST){
            $data = I('post.');
            $data['create_time'] = date('Y-m-d H:i:s');
            $result = M('mall_tag')->add($data);
            if($result > 0){
//                 $data['id'] = $this->Model->getLastInsID();
                $this->success($data);
            }
            $this->error('添加失败！');
        }
        $this->assign(array('data' => array()));
        $this->display('edit');
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
        $data1 = M('mall_tag')->find($id);
//         var_dump($data1);exit;
        if($data1['editable']==0){
            $this->error('系统默认分组禁止编辑');
        }
    
        if(!is_numeric($_REQUEST['id'])){
            $this->error('数据ID异常！');
        }
        if(IS_POST){
            $data = I('post.');
            $result = M('mall_tag')->save($data);
            if($result >= 0){
                $this->success('已修改！');
            }
            $this->error('修改失败！');
        }
        $data = M('mall_tag')->find($id);
        if(empty($data)){
            $this->error('数据不存在或已被删除！');
        }
        $this->assign(array('data' => $data ));
        $this->display();
    }
//     public function edit($id = 0){
//         if($id < 10000){
//             $this->error('系统默认分组禁止编辑');
//         }
//         if(!is_numeric($_REQUEST['id'])){
//             $this->error('数据ID异常！');
//         }
        
//         if(IS_POST){
//             $data = I('post.');
//             $result = M('mall_tag')->save($data);
//             if($result >= 0){
//                 $this->success('已修改！');
//             }
//             $this->error('修改失败！');
//         }
        
//         $data = M('mall_tag')->find($id);
//         if(empty($data)){
//             $this->error('数据不存在或已被删除！');
//         }
        
//         $this->assign(array('data' => $data ));
//         $this->display();
//     }
    
    /**
     * 删除分组
     */
//     public function delete($id = 0){
//         $list = explode(',', $id);
//         foreach ($list as $i=>$tagId){
//             if($tagId < 10000){
//                 unset($list[$i]);
//             }
//         }
        
//         if(count($list) == 0){
//             $this->error('系统默认分组无法删除');
//         }
        
//         $Model = M();
//         $result = $Model->execute("DELETE FROM mall_tag WHERE id IN (".implode(',', $list).")");
//         if($result > 0){
//             foreach ($list as $tagId){
//                 $Model->execute("UPDATE mall_goods SET tag_id=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',tag_id, ','), ',{$tagId},', ','))WHERE {$tagId} IN (tag_id)");
//             }
//         }
//         $this->success('删除成功！');
//     }
        public function delete($id = 0){
            $data = M('mall_tag')->select($id);
            foreach ($data as $val){
                if($val['editable'] == 0){
                    $this->error('系统默认分组无法删除');
                }
            }
            $Model = M();
            $result = $Model->query("DELETE FROM mall_tag WHERE id in ($id)");
            $data1 = M('mall_tag')->select($id);
            if($data1){
                $this->success('删除失败！');
            }
            $this->success('删除成功！');
        }
}

?>