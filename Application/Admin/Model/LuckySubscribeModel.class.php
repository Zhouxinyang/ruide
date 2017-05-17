<?php
namespace Admin\Model;

use Think\Model;

/**
 * 幸运之星
 * @author lanxuebao
 *
 */
class LuckySubscribeModel extends Model
{
    protected $tableName = 'active_lucky_subscribe';
    
    /*
     * 获取幸运之星列表
     */
    public function getAll($where,$offset,$limit){
        //->limit($offset, $limit)
        $rows = array();
        $total = $this->alias("als")
                      ->field("als.*,user.nickname,user.sex,user.city,user.subscribe_time,user.unsubscribe_time")
                      ->join("wx_user AS user ON als.mid=user.mid")
                      ->where($where)
                      ->limit($offset, $limit)
                      ->count();
        
        if($total > 0){
            $rows = $this->alias("als")
                         ->field("als.*,user.nickname,user.sex,user.city,
                                  from_unixtime(user.subscribe_time,'%Y-%m-%d %H:%i:%s') AS subscribe_time,
                                  from_unixtime(user.unsubscribe_time,'%Y-%m-%d %H:%i:%s') AS unsubscribe_time")
                         ->join("wx_user AS user ON als.mid=user.mid")
                         ->where($where)
                         ->limit($offset, $limit)
                         ->select();
        }
        
        return $data = array("total" => $total,"rows" => $rows);
                    
    }
    
    /*
     * 抽取一名幸运之星
     */
    public function getLucky($parameter){
        $_where = "WHERE subscribe_time between ".strtotime($parameter['subscribe_start'])." AND ".strtotime($parameter['subscribe_end'])." AND subscribe=1 ";
        if(!empty($parameter['appid']))
            $_where .= " AND appid='".$parameter['appid']."'";
        if($parameter['city'] != '' && $parameter['city'] != '不限')
            $_where .= " AND city='".addslashes($parameter['city'])."'";
        else if($parameter['province'] != '' && $parameter['province'] != '不限')
            $_where .= " AND province='".addslashes($parameter['province'])."'";
        

        // 查询符合条件的总数
        $total = $this->query("SELECT COUNT(*) AS `total` FROM `wx_user` ".$_where);
        
        if(empty($total[0]["total"])){
            $this->error = '没有符合条件的会员';
            return 0;
        }
        $total = $total[0]['total'];
        
        // 随机取值
        $offset = rand(0, $total-1);
        $users = $this->query("SELECT * FROM wx_user {$_where} LIMIT {$offset},1");
        $user = $users[0];
        
        $data = array(
            'mid'     => $user['mid'],
            'lucky_time' => date('Y-m-d H:i:s')
        );
        $this->add($data);
        
        $user['lucky_time'] = $data['lucky_time'];
        return $user;
    }
}
?>