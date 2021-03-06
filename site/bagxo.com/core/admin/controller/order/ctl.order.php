<?php
/**
 * ctl_olist
 *
 * @uses adminPage
 * @package
 * @version $Id: ctl.order.php 2009 2008-04-28 11:27:56Z alex $
 * @copyright 2003-2007 ShopEx
 * @author Wanglei <flaboy@zovatech.com>
 * @license Commercial
 */
include_once('delivercorp.php');
include_once('objectPage.php');
class ctl_order extends objectPage{

    var $name='订单';
    var $actionView = 'order/finder_action.html';
    var $filterView = 'order/finder_filter.html';
    var $workground = 'order';
    var $object = 'trading/order';
    var $actions= array(
        'showEdit'=>'编辑'
    );
    var $disableGridEditCols = "confirm,status";
    var $disableColumnEditCols = "confirm,status";
    var $disableGridShowCols = "confirm,status,mark_type";

    function index($operate){
        if($operate=='admin'){
            $operate = $this->system->loadModel('admin/operator');
            //parent::index();
            $operate->updateReadTime($this->op->opid,array('ordertime',time()));
        }
        parent::index();

    }
    function new_order_message_list(){
        $oShopbbs = $this->system->loadModel('resources/shopbbs');
        $order_list=$oShopbbs->getNewOrderMessage(true);
        $params['order_id']=$order_list;
        parent::index(array('params'=>$params));
    }
    function _views(){
        return array(
            '全部订单'=>"",
            '等待付款'=>array('pay_status'=>array(0),'status'=>'active'),
            '已付款未发货'=>array('pay_status'=>array(1,2,3,4),'ship_status'=>array(0),'status'=>'active'),
            '已发货'=>array('ship_status'=>array(1,2),'status'=>'active'),
            '已完成'=>array('status'=>'finish'),
            '已退款'=>array('pay_status'=>array(4,5),'status'=>'active'),
            '已退货'=>array('ship_status'=>array(3,4),'status'=>'active'),
            '已作废'=>array('status'=>'dead')
        );
    }

    function save_addr($order_id){
        $data = array(
            'ship_name'=>$_POST['order']['ship_name'],
            'ship_area'=>$_POST['order']['ship_area'],
            'ship_zip'=>$_POST['order']['ship_zip'],
            'ship_addr'=>$_POST['order']['ship_addr'],
            'ship_mobile'=>$_POST['order']['ship_mobile'],
            'ship_tel'=>$_POST['order']['ship_tel'],
            'memo'=>$_POST['order']['order_memo'],
        );
        if($this->model->update($data,array('order_id'=>$order_id))){
            echo 'ok';
        }
    }

    function filterActions(&$row){
        $return = $this->actions;
        if($row['__pay_status'] || $row['__ship_status'] || $row['status'] != 'active'){
            $return['showEdit'] = '_none_';
        }
        return $return;
    }

    function detail_items($orderid){    //订单明细
        $order = $this->system->loadModel('trading/order');
        $this->pagedata['orderid'] = $orderid;
        $aItems = $order->getItemList($orderid);
        foreach($aItems as $k => $rows){
            $aItems[$k]['addon'] = unserialize($rows['addon']);
            if($rows['minfo'] && unserialize($rows['minfo'])){
                $aItems[$k]['minfo'] = unserialize($rows['minfo']);
            }else{
                $aItems[$k]['minfo'] = array();
            }
            if($aItems[$k]['addon']['adjname']) $aItems[$k]['name'] .= '<br>配件：'.$aItems[$k]['addon']['adjname'];
        }
        $this->pagedata['goodsItems'] = $aItems;
        $aGiftItems = $order->getGiftItemList($orderid);
        $this->pagedata['giftItems'] = $aGiftItems;

        $this->setView('order/od_items.html');
        $this->output();
    }

