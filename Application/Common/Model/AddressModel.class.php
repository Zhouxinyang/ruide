<?php 
namespace Common\Model;

class AddressModel extends BaseModel{
    protected $tableName = 'member_address';
    private $allList = null;
    
    public function getCityList(){
        if(is_null($this->allList)){
            $this->allList = include COMMON_PATH.'/Model/city.php';
        }
        return $this->allList;
    }
    
    public function getCityName($id){
        $list = $this->getCityList();
        return $list[$id]['name'];
    }
    
    /**
     * 获取收货地址
     * @param unknown $mid
     * @return \Think\mixed
     */
    public function getDefault($mid){
        $address = $this->where("mid=%d", $mid)->order("is_default DESC")->find();
    
        if(!empty($address)){
            $City = D('City');
            $address['province_name'] = $this->getCityName($address['province_id']);
            $address['city_name'] = $this->getCityName($address['city_id']);
            $address['county_name'] = $this->getCityName($address['county_id']);
        }
    
        return $address;
    }
    
    public function toarray(){
        $model = M('city');
        $list = $model->order("id")->select();
    
        foreach($list as $item){
            echo "'{$item['code']}'=>array('name'=>'{$item['name']}','sname'=>'{$item['short_name']}','pcode'=>{$item['pcode']},'level'=>{$item['level']},'pinyin'=>'{$item['pinyin']}'),<br>";
        }
    }
    
    public function tojs(){
        $list = include COMMON_PATH.'/Model/city.php';
         
        $result = array();
        foreach($list as $code=>$item){
            $result[$item['pcode']][$code] = $item;
        }
    
        print_data(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
}

?>