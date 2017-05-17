<?php
namespace Admin\Model;

use Think\Model;

/**
 * 高级图文
 * @author wangjing
 *
 */
class AdvancedNewsModel extends Model
{
    protected $tableName = 'wx_advanced_news';
    
    /*
     * 高级图文列表
     */
    public function getAll($where,$offset,$limit){
        $rows = array();
        $total = $this->alias("wan")
                      ->field("wan.*")
                      ->where($where)
                      ->limit($offset, $limit)
                      ->count();
        
        if($total > 0){
            $rows = $this->alias("wan")
                         ->where($where)
                         ->order("wan.id asc")
                         ->limit($offset, $limit)
                         ->select();
            $rows2 = $this->alias("wan")
                         ->where("pid != 0")
                         ->order("wan.id asc")
                         ->select();
            foreach($rows as $k=>$v){
                $rows[$k]['items'] = array();
                foreach($rows2 as $k1=>$v1){
                    if($v['id'] == $v1['pid']){
                        $rows[$k]['items'][] = $v1;
                    }
                }
            }
        }
        return $data = array("total" => $total,"rows" => $rows);
    }
    
    /*
     * 添加图文
     */
    public function insert($data){
        $weixin = C("WEIXIN");
        $data = $data['data'];
        $data[0]['appid'] = $weixin['appid'];
        $data[0]['created'] = date("Y-m-d H:i:s",time());
        
        $length = count($data);
        if($length == 1){
            $this->add($data[0]);
        }else if($length > 1){
            $pid = $this->add($data[0]);
            unset($data[0]);
            if(!empty($data)){
                foreach($data as $k=>$v){
                    $data[$k]['pid'] = $pid;
                    $data[$k]['appid'] = $weixin['appid'];
                    $data[$k]['created'] = date("Y-m-d H:i:s",time());
                    $this->add($data[$k]);
                }
            }
        }else{
            $this->error = '数据不能为空！';
            return 0;
        }
        return '添加成功！';
    }
    
    /*
     * 编辑图文
     */
    public function update($data){
        $data = $data['data'];
        $length = count($data);
        if($length == 1){
            $this->where("pid = %d",$data[0]['id'])->delete();
            $this->save($data[0]);
        }else if($length > 1){
            $ids_arr = array();//原始id
            $child_ids = $this->where("pid = %d",$data[0]['id'])->field("group_concat(id) as ids")->find();
            if(!empty($child_ids['ids'])){
                $ids = $child_ids['ids'].','.$data[0]['id'];
                $ids_arr = explode(',',$ids);
            }else{
                $ids_arr = array($data[0]['id']);
            }
            
            foreach($data as $k=>$v){
                if(isset($v['id']) && !empty($v['id'])){
                    $new_ids[] = $v['id'];
                    $this->save($v);
                }else{
                    $weixin = C("WEIXIN");
                    $v['appid'] = $weixin['appid'];
                    $v['pid'] = $data[0]['id'];
                    $v['created'] = date("Y-m-d H:i:s",time());
                    $this->add($v);
                }
            }
            
            $cha = array_diff($ids_arr,$new_ids);//剩余id
            if(!empty($cha)){
                $delete_id = implode(',', $cha);
                $this->where("id IN(".$delete_id.")")->delete();
            }
        }else{
            $this->error = '数据不能为空！';
            return 0;
        }
        return '编辑成功！';
    }
    
    /*
     * 获取单条图文
     */
    public function getOne($id){
        $list = array();
        $list[0] = $this->alias("wan")
                    ->field("wan.id,wan.pid,wan.title,wan.digest,wan.link,wan.cover_url")
                    ->where("wan.id = %d",$id)
                    ->find();
        $child_list = $this->alias("wan")
                     ->field("wan.id,wan.pid,wan.title,wan.digest,wan.link,wan.cover_url")
                     ->where("wan.pid = %d",$id)
                     ->order("created asc")
                     ->select();
        if(!empty($child_list)){
            foreach($child_list as $k=>$v){
                $list[] = $v;
            }
        }
        return $list;
    }
}
?>