    function printing($type,$order_id){
        $order = $this->system->loadModel('trading/order');
        $member = $this->system->loadModel('member/member');
        $dbTmpl = $this->system->loadModel('content/systmpl');
        $order->setPrintStatus($order_id,$type,true);

        $orderInfo = $order->getFieldById($order_id);
        $orderInfo['self'] = 0-$orderInfo['discount']-$orderInfo['pmt_amount'];
        $goodsItem = $order->getItemList($order_id);
        $memberInfo = $member->getFieldById($orderInfo['member_id'],array("point"));
        foreach($goodsItem as $k => $rows){
            $goodsItem[$k]['addon'] = unserialize($rows['addon']);
            if($rows['minfo'] && unserialize($rows['minfo'])){
                $goodsItem[$k]['minfo'] = unserialize($rows['minfo']);
            }else{
                $goodsItem[$k]['minfo'] = array();
            }
        }
        $giftsItem = $order->getGiftItemList($order_id);
        $orderSum = $order->sumOrder($orderInfo['member_id']);
        $orderSum['sum'] = $orderSum['sum']?$orderSum['sum']:0;

        $this->pagedata['goodsItem'] = $goodsItem;
        $this->pagedata['giftsItem'] = $giftsItem;
        $this->pagedata['orderInfo'] = $orderInfo;
        $this->pagedata['orderSum'] = $orderSum;
        $this->pagedata['memberPoint'] = $memberInfo['point']?$memberInfo['point']:0;
        $shopinfo=array(
            'name'=>$this->system->getConf('system.shopname'),
            'url'=>$this->system->getConf('store.shop_url'),
            'email'=>$this->system->getConf('store.email'),
            'tel'=>$this->system->getConf('store.telephone'),
            'logo'=>$this->system->getConf('site.logo')
        );
        $this->pagedata['shop'] = $shopinfo;

        switch($type){
            case ORDER_PRINT_CART:  /*购物清单*/
            $this->pagedata['printType'] = array("cart");
            $this->pagedata['printContent']['cart'] = $dbTmpl->fetch('../admin/view/order/print_cart',array('goodsItem'=>$goodsItem, 'giftsItem'=>$giftsItem, 'orderInfo'=>$orderInfo,'shop'=>$shopinfo,'orderSum'=>$orderSum, 'memberPoint'=>$memberInfo['point']?$memberInfo['point']:0));
            $this->setView('order/print.html');
            $this->output();
                break;
            case ORDER_PRINT_SHEET:    /*配货单*/
//            $this->pagedata['printType'] = array("sheet");
            $this->pagedata['printContent']['sheet'] = $dbTmpl->fetch('../admin/view/order/print_sheet',array('goodsItem'=>$goodsItem, 'giftsItem'=>$giftsItem, 'orderInfo'=>$orderInfo,'shop'=>$shopinfo, 'orderSum'=>$orderSum, 'memberPoint'=>$memberInfo['point']?$memberInfo['point']:0));
            $this->setView('order/print.html');
            $this->output();
                break;
            case ORDER_PRINT_MERGE:    /*联合打印*/
//            $this->pagedata['printType'] = array("cart","sheet");
            $this->pagedata['printContent']['cart'] = $dbTmpl->fetch('../admin/view/order/print_cart',array('goodsItem'=>$goodsItem, 'giftsItem'=>$giftsItem, 'orderInfo'=>$orderInfo,'shop'=>$shopinfo, 'orderSum'=>$orderSum, 'memberPoint'=>$memberInfo['point']?$memberInfo['point']:0));
            $this->pagedata['printContent']['sheet'] = $dbTmpl->fetch('../admin/view/order/print_sheet',array('goodsItem'=>$goodsItem, 'giftsItem'=>$giftsItem, 'orderInfo'=>$orderInfo, 'orderSum'=>$orderSum, 'memberPoint'=>$memberInfo['point']?$memberInfo['point']:0));
            $this->setView('order/print.html');
            $this->output();
            break;
        case ORDER_PRINT_DLY:    /*物流单打印*/
            $printer = $this->system->loadModel('trading/dly_centers');
            $this->pagedata['dly_centers'] = $printer->getList('dly_center_id,name',array('disable'=>'false'));
            $this->pagedata['default_dc'] = $this->system->getConf('system.default_dc');
            $this->pagedata['the_dly_center'] = $printer->instance($this->pagedata['default_dc']?$this->pagedata['default_dc']:$this->pagedata['dly_centers'][0]['dly_center_id']);

            $printer = $this->system->loadModel('trading/dly_printer');
            $this->pagedata['printers'] = $printer->getList('prt_tmpl_id,prt_tmpl_title',array('shortcut'=>'true'));

            $this->setView('order/print_dly.html');
            $this->output();
                break;
            default:
                echo '无效的打印类型';
                break;
        }
    }

    function detail_message($orderid){    //订单留言
        $comment = $this->system->loadModel('resources/message');
        $aRow = $comment->getOrderMessage($orderid);
        $this->pagedata['message'] = $aRow;
        $this->pagedata['order']['messagenum'] = count($aRow);
        $this->pagedata['orderid'] = $orderid;
        $this->setView('order/od_message.html');
        $this->output();
    }

    function detail_bills($orderid){    //订单单据
        $objPayment = $this->system->loadModel('trading/payment');
        $aBill = $objPayment->getOrderBillList($orderid);
        $objRefund = $this->system->loadModel('trading/refund');
        $aRefund = $objRefund->getOrderBillList($orderid);
        $this->pagedata['bills'] = $aBill;
        $this->pagedata['refunds'] = $aRefund;

        $this->pagedata['orderid'] = $orderid;
        $this->setView('order/od_bill.html');
        $this->output();
    }

    function detail_delivery($orderid){
        $objDelivery = $this->system->loadModel('trading/delivery');
        $this->pagedata['consign'] = $objDelivery->getConsignList($orderid);
        $this->pagedata['reship'] = $objDelivery->getReshipList($orderid);
        $this->pagedata['orderid'] = $orderid;
        $this->setView('order/od_delivery.html');
        $this->output();
    }

    function detail_pmt($orderid){    //订单促销
        $order = $this->system->loadModel('trading/order');
        $aPmt = $order->getPmtList($orderid);

        $this->pagedata['pmtlist'] = $aPmt;
        $this->pagedata['orderid'] = $orderid;
        $this->setView('order/od_pmts.html');
        $this->output();
    }

    function detail_mark($orderid){    //订单备注
        $order = $this->system->loadModel('trading/order');
        $aOrder = $order->getFieldById($orderid, array('mark_text','mark_type'));
        $this->pagedata['mark_text'] = $aOrder['mark_text'];
        $this->pagedata['mark_type'] = $aOrder['mark_type'];
        $this->pagedata['orderid'] = $orderid;
        $this->setView('order/od_mark.html');
        $this->output();
    }

    function saveMarkText(){
        $this->begin('index.php?ctl=order/order&act=detail_mark&p[0]='.$_POST['orderid']);
        $order = $this->system->loadModel('trading/order');
        $this->end($order->saveMarkText($_POST['orderid'], $_POST),__('保存成功'));
    }

    function detail_logs($orderid, $page=1){    //订单LOG
        $order = $this->system->loadModel('trading/order');
        $pageLimit = 30;
        $aLog = $order->getOrderLogList($orderid, $page-1, $pageLimit);
        $this->pagedata['logs'] = $aLog;
        $this->pagedata['result'] = array('success'=>'成功','failure'=>'失败');
        $pager = array(
            'current'=> $page,
            'total'=> ceil($aLog['page']),
            'link'=> 'javascript:W.page(\'index.php?ctl=order/order&act=detail_logs&p[0]='.$orderid.'&p[1]=_PPP_\', {update:$E(\'.tableform\').parentNode, method:\'post\'});',
            'token'=> '_PPP_'
        );
        $this->pagedata['pager'] = $pager;
        $this->pagedata['pagestart'] = ($page-1)*$pageLimit;
        $this->setView('order/od_logs.html');
        $this->output();
    }

