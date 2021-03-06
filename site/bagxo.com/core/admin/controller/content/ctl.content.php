<?php
class ctl_content extends adminPage{

    var $workground ='site';

    function definedSet(){
        $this->setView('content/definedSets.html');
        $this->output();
    }
    function definedList(){
        $o=$this->system->loadModel('content/page');
        $this->system->output(json_encode($o->definedList()));
    }
    function addDefinedPage(){
        $this->setView('content/addDefined.html');
        $this->output();
    }


    function urllinkIndex($ident){
        $sitemap = $this->system->loadModel('content/sitemap');
        $result=$sitemap->getResult($ident);
        $this->path[]=array('text'=>'新增链接:'.$result[0]['title']);
        $this->pagedata['urladdress']=$result[0]['action'];
        $this->pagedata['url_set']=$result[0]['item_id'];
        $this->pagedata['ident'] = $ident;
        $this->page('content/page_url.html');
    }

    function urllinkSave(){
        
        if($_POST['ident'] && $_POST['url_address']){
            $sitemap = &$this->system->loadModel('content/sitemap');
            $setTitle = $sitemap->setAction($_POST['ident'],array('action'=>$_POST['url_address'],'item_id'=>$_POST['url_set']));
            $this->splash('success','index.php?ctl=content/sitemaps',__('页面成功保存'));
        }else{
            $this->splash('failed','index.php?ctl=content/sitemaps',__('页面保存失败'));
        }

    }

    function footEdit(){
        $this->path[] = array('text'=>'网页底部信息');
        $this->pagedata['footEdit'] = stripslashes($this->system->getConf('system.foot_edit'));
        $this->page('system/tools/footEdit.html');

    }
    function saveFoot(){
        if($this->system->setConf('system.foot_edit',stripslashes($_POST['footEdit']))){
            $this->splash('success','index.php?ctl=content/content&act=footEdit',__('保存成功'));
        }
    }

    function addDefined(){
        $o=$this->system->loadModel('content/page');
        $o->addDefined($this->in);
    }
    function delDefined($strId){
        $o=$this->system->loadModel('content/page');
        if($o->delDefined($strId,$msg)){
            $this->message = array('string'=>__('删除成功！'),'type'=>MSG_OK);
        }else{
            $this->message = array('string'=>$msg,'type'=>MSG_ERROR);
        }
        $this->ajax();
    }
    function definedDetailPage($id){
        $this->path[] = array('text'=>'页面内容编辑');
        $o=$this->system->loadModel('content/page');
        $this->pagedata['data']=$o->htmEdit($id);
        $this->page('content/definedDetail.html');
    }
    function editDefined(){
        $o=$this->system->loadModel('content/page');
        if (!$o->editDefined($this->in)) {
            $this->splash('failed','index.php?ctl=content/content&act=definedDetailPage&p[0]='.$this->in['page_name'],500,$msg);
        }
        $this->splash('success','index.php?ctl=content/content&act=definedDetailPage&p[0]='.$this->in['page_name'],500,__('编辑成功!'));
    }

    function articleList(){
        $oArticle = $this->system->loadModel('content/article');
        $aArticle = $oArticle->getArticleList($this->in['title'],$this->in['cat_id']);
        $this->system->output(json_encode($aArticle));
    }
    function addArticle(){
        $oArticle = $this->system->loadModel('content/article');
        if(!$oArticle->addArticle($this->in,$msg)){
            $this->splash('failed','index.php?ctl=content/content&act=main',$msg);
        }
        $this->splash('success','index.php?ctl=content/content&act=main',__('文章添加成功'));
    }
    function save(){
        $oArticle = $this->system->loadModel("content/article");
        if($oArticle->saveArticle($this->in)){
            $this->splash('success','index.php?ctl=content/content&act=main',__('修改成功'));
        }else{
            $this->splash('failed','iindex.php?ctl=content/content&act=main',__('修改失败'));
        }

    }
    function delAttachment($nConId){
        return true;
    }
//文章分类
    function type(){
        $this->path[] = array('text'=>'文章分类');
        $oArticle = $this->system->loadModel('content/article');
        $this->pagedata['type'] = $oArticle->getTypeList();
    
        $this->page('content/type_list.html');
    }
    function typeList(){
        $oArticle = $this->system->loadModel('content/article');
        $aType = $oArticle->getTypeList($_POST['start'],$_POST['limit']);
        $this->system->output(json_encode($aType));
    }

    function detailType($nTid){
        $this->pagedata['cat_id'] = $nTid;
        $this->setView('content/type_items.html');
        $this->output();
    }
    function showNewType(){
        $this->setView('content/type_new.html');
        $this->output();
    }
    function addType(){
        $oArticle = $this->system->loadModel('content/article');
        if($oArticle->addType($this->in)) {
            $this->splash('success','index.php?ctl=content/content&act=type',__('添加成功'));
        }else{
            $this->splash('failed','iindex.php?ctl=content/content&act=type',__('添加失败'));
        }

    }


    function indexOfgoodsCat($nId){
        $sitemap = $this->system->loadModel('content/sitemap');
        $result=$sitemap->getNowNod($nId);
        $this->pagedata['filter']=$result[0]['action'];;
        $this->pagedata['id'] = $nId;
        $this->page('content/goodscat.html');
    }

    function saveGoodsCat(){
        $searchtools = $this->system->loadModel('goods/search');
        //$path =array();
        //parse_str($_POST['filter'],$filter);
        //$filter    = $searchtools->encode($filter);
        
        if($_POST['id']){
            $sitemap = &$this->system->loadModel('content/sitemap');
            //$searchtools = &$this->system->loadModel('goods/search');
            //parse_str($_POST['filter'],$filter);
            $setTitle = $sitemap->setAction($_POST['id'],$_POST['filter']);
        
            $this->splash('success','index.php?ctl=content/sitemaps',__('页面成功保存'));
        }else{
            $this->splash('failed','index.php?ctl=content/sitemaps',__('页面保存失败'));
        }


    }

    function editType($nTid){
        $this->path[] = array('text'=>'文章分类编辑');
        $oArticle = $this->system->loadModel('content/article');
        $this->pagedata['type'] = $oArticle->getTypeById($nTid);
        $this->page('content/type_edit.html');
    }
    function saveType(){
        $oArticle = $this->system->loadModel("content/article");
        if($oArticle->saveType($this->in)){
            $this->splash('success','index.php?ctl=content/content&act=type',__('修改成功'));
        }else{
            $this->splash('failed','index.php?ctl=content/content&act=type',__('修改失败'));
        }                                                                    
    }
    function delType($sTypeId){
        $oArticle = $this->system->loadModel('content/article');
        if($oArticle->delType($sTypeId)){
            $this->splash('success','index.php?ctl=content/content&act=type',__('删除成功'));
        }else{
            $this->splash('failed','index.php?ctl=content/content&act=type',__('删除失败'));
        }
    }

}
?>