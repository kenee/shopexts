<?php
include_once('objectPage.php');
class ctl_reship extends objectPage{

    var $name = '退货单';
    var $workground = 'order';
    var $object='trading/reship';
    
    function detail($nID){
        $oReship=$this->system->loadModel('trading/reship');
        $aDetail=$oReship->detail($nID);
        
        $o = $this->system->loadModel('member/member');
        $aMember = $o->getFieldById($aDetail['member_id']);
        $aDetail['member_id'] = $aMember['uname'];
        
        $this->pagedata['detail']=$aDetail;
        $this->pagedata['items'] = $oReship->getItemList($nID);
        $this->setView('order/reship/detail.html');
        $this->output();
    }
    
    function edit(){
        $oReship=$this->system->loadModel('trading/reship');
        if($oReship->edit($this->in)){
            $this->splash('success','index.php?ctl=order/reship&act=index',__('修改成功'));
        }else{
            $this->splash('failed','index.php?ctl=order/reship&act=index',__('修改失败'));
        }
    }
    
    function delete(){
        $oReship = $this->system->loadModel('trading/reship');
        foreach($_POST['delivery_id'] as $v){
            $oReship->toRemove($v);
        }
        echo '删除成功！';
    }
}