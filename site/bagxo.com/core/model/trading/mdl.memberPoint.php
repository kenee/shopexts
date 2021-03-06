<?php
class mdl_memberPoint extends modelFactory{


    function toUpdatelevel($userId) {
        $oMember = $this->system->loadModel('member/member');
        $nPoint = $this->getMemberPoint($userId);
        $aTmp = $oMember->getLevelByPoint($nPoint);
        $aData['member_lv_id'] = $aTmp['member_lv_id'];
        $rRs = $this->db->query('select * from sdb_members where member_id='.$userId);
        $sSql = $this->db->GetUpdateSQL($rRs, $aData);
        if ($sSql) {
            $this->db->exec($sSql);
        }
    }

    function getMemberPoint($userId) {
        $sSql = 'SELECT point FROM sdb_members WHERE member_id='.intval($userId);
        $aUserPoint = $this->db->selectrow($sSql);        
        return intval($aUserPoint['point']);
    }
/*
    function updatePoint($userId, $nPoint) {
        $aPoint = $this->getMemberPoint($userId);
        $nChangePoint = $nPoint - $aPoint['point'];
        $this->chgPoint($userId, $nChangePoint, 'order_refund_method_adjust', 0);        
        return abs($nChangePoint);
    }*/

    //检查用户积分是否足够
    function _chgPoint($userId, $nCheckPoint) {
        if ($nCheckPoint<0) {
            $nPoint = $this->getMemberPoint($userId);
            if ($nPoint >= abs($nCheckPoint)) {
                return true;
            }else{
                return false;
            }
        }else {
            return true;
        }
    }

    function chgPoint($userId, $nPoint, $sReason, $relatedId=null){
        //初始化
        if ($nPoint==0) {
            return true;
        }else if (!$this->_chgPoint($userId, $nPoint)) {
            trigger_error(__('积分扣除超过会员已有积分'), E_USER_ERROR);
            return false;
        }

        $oMember = $this->system->loadModel('member/member');
        $aPoint = $oMember->getFieldById($userId, array('point'));
        $oLv = $this->system->loadModel('member/level');
        if ($userId && ($oLv->checkMemLvType($userId) != 'wholesale')) {
            $userId = intval($userId);
            $oPointHistory = $this->system->loadModel('trading/pointHistory');
            $aUserPoint['point'] = $this->getMemberPoint($userId);
            $aUserPoint['point'] += $nPoint;

            $rRs = $this->db->query('select * from sdb_members where member_id='.$userId);
            $sSql = $this->db->GetUpdateSQL($rRs, $aUserPoint);
            if($sSql)$this->db->exec($sSql);
            $this->toUpdatelevel($userId);
            $aPointHistory = array(
                                'member_id' => $userId,
                                'point' => $nPoint,
                                'reason' => $sReason,
                                'related_id' => $relatedId);
            $oPointHistory->addHistory($aPointHistory);
        }
        return true;
    }
    
    //部分付款
    function payPart($userId, $orderId, $nPoint) {
        return $this->chgPoint($userId, $nPoint, 'order_bill', $orderId);

    }

    function payBackAll($userId, $orderId) {
        $nAllOrderPoint = $oPointHistory->getOrderAllPoint($orderId);
        $nPayBackPoint = 0 - $oPointHistory->getOrderHistoryPoint($orderId);        
        return $this->chgPoint($userId, $nPayPoint, 'order_refund', $orderId);
    }

    function paybackPart($userId, $orderId, $nPoint) {
        return $this->chgPoint($userId, $nPoint, 'order_refund', $orderId);
    }




    //++++++++++消费积分
    function payAllConsumePoint($userId, $orderId) {
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        $oOrder = $this->system->loadModel('trading/order');
        $aTmp = $oOrder->getFieldById($orderId, array('score_u'));
        $nPayPoint = 0 - intval($aTmp['score_u']);
        return $this->chgPoint($userId, $nPayPoint, 'order_pay_use', $orderId);
    }

    function refundAllConsumePoint($userId, $orderId) {
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        $oOrder = $this->system->loadModel('trading/order');
        $aTmp = $oOrder->getFieldById($orderId, array('score_u'));
        $nPayPoint = $aTmp['score_u'];
        return $this->chgPoint($userId, $nPayPoint, 'order_refund_use', $orderId);
    }
    //++++++++++所得积分    
    function payAllGetPoint($userId, $orderId) {
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        $oOrder = $this->system->loadModel('trading/order');
        $aTmp = $oOrder->getFieldById($orderId, array('score_g'));
        $nPayPoint = $aTmp['score_g'];
        return $this->chgPoint($userId, $nPayPoint, 'order_pay_get', $orderId);
    }

    function refundPartGetPoint($userId, $orderId, $nPoint) {
        $this->chgPoint($userId, $nPoint, 'order_refund_get', $orderId);
    }

    function refundAllGetPoint($userId, $orderId) {
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        $oOrder = $this->system->loadModel('trading/order');
        $aTmp = $oOrder->getFieldById($orderId, array('score_g'));
        $nPayPoint = 0 - $aTmp['score_g'];
        return $this->chgPoint($userId, $nPayPoint, 'order_refund_get', $orderId);
    }
    
    //++++++++++取消订单,退还积分
    //todo
    
    function cancelOrderRefundConsumePoint($userId, $orderId) {
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        return $this->chgPoint($userId, $oPointHistory->getOrderConsumePoint($orderId), 'order_cancel_refund_consume_gift', $orderId);
    }
    function cancelOrderRefundChangePoint($userId, $orderId) {
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        return $this->chgPoint($userId, $oPointHistory->getOrderChangePoint($orderId), 'order_cancel_refund_change_goods', $orderId);
    }
}

?>