    function saveOrderMsgText(){
        $this->begin('index.php?ctl=order/order&act=detail_msg&p[0]='.$_POST['orderid']);
        $order = $this->system->loadModel('trading/order');
        $oMsg = $this->system->loadModel('resources/message');
        $orderMsg = $oMsg->getOrderMessage($_POST['orderid']);
        foreach( $orderMsg as $mk => $mv){
            $oMsg->setReaded($mv['msg_id']);
        }
        $data = $_POST['msg'];
        $data['rel_order'] = $_POST['orderid'];
        $data['unread'] = '1';
        $data['date_line'] = time();
        $data['msg_from'] = '管理员';
        $data['from_type'] = '1';
        $data['message'] = htmlspecialchars($data['message']);
        $this->end($order->addOrderMsg($data),__('保存成功'));
    }

    function detail_msg( $orderid ){
        $order = $this->system->loadModel('trading/order');
        $oMsg = $this->system->loadModel('resources/message');
        $orderMsg = $oMsg->getOrderMessage($orderid);
//        foreach( $orderMsg as $mk => $mv){
//            $oMsg->setReaded($mv['msg_id']);
//        }
        $aItems = $order->getItemList($orderid);
        $this->pagedata['ordermsg'] = $orderMsg;
        $this->pagedata['goodsItems'] = $aItems;
        $this->pagedata['orderid'] = $orderid;
        $this->setView('order/od_msg.html');
        $this->output();
    }

    function detail($order_id){    //订单详细信息
        $order = $this->system->loadModel('trading/order');
        $aOrder = $order->getFieldById($order_id);

        $oCur = $this->system->loadModel('system/cur');
        $aCur = $oCur->getSysCur();
        $aOrder['cur_name'] = $aCur[$aOrder['currency']];

        $payment = $this->system->loadModel('trading/payment');
        $aPayment = $payment->getPaymentById($aOrder['payment']);
        $aOrder['payment'] = $aPayment['custom_name'];

        if($aOrder['member_id']){
            $member = $this->system->loadModel('member/member');
            $aOrder['member'] = $member->getFieldById($aOrder['member_id'], array('name','uname','mobile','tel','addr','email','area','remark'));
        }
        $aItems = $order->getItemList($order_id);
        foreach($aItems as $k => $rows){
            $aItems[$k]['addon'] = unserialize($rows['addon']);
            if($rows['minfo'] && unserialize($rows['minfo'])){
                $aItems[$k]['minfo'] = unserialize($rows['minfo']);
            }else{
                $aItems[$k]['minfo'] = array();
            }
            if($aItems[$k]['addon']['adjname']) $aItems[$k]['name'] .= '<br>配件：'.$aItems[$k]['addon']['adjname'];
        }
        $this->pagedata['goodsItems'] = $aItems;
        $this->pagedata['giftItems'] = $order->getGiftItemList($order_id);
        $aOrder['discount'] = 0 - $aOrder['discount'];
        $this->pagedata['order'] = $aOrder;
        $this->pagedata['order']['flow']= array('refund' => $this->system->getConf('order.flow.refund'),
            'consign' => $this->system->getConf('order.flow.consign'),
            'reship' => $this->system->getConf('order.flow.reship'),
            'payed' => $this->system->getConf('order.flow.payed'));

        $Mem = $this->system->loadModel('member/member');
        $Memattr = $this->system->loadModel('member/memberattr');
        $nowmember =  $Memattr->getAlloption($aOrder['member_id']);
        $tree = $Mem->getContactObject($aOrder['member_id']);
        $this->pagedata['tree'] = $tree;
        $this->setView('order/order_detail.html');
        $this->output();
    }

    /**
     * toConfirm
     *
     * @param mixed $orderid
     * @access public
     * @return void
     */
    function toConfirm($orderid){
        $objOrder = $this->system->loadModel('trading/order');
        if($orderid){
            $order = $objOrder->toConfirm($orderid);
            $this->status($orderid,$order);
            exit;
        }
        if(is_array($_POST['items']['items'])){
            foreach($_POST['items']['items'] as $orderid){
                $order = $objOrder->toConfirm($orderid);
            }
        }
        echo '所选订单已确认完毕';
    }

    function status($orderid,$order=null){
        if(!$order){
            $order = $this->system->loadModel('trading/order');
            $order = $order->load($orderid);
        }

        $this->pagedata['order'] = $order;
        $this->pagedata['order']['flow']= array('refund' => $this->system->getConf('order.flow.refund'),
            'consign' => $this->system->getConf('order.flow.consign'),
            'reship' => $this->system->getConf('order.flow.reship'),
            'payed' => $this->system->getConf('order.flow.payed'));
        $this->setView('order/actbar.html');
        $this->output();
    }

    /**
     * archive
     *
     * @param mixed $orderid
     * @access public
     * @return void
     */
    function archive($orderid){
        $objOrder = $this->system->loadModel('trading/order');
        if($orderid){
            $objOrder->op_id = $this->op->opid;
            $objOrder->op_name = $this->op->loginName;
            $order = $objOrder->toArchive($orderid);
            $this->detail($orderid);
            exit;
        }
    }

    /**
     * remove
     *
     * @param mixed $orderid
     * @access public
     * @return void
     */
    function remove(){
        $objOrder = $this->system->loadModel('trading/order');
        if(is_array($_POST['items']['items'])){
            foreach($_POST['items']['items'] as $orderid){
                if(!$objOrder->toRemove($orderid, $message)){
                    echo $message;
                    exit;
                }
            }
            echo __('删除成功;');
        }else{
            echo __('没有选中记录;');
        }
    }

    /**
     * cancel
     *
     * @param mixed $orderid
     * @access public
     * @return void
     */
    function cancel($orderid){
        $objOrder = $this->system->loadModel('trading/order');
        if($orderid){
            $objOrder->op_id = $this->op->opid;
            $objOrder->op_name = $this->op->loginName;
            $order = $objOrder->toCancel($orderid);
            $this->detail($orderid);
            exit;
        }
    }

