<?php
include_once('objectPage.php');
class ctl_operator extends objectPage{

    var $name='管理员';
    var $workground ='setting';
    var $actionView = 'admin/finder_action.html';
    var $filterView = 'admin/finder_filter.html';
    var $object = 'admin/operator';
    var $finderVar = 'OperatorMgr';
    var $noRecycle = true;
    var $actions= array(
        'edit'=>'编辑',
    );

    function ctl_operator(){
        parent::objectPage();
        if(!$this->op->is_super){
              $this->system->responseCode(403);
              exit;
        }
    }

    /**
     * recycle
     * 重写父类objectPage中的recycle
     * 增加当前管理员不能删除自身功能
     * @access public
     * 
     */
    function recycle()
    {    
        foreach ($_POST['op_id'] as $a)
        {
            if ($a == $this->op->opid)
            {
                $tmp = 'self';
                break;
            }            
        }
        if ($tmp != '')
        {
            echo '当前管理员不能删除自身！请重新选择！';
            exit;
        }        
        else 
        {
            parent::recycle();
        }        
    }


    /**
     * edit
     *
     * @access public
     * @return array
     */
    function edit($nOpId){        
        if($nOpId){
            $operator = $this->model->instance($nOpId);
            $this->pagedata['roles'] = $this->model->getUsedRoles($nOpId);
            $this->path[] = array('text'=>'编辑 '.$operator['username']);    
        }else{
            $this->path[] = array('text'=>'添加管理员');                        
            $operator['super'] = 0;
            $operator['status'] = 1;
        }

        $admin_role = $this->system->loadModel('admin/adminroles');
        $this->pagedata['adminroles'] = $admin_role->getList('role_id,role_name');
        $operator['select_super'] = array('0'=>'普通管理员', '1'=>'超级管理员');
        $operator['select_status'] = array('1'=>'启用','0'=>'禁用',);

        $this->pagedata['operator'] = $operator;
        $this->page('admin/op_edit.html');
    }


    function save(){
        $this->begin('index.php?ctl='.$_GET['ctl'].'&act=index');
        $_POST['roles'] = $_POST['roles']?$_POST['roles']:array();
        if($_POST[$this->model->idColumn]){
            if($_POST['changepwd']){
                if($_POST['userpass_comfirm'] != $_POST['userpass']){
                    trigger_error('两次密码输入不一致',E_USER_ERROR);
                }
            }else{
                unset($_POST['userpass']);
            }
            $this->end($this->model->update($_POST,array($this->model->idColumn=>$_POST[$this->model->idColumn])));
        }else{
            if($_POST['userpass_comfirm'] != $_POST['userpass']){
                trigger_error('两次密码输入不一致',E_USER_ERROR);
            }
            $this->end($this->model->insert($_POST));
        }
    }

    function delete(){
        if($_POST['op_id']){
            foreach($_POST['op_id'] as $k=>$v){
                if($v==$this->op->opid){
                    echo '管理员不能删除自己的账号，';
                    unset($_POST['op_id'][$k]);
                    if(count($_POST['op_id'])==0){
                        echo '操作失败';
                        return;
                    }
                }
            }
        }else{
             echo '管理员无法删除自身，';
        }
        if($this->model->delete($_POST,$this->op->opid)){
            echo '选定记录已删除成功!';
        }else{
            echo '选定记录无法删除!';
        }
    }

}
?>
