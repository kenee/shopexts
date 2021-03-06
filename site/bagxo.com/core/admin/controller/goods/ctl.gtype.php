<?php
/**
 * ctl_gtype 
 * 
 * @uses adminPage
 * @package 
 * @version $Id: ctl.gtype.php 1928 2008-04-25 02:13:05Z alex $
 * @copyright 2003-2007 ShopEx
 * @author Wanglei <flaboy@zovatech.com> 
 * @license Commercial
 */
include_once('objectPage.php');
class ctl_gtype extends objectPage{

    var $name='商品类型';
    var $workground = 'goods';
    var $object = 'goods/gtype';
    var $actionView = 'product/gtype/finder_action.html';
    var $allowImport = true;
    var $allowExport = false;
    var $ioType = 'gtype';
    
    var $disableGridEditCols = "is_def,setting,schema_id";
    var $disableColumnEditCols = "is_def,setting,schema_id";
    var $disableGridShowCols = "is_def,setting,schema_id";
    
    var $actions= array(
        'toEdit'=>'编辑',
        'getXml'=>'下载'
    );
    
    function index(){
        parent::index(array('params'=>array('is_def'=>'false')));
    }

    function newType(){
        $this->path[] = array('text'=>'添加类型');
        $objGschema = $this->system->loadModel('goods/schema');
        $aSchema = $objGschema->getList();
        foreach($aSchema as $k=>$item){
            if($item['is_def']){
                unset($aSchema[$k]);
            }
        }
        $this->pagedata['schemas'] = &$aSchema;
        $this->title=__('new goods type');
        $this->page('product/gtype/newType.html');
    }

    function toAdd($schema, $tag){
        if($tag == 'commit'){
            $_SESSION['gtype']['setting'] = $_POST['setting'];
            $_SESSION['gtype']['is_physical'] = $_POST['is_physical'];
            $this->editType();
        }else{
            unset($_SESSION['gtype']);
            $_SESSION['gtype']['schema_id'] = $schema;
            $objGschema = $this->system->loadModel('goods/schema');
            $objGschema->dialog($schema);
        }
    }
    
    function toEdit($typeid){
        if($typeid){
            $gtype = $this->system->loadModel('goods/gtype');
            $aType = $gtype->getTypeDetail($typeid, true);
            $_SESSION['gtype'] = $aType;
            $_POST['setting'] = $aType['setting'];
        }

        $objGschema = $this->system->loadModel('goods/schema');
        $objGschema->dialog($aType['schema_id']);
    }
    
    function editType(){
        $this->path[] = array('text'=>'类型编辑');

        $aType = $_SESSION['gtype'];
        $this->pagedata['gtype'] = $aType;
        $brand = $this->system->loadModel('goods/brand');
        foreach($brand->getAll() as $rows){
            $aTmpList[$rows['brand_id']] = $rows['brand_name'];
        }
        $this->pagedata['brands'] = $aTmpList;
        $this->page('product/gtype/workpage.html');
    }
    
    function toSave(){
        if (empty($_POST['name'])){
            $this->begin('index.php?ctl=goods/gtype&act=newType');
            trigger_error('类型名称不能为空',E_USER_ERROR);
            $this->end();
        }
        $this->begin('index.php?ctl=goods/gtype&act=index');
        $objGtype = $this->system->loadModel('goods/gtype');
        if(isset($_SESSION['gtype']['is_physical'])) $_POST['is_physical'] = $_SESSION['gtype']['is_physical'];
        if(isset($_SESSION['gtype']['schema_id'])) $_POST['schema_id'] = $_SESSION['gtype']['schema_id'];
        if(isset($_SESSION['gtype']['setting'])) $_POST['setting'] = $_SESSION['gtype']['setting'];
        if(isset($_SESSION['gtype']['dly_func'])) $_POST['dly_func'] = $_SESSION['gtype']['dly_func'];
        if(isset($_SESSION['gtype']['ret_func'])) $_POST['ret_func'] = $_SESSION['gtype']['ret_func'];
        if(isset($_SESSION['gtype']['reship'])) $_POST['reship'] = $_SESSION['gtype']['reship'];
        if(isset($_SESSION['gtype']['disabled'])) $_POST['disabled'] = $_SESSION['gtype']['disabled'];
        if(isset($_SESSION['gtype']['is_def'])) $_POST['is_def'] = $_SESSION['gtype']['is_def'];

        $this->end($objGtype->toSave($_POST), '保存成功');
    }
    
    function delete(){
        $this->begin('index.php?ctl=goods/gtype&act=index');
        $objType = $this->system->loadModel('goods/gtype');
        if(is_array($_REQUEST['type_id']))
            foreach($_REQUEST['type_id'] as $id){
                $objType->toRemove($id);
            }
        $objType->checkDefined();
        $this->end_only(true);
        echo '删除成功';
    }

