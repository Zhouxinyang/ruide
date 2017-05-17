<?php
namespace Common\Model;

/**
 * 微信自动回复
 * 
 * @author lanxebao
 *        
 */
class ReplyModel extends BaseModel
{
    protected $tableName = 'wx_reply';
    
    public function getAll(){
        $appid = C('WEIXIN.appid');
        $data = array('total' => 0, 'rows' => array());
        
        $data['total'] = $this->where("appid='{$appid}'")->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $offset = I('get.offset/d', 0);
        $limit = I('get.limit/d', 50);
        
        $sql = "SELECT reply.*, kw.keyword, kw.full_match 
                FROM wx_reply AS reply
                JOIN wx_keyword AS kw ON kw.reply_id=reply.id
                WHERE reply.appid='{$appid}' ORDER BY reply.id DESC LIMIT {$offset},{$limit}";
        
        $list = $this->query($sql);

        // 组合
        $media_ids = "";
        $advanced_ids = "";
        foreach($list as $item){
            if(!isset($data['rows'][$item['id']])){
                $data['rows'][$item['id']] = array(
                    'id' => $item['id'],
                    'rule' => $item['rule'],
                    'is_default' => $item['is_default'],
                    'is_subscribe' => $item['is_subscribe'],
                    'content' => json_decode($item['content'], true),
                    'keyword' => array()
                );
                
                foreach($data['rows'][$item['id']]["content"] as $k=>$v){
                    if($v["type"] == "news" || $v["type"] == "voice" || $v["type"] == "video"){
                        $media_ids .= "'".$v["content"]."',";
                        continue;
                    }
                    if($v["type"] == "senior"){
                        $advanced_ids .= $v["content"].",";
                        continue;
                    }
                }
            }
            
            $data['rows'][$item['id']]['keyword'][] = array('keyword' => $item['keyword'], 'full_match' => $item['full_match']);
        }
        
        $data['rows'] = array_values($data['rows']);
        
        $material = $advanced = array();
        
        if($media_ids != ""){
            $material = $this->query("SELECT id,title,media_id FROM wx_material WHERE media_id IN (".rtrim($media_ids,',').")");
            $material = array_kv($material,"media_id");
        }
        
        if($advanced_ids != ""){
            $advanced = $this->query("SELECT id,title FROM wx_advanced_news WHERE id IN (".rtrim($advanced_ids,',').")");
            $advanced = array_kv($advanced);
        }
        
        foreach($data['rows'] as $k=>$v){
            foreach($v["content"] as $key=>$val){
                if($val["type"] == "text"){
                    continue;
                }
                
                if(($val["type"] == "news" || $val["type"] == "voice" || $val["type"] == "video") && isset($material[$val["content"]])){
                    $data['rows'][$k]["content"][$key]["title"] = $material[$val["content"]]["title"];
                    continue;
                }
                
                if($val["type"] == "senior" && isset($advanced[$val["content"]])){
                    $data['rows'][$k]["content"][$key]["title"] = $advanced[$val["content"]];
                    continue;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 根据id获取数据
     */
    public function getOne($id = 1){
        //获取回复表数据
        $reply = $this->where("id=".$id)->find();
        if(empty($reply)){
            $this->error = "暂无数据！";
            return -1;
        }
        
        $data = array(
            "id" => $id,
            "rule" => $reply["rule"],
            "is_subscribe" => $reply["is_subscribe"],
            "is_default" => $reply["is_default"]
        );
        
        //获取关键字数据
        $keyword = $this->query("SELECT full_match,keyword FROM wx_keyword WHERE reply_id=".$id);
        foreach($keyword as $k=>$v){
            $data["keyword"][] = $v;
        }
        
        //获取回复内容数据
        $content = json_decode($reply["content"],true);
        foreach($content as $k=>$v){
            if($v["type"] == "news"){
                $news = $this->query("SELECT media_id,update_time,content FROM wx_material WHERE media_id='{$v["content"]}'");
                
                $data["content"][] = array(
                    "media_id" => $news[0]["media_id"],
                    "update_time" => $news[0]["update_time"],
                    "content" => json_decode($news[0]["content"],true),
                    "type" => $v["type"]
                );
                continue;
            }else if($v["type"] == "voice" || $v["type"] == "video"){
                $news = $this->query("SELECT media_id,update_time,content FROM wx_material WHERE media_id='{$v["content"]}'");
                $data["content"][] = json_decode($news[0]["content"],true);
                continue;
            }else if($v["type"] == 'senior'){
                $new = $this->getById($v["content"]);
                $new['type'] = $v["type"];
                $data["content"][] = $new;
                continue;
            }
            
            $data["content"][] = array(
                    "content" => $v["content"],
                    "type" => $v["type"]
                );
        }
        return $data;
    }
    
    /**
     * 添加自动回复
     */
    public function addReply($data,$appid){
        if(empty($data)){
            $this->error = "数据不能为空！";
            return -1;
        }else if(empty($data["keyword"])){
            $this->error = "关键字不能为空！";
            return -1;
        }else if(empty($data["content"])){
            $this->error = "回复内容不能为空！";
            return -1;
        }else if(empty($appid) || $appid == ""){
            $this->error = "微信appid不能为空！";
            return -1;
        }
        
        //关键字唯一验证
        $check = $this->check_keyword($data["keyword"],$appid);
        if($check == -1){
            return -1;
        }
        
        //素材数据整理
        $content = $this->clean_material($data["content"], $appid);
        if($content == -1){
            return -1;
        }
        
        //保存wx_reply表信息
        if($data["is_subscribe"] == 1){
            $this->execute("UPDATE wx_reply SET is_subscribe=0 WHERE is_subscribe=1");
        }
        if($data["is_default"] == 1){
            $this->execute("UPDATE wx_reply SET is_default=0 WHERE is_default=1");
        }
        
        $date = date("Y-m-d H:i:s");
        $add_reply = array(
            "appid" => $appid,
            "rule"  => $data["rule"],
            "content"  => json_encode($content,JSON_UNESCAPED_UNICODE),
            "modified"  => $date,
            "is_subscribe"  => $data["is_subscribe"],
            "is_default"  => $data["is_default"],
        );
        
        $reply_id = $this->add($add_reply);
        if($reply_id <= 0){
            $this->error = "操作失败！";
            return -1;
        }
        
        //保存关键字
        $add_keword = array();
        $ModelKeyword = M("wx_keyword");
        foreach($data["keyword"] as $k=>$v){
            $add_keword[] = array(
                "reply_id" => $reply_id,
                "keyword" => $v["keyword"],
                "full_match" => $v["full_match"],
            );
        }
        
        $result = $ModelKeyword->addAll($add_keword);
        if($result <= 0){
            $this->error = "操作失败！";
            return -1;
        }
        
        return 1;
    }
    
    /**
     * 编辑自动回复
     */
    public function saveReply($data,$id){
        if(empty($data)){
            $this->error = "数据不能为空！";
            return -1;
        }else if(empty($data["keyword"])){
            $this->error = "关键字不能为空！";
            return -1;
        }else if(empty($data["content"])){
            $this->error = "回复内容不能为空！";
            return -1;
        }
        
        //获取回复信息数据
        $reply = $this->find($id);
        if(empty($reply)){
            $this->error = "暂无数据！";
            return -1;
        }
        
        //关键字唯一验证
        $check = $this->check_keyword($data["keyword"], $reply["appid"], $reply["id"]);
        if($check == -1){
            return -1;
        }
        
        //素材数据整理
        $content = $this->clean_material($data["content"], $reply["appid"]);
        if($content == -1){
            return -1;
        }
        
        //保存wx_reply表信息
        if($data["is_subscribe"] == 1){
            $this->execute("UPDATE wx_reply SET is_subscribe=0 WHERE is_subscribe=1");
        }
        if($data["is_default"] == 1){
            $this->execute("UPDATE wx_reply SET is_default=0 WHERE is_default=1");
        }
        
        $date = date("Y-m-d H:i:s");
        $up_reply = array(
            "rule"  => $data["rule"],
            "content"  => json_encode($content,JSON_UNESCAPED_UNICODE),
            "modified"  => $date,
            "is_subscribe"  => $data["is_subscribe"],
            "is_default"  => $data["is_default"],
        );
        
        $result = $this->where("id=".$id)->save($up_reply);
        if($result <= 0){
            $this->error = "操作失败！";
            return -1;
        }
        
        //保存关键字
        $add_keword = array();
        $ModelKeyword = M("wx_keyword");
        
        //删除原有关键字记录
        $ModelKeyword->where("reply_id=".$id)->delete();
        
        //添加新的关键字记录
        foreach($data["keyword"] as $k=>$v){
            $add_keword[] = array(
                "reply_id" => $id,
                "keyword" => $v["keyword"],
                "full_match" => $v["full_match"],
            );
        }
        
        $result = $ModelKeyword->addAll($add_keword);
        if($result <= 0){
            $this->error = "操作失败！";
            return -1;
        }
        
        return 1;
    }
    
    /**
     * 素材整理
     */
    private function clean_material($data, $appid){
        //整理素材信息
        $save_material = 0;//0不需要保存素材 1需要保存素材
        $sql_material = "REPLACE INTO wx_material(appid, update_time, type, media_id, title, content, url)
                         VALUES";
        $content = array();
        foreach($data as $k=>$v){
            $con = "";
            if($v["type"] == "news"){ //图文
                $material_content = json_encode($v["content"],JSON_UNESCAPED_UNICODE);
                $material_content = addslashes($material_content);
                $sql_material .= "('{$appid}','{$v["update_time"]}','{$v["type"]}','{$v["media_id"]}','{$v["content"][0]["title"]}','{$material_content}','{$v["content"][0]["url"]}'),";
                
                $con = $v["media_id"];
                
                $save_material = 1;
            }else if($v["type"] == "voice" || $v["type"] == "video"){ //语音   视频
                $material_content = json_encode($v,JSON_UNESCAPED_UNICODE);
                $material_content = addslashes($material_content);
                $sql_material .= "('{$appid}','{$v["update_time"]}','{$v["type"]}','{$v["media_id"]}','{$v["name"]}','{$material_content}','{$v["url"]}'),";
                
                $con = $v["media_id"];
                
                $save_material = 1;
            }else if($v["type"] == "senior"){ //高级图文
                $con = $v["id"];
            }else if($v["type"] == "text"){ //文字回复
                $con = $v["content"];
            }else{
                $this->error = "数据格式错误！";
                return -1;
            }
        
            $content[$k] = array(
                "type" => $v["type"],
                "content" => $con
            );
        }
        
        //保存素材
        if($save_material == 1){
            $result = $this->execute(rtrim($sql_material,','));
            if($result <= 0){
                $this->error = "操作失败！";
                return -1;
            }
        }
        
        return $content;
    }
    
    /**
     * 关键字唯一验证(同公众账号下不能有相同的关键字)
     */
    private function check_keyword($data, $appid, $reply_id = null){
        //整理关键字信息
        $keyword = "";
        $keywords = "";
        $where = "";
        if($reply_id > 0){
            $where = " AND wx_keyword.reply_id<>".$reply_id;
        }
        
        foreach($data as $k=>$v){
            if($v["keyword"] == $keyword){
                $this->error = "关键字重复！";
                return -1;
            }else{
                $keyword = $v["keyword"];
            }
        
            $keywords .= "'".$v["keyword"]."',";
        }
        //验证同appid下不能有相同的关键字
        $check = $this->query("SELECT wx_reply.id FROM wx_reply
                               LEFT JOIN wx_keyword ON wx_reply.id=wx_keyword.reply_id
                               WHERE wx_reply.appid='{$appid}' AND wx_keyword.keyword IN (".rtrim($keywords,',').") ".$where." LIMIT 1");
        if(!empty($check)){
            $this->error = "关键字重复！";
            return -1;
        }
    }
    
    /**
     * 根据id获取高级图文
     * @param unknown $id
     * @return Ambigous <multitype:, unknown, mixed, boolean, NULL, string, object>
     */
    public function getById($id){
        $rows = array();
        $Model = M('wx_advanced_news');
        $rows = $Model->find($id);
    
        $rows2 = $Model->alias("wan")
                       ->where('wan.pid = '.$id)
                       ->select();
    
        if(!empty($rows2)){
            $rows['items'] = array();
    
            foreach($rows2 as $k1=>$v1){
                if($rows['id'] == $v1['pid']){
                    $rows['items'][] = $v1;
                }
            }
        }
        return $rows;
    }
}
?>