<?php 
namespace Common\Model;

use Think\Model;
use Org\Wechat\WechatAuth;

/**
 * 获取二维码票据
 * @author lanxuebao
 *
 */
class QrcodeModel extends Model{
    protected $tableName = 'wx_qrcode';
    /* type类型常量 */
    const GOODS     = 1;
    const CUSTOMER_SERVICE = 2;
    const COUPON    = 3;
    
    /*
     * 获取商品临时二维码
     */
    public function getTicket($type, $outerId, $appid = null){
         $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=";
         if(empty($appid)){
             $appid = C('WEIXIN.appid');
         }
         
         $qrcode = $this->alias('qrcode')
             ->where("appid='".$appid."' AND outer_id='".$outerId."' AND type='".$type."'")
             ->find();
         
         if(!empty($qrcode) && NOW_TIME < $qrcode['expire_time']){
             $qrcode['url'] = $url.$qrcode['ticket'];
             return $qrcode;
         }
         
         $expire_time = WechatAuth::QR_SCENE_TIME - 300;
         $qrcode['outer_id']    = $outerId;
         $qrcode['expire_time'] = NOW_TIME + $expire_time;

         $this->startTrans();
         // 二维码场景值
         $sceneId = 0;
         if(empty($qrcode['id'])){
             // 到10万时清空数据库
             $qrcode['type']        = $type;
             $qrcode['appid']       = $appid;
             $sql = "INSERT INTO {$this->tableName} SET ";
             $sql .= "id=(SELECT next_id FROM (SELECT IF (ISNULL(MAX(id)),1,MAX(id) + 1) as next_id FROM {$this->tableName} WHERE appid = '{$appid}') as maxqr)";
             foreach ($qrcode as $field=>$value){
                 $sql .= ",`{$field}`='{$value}'";
             }
             $this->execute($sql);
             $sceneId = $this->getLastInsID();
         }else{
             $sceneId = $qrcode['id'];
         }
         
         $WechatAuth = new WechatAuth();
         $result = $WechatAuth->qrcodeCreate($sceneId, WechatAuth::QR_SCENE_TIME);
         if(empty($result) || $result['errcode'] != ''){
             $this->error = '二维码生成失败';
             $this->rollback();
             return null;
         }else{
             $qrcode['id']     = $sceneId;
             $qrcode['ticket'] = $result['ticket'];
         }
         
         $this->execute("UPDATE ".$this->tableName." SET ticket='".$qrcode['ticket']."' WHERE id=".$qrcode['id']);
         $this->commit();
         
         $qrcode['url'] = $url.$qrcode['ticket'];
         return $qrcode;
    }
}
?>