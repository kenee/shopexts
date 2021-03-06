<?php
class ctl_point extends adminPage{
    var $workground = 'sale';

    function pointSetting(){
        $this->path[] = array('text'=>"积分设置");
        $this->pagedata['point']['refund_method']=$this->system->getConf('point.refund_method');
        $this->pagedata['point']['get_policy']=$this->system->getConf('point.get_policy');
        $this->pagedata['point']['get_rate']=$this->system->getConf('point.get_rate');
        $this->pagedata['point']['set_register']=$this->system->getConf('point.set_register');
        $this->pagedata['point']['set_register_v']=$this->system->getConf('point.set_register_v');
        $this->pagedata['point']['set_commend']=$this->system->getConf('point.set_commend');
        $this->pagedata['point']['set_commend_v']=$this->system->getConf('point.set_commend_v');
        $this->pagedata['point']['set_commend_help']=$this->system->getConf('point.set_commend_help');
        $this->pagedata['point']['set_commend_help_v']=$this->system->getConf('point.set_commend_help_v');
        $this->pagedata['point']['set_coupon']=$this->system->getConf('point.set_coupon');
//        echox($this->pagedata);
        $this->page('sale/point/pointSetting.html');
    }
    
    function savePointSet(){
        $oPoint=$this->system->loadModel('trading/point');
        $data=array(
            'refund_method'=>$this->in['refund_method'],
            'get_policy'=>$this->in['get_policy'],
            'get_rate'=>$this->in['get_rate'],
            'set_register'=>$this->in['set_register'],
            'set_register_v'=>$this->in['set_register_v'],
            'set_commend'=>$this->in['set_commend'],
            'set_commend_v'=>$this->in['set_commend_v'],
            'set_commend_help'=>$this->in['set_commend_help'],
            'set_commend_help_v'=>$this->in['set_commend_help_v'],
            'set_coupon'=>$this->in['set_coupon']
        );
//        echox($data);

        $oPoint->savePointSetting($data);
        $this->splash('success', 'index.php?ctl=sale/point&act=pointSetting');
    }

}
?>
