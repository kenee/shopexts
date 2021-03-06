<?php
require_once('shopObject.php');
class mdl_article extends shopObject {

    var $idColumn = 'article_id';
    var $textColumn = 'title';
    var    $adminCtl = 'content/articles';
    var $defaultCols = 'title,uptime,ifpub,node_id,preview';
    var $defaultOrder = array('uptime','desc');
    var $tableName = 'sdb_articles';
    var $hasTag = false;

    function getColumns(){
        return array(
                'title'=>array('label'=>'文章标题','class'=>'span-8'),    /* 文章标题 */
                'node_id'=>array('label'=>'所属栏目','class'=>'span-3','type'=>'node_name'),    /* 所属栏目 */
                'uptime'=>array('label'=>'更新时间','class'=>'span-3','type'=>'time'),    /* 更新时间 */
                'ifpub'=>array('label'=>'是否发布','class'=>'span-3','type'=>'bool'),    /* 是否发布0不发布,1发布 */
                'preview'=>array('label'=>'预览','class'=>'span-3','sql'=>'article_id','type'=>'preview'),    /* 排序 */            
            );
    }
    function getFilter(){
       $filter['article_cat'] = $this->getArticleCat();
       return $filter;
    }
    function _filter($aFilter){
        if($aFilter['title']!=''){
            $where[] = 'title LIKE \''.$aFilter['title'].'%\'';
        }

        $ndata = $this->db->select("select node_id from sdb_sitemaps");
        foreach($ndata as $key => $v){
            $data[] = $v['node_id'];
        };
        $where[] = 'node_id in ('.implode(",",$data).')';
 
        if($aFilter['article_id']){
            if(is_array($aFilter['article_id'])){
                foreach($aFilter['article_id'] as $id){
                    if($id!='_ANY_'){
                        $aId[] = intval($id);
                    }
                }
                if(count($aId)>0){
                    $where[] = 'article_id IN ('.implode(',', $aId).')';
                }
            }else{
                $where[] = 'article_id='.$aFilter['article_id'];
            }
        }
        
        if($aFilter['node_id']){
            if(is_array($aFilter['node_id'])){
                foreach($aFilter['node_id'] as $catid){
                    if($catid!='_ANY_'){
                        $aCats[] = intval($catid);
                    }
                }
                if(count($aCats)>0){
                    $where[] = 'node_id IN ('.implode(',', $aCats).')';
                }
            }else{
                $where[] = 'node_id='.$aFilter['node_id'];
            }
        }
        unset($aFilter['node_id']);
        unset($aFilter['title']);
        
        if(count($where)>0){
            return implode(' AND ',$where).' AND '.parent::_filter($aFilter);
        }else{
            return parent::_filter($aFilter);
        }
    }

    function get($art_id) {
        $sql='SELECT * FROM sdb_articles WHERE article_id='.intval($art_id);
        return $aTemp = $this->db->selectRow($sql);
    }

    function saveArticle($aData){
        $aData['uptime'] = time();
        $aRs=$this->db->query("SELECT * FROM sdb_articles WHERE article_id=".$aData['article_id']);
        $sSql=$this->db->getUpdateSQL($aRs, $aData);
        return (!$sSql || $this->db->exec($sSql));
    }
    function searchOptions(){
        return array(
            'title'=>'文章名称'
        );
    }
    function addArticle($data){
        $data['uptime'] = time();
        $rs=$this->db->query('SELECT * FROM sdb_articles WHERE 0=1');
        $sql= $this->db->GetInsertSQL($rs, $data);
        return $this->db->exec($sql);
    }
    function modifier_preview(&$rows){
        foreach($rows as $name=>$value){
            $rows[$name]='<a href="'.
                $this->system->realUrl('article','index',array($value),null,$this->system->base_url()).'" target="_blank" title="点击这里前台预览">预览</a>';
        }
    }
    function modifier_node_name(&$rows){
        $result=$this->db->select('SELECT node_id,title FROM sdb_sitemaps WHERE node_id in (\''.implode($rows,'\',\'').'\')');
        foreach($result as $name=>$value){
            $rows[$value['node_id']]=$value['title'];
        }
        unset($rows);
     }
    function getArticleCat(){
        $sSql='SELECT title,node_id from sdb_sitemaps where node_type="articles"';
        return $this->db->select($sSql);
    }
    function delArticle($aID) {
        $sSql='SELECT article_id,filename from sdb_articles WHERE article_id in ('.$strId.')';
        return $this->db->exec($sSql);
    }

    function getCategorys(){
        $ret = array();
        foreach($this->db->select('select node_id,title from sdb_sitemaps where node_type=\'articles\'') as $cat){
            $ret[$cat['node_id']] = $cat['title'];
        }
        return $ret;
    }
}
?>
