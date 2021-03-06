<?php
require_once(dirname(__FILE__).'/mdl.delivery.php');
class mdl_reship extends mdl_delivery{
    //START
    var $idColumn='delivery_id';
    var $adminCtl='order/reship';
    var $textColumn = 'delivery_id';
    var $defaultCols = 'delivery_id,order_id,t_begin,member_id,money,is_protect,ship_name,delivery,logi_name,logi_no';
    var $defaultOrder = array('t_begin','DESC');
    var $tableName = 'sdb_delivery';

    function getColumns(){
        return array(
                        'delivery_id'=>array('label'=>'退货单号','class'=>'span-3'),    /* 配送流水号 */
                        'order_id'=>array('label'=>'订单号','class'=>'span-3'),    /* 订单号 */
                        'member_id'=>array('label'=>'用户名','class'=>'span-2','type'=>'object:member'),    /* 会员id */
                        'money'=>array('label'=>'配送费用','class'=>'span-2','type'=>'money'),    /* 配送费用 */
                        'type'=>array('label'=>'配送类型','class'=>'span-2'),    /* 配送类型 */
                        'is_protect'=>array('label'=>'是否保价','class'=>'span-2','type'=>'is_protect'),    /* 是否保价 */
                        'delivery'=>array('label'=>'配送方式','class'=>'span-3'),    /* 配送方式(货到付款、EMS...) */
                        //'logi_id'=>array('label'=>'物流公司id','class'=>'span-3'),    /* 物流公司id */
                        'logi_name'=>array('label'=>'物流公司','class'=>'span-3'),    /* 物流公司 */
                        'logi_no'=>array('label'=>'物流单号','class'=>'span-3'),    /* 物流单号 */
                        'ship_name'=>array('label'=>'退货人','class'=>'span-3'),    /* 收货人姓名 */
                        'ship_area'=>array('label'=>'退货人地区','class'=>'span-3','type'=>'region'),    /* 收货人地区 */
                        'ship_addr'=>array('label'=>'退货人地址','class'=>'span-3'),    /* 收货人地址 */
                        'ship_zip'=>array('label'=>'退货人邮编','class'=>'span-3'),    /* 收货人邮编 */
                        'ship_tel'=>array('label'=>'退货人电话','class'=>'span-3'),    /* 收货人电话 */
                        'ship_mobile'=>array('label'=>'退货人手机','class'=>'span-3'),    /* 收货人手机 */
                        'ship_email'=>array('label'=>'退货人Email','class'=>'span-3'),    /* 收货人Email */
                        't_begin'=>array('label'=>'单据创建时间','class'=>'span-3','type'=>'time'),    /* 单据生成时间 */
                        //'t_end'=>array('label'=>'单据结束时间','class'=>'span-3','type'=>'time'),    /* 单据结束时间 */
                        'op_name'=>array('label'=>'管理员','class'=>'span-3'),    /* 操作者 */
                        //'status'=>array('label'=>'succ 配送成功','class'=>'span-3'),    /* succ 配送成功failed 支付失败cancel 未支付error 参数异常progress 处理中timeout 超时ready 准备中 */
                        'memo'=>array('label'=>'备注','class'=>'span-3'),    /* 备注 */
                );
    }
    
    function modifier_is_protect(&$rows){
        $status = array('true'=>'是',
                    'false'=>'否' );
        foreach($rows as $k => $v){
            $rows[$k] = $status[$v];
        }
    }
    
    function searchOptions(){
        return array(
                'logi_no'=>'物流单号',
                'order_id'=>'订单号'
            );
    }
    
    function getFilter($p){
        //$return['payment']=array_merge(array(array('id'=>0,'custom_name'=>__('线下支付'))),$this->getMethods());
        //$aDelivery=$this->getPlugins();
        return $return;
    }
    
    function _filter($filter){
        $filter['type'] = 'return';
        return parent::_filter($filter);
    }
    
    function toCreate(&$data){
        $data['delivery_id'] = $this->getNewNumber($data['type']);
        $rs = $this->db->query('select * from sdb_delivery where 0=1');
        $sqlString = $this->db->GetInsertSQL($rs, $data);
        return $this->db->exec($sqlString)?$data['delivery_id']:false;
    }
    
