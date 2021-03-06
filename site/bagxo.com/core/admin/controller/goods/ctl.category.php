<?php
include_once('objectPage.php');
class ctl_category extends objectPage{

    var $name='商品分类';
    var $workground = 'goods';
    var $object = 'goods/productCat';

    function addNew($id=0){
        $this->path[] = array('text'=>'商品分类新增');
        $objCat = $this->system->loadModel('goods/productCat');
        $aCat = $objCat->get_cat_list(true);
        $aCatNull[] = array('cat_id'=>0,'cat_name'=>'----无----','step'=>1);
        if(empty($aCat)){
            $aCat = $aCatNull;
        }else{
            $aCat = array_merge($aCatNull, $aCat);
        }
        $this->pagedata['catList'] = $aCat;
        $this->pagedata['gtypes'] = $objCat->getTypeList();
        $oGtype = &$this->system->loadModel('goods/gtype');
        $this->pagedata['gtype']['status'] = $oGtype->checkDefined();
        if($id){
            $aCat = $objCat->getFieldById($id);
            $this->pagedata['cat']['parent_id'] = $aCat['cat_id'];
            $this->pagedata['cat']['type_id'] = $aCat['type_id'];
        }else{
            $aTmp = $oGtype->getDefault();
            $this->pagedata['cat']['type_id'] = $aTmp[0]['type_id'];
        }
        $this->pagedata['cat']['p_order'] = 0;
        
        $this->page('product/category/info.html');
    }

    function doAdd(){
        $objCat = $this->system->loadModel('goods/productCat');
        $objCat->addNew($_POST['cat']);
        $this->splash('success','index.php?ctl=goods/category&act=index','保存成功');
    }
    
    function view($catid){
        $objCat = $this->system->loadModel('goods/productCat');
        if($views = $objCat->getTabs($catid)){
            $this->pagedata['views'] = $views;
        }
        $this->pagedata['params'] = array('cat_id'=>$catid);
        $this->pagedata['catid'] = $catid;
        $this->page('product/category/view.html');
    }

    function edit($catid){
        $this->path[] = array('text'=>'商品分类编辑');
        $objCat = $this->system->loadModel('goods/productCat');
        $aCat = $objCat->getFieldById($catid);
        $aCat['addon'] = unserialize($aCat['addon']);
        $aCat['addon']['meta']['keywords'] = stripslashes($aCat['addon']['meta']['keywords']);
        $aCat['addon']['meta']['description'] = stripslashes($aCat['addon']['meta']['description']);
        $this->pagedata['cat'] = $aCat;

        $aCat = $objCat->get_cat_list();
        $aCatNull[] = array('cat_id'=>0,'cat_name'=>'----无----','step'=>1);
        $aCat = array_merge($aCatNull, $aCat);
        $this->pagedata['catList'] = $aCat;
        $this->pagedata['gtypes'] = $objCat->getTypeList();
        $oGtype = &$this->system->loadModel('goods/gtype');
        $this->pagedata['gtype']['status'] = $oGtype->checkDefined();

        $this->page('product/category/info.html');
    }

    function addView($catid){
        $this->pagedata['params'] = array('cat_id'=>$catid);
        $this->pagedata['item'] = array('label'=>'New View '.mydate('H:i:s'));
        $this->setView('product/category/view_row.html');
        $this->output();
    }

    function saveView($catid){
        foreach($_POST['view'] as $k=>$v){
            if($v) $views[] = array('label'=>$v,'filter'=>$_POST['filter'][$k]);
        }
        $objCat = $this->system->loadModel('goods/productCat');
        $this->splash($objCat->setTabs($catid,$views),'index.php?ctl=goods/category&act=index');
    }

    function filter($catid){
        $this->pagedata['catid'] = $catid;
        $this->pagedata['params'] = array('cat_id'=>$catid);
        $this->page('product/category/filter.html');
    }

    function index(){
        $objCat = $this->system->loadModel('goods/productCat');
        if($objCat->checkTreeSize()){
            $this->pagedata['hidenplus']=true;
        }
        $tree=$objCat->get_cat_list();
        $this->pagedata['tree_number']=count($tree);
        $this->pagedata['tree']=$tree;
        $depath=array_fill(0,$objCat->get_cat_depth(),'1');
        $this->pagedata['depath']=$depath;
        $this->page('product/category/map.html');
    }
    
    function getChildCat($pid){
        $pid = $_POST['pid'];
        $objType=$this->system->loadModel('goods/productCat');
        $this->pagedata['tree']=$objType->getTreeList($pid, false);
        $this->setView('product/category/rows.html');
        $this->output();
    }

    function toRemove($id){
        $objType=$this->system->loadModel('goods/productCat');
        if($objType->toRemove($id)){
            $this->splash('success','index.php?ctl=goods/category&act=index','分类删除成功');
        }else{
            $this->splash('failed','index.php?ctl=goods/category&act=index','分类删除失败');
        }
    }

    function update(){
        $objType=$this->system->loadModel('goods/productCat');
        if($objType->updateOrder($_POST['p_order'])){
            $this->splash('success','index.php?ctl=goods/category&act=index','更新成功');
        }else{
            $this->splash('failed','index.php?ctl=goods/category&act=index','更新失败');
        }
    }
}
?>