    function toReply($orderid){
        $order = $this->system->loadModel('trading/order');
        $data['object_type'] = 1;
        $data['object_id'] = $orderid;
        $data['comment'] = $this->in['reply'];
        $data['time'] = time();
        $data['member_id'] = 0;
        $order->toReply($data);
        $this->detail($orderid);
    }

    function showPayed($orderid){
        if(!$orderid){
            echo __('订单号传递出错');
            return false;
        }
        $this->pagedata['orderid'] = $orderid;
        $objOrder = $this->system->loadModel('trading/order');
        $aORet = $objOrder->getFieldById($orderid);

        $oCur = $this->system->loadModel('system/cur');
        $aCur = $oCur->getSysCur();
        $aORet['cur_name'] = $aCur[$aORet['currency']];

        $objPayment = $this->system->loadModel('trading/payment');
        $aPayment = $objPayment->getMethods();
        $this->pagedata['payment'] = $aPayment;
        $aPayid = $objPayment->getPaymentById($aORet['payment']);
        $this->pagedata['payment_id'] = $aORet['payment'];
        $this->pagedata['op_name'] = 'admin';
        $this->pagedata['typeList'] = array('online'=>"在线支付", 'offline'=>"线下支付", 'deposit'=>"预存款支付");
        $this->pagedata['pay_type'] = ($aPayid['pay_type'] == 'ADVANCE' ? 'deposit' : 'offline');

        if($aORet['member_id'] > 0){
            $objMember = $this->system->loadModel('member/member');
            $aRet = $objMember->getMemberInfo($aORet['member_id']);
            $this->pagedata['member'] = $aRet;
        }else{
            $this->pagedata['member'] = array();
        }
        $this->pagedata['order'] = $aORet;

        $aRet = $objPayment->getAccount();
        $aAccount = array('--使用已存在帐户--');
        foreach ($aRet as $v){
            $aAccount[$v['bank']."-".$v['account']] = $v['bank']." - ".$v['account'];
        }
        $this->pagedata['pay_account'] = $aAccount;

        $this->setView('order/orderpayed.html');
        $this->output();
    }

    /**
     * toPayed
     * 后台手动到款
     *
     * @param mixed $orderid
     * @access public
     * @return void
     */
    function toPayed($orderid){
        if(!$orderid) $orderid = $_POST['order_id'];
        else $_POST['order_id'] = $orderid;

        $_POST['opid'] = $this->op->opid;
        $_POST['opname'] = $this->op->loginName;
        $this->begin('index.php?ctl=order/order&act=detail&p[0]='.$orderid);
        $objOrder = $this->system->loadModel('trading/order');
        $objOrder->op_id = $this->op->opid;
        $objOrder->op_name = $this->op->loginName;
        if($objOrder->toPayed($_POST, true)){    //处理订单收款
            $this->setError(10001);
            trigger_error('支付成功',E_USER_NOTICE);
        }else{
            $this->setError(10002);
            trigger_error('支付失败',E_USER_ERROR);
        }
        $this->end();
    }

    function showRefund($orderid){/*{{{*/
        if(!$orderid){
            echo __('订单号传递出错');
            return false;
        }
        $this->pagedata['orderid'] = $orderid;
        $objOrder = $this->system->loadModel('trading/order');
        $aORet = $objOrder->getFieldById($orderid);

        $objPayment = $this->system->loadModel('trading/payment');
        $aPayment = $objPayment->getMethods();
        $this->pagedata['payment'] = $aPayment;
        $aPayid = $objPayment->getPaymentById($aORet['payment']);
        $this->pagedata['payment_id'] = $aORet['payment'];
        $this->pagedata['op_name'] = 'admin';
        $this->pagedata['typeList'] = array('online'=>"在线支付", 'offline'=>"线下支付", 'deposit'=>"预存款支付");
        $this->pagedata['pay_type'] = ($aPayid['pay_type'] == 'ADVANCE' ? 'deposit' : 'offline');

        if($aORet['member_id'] > 0){
            $objMember = $this->system->loadModel('member/member');
            $aRet = $objMember->getMemberInfo($aORet['member_id']);
            $this->pagedata['member'] = $aRet;
        }else{
            $this->pagedata['member'] = array();
        }
        $this->pagedata['order'] = $aORet;

        $aRet = $objPayment->getAccount();
        $aAccount = array('--使用已存在帐户--');
        foreach ($aRet as $v){
            $aAccount[$v['bank']."-".$v['account']] = $v['bank']." - ".$v['account'];
        }
        $this->pagedata['pay_account'] = $aAccount;
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        $this->pagedata['score_g'] = $this->pagedata['score_g'] - $oPointHistory->getOrderHistoryGetPoint($orderid);

        $this->setView('order/orderrefund.html');
        $this->output();
    }

    function toRefund($orderid){
        if(!$orderid) $orderid = $_POST['order_id'];
        else $_POST['order_id'] = $orderid;

        $_POST['opid'] = $this->op->opid;
        $_POST['opname'] = $this->op->loginName;
        $this->begin('index.php?ctl=order/order&act=detail&p[0]='.$orderid);
        $objOrder = $this->system->loadModel('trading/order');

        $objOrder->op_id = $this->op->opid;
        $objOrder->op_name = $this->op->loginName;
        if($objOrder->refund($_POST)){    //处理订单收款
            $this->setError(10001);
            trigger_error('退款成功',E_USER_NOTICE);
        }else{
            $this->setError(10002);
            trigger_error('退款失败',E_USER_ERROR);
        }
        $this->end();
    }

