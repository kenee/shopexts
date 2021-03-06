<?php
/**
 * mdl_advance 
 * 
 * @uses modelFactory
 * @package 
 * @version $Id: mdl.advance.php 1867 2008-04-23 04:00:24Z flaboy $
 * @copyright 2003-2007 ShopEx
 * @author Wanglei <flaboy@zovatech.com> 
 * @license Commercial
 */
 include_once('shopObject.php');
class mdl_advance extends shopObject{

    var $idColumn = 'log_id'; //表示id的列
    var $adminCtl = 'member/advance';
    var $textColumn = 'memo';
    var $defaultCols = 'member_id,mtime,memo,import_money,explode_money,member_advance,paymethod,payment_id,order_id,message';
    var $defaultOrder = array('log_id','DESC');
    var $orderAble = false;
    var $tableName = 'sdb_advance_logs';
//log_id  member_id  money  message  mtime  payment_id  order_id  paymethod  memo  import_money  explode_money  member_advance  shop_advance 
    function getColumns(){
        return array(
                        'log_id'=>array('label'=>'日志id','class'=>'span-3','readonly'=>true),    
                        'member_id'=>array('label'=>'用户','class'=>'span-3','type'=>'memberinfo'),   
                        'mtime'=>array('label'=>'交易时间','class'=>'span-2','type'=>'time'),    
                        'memo'=>array('label'=>'业务摘要','class'=>'span-3'),    
                        'import_money'=>array('label'=>'存入金额','class'=>'span-3','type'=>'import_money'),    
                        'explode_money'=>array('label'=>'支出金额','class'=>'span-3','type'=>'explode_money'),    
                        'member_advance'=>array('label'=>'当前余额','class'=>'span-3'),   
                        'paymethod'=>array('label'=>'支付方式','class'=>'span-3'),     
                        'payment_id'=>array('label'=>'支付单号','class'=>'span-3'),    
                        'order_id'=>array('label'=>'订单号','class'=>'span-3'),      
                        'message'=>array('label'=>'管理备注','class'=>'span-3'),   
                        'money'=>array('label'=>'出入金额','class'=>'span-3','readonly'=>true),  
                        'shop_advance'=>array('label'=>'商店余额','class'=>'span-3','readonly'=>true),    
                );
    }

    function searchOptions(){
        return array(
                'uname'=>'用户名'
            );
    }
    function modifier_import_money(&$row){
        foreach($row as $k=>$v){
            $row[$k]=($row[$k]>0)?$row[$k]:'-';
        }
    }
    function modifier_explode_money(&$row){
        foreach($row as $k=>$v){
            $row[$k]=($row[$k]>0)?$row[$k]:'-';
        }
    }
    function modifier_memberinfo(&$row){
        foreach( $this->db->select("SELECT member_id, uname, name FROM sdb_members WHERE member_id IN (".implode(",",array_keys($row)).")") as $v ){
            $row[$v['member_id']] = $v['name']."(".$v['uname'].")";
        }
    }

    function _filter($filter){
        $sdtime = '';
        if($filter['sdtime'])
            $sdtime = explode("/",$filter['sdtime']);
        else
            $sdtime = explode("/",$filter['sdtimecommon']);
        if(count($sdtime) == 1)
            $sdtime = explode('%2F', $sdtime[0]);
        $where = array(1);
        $filter['start_date'] = $sdtime[0];
        $filter['end_date'] = $sdtime[1];
        if($filter['start_date'])
            $where[] = " mtime >= ".strtotime($filter['start_date']);
        if($filter['end_date'])
            $where[] = " mtime <= ".(strtotime($filter['end_date'])+3600*24);
        unset($filter['sdtime'],$filter['sdtimecommon'],$sdtime);
        return parent::_filter($filter).' AND '.implode($where,' AND ');
    }

    /**
     * toLog 增加预存款记录
     * 
     * @param mixed $data 
     * @access public
     * @return void
     */
    function toLog($data){
        if($rs = $this->db->query('SELECT advance,member_id FROM sdb_members WHERE member_id='.intval($data['member_id']))){
            $sqlString = $this->db->GetUpdateSQL($rs, $data);
            if(!$sqlString || $this->db->exec($sqlString)){
                return true;
            }else{
                return false;    
            }
        }else{
            return false;
        }
    }
    
    function checkAccount($member_id,$money=0,&$errMsg,&$rows){
        if($rs = $this->db->exec('SELECT advance,member_id FROM sdb_members WHERE member_id='.intval($member_id))){
            $rows = $this->db->getRows($rs,1);
            if(count($rows)>0){
                if($money>$rows[0]['advance']){
                    $errMsg .= '预存款帐户余额不足';
                    return 0;
                }else{
                    return $rows;
                }
            }else{
                $errMsg .= __('预存款帐户不存在');
                return false;
            }
        }else{
            $errMsg .= __('查询预存款帐户失败');
            return false;
        }
    }

