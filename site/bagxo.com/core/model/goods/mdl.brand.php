<?php
include_once('shopObject.php');
/**
 * mdl_brand 
 * 
 * @uses shopObject
 * @package goods
 * @version $Id: mdl.brand.php 1867 2008-04-23 04:00:24Z flaboy $
 * @copyright 2003-2007 ShopEx
 * @license Commercial
 */
class mdl_brand extends shopObject{

    var $idColumn = 'brand_id';
    var $textColumn = 'brand_name';
    var $adminCtl = 'goods/brand';
    var $defaultCols = 'brand_name,brand_url,brand_logo,ordernum';
    var $defaultOrder = array('ordernum','desc');
    var $tableName = 'sdb_brand';

    function getColumns(){
        return array(
                        'brand_id'=>array('label'=>'品牌id','class'=>'span-4'),    /* 品牌id */
                        'brand_name'=>array('label'=>'品牌名称','class'=>'span-5','required'=>1),    /* 品牌名称 */
                        'brand_url'=>array('label'=>'品牌网址','class'=>'span-9'),    /* 品牌网址 */
                        'brand_keywords'=>array('label'=>'别名','class'=>'span-4'),    /* 别名 */
                        'ordernum'=>array('label'=>'排序','class'=>'span-4'),    /* 排序 */
        );
    }
    function brand2json($return=false){
        @set_time_limit(600);
        $file=MEDIA_DIR.'/brand_list.data';
        $contents=$this->db->select('SELECT brand_id,brand_name,brand_url,ordernum,brand_logo FROM sdb_brand WHERE disabled = \'false\' order by ordernum desc');
        if($return){
            file_put_contents($file,json_encode($contents));
            return $contents;
        }else{
            return file_put_contents($file,json_encode($contents));
        }
    }

    function getAll(){
        $file=MEDIA_DIR.'/brand_list.data';
        if(($contents=file_get_contents($file))){
            if(($result=json_decode($contents,true))){
                return json_decode($contents,true);
            }else{
                return $this->brand2json(true);
            }
        }else{
            return $this->brand2json(true);
        }
    }
    function getFieldById($brand_id, $aPara){
        $sqlString = 'SELECT '.implode(',', $aPara).' FROM sdb_brand WHERE brand_id = '.intval($brand_id);
        return $this->db->selectrow($sqlString);
    }
    function getTypeBrands($typeid){
        if(is_array($typeid)){
            foreach($typeid as $id){
                $where[] = 'type_id = '.intval($id);
            }
            if($where) $sql = ' WHERE '.implode(' OR ', $where);
        }else{
            $sql = ' WHERE type_id = '.intval($typeid);
        }
        $sql .= ' AND disabled =\'false\'';
        return $this->db->select('SELECT b.*,type_id FROM sdb_brand b LEFT JOIN sdb_type_brand t ON b.brand_id = t.brand_id'.$sql.' ORDER BY ordernum desc,brand_order');
    }
    
    function getBrandTypes($brandid){
        return $this->db->select('SELECT t.* FROM sdb_goods_type t LEFT JOIN sdb_type_brand b ON t.type_id = b.type_id
                WHERE brand_id = '.$brandid);
    }
    
    function getDefinedType(){
        $oType = $this->system->loadModel('goods/gtype');
        $aType = $oType->getList('type_id,name,setting,is_def',null,-1,-1);
        foreach($aType as $row){
            if($row['is_def'] == 'true'){
                $brandType['default'] = $row;
            }else{
                $row['setting'] = unserialize($row['setting']);
                if($row['setting']['use_brand']){
                    $brandType['custom'][] = $row;
                }
            }
        }
        return $brandType;
    }

    function getBrandGroup($cat_id){
        $cat_id='('.implode($cat_id,' OR ').')';
        if($cat_id){
            $sql='SELECT COUNT(brand_id) as brand_cat,brand_id From sdb_goods where cat_id in'.$cat_id.' AND marketable="true" AND disabled="false" GROUP By brand_id';
            return $this->db->select($sql);
        }
    }

    function save($brand_id,$aData){
        $storager = $this->system->loadModel('system/storager');
        if($_FILES){
            $aData['brand_logo'] = $storager->save_upload($_FILES['brand_logo'],'brand');
        }
        if(!$aData['brand_logo'])unset($aData['brand_logo']);
        if ($brand_id){
            $aData['brand_id'] = intval($brand_id);
            $aRs = $this->db->query("SELECT * FROM sdb_brand WHERE brand_id=".$aData['brand_id']);
            $r = $this->db->getRows($aRs);
            if($r[0]['brand_logo'] && $_FILES){
                $storager->remove($r[0]['brand_logo']);
            }
            $sSql = $this->db->getUpdateSql($aRs,$aData);
            $this->db->exec('DELETE FROM sdb_type_brand WHERE brand_id = '.$aData['brand_id']);
            if($sSql) $this->db->exec($sSql);
        }else{
            $aRs = $this->db->query("SELECT * FROM sdb_brand WHERE 0=1");
            $sSql = $this->db->getInsertSql($aRs,$aData);
            $this->db->exec($sSql);
            $aData['brand_id'] = $this->db->lastInsertId();
        }
        
        if($aData['gtype']){
            foreach($aData['gtype'] as $typeId){
                $aData['type_id'] = $typeId;
                $rs = $this->db->query('SELECT * FROM sdb_type_brand WHERE 0=1');
                $sql = $this->db->getInsertSQL($rs,$aData);
                $this->db->exec($sql);
            }
        }
        $this->brand2json();
        $this->system->cache->clear();
        return true;
    }
    
    function toRemove($aParam){
        $this->system->cache->clear();
        if(!empty($aParam)){
            $this->db->exec('DELETE FROM sdb_brand WHERE brand_id IN ('.implode(',', $aParam).')');
            $this->db->exec('DELETE FROM sdb_type_brand WHERE brand_id IN ('.implode(',', $aParam).')');
            return true;
        }else{
            return true;
        }
    }
    
    function getBrandbyAlias($cols,$key){
        $sqlString = 'SELECT '.$cols.' FROM sdb_brand WHERE brand_name = "'.$key.'" or brand_keywords like \'%|'.$key.'|%\' order by ordernum desc';
        return $this->db->selectrow($sqlString);
    }
    
    function getBrandsByNames($aBrandName=array()){
        $sqlString = 'SELECT brand_name,brand_id FROM sdb_brand WHERE brand_name IN (\''.implode('\',\'',$aBrandName).'\')';
        $aRet = array();
        foreach($this->db->select($sqlString) as $row){
            $aRet[$row['brand_name']] = $row['brand_id'];
        }
        return $aRet;
    }
}
?>