    /**
     * showConsignFlow
     *
     * @param mixed $orderid
     * @access public
     * @return void
     */
    function showConsignFlow($orderid){
        if(!$orderid){
            echo __('发货错误：订单ID传递出错');
            return false;
        }
        $objOrder = $this->system->loadModel('trading/order');
        $aShipping = $objOrder->getFieldById($orderid, array('order_id','ship_status','createtime','shipping_area','shipping_id','shipping','ship_name','is_delivery','ship_email','ship_tel','ship_mobile','ship_zip','ship_area','ship_addr','cost_freight','is_protect','cost_protect'));
        if(!$aShipping){
            echo __('发货错误：没有当前订单');
            return false;
        }

        $this->pagedata['order'] = $aShipping;
        $this->pagedata['order']['protectArr'] = array('false'=>__('否'), 'true'=>__('是'));

        $aItems = $objOrder->getItemList($orderid);
        foreach($aItems as $k => $rows){
            $aItems[$k]['addon'] = unserialize($rows['addon']);
            if($rows['minfo'] && unserialize($rows['minfo'])){
                $aItems[$k]['minfo'] = unserialize($rows['minfo']);
            }else{
                $aItems[$k]['minfo'] = array();
            }
            if($aItems[$k]['is_type'] == 'goods'){
                $p = $this->system->loadModel('goods/products');
                $aGoods = $p->getFieldById($aItems[$k]['product_id'], array('store'));
            }else{
                $g = $this->system->loadModel('trading/goods');
                $aGoods = $g->getFieldById($aItems[$k]['product_id'], array('store'));
            }
            $aItems[$k]['store'] = $aGoods['store'];
        }
        $this->pagedata['items'] = $aItems;
        $this->pagedata['giftItems'] = $objOrder->getGiftItemList($orderid);
        if ($this->pagedata['giftItems']) {
            foreach($this->pagedata['giftItems'] as $k=>$v){
                $this->pagedata['giftItems'][$k]['needsend'] = $v['nums'] - $v['sendnum'];
            }
        }

        $shipping = $this->system->loadModel('trading/delivery');
        $this->pagedata['shippings'] = $shipping->getDlTypeList();
        $this->pagedata['corplist'] = $shipping->getCropList();
        if(defined('SAAS_MODE')&&SAAS_MODE){
        $this->pagedata['corplist'] = getdeliverycorplist();
        $this->pagedata['corplist'][] = array('corp_id'=>'other','name'=>'其他');
        }
        $corp = $shipping->getCorpByShipId($aShipping['shipping_id']);
        $this->pagedata['corp_id'] = $corp['corp_id'];
        $this->setView('order/orderconsign.html');
        $this->output();
    }

    function toDelivery($orderid){
        if(!$orderid) $orderid = $_POST['order_id'];
        else $_POST['order_id'] = $orderid;
        $this->begin('index.php?ctl=order/order&act=detail&p[0]='.$orderid);

        $_POST['opid'] = $this->op->opid;
        $_POST['opname'] = $this->op->loginName;
        $objOrder = $this->system->loadModel('trading/order');
        $objOrder->op_id = $this->op->opid;
        $objOrder->op_name = $this->op->loginName;
        if($objOrder->delivery($_POST)){
            $this->setError(10001);
            $oPro = $this->system->loadModel('goods/products');
            $oPro->addSellLog($_POST);
            trigger_error('发货成功',E_USER_NOTICE);
        }else{
            $this->setError(10002);
            trigger_error('发货失败',E_USER_ERROR);
        }
        $this->end();
    }

    function showReturn($orderid){
        if(!$orderid){
            echo __('退货错误：订单ID传递出错');
            return false;
        }
        $objOrder = $this->system->loadModel('trading/order');
        $aShipping = $objOrder->getFieldById($orderid, array('order_id','ship_status','createtime','is_delivery','shipping_area','shipping','ship_name','ship_email','ship_tel','ship_mobile','ship_zip','ship_area','ship_addr','cost_freight','is_protect','cost_protect'));
        if(!$aShipping){
            echo __('退货错误：没有当前订单');
            return false;
        }

        $this->pagedata['order'] = $aShipping;
        $this->pagedata['order']['protectArr'] = array('false'=>__('否'), 'true'=>__('是'));
        $aItems = $objOrder->getItemList($orderid);
        foreach($aItems as $k => $rows){
            $aItems[$k]['addon'] = unserialize($rows['addon']);
            if($rows['minfo'] && unserialize($rows['minfo'])){
                $aItems[$k]['minfo'] = unserialize($rows['minfo']);
            }else{
                $aItems[$k]['minfo'] = array();
            }
        }
        $this->pagedata['items'] = $aItems;

        $shipping = $this->system->loadModel('trading/delivery');
        $this->pagedata['shippings'] = $shipping->getDlTypeList();
        $this->pagedata['corplist'] = $shipping->getCropList();
        if(defined('SAAS_MODE')&&SAAS_MODE){
        $this->pagedata['corplist'] = getdeliverycorplist();
        $this->pagedata['corplist'][] = array('corp_id'=>'other','name'=>'其他');
        }

        $this->setView('order/orderreturn.html');
        $this->output();
    }

    function toReturn($orderid){
        if(!$orderid) $orderid = $_POST['order_id'];
        else $_POST['order_id'] = $orderid;
        $this->begin('index.php?ctl=order/order&act=detail&p[0]='.$orderid);

        $_POST['opid'] = $this->op->opid;
        $_POST['opname'] = $this->op->loginName;
        $objOrder = $this->system->loadModel('trading/order');
        $objOrder->op_id = $this->op->opid;
        $objOrder->op_name = $this->op->loginName;
        if($objOrder->toReship($_POST)){
            $this->setError(10001);
            trigger_error('退货成功',E_USER_NOTICE);
        }else{
            $this->setError(10002);
            trigger_error('退货失败',E_USER_ERROR);
        }
        $this->end();
    }

    function showAdd(){
        $this->page('order/order_new.html');
        $this->path[] = array('text'=>'添加新订单');
    }