    function toInsertItem(&$data){
        if($data['type']=='delivery'){
            switch($data['status']){
            case 'succ':
                $dly_status='customer';
                break;
            case 'ready':
            case 'failed':
            case 'cancel':
                $dly_status='storage';
                break;
            case 'lost':
            case 'porgress':
                $dly_status='shipping';
                break;
            }
        }
        $this->db->exec("update sdb_order_items set dly_status=\"{$dly_status}\" where order_id = {$data['order_id']} and product_id = {$data['product_id']}");
        $rs = $this->db->query('select * from sdb_delivery_item where 0=1');
        $sqlString = $this->db->GetInsertSQL($rs, $data);
        return $this->db->query($sqlString);
    }
    
    function getNewNumber($type){
        if ($type == 'return'){
            $sign = 9;
        }else{
            $sign = 1;
        }
        $sqlString = "SELECT MAX(delivery_id) AS maxno FROM sdb_delivery
                WHERE type='delivery' AND delivery_id like '".$sign.date("Ymd", time())."%'";
        $aRet = $this->db->selectrow($sqlString);
        if(is_null($aRet['maxno'])) $aRet['maxno'] = 0;
        $orderid = substr($aRet['maxno'], -6) + 1;
        if ($orderid==1000000){
            $orderid = 1;
        }
        return $sign.date("Ymd").substr("00000".$orderid, -6);
    }

    function getConsignList($starttime,$endtime,$order_no,$logi_id,$consign_no,$logi_no){
        if(!empty($starttime)) {
            $tmpsql .= " AND consign_date>=".strtotime($starttime);
        }
        if(!empty($endtime)) {
            $tmpsql .= " AND consign_date<=".(strtotime($endtime)+86400);
        }
        if(!empty($order_no)) {
            $tmpsql .= " AND order_no ='{$order_no}'";
        }
        if(!empty($logi_id)) {
            $tmpsql .= " AND logi_id ='{$logi_id}'";
        }
        if(!empty($consign_no)) {
            $tmpsql .= " AND consign_no ='{$consign_no}'";
        }
        if(!empty($logi_no)) {
            $tmpsql .= " AND logi_no ='{$logi_no}'";
        }
        $sql = 'select id,consign_no,order_no,send_name,recv_name,logi_name,logi_fee,logi_no,consign_date,logi_type
                from sdb_mall_offer_consign 
                where bill_type=0 '.$tmpsql.' order by id desc';
        return $this->db->select($sql,0,null,PERPAGE,'consign');
    }
    
    function getReshipList($starttime,$endtime,$order_no,$consign_no) {
        if(!empty($starttime)) {
            $tmpsql .= " AND consign_date>=".strtotime($starttime);
        }
        if(!empty($endtime)) {
            $tmpsql .= " AND consign_date<=".(strtotime($endtime)+86400);
        }
        if(!empty($order_no)) {
            $tmpsql .= " AND order_no ='{$order_no}'";
        }
        if(!empty($consign_no)) {
            $tmpsql .= " AND consign_no ='{$consign_no}'";
        }
        $sql = 'select id,consign_no,order_no,consign_date,recv_name,uptime from sdb_mall_offer_consign where bill_type=1 '.$tmpsql.' order by id desc';
        return $this->db->select($sql,0,null,PERPAGE,'reship');
    }

    //wzp(2007-9-13)
    function consign($nStart,$nLimit,$aParame){
        if(!$limit)$limit = 20;
        foreach($aParame as $k=>$v){
            if($k=='t_begin' && $v!='')$sTmp.=' and '.$k.'>="'.$v.'"';
            elseif($k=='t_end' && $v!='')$sTmp.=' and '.$k.'<="'.$v.'"';
            elseif($v!='')$sTmp.=' and '.$k.'="'.$v.'"';
        }
        $aData=$this->db->selectRow('select count(*) as total from sdb_delivery where type="delivery"'.$sTmp);
        $aData['main']=$this->db->selectLimit('select * from sdb_delivery where type="delivery"'.$sTmp,intval($nLimit),intval($nStart),false,true);
        return $aData;
    }
    function consignDetail($nID){
        return $this->db->selectRow('select d.*,m.name as m_name from sdb_delivery d,sdb_members m where d.member_id=m.member_id and type="delivery" and delivery_id='.$nID);
    }
    function consignDetailEdit($nID,$aData){
        $aRs = $this->db->query('SELECT * FROM sdb_delivery WHERE type="delivery" and delivery_id='.$nID);
        $sSql = $this->db->GetUpdateSql($aRs, $aData);

        return (!$sSql || $this->db->exec($sSql));
    }
    function reship($nStart,$nLimit,$aParame){
        if(!$limit)$limit = 20;
        foreach($aParame as $k=>$v){
            if($k=='t_begin' && $v!='')$sTmp.=' and '.$k.'>="'.$v.'"';
            elseif($k=='t_end' && $v!='')$sTmp.=' and '.$k.'<="'.$v.'"';
            elseif($v!='')$sTmp.=' and '.$k.'="'.$v.'"';
        }
        $aData=$this->db->selectRow('select count(*) as total from sdb_delivery where type="return"'.$sTmp);
        $aData['main']=$this->db->selectLimit('select * from sdb_delivery where type="return"'.$sTmp,intval($nLimit),intval($nStart),false,true);
        return $aData;
    }
    function reshipDetail($nID){
        return $this->db->selectRow('select * from sdb_delivery where type="return" and delivery_id='.$nID);
    }
    function reshipDetailEdit($nID,$aData){
        $aRs = $this->db->query('SELECT * FROM sdb_delivery WHERE type="return" and delivery_id='.$nID);
        $sSql = $this->db->GetUpdateSql($aRs, $aData);
        return (!$sSql || $this->db->exec($sSql));
    }
// leolee -- 后台配送部分的管理
    function getDlTypeList(){
            return $this->db->select('SELECT dt_id, dt_name, ordernum FROM sdb_dly_type ORDER BY ordernum desc');
    }

