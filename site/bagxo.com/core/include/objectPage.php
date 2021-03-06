<?php
/**
 * objectPage
 *
 * @uses adminPage
 * @package
 * @version $Id$
 * @copyright 2003-2007 ShopEx
 * @author Wanglei <flaboy@shopex.cn>
 * @license Commercial
 */
class objectPage extends adminPage{

    var $editMode = false;
    var $canRemove = true;
    var $batchEdit = false;
    var $deleteAble = true;
    var $allowImport = false;
    var $allowExport = true;
    var $noRecycle = false;

    function objectPage(){
        parent::adminPage();
        $this->model = &$this->system->loadModel($this->object);
        $frontend = &$this->system->loadModel('system/frontend');
        $frontend->register_function('toolset',array(&$this,'_getTools'));
        $this->objectName = substr(get_class($this),4);
        $this->path[] = array('text'=>$this->name.'管理','url'=>'index.php?ctl='.$_GET['ctl'].'&act=index');
    }

    function batchEdit(){
        $this->model->setFinderCols(get_object_vars($this));
        $this->pagedata['editInfo'] = $this->model->batchEditCols($_POST);
        $this->pagedata['filter'] = htmlspecialchars(serialize($_POST));
        $this->pagedata['finder'] = stripslashes($_GET['finder']);
        $this->setView('finder/batchEdit.html');
        $this->output();
    }

    function _views(){
        return array();
    }


    /*--- 标签管理 begin-----*/

    function renTag(){
        if($_POST['tag_id']){
            $tag = $this->system->loadModel('system/tag');
            $tag->rename($_POST['tag_id'],$_POST['name']);
        }
        header("Location: index.php?ctl={$this->controller}&act=tagmgr&_ajax=1&_wg=".$this->workground);
    }

    function delTag(){
        if($_POST['tag_id']){
            $tag = $this->system->loadModel('system/tag');
            $tag->remove($_POST['tag_id']);
        }
        header("Location: index.php?ctl={$this->controller}&act=tagmgr&_ajax=1&_wg=".$this->workground);
    }

    function tagmgr(){
        $this->path[] = array('text'=>$this->name.'标签管理');
        $this->pagedata['controller'] = $this->controller;
        $this->pagedata['tags'] = $this->model->tagList(1);
        $this->page('system/tags/list.html');
    }

    function newTag(){
        $this->begin('index.php?ctl='.$this->controller.'&act=tagmgr');
        $this->end($this->model->newTag($_POST['tag_name'],'标签添加成功'));
    }

    /**
     * setTag
     * 设置标签
     *
     * @access public
     * @return void
     */
    function setTag(){
        $tag = space_split(stripslashes($_POST['_SET_TAGS_']));
        unset($_POST['_SET_TAGS_']);
        if($this->model->setTag($_POST,$tag)){
            echo '标签已设置';
        }
    }

    /*--- 标签管理 end-----*/

    function saveBatchEdit(){
        $filter = unserialize(stripslashes($_POST['filter']));
        $set = array();
        foreach($_POST['enable'] as $k=>$v){
            $set[$k]= $this->model->columnValue($k,$_POST['set'][$k]);
        }
        ini_set('track_errors','1');
        restore_error_handler();
        if(@$this->model->update($set,$filter)){
            echo 'ok';
        }else{
            echo $GLOBALS['php_errormsg'];
        }
    }

    function _actions(){
        if($this->actions){
            return $params['tools'] = array(
                'class'=>'span-3'
            );
        }else{
            return false;
        }
    }

    function _getTools($params,&$smarty){

        $row = &$params['item'];
        $ToolsHtml = '';
        if(is_callable(array($this,'filterActions'))){
            $ActionsFilter = $this->filterActions($row);
        }else{
            $ActionsFilter = $this->actions;
        }
        $this->controller = $_GET['ctl'];
        foreach($this->actions as $method=>$label){
            if(isset($ActionsFilter[$method])){
                if(is_array($ActionsFilter[$method])){
                    if($ActionsFilter[$method]['html']){
                        $ToolsHtml .= $ActionsFilter[$method]['html'];
                        continue;
                    }
                    $link = $ActionsFilter[$method]['link']
                        ?$ActionsFilter[$method]['link']
                        :'index.php?ctl='.$this->controller.'&act='.$method.'&p[0]='.$row[$this->model->idColumn];
                    $alterlabel = $ActionsFilter[$method]['label']?$ActionsFilter[$method]['label']:$label;

                    unset($ActionsFilter[$method]['label']);
                    unset($ActionsFilter[$method]['link']);
                    $sAttrs = '';
                    foreach($ActionsFilter[$method] as $attr=>$value) {
                        $sAttrs .= $attr.'='.'"'.$value.'" ';
                    }

                    $ToolsHtml .= '<a class="sysiconBtnNoIcon" href="'.$link.'" '.$sAttrs.'>'.$alterlabel.'</a>';
                }else{
                    if($ActionsFilter[$method]=='_none_'){
                        $ToolsHtml .= '';
                    }
                    elseif($ActionsFilter[$method]==''){
                        $ToolsHtml .= '<a class="sysiconBtnNoIcon" href="index.php?ctl='.$this->controller.'&act='.$method.'&p[0]='.$row[$this->model->idColumn].'">'.$label.'</a>';
                    }else{
                        $ToolsHtml .= '<a class="sysiconBtnNoIcon" href="index.php?ctl='.$this->controller.'&act='.$method.'&p[0]='.$row[$this->model->idColumn].'">'.$ActionsFilter[$method].'</a>';
                    }
                }
            }else{
                $ToolsHtml .= '<span class="sysiconBtnNoIconDisabled">'.$label.'</span>';
            }
        }
        return $ToolsHtml.'&nbsp;';
    }

