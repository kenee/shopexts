<?php
/**
 * 
 * 程序处理文件
 *
 * @package  ShopEx网上商店系统
 * @version  4.6
 * @author   ShopEx.cn <develop@.cn>
 * @url        http://www..cn/
 * @since    PHP 4.3
 * @copyright ShopEx.cn
 *
 **/

require_once(dirname(__FILE__).'/mdl.adminProfile.php');

class mdl_opSession extends modelFactory{

    var $closed = false;

    function start(){

//    session_write_close();
//    header("Cache-Control: no-cache, must-revalidate");
//    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//    header("Pragma: no-cache");

        if(isset($_GET['sess_id'])){
            $this->sess_id = $_GET['sess_id'];
            if($_COOKIE['SHOPEX_SID']!=$_GET['sess_id'])
                setcookie('SHOPEX_SID',$this->sess_id);
        }elseif($_COOKIE['SHOPEX_SID']){
            $this->sess_id = $_COOKIE['SHOPEX_SID'];
        }else{
            $this->sess_id = md5(microtime().remote_addr().mt_rand(0,9999));
            setcookie('SHOPEX_SID',$this->sess_id);
        }

        $this->_sess_clean(7200);
        $this->reload();
        register_shutdown_function(array(&$this,'_sess_write'));
    }

    function lock(){
        $sql = "UPDATE sdb_op_sessions
            SET status='2'
            WHERE sess_id='".$this->sess_id."'";
        return $this->db->exec($sql,true,true);
    }

    function unlock($aValue){
        $nId = $this->db->selectrow("SELECT username 
            FROM sdb_operators 
            WHERE username = '".$_SESSION['USERNAME']."' AND userpass = '".md5($aValue['password'])."'",true);
        if($nId['username']){
            $sql = "UPDATE sdb_op_sessions
                SET status='1'
                WHERE sess_id='".$this->sess_id."'";
            return $this->db->exec($sql,true,true);
        }else
            return false;
    }

    function isNotlocked(){
        $aNum = $this->db->selectrow("SELECT status 
            FROM sdb_op_sessions 
            WHERE sess_id='".$this->sess_id."'",true,true);
        return    $aNum['status']==2;
    }

    function logout(){
        $sql = "UPDATE sdb_op_sessions
            SET status='0'
            WHERE sess_id='".$this->sess_id."'";
        return $this->db->exec($sql,true,true);
    }

    function login(){
        $login_time = time();
        $login_time = mysql_escape_string($login_time);
        $sql = "UPDATE sdb_op_sessions
            SET login_time='{$login_time}' , status='1' 
            WHERE sess_id='".$this->sess_id."'";
        $this->db->exec("UPDATE sdb_operators SET Lastlogin='{$login_time}' WHERE op_id=".$_SESSION['OPID'],true,true);
        return $this->db->exec($sql,true,true);
    }

    function reload(){
        if($row = $this->db->selectrow("SELECT sess_data FROM sdb_op_sessions WHERE sess_id = '{$this->sess_id}'",true,true))
            $_SESSION = unserialize($row['sess_data']);
        else
            $_SESSION = array();
    }

    function close($write=true){
        if($write){
            $this->_sess_write();
        }
        $this->closed = true;
    }

    function _sess_write(){

        if($this->closed){
            return;
        }
        $id = $this->sess_id;

        if($_SESSION['profile']){
            $s = &$_SESSION['profile'];
            unset($s->system);
        }

        $pkg = defined('__PKG__') ? __PKG__ : '';
        $aRs = $this->db->exec("SELECT * FROM sdb_op_sessions WHERE sess_id='".$id."'",true,true);
        $aTemp = array(
            'sess_id'=>$id,
            'last_time'=>time(),
            'pkg'=>$pkg,
            'sess_data'=>$_SESSION,    
            'ip'=>remote_addr(),
        );

        $aIgnoreCtl = array('sfile'=>1,'dashboard'=>1,'profile/setting'=>1,'passport'=>1);

        if(!isset($aIgnoreCtl[$this->system->request['action']['controller']])){
            $aTemp['ctl']=$this->system->request['action']['controller'];
            $aTemp['act']=$this->system->request['action']['method'];
        }

        $sql = $this->db->GetUpdateSql($aRs,$aTemp,true);
        $this->system->log('session:'.$sql);
        if(!$sql || $this->db->exec($sql,true,true)){
            return true;
        }else{
            return false;
        }
    }


    function _sess_clean($max){

        if($rows = $this->db->select('select * from sdb_op_sessions',true,true)){
            foreach($rows as $row){
                $sess = unserialize($row['sess_data']);
                if(is_array($sess['_tmpfiles'])){
                    foreach($sess['_tmpfiles'] as $tmpfile){
                        @unlink($tmpfile);
                    }
                }
            }
        }
        unset($sess);

        $old = time() - $max;
        $sql = "DELETE
            FROM  sdb_op_sessions
            WHERE  last_time < '{$old}'";
        return $this->db->exec($sql,true,true);
    }
}
?>
