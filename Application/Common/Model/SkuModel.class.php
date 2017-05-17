<?php 
namespace Common\Model;

use Think\Model;
class SkuModel extends Model{
    protected $tableName = 'mall_sku';
    
    public function getAll(){
        // 系统默认sku
        $list = StaticModel::skuList();
        
        // 获取用户自定义的sku
        $data = $this->where("pid=0")->select();
        foreach($data as $item){
            $list[$item['id']] = $item['text'];
        }
        
        return $list;
    }
}
?>