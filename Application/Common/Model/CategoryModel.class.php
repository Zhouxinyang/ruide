<?php 
namespace Common\Model;

use Think\Model;
class CategoryModel extends Model{
    protected $tableName = 'mall_category';
    
    public function getAll(){
        
        print_data($result);
    }
    
    public function initList(){
        $now = date('Y-m-d H:i:s');
        $dataList = array(
            array('name' => '女人', 'pid' => 0, 'icon' => '/img/img-circle.png', 'sort' => 0, 'create_time' => $now),
            array('name' => '男人', 'pid' => 0, 'icon' => '/img/img-circle.png', 'sort' => 0, 'create_time' => $now),
            array('name' => '食品', 'pid' => 0, 'icon' => '/img/img-circle.png', 'sort' => 0, 'create_time' => $now),
            array('name' => '美妆', 'pid' => 0, 'icon' => '/img/img-circle.png', 'sort' => 0, 'create_time' => $now),
            array('name' => '亲子', 'pid' => 0, 'icon' => '/img/img-circle.png', 'sort' => 0, 'create_time' => $now),
            array('name' => '居家', 'pid' => 0, 'icon' => '/img/img-circle.png', 'sort' => 0, 'create_time' => $now),
            array('name' => '数码家电', 'pid' => 0, 'icon' => '/img/img-circle.png', 'sort' => 0, 'create_time' => $now),
        );
        $this->Model->addAll($dataList);
    }
    
    public function add($data){
        $data['created'] = date('Y-m-d H:i:s');
        return parent::add($data);
    }
    
    public function update($id, $data){
        return $this->where("id=%d", $id)->save($data);
    }
    
    public function delete($id){
        $result = $this->execute("DELETE FROM mall_category WHERE id IN ($id)");
        $result += $this->execute("DELETE FROM mall_category WHERE pid IN ($id)");
        return $result;
    }
}
?>