<?php 
namespace Common\Model;

use Think\Model;
/**
 * 资金流水modal
 * @author lanxuebao
 *
 */
class ShopModel extends Model{
    protected $tableName = 'shop';
    
    public function getAll($pid){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $data = array('total' => 0, 'rows' => array());
        $where = array('pid' => $pid);

        if($_GET['name'] != ''){
            $where['name'] = array('like','%'.addslashes($_GET['name']).'%');
        }
        
        $data['total'] = $this->where($where)->count();
        if($data['total'] <= 0){
            return $data;
        }
        
        $data['rows'] = $this->where($where)->limit($offset,$limit)->select();
        
        return $data;
    }
}