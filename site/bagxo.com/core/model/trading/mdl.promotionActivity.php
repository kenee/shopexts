<?php
include_once('shopObject.php');
class mdl_promotionActivity extends shopObject{
    var $idColumn = 'pmta_id'; //表示id的列 
    var $textColumn = 'pmta_name';
    var $defaultCols = 'pmta_id,pmta_name,pmta_time_begin,pmta_time_end,pmta_enabled,pmta_describe';
    var $adminCtl = 'sale/activity';
    var $defaultOrder = array('pmta_id','desc');
    var $tableName = 'sdb_promotion_activity';

    function getColumns(){
        return array(
                        'pmta_id'=>array('label'=>'序号','class'=>'span-1'),    /* 促销活动ID */
                        'pmta_name'=>array('label'=>'活动名称','class'=>'span-6'),    /* 促销活动名称 */
                        'pmta_enabled'=>array('label'=>'发布','class'=>'span-1','type'=>'bool'),    /* 促销活动 关闭/开启 */
                        'pmta_time_begin'=>array('label'=>'起始时间','class'=>'span-2','type'=>'time'),    /* 促销活动起始时间（只作为促销活动以下促销的起始时间的默认值） */
                        'pmta_time_end'=>array('label'=>'截止时间','class'=>'span-2','type'=>'time'),    /* 促销活动截止时间（同pmta_time_begin） */
                        'pmta_describe'=>array('label'=>'详细描述','class'=>'span-5'),    /* 促销活动详细描述 */
        );
    }

    function modifier_bool(&$rows,$options=array()){
        foreach($rows as $i=>$publish){
            $rows[$i] = ($publish=='true')?'是':'否';
        }
    }


    function searchOptions(){
        return array('pmta_name'=>'活动名称');
    }

    function _filter($filter){
        $where=array(1);
        if($filter['pmta_name']){
            $where[] = 'pmta_name like\'%'.$filter['pmta_name'].'%\'';
        }
        return parent::_filter($filter).' and '.implode($where,' and ');
    }

    function getActivityList($limit=true) {
        return $this->db->select_b('select * from sdb_promotion_activity order by pmta_id desc', $limit?PAGELIMIT:null);
    }

    function getActivityById($nId) {
        return $this->db->selectRow('select * from sdb_promotion_activity where pmta_id='.intval($nId));
    }

    function getSchemeList() {
        return $this->db->selectRow('select pmts_id,pmts_name from sdb_promotion_scheme');
    }

    function saveActivity($aData) {

        $aData['pmta_time_begin'] = strtotime($aData['pmta_time_begin']);
        $aData['pmta_time_end'] = strtotime($aData['pmta_time_end']);
        if ($aData['pmta_id']){
            $aRs = $this->db->query('SELECT * FROM sdb_promotion_activity WHERE pmta_id='.$aData['pmta_id']);
            $sSql = $this->db->getUpdateSql($aRs,$aData);
            return (!$sSql || $this->db->exec($sSql));
        }else{
            $aRs = $this->db->query('SELECT * FROM sdb_promotion_activity WHERE 0');
            $sSql = $this->db->getInsertSql($aRs,$aData);
            if ($this->db->exec($sSql)){
                return $this->db->lastInsertId();
            }else{
                return false;
            }            
        }
    }

    function delActivity($arrId,&$msg) {
        if ($arrId) {
            $sSql = 'delete from sdb_promotion_activity where  pmta_id in ('.implode($arrId, ',').')';
            if ($this->db->exec($sSql)) {
                $sSql = 'delete from sdb_promotion where  pmta_id in ('.implode($arrId, ',').')';
                $this->db->exec($sSql);
                return true;
            } else {
                $msg = __('数据删除失败！');
                return false;
            }
        }else{            
            $msg = 'no select';
            return false;
        }
    }
}
?>