    /**
     * create
     * 切记要和shop/cart::checkout保持功能上的同步
     *
     * @access public
     * @return void
     */
    function create(){
        if(!empty($_POST['username'])){
            $objMember = $this->system->loadModel('member/member');
            $aUser = $objMember->getMemberByUser($_POST['username']);
            if(empty($aUser['member_id'])){
                echo __('<script>alert("不存在的会员名称!")</script>');
                exit;
            }else{
                $aHidden['aMember[member_id]'] = $aUser['member_id'];
                $aHidden['aMember[member_lv_id]'] = $aUser['member_lv_id'];
                $aHidden['aMember[uname]'] = $aUser['uname'];
            }
            $_SESSION['order_add_userid'] = $aUser['member_id'];
            $_SESSION['order_user'] = $aUser;
        }else{
            $aUser = array('member_id' => 0, 'member_lv_id' => 0);
        }

        if($_POST['goods']){
            $aTmp['product_id'] = $_POST['goods'];
            $objPdt = $this->system->loadModel('goods/finderPdt');
            $aPdt = $objPdt->getList('goods_id, product_id', $aTmp, 0, count($_POST['goods']));
            unset($aTmp);
            foreach($aPdt as $key => $row){
                $num = ceil($_POST['goodsnum'][$aPdt[$key]['product_id']]);
                if($num > 0){
                    $cart['g']['cart'][$row['goods_id'].'-'.$aPdt[$key]['product_id'].'-na'] = $num;
                    $aHidden['aCart[g][cart]['.$row['goods_id'].'-'.$aPdt[$key]['product_id'].'-na]'] = $num;

                    $oPromotion = $this->system->loadModel('trading/promotion');
                    if($pmtid = $oPromotion->getGoodsPromotionId($row['goods_id'], $aUser['member_lv_id'])){
                        $cart['g']['pmt'][$row['goods_id']] = $pmtid;
                        $aHidden['aCart[g][pmt]['.$row['goods_id'].']'] = $pmtid;
                    }
                }
            }
        }
        if($_POST['package']){
            $aTmp['goods_id'] = $_POST['package'];
            $oPackage = $this->system->loadModel('trading/package');
            $aPkg = $oPackage->getList('goods_id', $aTmp, 0, count($_POST['package']));
            unset($aTmp);
            foreach($aPkg as $key => $row){
                $num = ceil($_POST['pkgnum'][$aPkg[$key]['goods_id']]);
                if($num > 0){
                    $cart['p'][$row['goods_id']]['num'] = $num;
                    $aHidden['aCart[p]['.$row['goods_id'].'][num]'] = $num;
                }
            }
        }
        if(!$cart){
            echo __('<script>alert("没有购买商品或者购买数量为0!")</script>');
            exit;
        }

        $this->pagedata['hiddenInput'] = $aHidden;

        $objCart = $this->system->loadModel('trading/cart');
        $aOut = $objCart->getCheckout($cart, $aUser, '');
        $aOut['trading']['admindo'] = 1;
        $this->pagedata['has_physical'] = $aOut['has_physical'];
        $this->pagedata['minfo'] = $aOut['minfo'];
        $this->pagedata['areas'] = $aOut['areas'];
        $this->pagedata['currencys'] = $aOut['currencys'];
        $this->pagedata['currency'] = $aOut['currency'];
        $this->pagedata['payments'] = $aOut['payments'];
        $this->pagedata['trading'] = $aOut['trading'];

        if($aUser['member_id']){
            $member = $this->system->loadModel('member/member');
            $addrlist = $member->getMemberAddr($aUser['member_id']);
            foreach($addrlist as $rows){
                if(empty($rows['tel'])){
                    $str_tel = '手机：'.$rows['mobile'];
                }else{
                    $str_tel = '电话：'.$rows['tel'];
                }
                $addr[] = array('addr_id'=> $rows['addr_id'],'def_addr'=>$rows['def_addr'],'addr_region'=> $rows['area'],
                                'addr_label'=> $rows['addr'].' (收货人：'.$rows['name'].' '.$str_tel.' 邮编：'.$rows['zip'].')');
            }
            $this->pagedata['trading']['receiver']['addrlist'] = $addr;
            $this->pagedata['is_allow'] = (count($addr)<5 ? 1 : 0);
        }

        $this->setView('order/order_create.html');
        $this->output();
    }

    function getAddr(){
        if($_GET['addr_id']){
            $oMem = $this->system->loadModel('member/member');
            $this->pagedata['trading']['receiver'] = $oMem->getAddrById($_GET['addr_id']);
        }
        $this->pagedata['trading']['member_id'] = $_SESSION['order_add_userid'];
        $this->setView('shop:common/rec_addr.html');
        $this->output();
    }

    function shipping(){
        $aCart = $_POST['aCart'];
        $aMember = $_SESSION['order_user'];

        $sale = $this->system->loadModel('trading/sale');
        $trading = $sale->getCartObject($aCart,$aMember['member_lv_id'],true);

        $shipping = $this->system->loadModel('trading/delivery');
        $aShippings = $shipping->getDlTypeByArea($_POST['area']);
        foreach($aShippings as $k=>$s){
            $aShippings[$k]['price'] = cal_fee($s['expressions'],$trading['weight'],$trading['pmt_b']['totalPrice'],$s['price']);
            if($s['pad'] == 0 || $s['has_cod'] == 0) $aShippings[$k]['has_cod'] = 0;
            if($s['protect']==1){
                $aShippings[$k]['protect'] = max($trading['totalPrice']*$s['protect_rate'],$s['minprice']);
            }else{
                $aShippings[$k]['protect'] = false;
            }
        }
        $this->pagedata['shippings'] = $aShippings;

        $this->setView('shop:cart/checkout_shipping.html');
        $this->output();
    }

    function payment(){
        $payment = $this->system->loadModel('trading/payment');
        $oCur = $this->system->loadModel('system/cur');
        $this->pagedata['payments'] = $payment->getByCur($_POST['cur']);
        $this->pagedata['delivery']['has_cod'] = $_POST['d_pay'];
        $this->pagedata['order']['payment'] = $_POST['payment'];
        //todo 需要确定支付费率的需求
        $this->setView('shop:common/paymethod.html');
        $this->output();
    }

    function total(){
        $aCart = $_GET['aCart'];
        $aMember = $_SESSION['order_user'];
        $tarea = explode(':', $_POST['area'] );
        $_POST['area'] = $tarea[count($tarea)-1];
        $objCart = $this->system->loadModel('trading/cart');
        $this->pagedata['trading'] = $objCart->checkoutInfo($aCart, $aMember, $_POST);
        $this->setView('shop:cart/checkout_total.html');
        $this->output();
    }

