<?php
/**
 * mdl_tag 
 * 
 * @uses modelFactory
 * @package goods
 * @version $Id: mdl.tag.php 1867 2008-04-23 04:00:24Z flaboy $
 * @copyright 2003-2007 ShopEx
 * @author Ever <ever@shopex.cn> 
 * @license Commercial
 */

class mdl_tag extends modelFactory{

    /**
     * tagList 
     * 
     * @param mixed $type 
     * @param mixed $count 
     * @access public
     * @return void
     */
    function &tagList($type,$count=false,$joinTable=null,$obj_id=null){
        if($count){
            if($joinTable && $obj_id){
                $sql = "select t.tag_id,t.tag_name,t.tag_type,count(o.{$obj_id}) as rel_count
                 FROM sdb_tags t 
                 LEFT JOIN sdb_tag_rel r ON r.tag_id=t.tag_id 
                 LEFT JOIN {$joinTable} o ON r.rel_id=o.{$obj_id} and o.disabled!='true'
                 where tag_type='$type' group by t.tag_id";
            }else{
                $sql = "select t.tag_id,t.tag_name,t.tag_type,count(r.rel_id) as rel_count FROM sdb_tags t LEFT JOIN sdb_tag_rel r ON r.tag_id=t.tag_id where tag_type='$type' group by t.tag_id";
            }
        }else{
            $sql = "select * FROM sdb_tags where tag_type='$type'";    
        }
        $aRet = $this->db->select($sql);        
        return $aRet;
    }
//  
//  function getObjTagList($objid){
//    $aRet = $this->db->select("select * FROM sdb_tag_rel where rel_id=".intval($objid));
//    foreach($aRet as $rows){
//      $aTag[$rows['tag_id']] = 1;
//    }
//    return $aTag;
//  }


    function getTags($type,$id){
        $aRet = $this->db->select("select t.tag_name FROM sdb_tag_rel r left join sdb_tags t on t.tag_id=r.tag_id where t.tag_type='{$type}' and r.rel_id=".intval($id));
        foreach($aRet as $row){
            $aTag[] = $row['tag_name'];
        }
        return $aTag;
    }
    
    function getTagByName($type,$name){
        $aRet = $this->db->selectrow("SELECT tag_id FROM sdb_tags WHERE tag_type='{$type}' AND tag_name='{$name}'");
        return $aRet['tag_id'];
    }

    function tagId($tag,$type){
        $rs = $this->db->exec("select * from sdb_tags where tag_name='{$tag}' and tag_type='{$type}'");
        if($rows = $rs->getRows()){
            return $rows[0]['tag_id'];
        }else{
            $sql = $this->db->getInsertSQL($rs,array('tag_name'=>$tag,'tag_type'=>$type));
            if($this->db->exec($sql)){
                return $this->db->lastInsertId();
            }else{
                return false;
            }
        }
    }

    function removeObjTag($objid){
        foreach($this->db->select("select DISTINCT(tag_id) FROM sdb_tag_rel where rel_id=".intval($objid)) as $rows){
            $aDel[] = $rows['tag_id'];
        }
        $this->db->exec("DELETE FROM sdb_tag_rel where rel_id=".intval($objid));
        return $this->recount($aDel);
    }

    function begin(){
    }

    function addTag($tag_id,$obj_id){
        $this->db->exec('INSERT INTO `sdb_tag_rel` ( `tag_id`,`rel_id` ) VALUES ( '.intval($tag_id).','.intval($obj_id).' )');
        return $this->recount(array($tag_id));
    }

    function end(){
    }

    function newTag($tagName,$type){
        $tagName = trim($tagName);
        $rs = $this->db->exec('select * FROM sdb_tags');
        $sql = $this->db->getInsertSQL($rs,array('tag_name'=>$tagName,'tag_type'=>$type));
        if($this->db->exec($sql)){
            return $this->db->lastInsertId();
        }else{
            return false;
        }
    }

    function rename($tag_id,$name){
        $rs = $this->db->exec('select tag_name from sdb_tags where tag_id='.intval($tag_id));
        $sql = $sql = $this->db->getUpdateSQL($rs,array('tag_name'=>$name));
        return !$sql || $this->db->exec($sql);
    }

    function remove($tag_id){
        $this->db->exec('delete from sdb_tags where tag_id='.intval($tag_id));
        $this->db->exec('delete from sdb_tag_rel where tag_id='.intval($tag_id));
    }

    function recount($tags){
        foreach($tags as $tag_id){
            $row = $this->db->selectrow('select count(*) as count from sdb_tag_rel where tag_id='.intval($tag_id));
            $this->db->exec("update sdb_tags set rel_count={$row['count']} where tag_id=".intval($tag_id));
        }
    }
}
?>
