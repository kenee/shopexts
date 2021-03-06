<?php

###IP 要用ip2long转化后存储，反之取出时要用long2ip还原###

require_once('shopObject.php');

class mdl_paymentcfg extends shopObject{
    //var $__setting;

    var $adminCtl = 'order/payment';
    var $idColumn='id';
    var $textColumn = 'id';
    var $defaultCols = 'custom_name,pay_type,orderlist';
    var $defaultOrder = array('orderlist','desc',',','id','ASC');
    var $tableName = 'sdb_payment_cfg';

    function getColumns(){
        return array(
                    'id'=>array('label'=>'支付方式ID','class'=>'span-3'),
                    'custom_name'=>array('label'=>'支付方式名称','class'=>'span-6'),
                    'orderlist'=>array('label'=>'排序','class'=>'span-1'),
                );
    }

    
}
?>
