<?php
include_once('objectPage.php');
class ctl_shipping extends objectPage{

    var $name = '发货单';
    var $workground = 'order';
    var $object='trading/shipping';
    
    function detail($nID){
        $oDelivery=$this->system->loadModel('trading/shipping');
        $aDetail=$oDelivery->detail($nID);
        
        $o = $this->system->loadModel('member/member');
        $aMember = $o->getFieldById($aDetail['member_id']);
        $aDetail['member_id'] = $aMember['uname'];

        $this->pagedata['detail']=$aDetail;
        $this->pagedata['items'] = $oDelivery->getItemList($nID);
        $this->setView('order/shipping/detail.html');
        $this->output();
    }
    
    function edit(){
        $oRefund=$this->system->loadModel('trading/shipping');
        if($oRefund->edit($_GET)){
            $this->splash('success','index.php?ctl=order/shipping&act=index',__('修改成功'));
        }else{
            $this->splash('failed','index.php?ctl=order/shipping&act=index',__('修改失败'));
        }
    }
    
    function delete(){
        $oShipping = $this->system->loadModel('trading/shipping');
        foreach($_POST['delivery_id'] as $v){
            $oShipping->toRemove($v);
        }
        echo '删除成功！';
    }
}
?>
