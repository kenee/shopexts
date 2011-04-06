<?php
class ctl_adjunct extends adminPage{

    function addGrp(){
        $this->pagedata['aOptions'] = array('goods'=>__('选择几件商品作为配件'), 'filter'=>__('选择一组商品搜索结果作为配件'));        
        $this->setView('product/adjunct/info.html');
        $this->output();
        return;
    }

    function doAddGrp(){
        $this->pagedata['adjunct'] =array('name'=>$_POST['name'],'type'=>$_POST['type']);
        $this->pagedata['key'] = time();
        $this->setView('product/adjunct/row.html');
        $this->output();
    }
}
?>
