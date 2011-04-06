<?php
include_once('objectPage.php');
class ctl_items extends objectPage{

    var $name='货物';
    var $actionView = 'product/finder_products_action.html';
    var $filterView = 'product/finder_products_filter.html';
    var $workground = 'goods';
    var $object = 'goods/finderPdt';
    var $actions= array(
        'edit'=>'编辑',
        'toRemove'=>'删除',
    );
    var $disableGridEditCols = "product_id,goods_id,title,cost,props";
    var $disableColumnEditCols = "product_id,goods_id,title,cost,props";
    var $disableGridShowCols = "barcode,product_id,goods_id,title,cost,props,uptime,last_modify,freez";    
}
?>
