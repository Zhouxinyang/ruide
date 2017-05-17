<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Common\Model\CategoryModel;

/**
 * 商品类目
 * @author lanxuebao
 *
 */
class CategoryController extends CommonController{
    
    public function index(){
        $list = M('mall_category')->order("pid, sort DESC")->select();
        
        $rows = array();
        foreach ($list as $item){
            $rows[$item['pid']][] = $item;
        }
        
        $this->assign('list', $rows);
        $this->display(IS_AJAX ? 'ajax' : '');
    }
}
?>