    function recycle(){
        $objType = $this->system->loadModel('goods/gtype');
        $varGoto = 1;
        foreach($_REQUEST['type_id'] as $type_id){
            if(!$objType->checkDelete($type_id, $result)){
                if($result == 1){
                    echo __('通用商品类型为系统默认类型，不能删除');
                }
                if($result == 2){
                    echo __('类型下存在与之关联的商品，无法删除');
                }
                $varGoto = 0;
                break;
            }
        }
        if($varGoto){
            parent::recycle();
            $objType = $this->system->loadModel('goods/gtype');
            $objType->checkDefined();
        }
    }

    function fetchProtoTypes($link,$querystring=''){
        header('Content-Type: text/html;charset=utf-8');
        $network = $this->system->network();
        $network->read_timeout = 15;
        $network->_fp_timeout = 10;
        $cert = $this->system->getConf('certificate.id');
        $token = $this->system->getConf('certificate.token');
        $sc = md5('goostypefeed'.$cert.$token);
        if($network->fetch('http://feed.shopex.cn/goodstype/'.$link.'?certificate='.$cert.'&sc='.$sc.($querystring?'&'.$querystring:''))&&strstr($network->results,'shopexfeed')){
            echo $network->results;
        }else{
            echo '<div style="width:300px;height:80px;"><BR><BR>因网络连接或其它原因，暂时无法获取系统默认类型信息。<BR>请稍候再试...</div><div style="clear:both">';
        }
    }

    function getXml($id){
        $o = $this->system->loadModel('goods/gtype');
        $xml = $this->system->loadModel('utility/xml');
        $xmlpart = $xml->array2xml($o->getTypeObj($id,$name),'goodstype');
        $charset = $this->system->loadModel('utility/charset');
        download($name.'.typ',$xmlpart);
    }

    function saveSpec(){
        $this->begin('index.php?ctl=goods/gtype&act=index');
        $objType = $this->system->loadModel('goods/gtype');
        $this->end($objType->saveSpec($_POST['type_id'], $_POST['specs']), '保存成功');
    }

    function checkTypeNameExists(){
        $o = $this->system->loadModel('goods/gtype');
        if($o->getList('type_id',array('name'=>$_POST['gtypename']))){
            echo '<script>alert("本类型名在系统中已存在，请更名");</script>';
        }else{
            echo '<script>alert("本类型名在系统中不存在，可正常添加");</script>';
        }
    }

    function fetchSave(){
        $this->begin('index.php?ctl=goods/gtype&act=index',array(300001=>'index.php?ctl=goods/gtype&act=fetchProtoTypes&p[0]=gtype.php&p[1]=id='.$_POST['param_id']));
        $xml = $this->system->loadModel('utility/xml');
        $arr = $xml->xml2array(stripslashes($_POST['xml']));
        $gtype = $arr['goodstype'];
        $gtype['name'] = $_POST['gtypename'];
        $o = $this->system->loadModel('goods/gtype');
        $this->end($o->fetchSave($gtype), __('类型导入成功'));
    }

    function filterActions ($row){
        $return = $this->actions;
//        $return['editType'] = array('target'=>"{update:'main',fm:'addnew'}");
        $return['getXml'] = array('link'=>'index.php?ctl=goods/gtype&act=getXml&p[0]='.$row['type_id'],'target'=>'download');
        return $return;
    }

    function import(){
        $dataio = &$this->system->loadModel('system/dataio');
        $dataio->privateImport = true;
        $this->pagedata['importer'] = $dataio->importer($this->ioType);
        $this->pagedata['ctl'] = 'goods/gtype';
        $this->pagedata['optionsView'] = $this->importOptions;
        $this->page('finder/import.html');
    }

    function importer(){
        $this->begin('index.php?ctl=goods/gtype&act=index');
        $dataio = $this->system->loadModel('system/dataio');
        $gtype = $this->system->loadModel('goods/gtype');
        if(substr($_FILES['upload']['name'],-4)!='.typ'){
            trigger_error(__('文件格式有误'),E_USER_ERROR);
            exit;
        }

        $content = file_get_contents($_FILES['upload']['tmp_name']);
        if(substr($content,0,3)=="\xEF\xBB\xBF")
            $content = substr($content,3);
        $data = $dataio->import_rows($_POST['type'],$content);
        $imported = false;
        foreach($data as $type){
            if($type['name']){
                $type['name'] = $type['name'].time();
                $gtype->fetchSave($type);
                $imported = true;
            }
        }
        if($imported){
            $this->end(true,__('类型数据导入成功,请修改自动生成的类型名'));
        }else{
            trigger_error(__('由于格式错误或上传问题，类型数据无法导入'),E_USER_ERROR);
        }
    }
}
