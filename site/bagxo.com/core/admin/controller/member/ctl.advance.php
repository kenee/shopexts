<?php
include_once('objectPage.php');
class ctl_advance extends objectPage {

    var $name='全站预付款';
    var $workground = 'analytics';
    var $object = 'member/advance';
    var $actionView = 'member/advance_finder_action.html'; //默认的动作html模板,可以为null
 //   var $filterView = 'member/advance_finder_filter.html'; //默认的过滤器html,可以为null
    var $disableGridEditCols = "log_id,shop_advance,money";
    var $disableColumnEditCols = "log_id,shop_advance,money";
    var $disableGridShowCols = "log_id,shop_advance,money";
//    var $noRecycle = false; //是否存在回收站
    var $deleteAble=false; //是否可删除
    var $actions= array(
        'edit'=> array('html'=>'<span class="sysiconBtnNoIcon" onclick=\'new Dialog("index.php?ctl=member/advance&act=advancelist&log_id="+this.parentNode.parentNode.parentNode.getAttribute("item-id"), {height:600,width:800})\'>查看</span>')
    );

    function index(){
        $this->pagedata['sfinddate'] = date("Y-m-",time()).'1';
        $this->pagedata['efinddate'] = date("Y-m-d",time());
//        $options['params']['sdtime'] = date("Y-m-",time()).'1/'.date("Y-m-d",time());
        parent::index($options);
    }

    function _finder_common($options){
        $options['params']['sdtimecommon'] = date("Y-m-",time()).'1/'.date("Y-m-d",time());
        parent::_finder_common($options);
        $oAdv = $this->system->loadModel('member/advance');
        $advanceStatistics = $oAdv->getAdvanceStatistics(date("Y-m-",time()).'1', date("Y-m-d",time()));
        $statusStr = '当前共'.$advanceStatistics['count'].'笔  总转入'.$advanceStatistics['import_money'].'元  总转出'.$advanceStatistics['explode_money'].'元 店内总余额'.$oAdv->getShopAdvance().'元';
//        $statusStr .= "<script>(function(){<{$_finder.var}>.selectAll.call(<{$_finder.var}>);})()</script>";
        $this->pagedata['_finder']['statusStr'] = $statusStr;
        $this->pagedata['_finder']['select'] = false;
    }
    
    /*
    function detail(){ //可选方法。存在的话，则单行可点击
    }
    */

    function finder($type,$view,$cols,$finder_id,$limit){
        $sdtime = explode("/",$_GET['sdtime']);
        $oAdv = $this->system->loadModel('member/advance');
        $advanceStatistics = $oAdv->getAdvanceStatistics($sdtime[0], $sdtime[1]);
        $statusStr = '当前共'.$advanceStatistics['count'].'笔 总转入'.$advanceStatistics['import_money'].'元 总转出'.$advanceStatistics['explode_money'].'元 店内总余额'.$oAdv->getShopAdvance().'元 ';
        
        $_GET['_finder']['statusStr'] =  $statusStr;
        $finder_id = stripslashes($finder_id);
        parent::finder($type,$view,$cols,$finder_id,$limit);
    }

    function advancelist($nMId,$nPage=1){
        $oAdv = $this->system->loadModel('member/advance');
        if($_GET['member_id'])
            $nMId = $_GET['member_id'];
        if($_GET['log_id']){
            $rs = $oAdv->getAdvanceLogByLogId($_GET['log_id']);
            $nMId = $rs['member_id'];
        }
        $advList = $oAdv->getFrontAdvList($nMId,$nPage-1,10);

        $advanceStatistics = $oAdv->getMemberAdvanceStatistics($nMId);
        $statusStr = '<span class="colborder">当前共'.$advanceStatistics['count'].'笔</span> <span class="colborder">总转入'.$advanceStatistics['import_money'].'元</span> <span class="colborder">总转出'.$advanceStatistics['explode_money'].'元</span> 余额'.$oAdv->get($nMId).'元';
        $this->pagedata['items'] = $advList['data'];
        $this->pagedata['page'] = $nPage;
        $this->pagedata['totalpage'] = $advList['page'];
        $this->pagedata['member_id'] = $nMId;
        $this->pagedata['statusStr'] = $statusStr;
        $this->setView('member/advancelist.html');
        $this->output();
    }


}
?>
