<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 高级图文
 * 
 * @author wangjing
 *        
 */
class AdvancedNewsController extends CommonController
{
    public function index(){
        if(IS_AJAX){
            $page = I('get.page/d', 1);
            $offset = 20;
            $limit = ($page - 1) * $offset;
            $total = 0;
            
            $where = " 1=1 ";
            $where .= " AND wan.pid = 0";
            
            $Model = D('AdvancedNews');
            $data = $Model->getAll($where,$offset,$limit);
            $total = $data['total'];
            foreach ($data['rows'] as $k=>$v){
                $line = floor($k/3);//当前行数
                $column = $k-$line*3;//所在列
                if($column == 0){
                    $list1[] = $v;
                }else if($column == 1){
                    $list2[] = $v;
                }else if($column == 2){
                    $list3[] = $v;
                }
            }
            $this->assign(array(
                'total' => $total,
                'page'=>$page,
                'offset'=>$offset,
                'list1' => $list1,
                'list2' => $list2,
                'list3' => $list3
            ));
            $this->display('list');
        }
        $this->display();
    }
    
    public function add(){
        if(IS_POST){
            $data = I("post.");
            $Model = D('AdvancedNews');
            $result = $Model->insert($data);
            if(empty($result)){
                $this->error($Model->getError());
            }
            $this->success($result,'/admin/advanced_news');
        }
        $this->display("edit");
    }
    
    public function edit($id = 0){
        $Model = D('AdvancedNews');
        if(IS_POST){
            $data = I("post.");
            $result = $Model->update($data);
            if(empty($result)){
                $this->error($Model->getError());
            }
            $this->success($result,'/admin/advanced_news');
        }
        
        $data = $Model->getOne($id);
        $data = json_encode($data);
        if(empty($data)){
            $this->error('数据不存在或已被删除！');
        }
        
        $this->assign(array(
            'data' => $data
        ));
        $this->display();
    }
    
    public function delete($id = 0){
        $result = M("wx_advanced_news")->where("id IN ({$id})")->delete();
        if($result){
            M("wx_advanced_news")->where("pid IN ({$id})")->delete();
        }
        $this->success('删除成功！');
    }
}
?>