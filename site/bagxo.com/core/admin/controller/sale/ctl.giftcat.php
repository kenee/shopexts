<?php
include_once('objectPage.php');
class ctl_giftcat extends objectPage{

    var $name = '赠品分类';
    var $object = 'trading/giftcat';
    var $workground = 'sale';
    var $actionView = 'sale/giftcat/finder_action.html'; //默认的动作html模板,可以为null
    var $filterView = null; //默认的过滤器html,可以为null
    var $actions= array(
                'showAddType'=>'编辑',
            );

    function getTypeList() {
        $this->page('sale/giftcat/list.html',true);
    }

    function addType(){
        $oGiftCat = $this->system->loadModel('trading/giftcat');
        if(!$oGiftCat->addType($this->in)){
            //todo 出错信息
        }
        $this->splash('success','index.php?ctl=sale/giftcat&act=index');
    }

    function showAddType($catId){
        $oGiftCat = $this->system->loadModel('trading/giftcat');
        if ($catId) {
            $this->pagedata['giftcat'] = $oGiftCat->getTypeById($catId);
        } else {
            $this->pagedata['giftcat']['shop_iffb'] = 0;
            $this->pagedata['giftcat']['orderlist'] = $oGiftCat->getInitOrder();
        }
        $this->page('sale/giftcat/addType.html');
    }
    
    function recycle(){
        $oGift = $this->system->loadModel('trading/gift');
        $varGoto = 1;
        foreach($_REQUEST['giftcat_id'] as $cat_id){
            $oGift->getList('',array('giftcat_id'=>array($cat_id)),0,10,$count);
            if($count){
                echo __('该赠品分类下还有赠品，请先删除赠品后再删除分类！');
                $varGoto = 0;
                break;
            }
        }
        if($varGoto){
            parent::recycle();
        }
    }
}
?>