    /**
     * add 预存款充值
     * 
     * @param mixed $member_id 
     * @param mixed $money 
     * @param mixed $message 
     * @access public
     * @return void
     */
    function add($member_id,$money,$message,&$errMsg, $payment_id='', $order_id='' ,$paymethod='' ,$memo=''){
        if($rows = $this->checkAccount($member_id,0,$errMsg)){
            $data=array('advance'=>$rows[0]['advance'] + $money);
            $member_advance = $data['advance'];
            $rs = $this->db->exec('SELECT * FROM sdb_members WHERE member_id='.intval($member_id));
            $sql = $this->db->getUpdateSQL($rs,$data);
            if($this->db->exec($sql)){
                $this->log($member_id,$money,$message, $payment_id, $order_id ,$paymethod ,$memo ,$member_advance);
                return true;
            }else{
                $errMsg .= __('更新预存款帐户失败');
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * deduct 扣除预存款
     * 
     * @param mixed $member_id 
     * @param mixed $money 
     * @param mixed $message 
     * @access public
     * @return void
     */
    function deduct($member_id,$money,$message,&$errMsg, $payment_id='', $order_id='' ,$paymethod='' ,$memo=''){
        if($rows = $this->checkAccount($member_id,$money,$errMsg)){
            $data=array('advance'=>$rows[0]['advance']-$money);
            $member_advance = $data['advance'];
            $rs = $this->db->exec('SELECT * FROM sdb_members WHERE member_id='.intval($member_id));
            $sql = $this->db->getUpdateSQL($rs,$data);
            if(!$sql || $this->db->exec($sql)){
                $this->log($member_id,-$money,$message, $payment_id, $order_id ,$paymethod ,$memo ,$member_advance );
                return true;
            }else{
                $errMsg .= __('更新预存款帐户失败');
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * log 取得记录
     * 
     * @param mixed $member_id 
     * @param mixed $start 
     * @param mixed $end 
     * @access public
     * @return void
     */
    function log($member_id,$money,$message, $payment_id='', $order_id='' ,$paymethod='' ,$memo='' ,$member_advance='' ){
        $shop_advance = $this->getShopAdvance();
        $rs = $this->db->exec('select * from sdb_advance_logs where 0=1');
        $sql = $this->db->getInsertSQL($rs,array(
            'member_id'=>$member_id,
            'money'=>$money,
            'mtime'=>time(),
            'message'=>$message,
            'payment_id'=>$payment_id,
            'order_id'=>$order_id,
            'paymethod'=>$paymethod,
            'memo'=>$memo,
            'import_money'=>($money>0?$money:0),
            'explode_money'=>($money<0?-$money:0),
            'member_advance'=>$member_advance,
            'shop_advance'=>$shop_advance
            ));
        return $this->db->exec($sql);
    }

    /**
     * getListByMemId 取得现有预存款
     * 
     * @param mixed $member_id 
     * @access public
     * @return void
     */
    function getListByMemId($member_id){
        return $this->db->select('SELECT * FROM sdb_advance_logs WHERE member_id='.$member_id);
    }
    
    function getFrontAdvList($memberId,$nPage,$perpage=PERPAGE){
        return $this->db->select_f('SELECT * FROM sdb_advance_logs WHERE member_id='.$memberId.' ORDER BY mtime DESC',$nPage,$perpage);
    }

    function getShopAdvance(){
        $row = $this->db->selectrow("SELECT SUM(advance) as sum_advance FROM sdb_members");
        return $row['sum_advance'];
    }

    /**
     * get 取得现有预存款
     * 
     * @param mixed $member_id 
     * @access public
     * @return void
     */
    function get($member_id){
        $row = $this->db->selectrow('SELECT advance FROM sdb_members WHERE member_id='.intval($member_id));
        return $row['advance'];
    }
    
    /**
     * getFrontAdvList 取得现有预存款的详细日志
     * 
     * @param mixed $member_id 
     * @access public
     * @return void
    function getFrontAdvDetailList($memberId,$nPage){
        return $this->db->select_f('SELECT money, message, mtime FROM sdb_advance_logs l LEFT JOIN sdb_payment p ON  WHERE member_id='.$memberId.' ORDER BY mtime DESC',$nPage,PERPAGE);
    }
     */

    function getAdvanceStatistics($sdate=null,$edate=null){
        $sql = 'SELECT COUNT(*) AS count, SUM(import_money) AS import_money, SUM(explode_money) AS explode_money FROM sdb_advance_logs ';
        $where = array();

        if($sdate)
            $where[] = " mtime >= ".strtotime($sdate);
        if($edate)
            $where[] = " mtime <= ".(strtotime($edate)+3600*1000);

        if(!empty($where)){
            $sql .= ' WHERE '.implode(' AND ',$where);
        }
        return $this->db->selectrow($sql);
    }
    
    function getMemberAdvanceStatistics($mId){
        $sql = 'SELECT COUNT(*) AS count, SUM(import_money) AS import_money, SUM(explode_money) AS explode_money FROM sdb_advance_logs WHERE member_id = '.$mId;
        return $this->db->selectrow($sql);
    }

    function getAdvanceLogByLogId($logid){
        return $this->db->selectrow('SELECT * FROM sdb_advance_logs WHERE log_id = '.$logid);
    }
}
?>
