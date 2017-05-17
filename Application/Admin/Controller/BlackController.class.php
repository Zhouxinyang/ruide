<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 会员黑名单--管理
 * @author lanxuebao
 *
 */
class BlackController extends CommonController
{
	function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 会员黑名单列表
     */
    public function black_list(){
    	$model = M("member_black");
    	$wx_config = C('WEIXIN');
    	$where = '';
            
    	if(IS_AJAX){
	        $page = I('get.page', 1, '/^\d+$/');
    		$offset = I('get.offset', 0);
            $limit = I('get.limit', 6);
            $nickname = I("get.nickname");
            $list = array();
    		//昵称
            if($nickname){
                $where = " AND member.nick like '%".$nickname."%'";
            }
            
            $total = $model->join("member ON member_black.mid = member.id")
            				->join("wx_user ON member.id = wx_user.mid")
		            		->where("wx_user.appid = '{$wx_config["appid"]}' ".$where)
		            		->count();
            
            if($total > 0){
            	$rows = $model->join("member ON member_black.mid = member.id")
            			->join("wx_user ON member.id = wx_user.mid")
		            	->where("wx_user.appid = '{$wx_config["appid"]}'".$where)
		            	->order("member_black.end_time desc")
		            	->field("member.nick,member_black.start_time,member_black.end_time,member_black.remark,member_black.id,member_black.enabled")
		            	->select();
		        foreach($rows as $key => $value){
		        	$rows[$key]['start_time'] = date("Y-m-d H:i:s",$value['start_time']);
		        	if(!empty($value['dis_endtime'])){
		        		$rows[$key]['end_time'] = date("Y-m-d H:i:s",$value['end_time']);
		        	}
		        	$rows[$key]['dis_daycount'] = round(($value['end_time']-$value['start_time'])/86400)+1;
		        	if(time()>$value['end_time']){
		        		$rows[$key]['enabled'] = '解除';
		        	}else{
		        		$rows[$key]['enabled'] = $value['enabled'] == '1' ? '未解除' : '解除' ;
		        	}
		        }
            }else{
            	$rows = array();
            }
            
            
            $data = array(
                "total" => $total,
                "rows" => $rows
            );
            $this->ajaxReturn($data);
            
    	}
    	$this->display();
    }
    
    /**
     * 会员加入黑名单
     */
    public function black_add(){
    	$model = M("member_black");
    	if(IS_POST){
    		if(empty($_POST['end_time'])){
    			$this->error("请选择解封时间");
    		}
    		if(empty($_POST['remark'])){
    			$this->error("请输入封号原因");
    		}
    		
    		$start_time = time();
    		$end_time = strtotime($_POST['end_time']);
    		
    		if($end_time <= $start_time){
    			$this->error("解封时间不能小于当前时间");
    		}
    		
    		$id = explode(",",$_POST['ids']);
    		if(empty($id)){
    			$this->error("请选择封号会员");
    		}
    		
    		$data = array(
    		    'mid'         => '',
    		    'start_time'  => $start_time,
    		    'end_time'    => $end_time,
    		    'remark'      => $_POST['remark'],
    		    'uid'         => $this->user('id')
    		);
    		foreach($id as $key => $value){
    		    $data['mid'] = $value;
    			$model->add($data);
    		}
    		$this->success("添加成功");
    	}else{
    		$id = explode(",",$_GET['id']);
    		if(empty($id)){
    			$this->error("ID 错误,请稍后重试");
    		}
    		$this->assign("ids",$_GET['id']);
    		$this->display("black_add");
    	}
    }
    
    /**
     * 会员解除黑名单
     */
    public function black_delete(){
    	if(IS_POST){
    		$model = M("member_black");
    		$id = $_POST['ids'];
    		if(empty($id)){
    			$this->error("ID 错误,请稍后重试");
    		}
    		$data['is_black'] = 0;
    		$model->where("id IN ({$id})")->save($data);
    		$this->success();
    	}
    }
    
    /**
     * 黑名单记录删除
     */
    public function black_del(){
    	if(IS_POST){
    		$model = M("member_black");
    		$id = $_POST['ids'];
    		if(empty($id)){
    			$this->error("ID 错误,请稍后重试");
    		}
    		$model->where("id IN ({$id})")->delete();
    		$this->success();
    	}
    }
}