<?php
include_once('objectPage.php');
class ctl_gnotify extends objectPage{

    var $name='缺货登记';
    var $workground = 'goods';
    var $actionView = 'product/gnotify/finder_action.html';
    var $object = 'goods/goodsNotify';
    var $disableGridEditCols = "gnotify_id";
    var $disableColumnEditCols = "gnotify_id";
    var $disableGridShowCols = "gnotify_id";
    function index($operate){
        if($operate=='admin'){
            $operate = $this->system->loadModel('admin/operator');
            $operate->updateReadTime($this->op->opid,array('notifytime',time()));
        }
        parent::index();
    }
    function toRemove($gid){
        $objGoods = $this->system->loadModel('trading/goods');
        $objGoods->toRemove($gid);
        $this->splash('success','index.php?ctl=goods/product&act=index');
    }

    function toNotify(){
        if($_POST['gnotify_id']){
            $notify = $this->system->loadModel('goods/goodsNotify');
            $aRet = $notify->toNofity($_POST['gnotify_id']);
            if($aRet['success'])
                echo "邮件发送成功，".$aRet['success']."条邮件已发出！";
            if($aRet['failed'])
                echo "邮件发送失败，".$aRet['failed']."条邮件未发送！";
        }else{
            echo "请先从列表中选择需要发送的记录！";
        }
    }
}
?>