    function getDlTypeById($nDlid){
        return $this->db->selectrow('SELECT * FROM sdb_dly_type WHERE dt_id='.$nDlid);
    }

    function getRelByDid($nDid){
        return $this->db->select('SELECT * FROM sdb_dly_h_area WHERE dt_id='.$nDid);
    }

    function updateType($aData,&$msg){
        if(!$aData['dt_id']){
            $msg = __('参数丢失！');
            return false;
        }
        if($aData['protect'] == 0){
            $aData['protect_rates'] = 0;
            $aData['minprice'] = 0;
        }
        if($aData['area']){
            foreach($aData['area'] as $val){
                if(!$aData['money'][$val]){
                    $msg = __('请填写"').$aData['areahide'][$val].__('"对应的配送费用');
                    return false;
                }else{
                    $aTemp[]=array('area_id'=>$val,'price'=>$aData['money'][$val],'has_cod'=>$aData['iscod'][$val]?1:0);
                }
            }
        }
        $aRs = $this->db->query('SELECT * FROM sdb_dly_type WHERE dt_id='.$aData['dt_id']);
        $sSql = $this->db->GetUpdateSql($aRs,$aData);
        if(!$sSql || $this->db->exec($sSql)){
            return $this->saveRelation($aData['dt_id'],$aTemp);
        }
        return false;
    }
    function saveRelation($nDid,$aData){
        foreach($aData as $val){
            $val['dt_id'] = $nDid;
            $aRs = $this->db->query('SELECT * FROM sdb_dly_h_area WHERE 0');
            $sSql = $this->db->GetInsertSql($aRs,$val);
            if($sSql){
                $this->db->exec($sSql);
            }
        }
        return true;
    }
    function updateTypeExtr($aData,&$msg){
        if(!$aData['dt_id']){
            $msg = __('参数丢失！');
            return false;
        }
        if($aData['area']){
            foreach($aData['area'] as $val){
                if(!$aData['money'][$val]){
                    $msg = __('请填写"').$aData['areahide'][$val].__('"对应的配送费用');
                    return false;
                }else{
                    $aTemp[]=array('area_id'=>$val,'price'=>$aData['money'][$val],'has_cod'=>$aData['iscod'][$val]?1:0);
                }
            }
        }
        $this->saveRelation($aTemp);
        return true;
    }
    function checkDlType($sName){
        $aTemp = $this->db->selectrow("SELECT dt_id FROM sdb_dly_type WHERE dt_name='".$sName."' order by ordernum desc");
        return $aTemp['dt_id'];
    }
    function insertDlType($aData,&$msg){
        if($this->checkDlType($aData['dt_name'])){
            $msg = __('该名称已经存在！');
            return false;
        }
        if($aData['area']){
            $aTemp = array();
            foreach($aData['area'] as $val){
                if(!$aData['money'][$val]){
                    $msg = __('请填写"').$aData['areahide'][$val].__('"对应的配送费用');
                    return false;
                }else{
                    $aTemp[]=array('dt_id'=>$aData['dt_id'],'area_id'=>$val,'price'=>$aData['money'][$val],'has_cod'=>$aData['iscod'][$val]?1:0);
                }
            }
        }
        $aRs = $this->db->query('SELECT * FROM sdb_dly_type WHERE 0');
        $sSql = $this->db->GetInsertSql($aRs,$aData);
        if($sSql){
            if($this->db->exec($sSql)){
                $id = $this->db->lastInsertId();
                return $this->saveRelation($id,$aTemp);
            }

        }
    }
    function deleteDlType($aId){
        if($aId){
            $sSql = 'DELETE FROM sdb_dly_type WHERE dt_id IN ('.$aId.')';            
            return $this->db->exec($sSql);
        }else{
            return false;
        }
    }
    //配送地区
    function checkDlArea($sName){
        $aTemp = $this->db->selectrow("SELECT area_id FROM sdb_dly_area WHERE name='".$sName."' order by ordernum desc");
        return $aTemp['area_id'];
    }
    function getDlAreaList(){
        return $this->db->select('SELECT * FROM sdb_dly_area WHERE 1');
    }
    function getDlAreaById($aAreaId){
        return $this->db->selectrow('SELECT * FROM sdb_dly_area WHERE area_id='.$aAreaId);
    }
    function insertDlArea($aData,&$msg){
        if(!trim($aData['name'])){
            $msg = __('地区名称不能为空！');
            return false;
        }
        if($this->checkDlArea($aData['name'])){
            $msg = __('该地区名称已经存在！');
            return false;
        }
        $aRs = $this->db->query('SELECT * FROM sdb_dly_area WHERE 0');
        $sSql = $this->db->GetInsertSql($aRs,$aData);
        return (!$sSql || $this->db->exec($sSql));
    }
    function updateDlArea($aData,&$msg){
        if(!$aData['area_id']){
            $msg = __('参数丢失！');
            return false;
        }
        $aRs = $this->db->query('SELECT * FROM sdb_dly_area WHERE area_id='.$aData['area_id']);
        $sSql = $this->db->GetUpdateSql($aRs,$aData);
        return (!$sSql || $this->db->exec($sSql));
    }
    function deleteDlArea($sId){
        $sSql = '';
        if($sId){
            $sSql = $sId?'DELETE FROM sdb_dly_area WHERE area_id in ('.$sId.')':'';
        }
        return (!$sSql || $this->db->exec($sSql));
    }
    function assistantInsertArea($aData){
        if($this->checkDlArea($aData['name'])){
            return -1;
        }
        $aRs = $this->db->query('SELECT * FROM sdb_dly_area WHERE 0');
        $sSql = $this->db->GetInsertSql($aRs,$aData);
        if(!$sSql || $this->db->exec($sSql)){
            return $this->db->lastInsertId();
        }else{
            return 0;
        }
    }
    // 配送公司
    function getCropList(){
        return $this->db->select('SELECT * FROM sdb_dly_corp WHERE 1 order by ordernum desc');
    }
    function getCorpById($nCorpId){
        return $this->db->selectrow('SELECT * FROM sdb_dly_corp WHERE corp_id='.$nCorpId);
    }
    function checkCorp($sName){
        $aTemp = $this->db->selectrow("SELECT corp_id FROM sdb_dly_corp WHERE name='".$sName."'");
        return $aTemp['corp_id'];
    }
    function insertCorp($aData,&$msg){
        if($this->checkCorp($aData['name'])){
            $msg = __('该物流公司已经存在！');
            return false;
        }
        $aRs = $this->db->query('SELECT * FROM sdb_dly_corp WHERE 0');
        $sSql = $this->db->GetInsertSql($aRs,$aData);
        return (!$sSql || $this->db->exec($sSql));
    }
    function updateCorp($aData,&$msg){
        if(!$aData['corp_id']){
            $msg = __('参数丢失！');
            return false;
        }
        $aRs = $this->db->query('SELECT * FROM sdb_dly_corp WHERE corp_id='.$aData['corp_id']);
        $sSql = $this->db->GetUpdateSql($aRs,$aData);
        return (!$sSql || $this->db->exec($sSql));
    }
    function deleteCorp($sId){
        if($sId){
            $sSql = 'DELETE FROM sdb_dly_corp WHERE corp_id IN ('.$sId.')';
            return $this->db->exec($sSql);
        }
        return false;
    }
    
    function toRemove($id){
        $sqlString = "DELETE FROM sdb_delivery WHERE delivery_id='".$id."'";
        $this->db->exec($sqlString);
        
        $sqlString = "DELETE FROM sdb_delivery_item WHERE delivery_id='".$id."'";
        $this->db->exec($sqlString);
    }
}