    function _finder_common($options=null){
        $o = &$this->model;
        if($options['disabledMark'])  $o->disabledMark = $options['disabledMark'];
        $o->setFinderCols(get_object_vars($this));
        $this->pagedata['filter'] = &$o->getFilter(null); //TODO:需要调整
        $this->pagedata['allowImport'] = $this->allowImport;
        $this->pagedata['allowExport'] = $this->allowExport;
        $cols = $this->op->get('view.'.$this->object);
        if(!$cols) $cols = $o->defaultCols;
        $params['order'] = $o->defaultOrder;

        if(!$params['plimit'])$params['plimit'] = 20;

        if(method_exists($this,'detail')){
            $params['rowselect'] = true;
            $params['infoUrl'] = 'index.php?ctl='.$this->controller.'&act=detail&p[0]=';
        }
        if($this->batchEdit){
            $params['batchEdit'] = true;
        }

        $views = $this->_views();
        $options['views'] = array_keys($views);
        $current_view_filter = $views[$options['views'][$_GET['view']+0]];
        $options['params'] = array_merge((array)$options['params'],(array)$current_view_filter);
        $params['filterView'] = $current_view_filter?'':$this->filterView;

        if($options['withTools']){
            $params['tools'] = $this->_actions();
        }

        //todo：自定义列
        $this->pagedata['items'] = &$o->getFinder($cols,$options['params'],0,$params['plimit'],$count,$params['order'],$params['disabledCols'],$options['editMode']);

        if($this->pagedata['items']===false){
            $db = &$this->system->database();
            $this->pagedata['sql'] = str_replace(","," ,",$db->_instance->sql);
            $this->pagedata['sql_error'] = $db->errorInfo();
            $this->page('finder/list-error.html');
        }

        $params['_name'] = substr(md5($_SERVER['QUERY_STRING']),0,6);
        $params['var'] = 'window.finder[\''.$params['_name'].'\']';
        if($colOrder = $this->op->get('listColOrder.'.$this->object)){
            array_multisort($this->pagedata['items']['allCols'],SORT_NUMERIC ,explode(',',$colOrder));
        }

        $dataio = &$this->system->loadModel('system/dataio');
        $this->pagedata['exporter'] = $dataio->exporter($this->ioType);
        $pager = array(
            'current'=> 1,
            'total'=> ceil($count/$params['plimit']),
            'link'=> 'javascript:'.$params['var'].'.jumpTo.bind('.$params['var'].')(_PPP_)',
            'token'=> '_PPP_'
        );

        $params['id'] = $o->idColumn;
        $params['hasTag'] = $o->hasTag;

        if($o->hasTag){
            $params['tagList'] = &$o->tagList();
        }

        $params['deleteAble'] = $this->deleteAble;
        $params['noRecycle'] = $this->noRecycle;
        $params['count'] = $count;
        if(!$params['select'])$params['select'] = 'multi';
        $params['actionView'] = $this->actionView;
        $params['listView'] = 'finder/list.html';
        $params['pager'] = &$pager;
        $params['searchOptions'] = $o->searchOptions();
        $params['currentSearchKey'] = key($params['searchOptions']);
        $params['orderBy'] = implode(' ', $o->defaultOrder);
        $params['orderType'] = '';
//        $params['orderBy'] = $o->defaultOrder[0];
//        $params['orderType'] = $o->defaultOrder[1];
        $params['controller'] = $this->controller;

        if(is_array($options)){
            $params = array_merge($params,$options);
        }
        if($params['editMode']){
            $params['rowselect'] = false;
        }elseif($this->editMode){
            $params['editMode'] = false;
        }

        $this->pagedata['_finder'] = $params;
    }

