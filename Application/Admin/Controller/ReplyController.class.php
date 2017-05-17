<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 微信 - 自动回复
 * @author lanxuebao
 *
 */
class ReplyController extends CommonController
{
    private $Model;
    function __construct()
    {
        parent::__construct();
        $this->Model = D('Common/Reply');
    }
    
    public function index(){
        if(!IS_AJAX){
            $this->display();
        }
        
        $data = $this->Model->getAll();
        $this->ajaxReturn($data);
    }
    
    public function add(){
        if(IS_GET){
            $this->display('edit');
        }
        
        $config = C("WEIXIN");
        $result = $this->Model->addReply($_POST,$config["appid"]);
        if($result <= 0){
            $this->error($this->Model->getError());
        }
        
        $this->success("已保存！");
    }
    
    public function edit(){
        $id = $_GET["id"];
        if(IS_POST){
            $result = $this->Model->saveReply($_POST,$id);
            if($result <= 0){
                $this->error($this->Model->getError());
            }
            
            $this->success("已保存！");
        }
        
        $data = $this->Model->getOne($id);
        $this->assign("data",json_encode($data,JSON_UNESCAPED_UNICODE));
        $this->display();
    }
    
    public function delete(){
        $id = $_POST["id"];
        M("wx_keyword")->where("reply_id IN ({$id})")->delete();
        M("wx_reply")->delete($id);
        
        $this->success("操作成功！");
    }
    
    public function getAdvanced(){
        $where = ' wan.pid = 0 ';
        $page = I('get.page/d', 1);
        $offset = 20;
        $limit = ($page - 1) * $offset;
        $total = 0;
        
        $Model = D('AdvancedNews');
        $data = $Model->getAll($where,$offset,$limit);
        
        $this->ajaxReturn($data);
    }
}