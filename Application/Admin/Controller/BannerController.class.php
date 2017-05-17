<?php
namespace Admin\Controller;

use Common\Common\CommonController;
/**
* @author 刘聪
* 模块 : 广告
*/
class BannerController extends CommonController
{
	private $Model;
    function __construct()
    {
        parent::__construct();
        $this->Model = M("mall_banner");
    }

    public function index(){
		if(IS_AJAX){
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 20);
            $total = $this->Model->count();
            
            if($total > 0){
	            $rows =  $this->Model->limit($offset,$limit)->order("is_show desc, sort desc")->select();
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
     * 删除
     */ 
    public function delete($id=null){
        if (empty($id)) {
            $this->error('删除项不能为空！');
        }
        $result = $this->Model->delete($id);
        if ($result) {
            $this->success('删除成功！');
        }
        $this->error('删除失败！');
    }
	
    /**
     * 添加
     */
    public function add(){
    	if(IS_POST){
    		$data = array(
    			'title' => I('post.title'),
    			'img_url' => I('post.img_url'),
    			'url' => I('post.url'),
    			'create_time' => date("Y-m-d H:i:s",time()),
                'sort' => I('post.sort'),
                'seat' => implode(',',I('post.seat')),
    			'is_show' => $_POST['is_show'] ? 1 : 0,
    			'home' => $_POST['home'],
    		    'personal' => $_POST['personal'] ? 1 : 0
    		);
    		//标题验证
    		if(empty($data['title'])){
    			$this->error("请输入标题");
    		}
    		
    		//图片验证
    		if(empty($data['img_url']) && $data['personal'] == 0){
    			$this->error("请添加图片");
    		}
    		$this->Model->data($data)->add();
    		$this->success("添加成功");
    	}
    	
        $tags = M('mall_tag')->select();
        $this->assign("tags",$tags);
    	$this->assign('data', array('sort' => 0));
    	$this->display();
    }
    
    /**
     * 编辑
     */
    public function edit(){
    	if(IS_POST){
    		$id = I('post.banner_id');
    		$data = array(
    			'title' => I('post.title'),
    			'img_url' => I('post.img_url'),
    			'url' => I('post.url'),
    			'sort' => I('post.sort'),
                'seat' => implode(',',I('post.seat')),
    			'is_show' => $_POST['is_show'] ? 1 : 0,
    			'home' => $_POST['home'],
    		    'personal' => $_POST['personal'] ? 1 : 0
    		);
    		//标题验证
    		if(empty($data['title'])){
    			$this->error("请输入标题");
    		}
    		
    		//图片验证
    		if(empty($data['img_url']) && $data['personal'] == 0){
    			$this->error("请添加图片");
    		}
    		
    		$this->Model->where("id = {$id}")->save($data);
    		$this->success("修改成功");
    	}
    	
    	$id = I("get.id");
    	if(!is_numeric($id)){
            $this->error("广告ID不能为空.");
        }
        
        $data = $this->Model->find($id);
        if(empty($data)){
            $this->error("该广告不存在.");
        }
        $tags = M('mall_tag')->select();
        $this->assign("tags",$tags);
    	$this->assign("data",$data);
    	$this->display("add");
    }
}
?>