    function doCreate(){
        $this->begin('index.php?ctl=order/order&act=index');
        $aCart = $_POST['aCart'];
        $aMember = $_POST['aMember'];
        unset($_SESSION['order_add_userid']);
        unset($_SESSION['order_user']);

        $order = $this->system->loadModel('trading/order');
        $order->op_id = $this->op->opid;
        $order->op_name = $this->op->loginName;

        $orderid = $order->create($aCart,$aMember,$_POST['delivery'],$_POST['payment'],$_POST['minfo'] );
//        $order->_info['order_id'] = $orderid;
//        $order->addLog('订单: '.$orderid.' 生成成功',$this->op->opid, $this->op->loginName, '添加');
        $this->end($orderid,'订单: '.$orderid.' 生成成功');
    }

    function showEdit($orderid){
        $this->path[] = array('text'=>'订单编辑');
        $objOrder = $this->system->loadModel('trading/order');
        $aOrder = $objOrder->getFieldById($orderid);
        $aOrder['discount'] = 0 - $aOrder['discount'];

        $oCur = $this->system->loadModel('system/cur');
        $aCur = $oCur->getSysCur();
        $aOrder['cur_name'] = $aCur[$aOrder['currency']];

        $aOrder['items'] = $objOrder->getItemList($orderid);
        $aOrder['pmt'] = $objOrder->getPmtList($orderid);

        if($aOrder['member_id'] > 0){
            $objMember = $this->system->loadModel('member/member');
            $aOrder['member'] = $objMember->getFieldById($aOrder['member_id'], array('*'));
            $aOrder['ship_email'] = $aOrder['member']['email'];
        }else{
            $aOrder['member'] = array();
        }

        $objDelivery = $this->system->loadModel('trading/delivery');
        $aArea = $objDelivery->getDlAreaList();
        foreach ($aArea as $v){
            $aTmp[$v['name']] = $v['name'];
        }
        $aOrder['deliveryArea'] = $aTmp;

        $aRet = $objDelivery->getDlTypeList();
        foreach ($aRet as $v){
            $aShipping[$v['dt_id']] = $v['dt_name'];
        }
        $aOrder['selectDelivery'] = $aShipping;

        $objPayment = $this->system->loadModel('trading/payment');
        $aRet = $objPayment->getMethods();
        $aPayment[-1] = '货到付款';
        foreach ($aRet as $v){
            $aPayment[$v['id']] = $v['custom_name'];
        }
        $aOrder['selectPayment'] = $aPayment;


        $objCurrency = $this->system->loadModel('system/cur');
        $aRet = $objCurrency->curList();
        foreach ($aRet['main'] as $v){
            $aCurrency[$v['cur_code']] = $v['cur_name'];
        }
        $aOrder['curList'] = $aCurrency;
        $this->pagedata['order'] = $aOrder;
        $this->page('order/order_edit.html');
    }

    function addItem(){
        if($_POST['order_id']){
            $objOrder = $this->system->loadModel('trading/order');
            $retMark = $objOrder->insertOrderItem($_POST['order_id'], $_POST['newbn'], 1);
            $aOrder['items'] = $objOrder->getItemList($_POST['order_id']);
            $aOrder['order_id'] = $_POST['order_id'];

            if($retMark == 'none'){
                $aOrder['alertJs'] = '<script>alert("商品货号输入不正确，没有该商品或者商品已经下架。\n注意：如果是多规格商品，请输入规格编号。");</script>';
            }elseif($retMark == 'exist'){
                $aOrder['alertJs'] = '<script>alert("订单中存在相同的商品货号。");</script>';
            }else{
                if(!$retMark){
                    $aOrder['alertJs'] = '<script>alert("插入数据库失败");</script>';
                }else{
                    $aOrder['alertJs'] = '<script>countF();</script>';
                }
            }
            $this->pagedata['order'] = $aOrder;
            $this->__tmpl='order/edit_items.html';
            $this->output();
        }
    }

    function toEdit(){
        $_POST['is_protect'] = isset($_POST['is_protect']) ? $_POST['is_protect'] : 'false';
        $_POST['is_tax'] = isset($_POST['is_tax']) ? $_POST['is_tax'] : 'false';
        $_POST['discount'] = 0 - $_POST['discount'];
        $this->begin('index.php?ctl=order/order&act=index');
        if (count($_POST['aItems'])){
            $objOrder = $this->system->loadModel('trading/order');
            $objOrder->op_id = $this->op->opid;
            $objOrder->op_name = $this->op->loginName;
            if($objOrder->editOrder($_POST )){
                $this->end(true,'保存成功');
            }else{
                trigger_error('库存不足，请确认！',E_USER_ERROR);
            }
        }else{
            trigger_error('没有商品明细',E_USER_ERROR);
        }
    }

