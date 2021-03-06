<?php
require_once('shopObject.php');

class mdl_dly_centers extends shopObject{

    var $idColumn = 'dly_center_id'; //表示id的列 
    var $textColumn = 'name';
    var $defaultCols = 'name,region,address,area_id,zip,phone,uname';
    var $adminCtl = 'trading/delivery_centers';
    var $defaultOrder = array('dly_center_id','desc');
    var $tableName = 'sdb_dly_center';

    function getColumns(){
        return array(
            'dly_center_id'=>array('label'=>'发货点id','class'=>'span-1'),
            'name'=>array('label'=>'发货点名称','class'=>'span-4','required'=>1),
            'region'=>array('label'=>'地区','class'=>'span-5','type'=>'region'),
            'address'=>array('label'=>'地址','class'=>'span-5','required'=>1),
            'uname'=>array('label'=>'发货人姓名','class'=>'span-3'),
            'sex'=>array('label'=>'性别','class'=>'span-1','type'=>'sex'),
            'phone'=>array('label'=>'电话','class'=>'span-3'),
            'cellphone'=>array('label'=>'手机','class'=>'span-3'),
            'zip'=>array('label'=>'邮编','class'=>'span-2'),
        );
    }

}
?>
