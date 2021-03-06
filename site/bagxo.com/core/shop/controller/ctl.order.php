<?php
class ctl_order extends shopPage{

    var $noCache = true;

    function create(){
        $this->begin($this->system->mkUrl('cart', 'checkout'));
        $this->_verifyMember(false);

        $order = $this->system->loadModel('trading/order');
        $oCart = $this->system->loadModel('trading/cart');
        $oCart->checkMember($this->member);
        $cart = $oCart->getCart('all');

        $orderid = $order->create($cart, $this->member,$_POST['delivery'],$_POST['payment'],$_POST['minfo']);
        if($orderid){
            if($_POST['fromCart']){
                $oCart->clearAll();
            }
/*             $this->redirect('index','order',array($orderid)); */
        }else{
            trigger_error('对不起，订单创建过程中发生问题，请重新提交或稍后提交',E_USER_ERROR);            
        }
        $this->system->setcookie('ST_ShopEx-Order-Buy', md5($this->system->getConf('certificate.token').$orderid));
        
         $this->end_only(true, '订单建立成功', $this->system->mkUrl('order', 'index', array($orderid)));
        $this->redirect('order','index',array($orderid));
    }

    function index($order_id, $selecttype=false){
        if($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->system->getConf('certificate.token').$order_id)){
            $this->splash('failed','index.php',__('订单无效！'));
        }
        $objOrder = $this->system->loadModel('trading/order');
        $aOrder = $objOrder->load($order_id);
        $aOrder['member_id'] = is_null($aOrder['member_id'])?false:$aOrder['member_id'];
        $this->_verifyMember($aOrder['member_id']);
        $aOrder['cur_money'] = ($aOrder['amount']['total'] - $aOrder['amount']['payed']) * $aOrder['cur_rate'];
        $this->pagedata['order'] = $aOrder;
        
        if(!$this->pagedata['order']){
            $this->system->error(404);
            exit;
        }
        
        if($selecttype){
            $selecttype = 1;
//            $shipping = $this->system->loadModel('trading/delivery');
//            $this->pagedata['delivery'] = $shipping->checkDlTypePay($this->pagedata['order']['shipping']['id'], $this->pagedata['order']['shipping']['area']);
            $payment = $this->system->loadModel('trading/payment');
            $payments = $payment->getByCur($this->pagedata['order']['currency']);
            foreach($payments as $key => $val){
                $payments[$key]['money'] = $objOrder->chgPayment($order_id,$val['id'],$aOrder['amount']['total']-$aOrder['amount']['payed'],1);
            }
            $this->pagedata['payments'] = $payments;
        }else{
            $selecttype = 0;
        }
        $this->pagedata['order']['selecttype'] = $selecttype;
        $this->pagedata['order']['paytype'] = strtoupper($this->pagedata['order']['paytype']);
        $objCur = $this->system->loadModel('system/cur');
        $aCur = $objCur->getDefault();
        $this->pagedata['order']['cur_def'] = $aCur['cur_code'];
        $this->output();
    }
    
    function detail($order_id, $selecttype=false){
        if($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->system->getConf('certificate.token').$order_id)){
            $this->splash('failed','index.php',__('订单无效！'));
        }
        $objOrder = $this->system->loadModel('trading/order');
        $aOrder = $objOrder->load($order_id);
        $aOrder['member_id'] = is_null($aOrder['member_id'])?false:$aOrder['member_id'];
        $this->_verifyMember($aOrder['member_id']);
        $aOrder['cur_money'] = ($aOrder['amount']['total'] - $aOrder['amount']['payed']) * $aOrder['cur_rate'];
        if($aOrder['member_id']){
            $member = $this->system->loadModel('member/member');
            $aMember = $member->getFieldById($aOrder['member_id'], array('email'));
            $aOrder['receiver']['email'] = $aMember['email'];
        }
        $shiparea = explode( ':', $aOrder['receiver']['area'] );
        $aOrder['shipping']['area'] = $shiparea[1];
        $this->pagedata['order'] = $aOrder;
        if(!$this->pagedata['order']){
            $this->system->error(404);
            exit;
        }
        $gItems = $objOrder->getItemList($order_id);
        foreach($gItems as $key => $item){
            $gItems[$key]['addon'] = unserialize($item['addon']);
            if($item['minfo'] && unserialize($item['minfo'])){
                $gItems[$key]['minfo'] = unserialize($item['minfo']);
            }else{
                $gItems[$key]['minfo'] = array();
            }
        }
        $this->pagedata['order']['items'] = $gItems;
        $this->pagedata['order']['giftItems'] = $objOrder->getGiftItemList($order_id);
        
        if($selecttype){
            $selecttype = 1;
//            $shipping = $this->system->loadModel('trading/delivery');
//            $this->pagedata['delivery'] = $shipping->checkDlTypePay($this->pagedata['order']['shipping']['id'], $this->pagedata['order']['shipping']['area']);
            $payment = $this->system->loadModel('trading/payment');
            $this->pagedata['payments'] = $payment->getByCur($this->pagedata['order']['currency']);
        }else{
            $selecttype = 0;
        }
        $this->pagedata['order']['selecttype'] = $selecttype;
        
        $objCur = $this->system->loadModel('system/cur');
        $aCur = $objCur->getDefault();
        $this->pagedata['order']['cur_def'] = $aCur['cur_code'];
        $this->output();
    }
}
?>