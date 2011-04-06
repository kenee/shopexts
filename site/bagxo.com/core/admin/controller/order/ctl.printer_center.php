<?php
include_once('objectPage.php');
class ctl_printer_center extends objectPage{

    var $name = '发货信息';
    var $workground = 'order';
    var $object='trading/dly_centers';
    var $actionView = 'order/dly_center_action.html';
    var $actions= array(
        'edit_tmpl'=>'编辑',
    );
    function printer_select(){
        $path="/pxml";
        $handle=opendir('../../pxml');
        $ct=array();
        while(false !==($file=readdir($handle))){
            if(preg_match('/.*\\.xml+/',$file)){
             //echo $file;
             $ct[]=$file;
            }

        }
        $this->pagedata['pdate'] = $ct;

        $this->setView('order/printertest.html');
        $this->output();
    }
    function add_center(){
        $this->page('order/dly_center_editor.html');
    }

    function save_data(){
        $this->begin('index.php?ctl=order/delivery_centers&act=index');
        $this->end( $this->model->insert($_POST),'发货信息添加成功');
    }
}
?>
