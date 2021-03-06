<?php
include_once('objectPage.php');
class ctl_level extends objectPage {

    var $name='会员等级';
    var $workground = 'member';
    var $actionView = 'member/level/finder_action.html'; //默认的动作html模板,可以为null
    var $object = 'member/level';
    var $actions= array(
        'edit'=>'编辑',
    );
    var $editMode = true;
    var $disableGridEditCols = "member_lv_id,pre_id";
    var $disableColumnEditCols = "member_lv_id,pre_id";
    var $disableGridShowCols = "member_lv_id,pre_id";

    
    function showNewMemLv(){
        $this->path[] = array('text'=>'添加会员等级');
        $this->page('member/level/level_new.html');
    }

    function addMemLevel(){
        if(empty($_POST['lv_name'])){
            $this->splash('failed','index.php?ctl=member/level&act=index','请输入等级名称');
            exit;
        }
        if(empty($_POST['dis_count'])){
            $this->splash('failed','index.php?ctl=member/level&act=index','请输入会员折扣率');
            exit;
        }
       

        $_POST['point'] = intval($_POST['point']);
        $oMem = $this->system->loadModel("member/level");
        $filter['lv_type'] = $_POST['lv_type'];
        $tmpdate = $oMem->getList('*',$filter,0,-1);
        foreach($tmpdate as $k => $v){
            if($_POST['point']==$v['point']&&$_POST['lv_type']!='wholesale'){
                $this->splash('failed','index.php?ctl=member/level&act=index','已存在相同积分的会员等级');
                exit;
            }
        }
        $_POST['dis_count'] = $_POST['dis_count'] / 100;
        if($oMem->checkLevel($_POST,'INSERT')){
            $this->splash('failed','index.php?ctl=member/level&act=index','有同名会员等级存在');
            exit;            
        }
        if($oMem->checkMlevel($_POST,'INSERT')){
            $this->splash('failed','index.php?ctl=member/level&act=index','默认级别已经存在');
            exit;
        }
        $r =  $oMem->insertLevel($_POST);
        $this->splash('success','index.php?ctl=member/level&act=index','添加会员等级成功');
    }
    
    function edit($nLvId){
        $this->path[] = array('text'=>'会员等级编辑');
        $oLv = $this->system->loadModel("member/level");
        $aLv = $oLv->getFieldById($nLvId);
        $aLv['dis_count'] *= 100;
        $this->pagedata['lv'] = $aLv;
        $this->pagedata['lv_type']=array('retail'=>'普通零售会员等级','wholesale'=>'批发代理会员等级');
        $this->page('member/level/level_edit.html');
    }

    function saveLevelInfo(){
        $oMem = $this->system->loadModel("member/level");
        $this->in['dis_count'] = $this->in['dis_count'] / 100;
        if($oMem->checkLevel($this->in,'UPDATE')){
            $this->splash('failed','index.php?ctl=member/level&act=index','有同名会员等级存在');
            exit;            
        }
        if($oMem->checkMlevel($this->in,'UPDATE')){
            $this->splash('failed','index.php?ctl=member/level&act=index','默认级别已经存在');
            exit;
        }
        $filter['lv_type'] = $_POST['lv_type'];
        $tmpdate = $oMem->getList('*',$filter,0,-1);
        foreach($tmpdate as $k => $v){
            if($this->in['member_lv_id']!=$v['member_lv_id']){
                if($_POST['point']==$v['point']&&$_POST['lv_type']!='wholesale'){
                    $this->splash('failed','index.php?ctl=member/level&act=index','已存在相同积分的会员等级');
                    exit;
                }
            }
        }
        $r=$oMem->saveLevel($this->in);
        if($r){
            $this->splash('success','index.php?ctl=member/level&act=index','修改成功');
        }else{
            $this->splash('failed','index.php?ctl=member/level&act=index','修改失败');
        }
    }
    function delete(){
        $oLv = $this->system->loadModel("member/level");
        $aLvId = $this->in['member_lv_id'];
        if($oLv->delLevel($aLvId)){
            $this->message = array('string'=>__('删除成功！'),'type'=>MSG_OK);
            $this->splash('success','index.php?ctl=member/level&act=index');
        }else{
            $this->message = array('string'=>__('删除失败！'),'type'=>MSG_ERROR);
            $this->splash('success','index.php?ctl=member/level&act=index');
        }
    }
}
?>