    function editMode($options=null){
        $options['editMode']=true;
        $options['withTools'] = true;
        $options['compact'] = $_GET['compact'];
        $this->_finder_common($options);
        if($_GET['compact']){
            $this->setView('finder/compact.html');
            $this->output();
        }else{
            $this->page('finder/common.html');
        }
    }

    function index($options=null){
        $options['withTools'] = true;
        $options['compact'] = $_GET['compact'];
        $this->_finder_common($options);
        if($_GET['compact']){
            $this->setView('finder/compact.html');
            $this->output();
        }else{
            $this->page('finder/common.html');
        }
    }

    function recycleIndex($options=null){
        $this->path[] = array('text'=>$this->name.'回收站');
        $options['withTools'] = false;
        $options['compact'] = $_GET['compact'];
        $options['disabledMark'] = 'recycle';
        $this->_finder_common($options);
        if($_GET['compact']){
            $this->setView('finder/recycleCompact.html');
            $this->output();
        }else{
            $this->page('finder/recycleCommon.html');
        }
    }

    function select(){
        /* <{finder disabledCols="_tools_" var="_select_" struct="finder/browser.html" params=$options select=$smarty.get.s_type}> */
        $params = unserialize(stripslashes($_POST['data']));

        $this->_finder_common(array('params'=>$params,'select'=>'checkbox'));
        $this->pagedata['options'] = $params;
        $this->pagedata['_finder']['rowselect'] = false;
        $this->setView('finder/browser.html');
        $this->output();
    }

    function finder($type,$view,$cols,$finder_id,$limit){
        set_error_handler(array(&$this,'finder_error_handler'));
        $finder_id = stripslashes($finder_id);

        $finder = &$this->model;
        $finder->setFinderCols(get_object_vars($this));
        $page = $_GET['page']?$_GET['page']:1;
        $cols = $this->op->get('view.'.$this->object);
        if(!$cols) $cols = $this->model->defaultCols;

        $orderBy = array($_GET['_finder']['orderBy'],$_GET['_finder']['orderType']);
        if($_GET['_finder']['disabledMark']=='recycle'){
            $finder->disabledMark = 'recycle';
        }
        $this->pagedata['items'] = $finder->getFinder($cols,$_REQUEST,($page-1)*$limit,$limit,$count,$orderBy,null,$_GET['_finder']['editMode']);
        $totalPages = ceil($count/$limit);
        if($page>$totalPages){
            $page = 1;
            $this->pagedata['items'] = $finder->getFinder($cols,$_REQUEST,($page-1)*$limit,$limit,$count,$orderBy,null,$_GET['_finder']['editMode']);
        }

        //ever 2009-1-8
        foreach($this->pagedata['items']['cols'] as $col => $row){
            if(isset($row['vtype'])){
                $this->pagedata['items']['cols'][$col]['type'] = $row['vtype'];
            }
        }

        if($colOrder = $this->op->get('listColOrder.'.$this->object)){
            array_multisort($this->pagedata['items']['allCols'],SORT_NUMERIC ,explode(',',$colOrder));
        }

        $this->pagedata['_finder'] = $_GET['_finder'];

        if(!$_GET['_finder']['without_rowselect'] && method_exists($this,'detail')){
            $this->pagedata['_finder']['rowselect'] = true;
        }

        if($_GET['_finder']['withTools']){
            $this->pagedata['_finder']['tools'] = $this->_actions();
        }

        if($finder->hasTag){
            $this->pagedata['_finder']['tagList'] = &$finder->tagList();
        }

        $this->pagedata['_finder']['pager']=array(
            'current'=>$page,
            'total'=>$totalPages,
            'link'=>'javascript:'.$finder_id.'.jumpTo.bind('.$finder_id.')(_PPP_)',
            'token'=>'_PPP_'
        );

        $this->pagedata['_finder']['listOnly'] = true;
        $this->pagedata['_finder']['view'] = $view;
        $this->pagedata['_finder']['var'] = $finder_id;
        $this->pagedata['_finder']['count'] = $count;
        $this->pagedata['_finder']['order'] = $orderBy;
        if($_GET['notools'])unset($this->pagedata['_finder']['tools']);
        $this->setView('finder/list.html');
        $this->output();
    }

    function finder_error_handler($errno, $errstr, $errfile, $errline){
        if($errno ==( $errno &  E_ERROR | E_USER_ERROR)){
            exit();
        }
        return true;
    }