    /**
     * toPrint
     *
     * @access public
     * @return void
     */
    function toPrint($orderid){
        if($_POST['order_id']){
            $aInput = $_POST['order_id'];
        }elseif($orderid){
            $aInput = array($orderid);
        }else{
            $this->begin('index.php?ctl=order/order&act=index');
            $this->end(false, __('打印失败：订单参数传递出错'));
            exit();
        }

        $oCur = $this->system->loadModel('system/cur');
        $aCur = $oCur->getSysCur();

        $dbTmpl = $this->system->loadModel('content/systmpl');
        foreach($aInput as $orderid){
            $aData=array();
            $objOrder = $this->system->loadModel('trading/order');
            $aData = $objOrder->getFieldById($orderid);
            $aData['currency'] = $aCur[$aData['currency']];

            $objMember = $this->system->loadModel('member/member');
            $aMember = $objMember->getFieldById($aData['member_id'], array('uname','name','tel','mobile','email','zip','addr'));
            $aData['member'] = $aMember;

            $payment = $this->system->loadModel('trading/payment');
            $aPayment = $payment->getPaymentById($aData['payment']);
            $aData['payment'] = $aPayment['custom_name'];

            $aData['shopname'] = $this->system->getConf('store.company_name');
            $aData['shopaddress'] = $this->system->getConf('store.address');
            $aData['shoptelphone'] = $this->system->getConf('store.telephone');
            $aData['shopzip'] = $this->system->getConf('store.zip_code');

            $aItems = $objOrder->getItemList($orderid);
            foreach($aItems as $k => $rows){
                $aItems[$k]['addon'] = unserialize($rows['addon']);
                if($rows['minfo'] && unserialize($rows['minfo'])){
                    $aItems[$k]['minfo'] = unserialize($rows['minfo']);
                }else{
                    $aItems[$k]['minfo'] = array();
                }
                if($aItems[$k]['addon']['adjname']) $aItems[$k]['name'] .= '<br>配件：'.$aItems[$k]['addon']['adjname'];
                $aItems[$k]['catname'] = $objOrder->getCatByPid($rows['product_id']);
            }
            $aData['goodsItems'] = $aItems;
            $aData['giftItems'] = $objOrder->getGiftItemList($orderid);
            $this->pagedata['pages'][] = $dbTmpl->fetch('misc/orderprint',array('order'=>$aData));
        }
        $this->pagedata['shopname'] = $aData['shopname'];

        $this->setView('print.html');
        $this->output();
    }

    /**
     * showOrderFlow
     *
     * @param mixed $tag pay, refund, consign, reshipping
     * @param mixed 1,0
     * @access public
     * @return void
     */
    function showOrderFlow(){
        $this->path[] = array('text'=>'订单是否创建单据');
        $this->pagedata['flow']= array('refund' => $this->system->getConf('order.flow.refund'),
            'consign' => $this->system->getConf('order.flow.consign'),
            'reship' => $this->system->getConf('order.flow.reship'),
            'payed' => $this->system->getConf('order.flow.payed'));
        $this->page('order/order_flow.html');
    }
/*
    function showOrderMsg($orderid) {
        $oMsg = $this->system->loadModel('resources/message');
        $orderMsg = $oMsg->getOrderMessage($orderid);
        foreach( $orderMsg as $mk => $mv){
            $oMsg->setReaded($mv['msg_id']);
        }
        $this->pagedata['ordermsg'] = $orderMsg;
        $this->pagedata['orderid'] = $orderid;
        $this->setView('order/show_order_msg.html');
        $this->output();
    }
*/
    /**
     * saveFlow
     *
     * @param mixed $tag pay, refund, consign, reshipping
     * @param mixed 1,0
     * @access public
     * @return void
     */
    function saveFlow($tag, $checkmark){
        $items = array('payed','refund','consign','reship');
        foreach($items as $item){
            if(!$_POST['aFlow'][$item]){
                $_POST['aFlow'][$item] = false;
            }
            $this->system->setConf('order.flow.'.$item, $_POST['aFlow'][$item]);
        }
        $this->splash('success','index.php?ctl=order/order&act=showOrderFlow');
    }

    /**
     * showPrintStyle
     *
     * @access public
     * @return void
     */
    function showPrintStyle(){
        $this->path[] = array('text'=>'订单打印格式设置');
        $dbTmpl = $this->system->loadModel('content/systmpl');
        $filetxt = $dbTmpl->get('misc/orderprint');
        $cartfiletxt = $dbTmpl->get('../admin/view/order/print_cart');
        $sheetfiletxt = $dbTmpl->get('../admin/view/order/print_sheet');
        $this->pagedata['styleContent'] = $filetxt;
        $this->pagedata['styleContentCart'] = $cartfiletxt;
        $this->pagedata['styleContentSheet'] = $sheetfiletxt;
        $this->page('order/printstyle.html');
    }

    /**
     * savePrintStyle
     *
     * @access public
     * @return void
     */
    function savePrintStyle(){
        $this->begin('index.php?ctl=order/order&act=showPrintStyle');
        $dbTmpl = $this->system->loadModel('content/systmpl');
        $dbTmpl->set('../admin/view/order/print_sheet', $this->in["txtcontentsheet"]);
        $dbTmpl->set('../admin/view/order/print_cart', $this->in["txtcontentcart"]);
        $this->end($dbTmpl->set('misc/orderprint', $this->in["txtcontent"]),__('订单打印模板保存成功'));
    }

    /**
     * rebackPrintStyle
     *
     * @access public
     * @return void
     */
    function rebackPrintStyle(){
        $this->begin('index.php?ctl=order/order&act=showPrintStyle');
        $dbTmpl = $this->system->loadModel('content/systmpl');
        $dbTmpl->clear('../admin/view/order/print_sheet');
        $dbTmpl->clear('../admin/view/order/print_cart');
        $this->end($dbTmpl->clear('misc/orderprint'),'恢复默认值成功');
    }

    function delete(){
        $oOrder = $this->system->loadModel("trading/order");
        $msg = '';
        foreach($_POST['order_id'] as $v)
            $oOrder->toRemove($v ,  $msg);
        echo $msg;
    }
    function memberInfo($nMId){
        $this->pagedata['member_id'] = $nMId;
        $this->setView('order/order_membertab.html');
        $this->output();
    }

    function recycle() {
        $this->model->op_id = $this->op->opid;
        $this->model->op_name = $this->op->loginName;
        parent::recycle();
        $oOrder = $this->system->loadModel("trading/order");
        foreach($_POST['order_id'] as $v){
            $oOrder->toUnfreez($v);
        }

        $status = $this->system->loadModel('system/status');
        $status->count_order_to_pay();
        $status->count_order_new();
        $status->count_order_to_dly();
    }
    function active() {
        $this->model->op_id = $this->op->opid;
        $this->model->op_name = $this->op->loginName;
        parent::active();

        $status = $this->system->loadModel('system/status');
        $status->count_order_to_pay();
        $status->count_order_new();
        $status->count_order_to_dly();
    }

}
