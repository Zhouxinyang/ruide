<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\ExpressModel;
use Common\Model\CityModel;

/**
 * 运费模板
 * @author yujinghua
 *
 */
class FreightTemplateController extends CommonController{
    private $myShopId;
    function __construct(){
        parent::__construct();
        $this->myShopId = $this->user('shop_id');
    }
    
    public function index(){
        if(!IS_AJAX){
            $this->display();
        }
        
        $Model = new ExpressModel();
        $list = $Model->getShopFreightTemplates($this->myShopId);
        $this->ajaxReturn($list);
    }
    
    /**
     * 添加运费模板
     */
    public function add(){
        $Model = new ExpressModel();
        if(IS_POST){
            $data = $_POST;
            $data['templates'] = json_encode($_POST['templates'], JSON_UNESCAPED_UNICODE);
            $data['shop_id'] = $this->myShopId;
            $Model->add($data);
            $this->success();
        }
        
        $data = array(
            'name'  => '',
            'templates' => array(
                array(
                    'express' => array(10),
                    'type'    => 0,
                    'default' => array(
                        'start' => '',
                        'postage' => '',
                        'plus' => '',
                        'postage_plus' => ''
                    ),
                    'specials' => array()
                )
            )
        );
        $expressList = $Model->getAllExpress();
        $this->assign(array(
            'expressList' => $expressList,
            'data'        => $data
        ));
        $this->display('edit');
    }
    
    public function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }

        $Model = new ExpressModel();
        $data = $Model->find($id);
        if($data['shop_id'] != $this->myShopId){
            $this->error('您无权修改其他店铺的模板');
        }
        
        if(IS_POST){
            $data = $_POST;
            $data['templates'] = json_encode($_POST['templates'], JSON_UNESCAPED_UNICODE);
            $Model->where("id=".$id)->save($data);
            $this->success();
        }
        
        if($data['send_place'] > 0){
            $City = new CityModel();
            $county = $City->find($data['send_place'], true);
            $data['count_id'] = $county['id'];
            $city = $City->find($county['pcode'], true);
            $data['city_id'] = $city['id'];
            $province = $City->find($city['pcode'], true);
            $data['province_id'] = $province['id'];
        }
        $data['templates'] = json_decode($data['templates'], true);
        
        $expressList = $Model->getAllExpress();
        $this->assign(array(
            'expressList' => $expressList,
            'data'        => $data
        ));
        $this->display();
    }
}
?>