<?php
include_once('shopObject.php');
class mdl_pubfile extends shopObject {

    var $defaultCols = 'file_name,file_ident,cdate,memo';
    var $idColumn = 'file_id';
//  var $adminCtl = 'member/member';
    var $textColumn = 'file_name';
    var $defaultOrder = array('file_id','desc');
    var $tableName = 'sdb_pub_files';

    function getColumns(){
        return array(
//        'file_id'=>array('label'=>'','class'=>'span-3','readonly'=>true),    /* 会员id */
                'file_name'=>array('label'=>'文件名','class'=>'span-3'),    /* 登陆名称 */
                'file_ident'=>array('label'=>'文件','class'=>'span-3'),    /* 会员姓名 */
                'cdate'=>array('label'=>'日期','class'=>'span-3'),    /* 会员姓名 */
                'memo'=>array('label'=>'描述','class'=>'span-3'),    /* 会员姓名 */
                );
    }
    function _filter($filter){
        $where=array(1);
        if($filter['file_type']){
            $where[]='file_type = '.intval($filter['file_type']);
        }
        return parent::_filter($filter).' and '.implode($where,' AND ');
    }
    function insert($data){

        $tags = $data['tags'];
        unset($data['tags']);

        if($imgId = parent::insert($data)){
            $tag = &$this->system->loadModel('system/tag');
            foreach($tags as $t){
                if($tagid = $tag->tagId($t,'image')){
                    $tagList[] = $tagid;
                    $this->db->exec('insert into sdb_tag_rel (tag_id,rel_id) values ('.$tagid.','.$imgId.')');
                }
            }
            $tag->recount($tagList);
            return $imgId;
        }else{
            return false;
        }

    }
}
?>
