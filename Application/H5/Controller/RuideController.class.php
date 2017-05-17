<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 授权中心
 * @author zxy
 *
 */
class RuideController extends CommonController{

    public function index(){

        $this->display();
    }

    public function check(){
        $type = $_POST['type'];
        $content = $_POST['content'];
        $Model = M();
        if($type == 'mobile'){
            $sql = "SELECT user.id,user.username,user.card,user.wechat,user.mobile,power.id,power.product,power.position,power.show
            FROM wx_rduser AS user
            LEFT JOIN wx_power AS power ON power.rd_uid=user.id
            WHERE user.mobile='".$content."'"."
            ORDER BY power.id DESC";
        }else{
            $sql = "SELECT user.id,user.username,user.card,user.wechat,user.mobile,power.product,power.position,power.show
            FROM wx_rduser AS user
            LEFT JOIN wx_power AS power ON power.rd_uid=user.id
            WHERE user.wechat='".$content."'"."
            ORDER BY power.id DESC";

        }
        $data = $Model->query($sql);
        $this->ajaxReturn($data);
    }

}
?>