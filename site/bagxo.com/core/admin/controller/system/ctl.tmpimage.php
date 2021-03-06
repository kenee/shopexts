<?php
include_once('objectPage.php');
class ctl_tmpimage extends objectPage{

    var $name='模板文件';
    var $workground ='site';
    var $object = 'resources/tmpimage';
    var $disableGridEditCols = "id";
    var $disableColumnEditCols = "id";
    var $disableGridShowCols = "id";
    var $noRecycle = true;
    var $deleteAble = false;
    var $allowExport = false;

    function index($tmpid,$type='pic'){
        parent::index(array('params'=>array('tmpid'=>$tmpid,'type'=>$type)));
    }

    function detail($sName) {
        $oImg = $this->system->loadModel("resources/tmpimage");
        $aId = $oImg->getId($sName);
        $file = $oImg->getFile($aId['name'],$aId['tmpid']);
        $this->pagedata['file'] = $file;
        $this->pagedata['tmpid'] = $aId['tmpid'];
        $this->pagedata['basic_path'] = '../themes/'.$aId['tmpid'].'/images/';
       
        if($file['filetype'] == "css"){
            $dir = THEME_DIR.'/'.$aId['tmpid'].'/images/';
            $this->pagedata['file']['content'] = file_get_contents($dir.$file['name']);
            $this->setView('system/template/templateSource.html');
        }else{
            $this->setView('system/template/templateImage.html');
        }
        $this->output();
    }

    function delete($fileName){
        $this->begin('index.php?ctl=system/tmpimage&act=detail&p[0]='.$_POST['id']);
        $this->system->setConf('system.theme_last_modified',time());
        $oImg = $this->system->loadModel("resources/tmpimage");
        $this->end($oImg->toRemove($fileName, $_POST['tmpid']), __('文件删除成功'));
        return true;
    }

    function saveImage(){
        $this->begin('index.php?ctl=system/tmpimage&act=detail&p[0]='.$_POST['id']);
        $this->system->setConf('system.theme_last_modified',time());
        $oImg = $this->system->loadModel("resources/tmpimage");

        if(($result=$oImg->saveFile(array_merge($_POST, $_FILES)))===true){
            $this->end(true,__('操作成功'));
        }else{
            $this->end(false,__($result));
        }
        
    }
    
    function saveSource(){
        $this->system->setConf('system.theme_last_modified',time());
        $this->begin('index.php?ctl=system/tmpimage&act=detail&p[0]='.$_POST['id']);
        $oImg = $this->system->loadModel('resources/tmpimage');
        unSafeVar($_POST);
        $this->end($oImg->saveSource($_POST), __('样式文件保存成功'));
    }
    
    function recoverSource($sName, $dest, $tmpid){
        $this->system->setConf('system.theme_last_modified',time());
        $this->begin('index.php?ctl=system/tmpimage&act=detail&p[0]='.$tmpid.'-'.$sName);
        $oImg = $this->system->loadModel('resources/tmpimage');
        $this->end($oImg->recoverSource($sName, $dest, $tmpid), __('恢复成功'));
    }
}
?>