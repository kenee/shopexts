<?php
class ctl_passport extends adminPage{
    var $login_times_error=3;
    function login(){
        $this->pagedata['message'] = $_SESSION['loginmsg'];
        unset($_SESSION['loginmsg']);
        $this->pagedata['show_varycode']=$this->checkVeryCode();
        $this->system->session->close(false);
        if($_COOKIE["SHOPEX_LOGIN_NAME"]){
            $this->pagedata['username']=$_COOKIE["SHOPEX_LOGIN_NAME"];
            $this->pagedata['save_login_name']=true;
        }
        $auth_type=$this->system->getConf('certificate.auth_type');
        if ($auth_type){
            $this->pagedata['authtype'] = $auth_type;
            $certificate = $this->system->loadModel('service/certificate');
            if ($auth_type=="free"){
                $this->pagedata['authstate'] = $certificate->getUrl($this->system->getConf('certificate.auth_strname'),1);
            }elseif($auth_type=="commercial"){
                $this->pagedata['authstate'] = $certificate->getUrl($this->system->getConf('certificate.auth_strname'),1);
            }
        }
        $this->setView('login.html');
        $this->output();
    }
    function checkVeryCode(){

        if($this->system->getConf('system.admin_verycode') || ($this->system->getConf('system.admin_error_login_times')>$this->login_times_error && intval($this->system->getConf('system.admin_error_login_time')+3600)>time())){
            return true;
        }else{
            return false;
        }
    }
    function dologin(){
        if($this->system->getConf('system.admin_verycode') || $this->system->getConf('system.admin_error_login_times')>$this->login_times_error){
            if(strtolower($this->in["verifycode"]) !== strtolower($_SESSION["RANDOM_CODE"]))
            {
                $_SESSION['loginmsg'] = __("验证码输入错误!");
                header('Location: index.php?ctl=passport&act=login');
                exit;
            }
        }
        $oOpt = $this->system->loadModel('admin/operator');
        $aResult = $oOpt->tryLogin($_POST);

        if ($aResult){
            if($_POST['save_login_name']){
                setcookie("SHOPEX_LOGIN_NAME",$_POST['usrname'],(time()+86400*10));
            }else{
                setcookie("SHOPEX_LOGIN_NAME","");
            }
            $config=unserialize($aResult['config']);
            $oOpt->update(array('lastlogin'=>time(),'lastip'=>remote_addr(),'logincount'=>$aResult['logincount']+1),array('op_id'=>$aResult['op_id']));
            unset($_SESSION["loginmsg"]);
            unset($_SESSION['_PageData']);
            unset($_SESSION['OPID']);
            unset($_SESSION['SUPER']);
            $profile = $this->system->loadModel('adminProfile');

            $status = $this->system->loadModel('system/status');
            $status->update(1);

            $profile->load($aResult['op_id']);
            $_SESSION['OPID'] = $aResult['op_id'];
            $_SESSION['SUPER'] = $aResult['super'];
            $_SESSION['profile'] = &$profile;
            $this->system->session->login();
            if($_REQUEST['return']){
                header("Location: index.php#".$_REQUEST['return']);
            }else{
                header("Location: index.php");
            }

        }else{
            if(intval($this->system->getConf('system.admin_error_login_time')+3600)>time()){
                $this->system->setConf('system.admin_error_login_times',$this->system->getConf('system.admin_error_login_times')+1);
            }else{

                $this->system->setConf('system.admin_error_login_times',1);
            }
            $this->system->setConf('system.admin_error_login_time',time());
            $_SESSION['loginmsg'] = __('用户名或密码错误!');
            header('Location: index.php?ctl=passport&act=login');
            exit;
        }
    }
    function check_login(){
        $oOpt = $this->system->loadModel('admin/operator');
        $aResult = $oOpt->doLogin($_POST);
        if($aResult){
            echo 'true';
            exit;
        }else{
            echo 'false';
            exit;
        }
    }

    function logout(){
        unset($_SESSION['profile']);
        unset($_SESSION['_PageData']);

        if($_GET['_']){
            $this->system->session->logout();
            $this->ajaxdata['jscall']='X.doLogOut()';
            $this->ajax();
        }else{
            header('Location: index.php?ctl=passport&act=login');
        }
    }

    function verifycode(){
        header("Cache-Control: no-cache, no-store, must-revalidate"); // 强制更新
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Pragma: no-cache");
        $oVerifyCode = &$this->system->loadmodel('utility/vcode');
        $_SESSION["RANDOM_CODE"] = $oVerifyCode->init(4);
        $this->system->session->close(true);
        $oVerifyCode->output();
    }

    function lock(){
        $this->system->session->lock();
        $this->ajax();
    }

    function unlock(){
        if($this->system->session->unlock($this->in)){
            $this->ajaxdata['jscall'] = 'fbox.close()';
        }
        $this->ajax();
    }
    function certi_validate(){
        $cert = $this->system->loadModel('service/certificate');
        $sess_id = $_POST['session_id'];

        $return = array();
        if($sess_id == $cert->get_sess()){
            $return = array(
                'res' => 'succ',
                'msg' => '',
                'info' => ''
            );
            
            echo json_encode($return);
        }else{
            $return = array(
                'res' => 'fail',
                'msg' => '000001',
                'info' => 'You have the different session!'
            );
            
            echo json_encode($return);
        }
    }
}
?>