    function fresult($view){
        $o = &$this->model;
        $this->pagedata['_finder'] = $_POST['_opts'];
        $this->pagedata['_finder']['id']=$o->idColumn;
        $this->pagedata['_finder']['listOnly'] = true;
        $cols =$this->pagedata['_finder']['cols']?$this->pagedata['_finder']['cols']:$o->textColumn;
        if(count($_POST[$o->idColumn])==0){
            $_POST = -1;
        }
        unset($_POST['_opts']);
        $this->pagedata['items'] = &$o->getFinder($cols,$_POST,0,-1,$count);
        $this->setView('finder/input.html');
        $this->output();
    }

    function prefilter($type){
        include_once('shopObject.php');
        $objects = shopObject::objects();
        $this->pagedata['type'] = $type;
        $this->pagedata['filter'] = stripslashes($_POST['data']);
        $this->pagedata['_finder']['select']='none';
        $this->pagedata['options']=$_POST;
        $this->setView('finder/pvfilter.html');
        $this->output();
    }

    function import(){
        $this->pagedata['options'] = $this->importOptions;
        $dataio = &$this->system->loadModel('system/dataio');
        $this->pagedata['importer'] = $dataio->importer($this->ioType);
        $this->pagedata['optionsView'] = $this->importOptions;
        $this->page('finder/import.html');
    }

    function export($io='csv'){
        include_once('shopObject.php');
        $allCols = $this->model->getColumns();
        $step = 20;
        $offset=0;
        $dataio = $this->system->loadModel('system/dataio');
        $cols = $this->op->get('view.'.$this->object);
        if(!$cols){
             $cols = $this->model->defaultCols;
        }elseif($cols = explode(',',$cols)){
            $cols = array_flip($cols);
            unset($cols['_tag_']);
            $cols = implode(',',array_flip($cols));
        }
        $this->model->appendCols=null;
        foreach(explode(',',$cols) as $col){
            if(!isset($disabledCols[$col])){
                if(isset($allCols[$col])){
                    $allCols[$col]['used'] = true;
                    $colArray[$col] = &$allCols[$col];
                    if(isset($allCols[$col]['sql'])){
                        $sql[] = $allCols[$col]['sql'].' as '.$col;
                    }elseif($col=='_tag_'){
                        $sql[] = $this->idColumn.' as _tag_';
                    }else{
                        $sql[] = $col;
                    }
                }else{
                    trigger_error('Undefined column "'.$col.'"',E_USER_WARNING);
                }
            }
        }
        $list = &$this->model->export($this->model->getList(implode(',',$sql),$_POST,$offset,$step,$count));
        $allCols = $this->model->getColumns();
        $headCols = array();
        foreach($list[0] as $k=>$v){
            $headCols[] = $allCols[$k]['label'].'('.$k.')';
        }

        $dataio->export_begin($io,$headCols,$this->objectName,$count);
        $dataio->export_rows($io,$list);
        while($count>($offset+=$step)){
            $list = &$this->model->export($this->model->getList(implode(',',$sql),$_POST,$offset,$step,$count));
            $dataio->export_rows($io,$list);
        }
        $dataio->export_finish($io);
    }
    function delete() {
        if($this->model->delete($_POST)){
            echo '选定记录已删除成功!';
        }else{
            echo '选定记录无法删除!';
        }
    }

    function recycle() {
        if($this->model->recycle($_POST)){
            echo '选定记录删入回收站';
        }else{
            echo '选定记录无法删入回收站';
        }
    }
    function active() {
        if($this->model->active($_POST)){
            echo '选定记录已从回收站恢复';
        }else{
            echo '选定记录无法从回收站恢复';
        }
    }

    function saveValue(){
        foreach($_POST['items'] as $id=>$item){
            foreach($item as $k=>$v){
                $item[$k] = $this->model->columnValue($k,$v);
            }
            $this->model->update($item,array($this->model->idColumn=>$id));
        }
    }

    function saveColSet(){
        $this->op->set('view.'.$this->object,implode(',',$_POST['cols']));

        //设置排序
        $orders = array_flip($_POST['orders']);
        $len = count($orders);
        $cols = $this->model->getColumns();
        $o = array();
        foreach($cols as $k=>$col){
            $o[]=(isset($orders[$k])?$orders[$k]:$len)+1;
        }
        $this->op->set('listColOrder.'.$this->object,implode(',',$o));
    }

    function save(){
        $this->begin('index.php?ctl='.$_GET['ctl'].'&act=index');
        if($_POST[$this->model->idColumn]){
            $this->end($this->model->update($_POST,array($this->model->idColumn=>$_POST[$this->model->idColumn])));
        }else{
            $this->end($this->model->insert($_POST));
        }
    }

}
?>
