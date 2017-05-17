<?php
namespace H5\Controller;
use Common\Common\CommonController;
use Org\Wechat\WechatAuth;

/**
 * 微信二维码
 * @author 兰学宝
 *
 */
class QrController extends CommonController
{
    /**
     * 生成推荐二维码
     */
    public function recommend(){
         ignore_user_abort(true);
         header('X-Accel-Buffering: no');
         header('Content-Length: '. strlen(ob_get_contents()));
         header("Connection: close");
         header("HTTP/1.1 200 OK");
         ob_end_flush();
         flush();

         $openid = $_GET['openid'];
         if(empty($openid)){
             $this->error('openid不能为空');
         }
         set_time_limit(0);
         
         $Module = M("wx_user");
         
         //获取用户信息
         $user = $Module
                 ->alias("wx")
                 ->field("member.id, wx.openid, member.agent_level, member.nickname AS mname, wx.nickname, wx.headimgurl, dlsqr.mediaid, dlsqr.expires, wx.appid")
                 ->join("member ON member.id=wx.mid")
                 ->join("member_dlsqr AS dlsqr ON dlsqr.openid=wx.openid")
                 ->where("wx.openid='{$openid}'")
                 ->find();

         if(empty($user)){
             return;
         }
         $wechatAuth = new WechatAuth($user['appid']);
         
         // 素材已上传并未过期
         if(!empty($user['expires']) && time() < $user['expires']){
             $wechatAuth->sendImage($openid, $user['mediaid']);
         }
         
         // 1000人为一个文件夹
         $folderStep = 1000;
         $num = bcdiv($user['id']-1, $folderStep)+1;
         $folder = ($num*$folderStep - $folderStep+1).'-'.$num*$folderStep;
         
         // 生成带参数二维码
         $ticket = $user["ticket"];
         if(empty($ticket)){
             $scene_id = "dls_".$user["id"];
             $qrcode   = $wechatAuth->qrcodeCreate($scene_id);
             $ticket   = $qrcode["ticket"];
         }
     
         // 判断用户是否生成过本地二维码图片
         $qrcodeImg = $_SERVER['DOCUMENT_ROOT'].'/img/wximg/dls/'.$folder.'/'.$user["id"].'.jpg';
         if(!@is_file($qrcodeImg) || filesize($qrcodeImg) == 0){
            $qrcodeUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
            \Org\Net\Http::curlDownload($qrcodeUrl, $qrcodeImg);
            if(!@is_file($qrcodeImg) || filesize($qrcodeImg) == 0){
                $wechatAuth->sendText($openid, '生成二维码失败，请重试！');
                return;
            }
         }

         // 下载用户头像
         $localHeadimg = $_SERVER['DOCUMENT_ROOT'].'/img/wximg/headimg/'.$folder.'/'.$openid.'.jpg';
         $headimgurl = str_replace('http://', 'https://', $user['headimgurl']);

         \Org\Net\Http::curlDownload($headimgurl, $localHeadimg);
         if(!@is_file($localHeadimg) || filesize($localHeadimg) == 0){
             $wechatAuth->sendText($openid, '下载头像失败，请重试！');
             $localHeadimg = $_SERVER['DOCUMENT_ROOT'].'/img/logo.jpg';
         }
         
         // 生成推送图片-PHP画布
         $recommendFile = $this->createRecommendImg($user, $qrcodeImg, $localHeadimg, $folder);

         // 上传文件
         $result = $wechatAuth->mediaUpload($recommendFile, 'image');
         $mediaid  = $result['media_id'];
         $expires = $result['created_at'] + 3600 * 7 - 300; // 临时素材过期时间7天，减去意外5分钟
         
         $Module->execute("REPLACE INTO member_dlsqr(openid, mediaid, expires, ticket) VALUES('{$user['openid']}', '{$mediaid}', '{$expires}', '{$ticket}')");
         
         // 发送图片消息
         $result = $wechatAuth->sendImage($openid, $mediaid);
    }
    
    /**
     * 图片合成方法
     */
    private function createRecommendImg($user, $qrcodeFile, $headimgFile, $folder){
        // 最终文件路基
        $folder = $_SERVER['DOCUMENT_ROOT'].'/img/wximg/dls_recommend/'.$folder;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }
        $filename = $folder.'/'.$user['openid'].'.jpg';

        // 创建最终背景图
        $width          = 640;
        $height         = 780;
        $image          = imagecreatetruecolor($width, $height); 

        // 读取头像图片
        $img_headimg    = imagecreatefromjpeg($headimgFile);
        $psizearray     = getimagesize($headimgFile);
        //imagecopyresized($image, $img_headimg, 48, 38, 0, 0, 103, 103, $psizearray[0], $psizearray[1]);
        imagecopyresized($image, $img_headimg, 52, 42, 0, 0, 95, 95, $psizearray[0], $psizearray[1]);
        imagedestroy($img_headimg);
        
        // 默认背景图
        $background     = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'].'/img/wximg/dls_recommend.png');
        imagesavealpha($background,true);
        
        // 将背景图覆盖到初始化的图形中
        imagecopyresized($image, $background, 0, 0, 0, 0, $width, $height, $width, $height);
        imagedestroy($background);
        
        // 填充二维码
        $img_qrcode     = imagecreatefromjpeg($qrcodeFile);
        $qsizearray     = getimagesize($qrcodeFile);
        imagecopyresized($image, $img_qrcode, 375, 377, 0, 0, 230, 230, $qsizearray[0], $qsizearray[1]);
        imagedestroy($qsizearray);
        
        // 白色文字
        $font           = $_SERVER['DOCUMENT_ROOT'].'/font/msyh.ttf';
        $whitecolor     = imagecolorallocate($image, 255, 255, 255);
        
        // 代理姓名
        $str = '你好，我是'.$user['nickname'];
        $length = mb_strlen($str, 'utf-8');
        $name           = $length > 10 ? mb_substr($str, 0, 10, 'utf-8').'...' : $str;
        imagefttext($image, 25, 0, 165, 102, $whitecolor, $font, $name);
        
        // 保存图像
        imagejpeg($image, $filename);
    
        // 释放内存
        imagedestroy($image);
        return $filename;
    }
    
    private function showImg($image){
        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
        die;
    }
    
    public function create(){
        $scene_id = $_GET['scene_id'];
        $wechatAuth = new \Org\Wechat\WechatAuth();
        $result = $wechatAuth->qrcodeCreate($scene_id);
        
        $result['outer_url'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$result['ticket'];
        print_data($result);
    }
}