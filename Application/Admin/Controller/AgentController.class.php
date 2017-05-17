<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 代理管理
 * 
 * @author wangjing
 *        
 */
class AgentController extends CommonController
{
    /*代理升级*/
    public function index(){
        if(IS_AJAX){
            $Model = D("Agent");
            $data = $Model->getAllLevel();
            foreach($data['rows'] as $k=>$v){
                $data['rows'][$k]['level'] = $v['level'];
                $data['rows'][$k]['upgrade'] = ($v['upgrade']==1)?"是":"否";
                $data['rows'][$k]['only_employee'] = ($v['only_employee']==1)?"是":"否";
            }
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    public function edit($id = 0){
        $Model = D('Agent');
        if(IS_POST){
            $data = I("post.");
            $result = $Model->updateLevel($data);
            if($result < 0){
                $this->error($Model->getError());
            }
            $this->success();
        }
        
        $data = $Model->getOneLevel($id);
        $level = $this->agentLevel($data['level']);
        $data['level'] = $level['level'];
        if(empty($data)){
            $this->error('数据不存在或已被删除！');
        }
        
        $this->assign(array(
            'data' => $data
        ));
        $this->display();
    }
}
?>