<?php
class mdl_template extends modelFactory{

    function getList(){

        $handle = opendir(THEME_DIR);
        while(false!==($item=readdir($handle))){
            if(substr($item,0,1)!='.' && is_dir(THEME_DIR.'/'.$item)){
                $t[$item]=1;
            }
        }
        closedir($handle);
        foreach($this->db->select('select * from sdb_themes where theme in("'.implode('","',array_keys($t)).'")') as $theme){
            $themes[$theme['theme']] = $theme;
            unset($t[$theme['theme']]);
        }
        
        $objWg = $this->system->loadModel('content/widgets');
        foreach($objWg->getLibs() as $w){
            $widgets[$w['name']] = $w;
            $widgets[$w['name']]['installed'] = true;
        }
        //未安装
        foreach(array_keys($t) as $item){
            if(file_exists(THEME_DIR.'/'.$item.'/theme.xml')){
                $themes[$item] = $this->initTheme($item);
            }
        }

        foreach($this->db->select('SELECT base_file,widgets_type FROM sdb_widgets_set s') as $widset){
            $tname = substr($widset['base_file'],($p=strpos($widset['base_file'],':')+1),strpos($widset['base_file'],'/')-$p);
            if($themes[$tname]){
                $themes[$tname]['widgets'][$widset['widgets_type']] = $widgets[$widset['widgets_type']]?$widgets[$widset['widgets_type']]:array('label'=>$widset['widgets_type'],'name'=>$widset['widgets_type'],'installed'=>false);
            }
        }
        ksort($themes);

        return $themes;

    }

    function remove($theme){
        return $this->__removeDir(THEME_DIR.'/'.$theme);
    }

    function remove_directory($dir){
        if(substr($dir, -1, 1)=="/"){
            $dir=substr($dir, 0, strlen($dir)-1);
        }
        if ($handle=opendir("$dir")){
            while(false!==($item=readdir($handle))){
                if($item!="." && $item!=".."){
                    if (is_dir("$dir/$item")){
                        remove_directory("$dir/$item");
                    }else{
                        unlink("$dir/$item");
                    }
                }
            }
            closedir($handle);
            rmdir($dir);
        }
    }
    function backTemplate($p1,$p2){

        echo '备份成功';

        

    }
    function previewImg($theme){
        $this->system->sfile(THEME_DIR.'/'.$theme.'/preview.jpg','images/theme_dft.jpg',true);
    }

    function allowUpload(&$message){
        if(!function_exists("gzinflate")){
            $message = 'gzip';
            return $message;
        }
        if(!is_writable(THEME_DIR)){
            $message = 'writeable';
            return $message;
        }
        return true;
    }
    function makeXml($theme,$name){
        //THEME_DIR.'/'.$theme

        if(file_put_contents(THEME_DIR.'/'.$theme.'/'.$name.'.xml',$this->__makeConfigFile($theme))){
            return true;
        }else{
            return false;
        }
        //print_r($this->__makeConfigFile($theme));


    }
    function getThemes($theme){
        
        $list=array("theme.xml","theme-bak.xml");
        $views = array();
        foreach($list as $v){
            if(is_file(THEME_DIR.'/'.$theme.'/'.$v)){
                $views[]=$v;
            }
        }
        
        return $views;
        


    }
    function getViews($theme){
        

        if ($handle=opendir(THEME_DIR.'/'.$theme)){
            $views = array();
            while(false!==($file=readdir($handle))){
                if ($file{0}!=='.' && $file{0}!=='_' && is_file(THEME_DIR.'/'.$theme.'/'.$file) && (($t=strtolower(strstr($file,'.')))=='.html' || $t=='.htm')){
                    $views[] = $file;
                }
            }
            closedir($handle);
            return $views;
        }else{
            return false;
        }
    }

    function upload($file,&$msg){
        if(!$this->allowUpload($msg)) return false;
        $tar = $this->system->loadModel('utility/tar');
        $handle = fopen ($file['tmp_name'], "r");
        while(!feof($handle)){
            $contents= fread($handle, 1000);
            if(preg_match('/\<id\>(.*?)\<\/id\>/',$contents,$tar_name)){
                break;
            }
        }
        $filename=$tar_name[1]?$tar_name[1]:time();
        if(is_dir(THEME_DIR.'/'.trim($filename))){
           $filename=time();
        }
        $sDir=$this->__buildDir(str_replace('\\','/',THEME_DIR.'/'.trim($filename)));
        if($tar->openTAR($file['tmp_name'])){
            if($tar->containsFile('theme.xml')) {
                
                foreach($tar->files as $id => $file) {
                    $fpath = $sDir.$file['name'];
                    if(!is_dir(dirname($fpath))){
                        if(mkdir_p(dirname($fpath))){
                            file_put_contents($fpath,$tar->getContents($file));
                        }else{
                            $msg = '权限不允许';
                            return false;
                        }
                    }else{
                        file_put_contents($fpath,$tar->getContents($file));
                    }
                }
                $tar->closeTAR();
                if(!$config=$this->initTheme(basename($sDir),'','upload')){
                    $this->__removeDir($sDir);
                    $msg='shopEx模板包创建失败';
                    return false;
                }                
                return $config;
            }else{
                $msg = '不是标准的shopEx模板包';
                return false;
            }
        }else{
            $msg = '模板包已损坏，不是标准的shopEx模板包'.$file['tmp_name'];
            return false;
        }
    }

    function getBorderFromThemes($file){
        /*
        $wights_border=Array();
        $path=THEME_DIR.'/'.substr($file,5,strpos($file,'/')-5).'/borders/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if(substr($file,0,1)!='.'){
                $wights_border['borders/'.$file]=file_get_contents($path.$file);
            }
        }
        */
        //closedir($handle);
        $wights_border=Array();
        $path=THEME_DIR.'/'.$file;
        $workdir = getcwd();
         chdir($path);
         $xml = $this->system->loadModel('utility/xml');
         $content=file_get_contents('info.xml');
         $config = $xml->xml2arrayValues($content);
            
         foreach($config['theme']['borders']['set'] as $k=>$v){
           $wights_border[$v['attr']['tpl']]=file_get_contents($v['attr']['tpl']);
         }
         chdir($workdir);

        return $wights_border;
    }
    function resetTheme($theme){

        $path=THEME_DIR.'/'.$theme;
        $workdir = getcwd();
        chdir($path);
        if(is_file('info.xml')){
            $xml = $this->system->loadModel('utility/xml');
            $config = $xml->xml2arrayValues(file_get_contents('info.xml'));
            $aTheme=array(
                'theme'=>$theme,
                'name'=>$config['theme']['name']['value'],
                'id'=>$config['theme']['id']['value'],
                'version'=>$config['theme']['version']['value'],
                'info'=>$config['theme']['info']['value'],
                'author'=>$config['theme']['author']['value'],
                'site'=>$config['theme']['site']['value'],
                'update_url'=>$config['theme']['update_url']['value'],
                'config'=>array(
                    'config'=>$this->__changeXMLArray($config['theme']['config']['set']),
                    'borders'=>$this->__changeXMLArray($config['theme']['borders']['set']),
                    'views'=>$this->__changeXMLArray($config['theme']['views']['view'])
                )
            );
            /*
                $update=array(
                        'config'=>$this->__changeXMLArray($config['theme']['config']['set']),
                        'borders'=>$this->__changeXMLArray($config['theme']['borders']['set']),
                        'views'=>$this->__changeXMLArray($config['theme']['views']['view'])
                    );

            */
            chdir($workdir);
            if(!$this->updateThemes($aTheme)){
                return false;
            }else{
                return true;
            }
       }
    }
    function initTheme($theme,$replaceWg=false,$upload='',$loadxml=''){
        if(empty($loadxml)){
            $loadxml='theme.xml';
        }    
        $configxml='info.xml';
        $this->separatXml($theme);
        $sDir=THEME_DIR.'/'.$theme.'/';
        $xml = $this->system->loadModel('utility/xml');
        $wightes_info = $xml->xml2arrayValues(file_get_contents($sDir.$loadxml));
        if(is_file($sDir.$configxml)){
            $config = $xml->xml2arrayValues(file_get_contents($sDir.$configxml));
        }else{
           $config=$wightes_info;
        }
        if($upload=="upload" && $config['theme']['id']['value']){
            $config['theme']['id']['value']=preg_replace('@[^a-zA-Z0-9]@','_',$config['theme']['id']['value']);
            if(file_rename(THEME_DIR.'/'.$theme,THEME_DIR.'/'.$config['theme']['id']['value'])){
                $sDir=THEME_DIR.'/'.$config['theme']['id']['value'];
                $theme=$config['theme']['id']['value'];
                $replaceWg=false;
            }
        }
        $aTheme=array(
            'name'=>$config['theme']['name']['value'],
            'id'=>$config['theme']['id']['value'],
            'version'=>$config['theme']['version']['value'],
            'info'=>$config['theme']['info']['value'],
            'author'=>$config['theme']['author']['value'],
            'site'=>$config['theme']['site']['value'],
            'update_url'=>$config['theme']['update_url']['value'],
            'config'=>array(
                'config'=>$this->__changeXMLArray($config['theme']['config']['set']),
                'borders'=>$this->__changeXMLArray($config['theme']['borders']['set']),
                'views'=>$this->__changeXMLArray($config['theme']['views']['view'])
            )
        );
        $aWidgets=$wightes_info['theme']['widgets']['widget'];
        $aTheme['theme']=$theme;
        $aTheme['stime']=time();
        if(is_array($aTheme['config']['views']) && count($aTheme['config']['views'])>0){
            foreach($aTheme['config']['views'] as $v){
                $tmp[]=$v['tpl'];
            }
            $aTheme['template']=implode(',',$tmp);
        }else{
            $aTheme['template']='';
        }
        
        for($i=0;$i<count($aWidgets);$i++){
            $aTmp[$i]['base_file']='user:'.$aTheme['theme'].'/'.$aWidgets[$i]['attr']['file'];
            $aTmp[$i]['base_slot']=$aWidgets[$i]['attr']['slot'];
            $aTmp[$i]['widgets_type']=$aWidgets[$i]['attr']['type'];
            $aTmp[$i]['widgets_order']=$aWidgets[$i]['attr']['order'];
            $aTmp[$i]['title']=$aWidgets[$i]['attr']['title'];
            $aTmp[$i]['domid']=$aWidgets[$i]['attr']['domid'];
            $aTmp[$i]['border']=$aWidgets[$i]['attr']['border'];
            $aTmp[$i]['classname']=$aWidgets[$i]['attr']['classname'];
            $aTmp[$i]['tpl']=$aWidgets[$i]['attr']['tpl'];
            $aTmp[$i]['base_id']=$aWidgets[$i]['attr']['baseid'];

            /*$set=$aWidgets[$i]['set'];
            $aSet = array();
            for($y=0;$y<count($set);$y++){
                $aSet[$set[$y]['attr']['key']]=$set[$y]['attr']['value'];
            }*/
            $aTmp[$i]['params']=strtr($aWidgets[$i]['value'], array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
        }
        $aWidgets=$aTmp;

        $aNumber=$this->countWidgetsByTheme($theme);
        $nNumber=intval($aNumber['num']);
        $insertWidgets=false;
        if($replaceWg){
            
            if($nNumber){
                $this->deleteWidgetsByTheme($theme);
            }
            $insertWidgets=true;
        }else{
            if($nNumber==0){
                $insertWidgets=true;
            }
        }
        if($insertWidgets && count($aWidgets)>0){
            
            foreach($aWidgets as $k=>$wg){
                $this->insertWidgets($wg);
            }
        }
        if(!$this->updateThemes($aTheme)){
            return false;
        }else{
            return $aTheme;
        }
    }
    function countWidgetsByTheme($sTheme){
        return $this->db->selectrow('select count("widgets_id") as num from sdb_widgets_set where  base_file like "user:'.$sTheme.'%"');    
    }
    function deleteWidgetsByTheme($sTheme){
        return $this->db->query('delete from sdb_widgets_set where  base_file like "user:'.$sTheme.'%"');    
    }
    function insertWidgets($aData){
        $rs=$this->db->query('select * from sdb_widgets_set where 1');
        $aData['params']=str_replace('\'','\\\'',$aData['params']);
        $sSQL= $this->db->GetInsertSQL($rs,$aData);
        if(!$sSQL || $this->db->query($sSQL)){
            return true;
        }else{
            return false;
        }
    }
    function __buildDir($sDir){
        if(file_exists($sDir)){
            $aTmp=explode('/',$sDir);
            $sTmp=end($aTmp);
            if(strpos($sTmp,'(')){
                $i=substr($sTmp,strpos($sTmp,'(')+1,-1);
                $i++;
                $sDir=str_replace('('.($i-1).')','('.$i.')',$sDir);
            }else{
                $sDir.='(1)';
            }
            return $this->__buildDir($sDir);
        }else{
            return $sDir.'/';
        }
    }
    function __removeDir($sDir) {
        if($rHandle=opendir($sDir)){
            while(false!==($sItem=readdir($rHandle))){
                if ($sItem!='.' && $sItem!='..'){
                    if(is_dir($sDir.'/'.$sItem)){
                        $this->__removeDir($sDir.'/'.$sItem);
                    }else{
                        if(!unlink($sDir.'/'.$sItem)){
                            trigger_error('因权限原因，模板文件'.$sDir.'/'.$sItem.'无法删除',E_USER_NOTICE);
                        }
                    }
                }
            }
            closedir($rHandle);
            
            rmdir($sDir);
            return true;
        }else{
            return false;
        }
    }

    function updateThemes($aData){//更新 sdb_themes
        $rs=$this->db->query('select * from sdb_themes where theme="'.$aData['theme'].'"');
        $sql= $this->db->GetUpdateSQL($rs,$aData,true);

        return (!$sql || $this->db->exec($sql));
    }

    function setDefault($theme){
        return $this->system->setConf('system.ui.current_theme',$theme);
    }

    function reset($theme,$xml=''){
    
        return $this->initTheme($theme,true,'',$xml);
    }

    function getDefault(){
        $defaultTheme=$this->system->getConf('system.ui.current_theme');
        $this->separatXml($defaultTheme);
        return $defaultTheme;
    }
    function separatXml($theme){
        $workdir = getcwd();
        chdir(THEME_DIR.'/'.$theme);
        if(!is_file('info.xml')){
            $content=file_get_contents('theme.xml');
            $rContent=substr($content,0,strpos($content,'<widgets>'));
            /*
            $xml = $this->system->loadModel('utility/xml');
            $content=file_get_contents('theme.xml');
            $config = $xml->xml2arrayValues($content);
            $info=array();
            foreach($config['theme'] as $k=>$v){
                if($k!='widgets'){
                    $info[$k]=$v;
                }
            }
            */
            file_put_contents('info.xml',$rContent.'</theme>');
        }
        chdir($workdir);

    }
    function outputPkg($theme){
        $tar = $this->system->loadModel('utility/tar');
        $workdir = getcwd();
        
        if(chdir(THEME_DIR.'/'.$theme)){
            $this->__getAllFiles('.',$aFile);
            for($i=0;$i<count($aFile);$i++){
                if($f = substr($aFile[$i],2)){
                    if($f!='theme.xml'){
                        $tar->addFile($f);
                    }
                }
            }
            if(is_file('info.xml')){
                $tar->addFile('info.xml',file_get_contents('info.xml'));
            }
            $tar->addFile('theme.xml',$this->__makeConfigFile($theme));
            //$tar->addFile('info.xml',$this->__makeConfigFile($theme));

            $aTheme=$this->getThemeInfo($theme);
            
            $this->system->session->close();
            $charset = $this->system->loadModel('utility/charset');
            //$name = $charset->utf2local(preg_replace('/\s/','-',$aTheme['name'].'-'.$aTheme['version']),'zh');
            $name = $charset->utf2local(preg_replace('/\s/','-',$aTheme['name'].'-'.$aTheme['version']),'zh');
            @set_time_limit(0);
            //header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header('Content-type: application/octet-stream');
            header('Content-type: application/force-download');
            header('Content-Disposition: attachment; filename="'.$name.'.tgz"');
            $tar->getTar('output');
            chdir($workdir);
        }else{
            chdir($workdir);
            return false;
        }
    }

    function __getAllFiles($sDir,&$aFile){
        if($rHandle=opendir($sDir)){
            while(false!==($sItem=readdir($rHandle))){
                if ($sItem!='.' && $sItem!='..' && $sItem!=''){
                    if(is_dir($sDir.'/'.$sItem)){
                        $this->__getAllFiles($sDir.'/'.$sItem,$aFile);
                    }else{
                        $aFile[]=$sDir.'/'.$sItem;
                    }
                }
            }
            closedir($rHandle);
        }
    }

    function __makeConfigFile($sTheme){
        $aTheme=$this->getThemeInfo($sTheme);
        
        
        
        $aWidget['widgets']=$this->getWidgetsInfo($sTheme);
        foreach($aWidget['widgets'] as $i=>$widget){
            
            $aWidget['widgets'][$i]['params']=$aWidget['widgets'][$i]['params'];
            $aWidget['widgets'][$i]['base_file']=str_replace('user:'.$sTheme.'/','',$aWidget['widgets'][$i]['base_file']);
            
        }
        
        $aTheme['config']['config']=$aTheme['config']['config'];
        
        $aTheme['config']['views']=$aTheme['views'];
        $aTheme['id']=$aTheme['theme'];
    
        $smarty = &$this->system->loadModel('system/frontend');
        $aTheme=array_merge($aTheme,$aWidget);
        foreach($aTheme as $k=>$v){
            
            $smarty->assign($k,$v);
        }
    
        $sXML=$smarty->fetch('admin:system/template/theme.xml');
        
        return $sXML;


    }

    function __changeXMLArray($aArray){
        for($i=0;$i<count($aArray);$i++){
            $aData[$i]=$aArray[$i]['attr'];
        }
        return $aData;
    }

    function getThemeInfo($sTheme){
        $info = $this->db->selectrow('select * from sdb_themes where theme="'.$sTheme.'"');
        
        if(isset($info['config'])){
            
            $info['config'] = unserialize($info['config']);
            
            
        }
        
        return $info;
    }

    function getWidgetsInfo($sName){
        return $this->db->select('select * from sdb_widgets_set where base_file like "user:'.$sName.'/%"');    
    }

    //todo page不属于模板
    function htmEdit($src){
        return $this->db->selectrow('select * from sdb_pages where page_title="'.$src.'"');
    }

    function editHtml($title,$data){
        $dat=$this->db->selectrow('select * from sdb_pages where page_title="'.$title.'"');
        if($dat['page_title']){
            $rs=$this->db->query('select * from sdb_pages where page_title="'.$title.'"');
            $sql=$this->db->GetUpdateSQL($rs,$data);
        }else{
            $rs=$this->db->query('select * from sdb_pages where 1');
            $sql= $this->db->GetInsertSQL($rs,array_merge($data,array('page_title'=>$title)));
        }
        return (!$sql || $this->db->exec($sql));
    }

    function templateConfig($sTemplate){
        $aData=$this->db->selectrow('select config from sdb_themes where theme="'.$sTemplate.'"');
        $aData=unserialize($aData['config']);
        if(count($aData)>0){
            foreach($aData as $k=>$v){
                $tmp[]=array('key'=>$k,'value'=>$v);
            }
        }
        return array('total'=>count($aData),'main'=>$tmp);

    }
    function templateConfigEdit($sTemplate,$sKey,$sValue){
        $aData=$this->db->selectrow('select config from sdb_themes where name="'.$sTemplate.'"');
        $aConfig=unserialize($aData['config']);
        $aConfig[$sKey]=$sValue;
        $aTmp=array('config'=>serialize($aConfig));

        $rs=$this->db->query('select config from sdb_themes where name="'.$sTemplate.'"');
        $sql=$this->db->GetUpdateSQL($rs,$aTmp);
        if(!$sql || $this->db->query($sql)){
            return true;
        }else{
            return false;
        }
    }

    function applyTheme($theme=null){
        if(!$theme){
            $theme = $this->system->getConf('system.ui.current_theme');
            define('TPL_ID',$theme);
        }
        return $this->getThemeInfo($theme);
    }

    function getContent($theme,$file){
        return file_get_contents(THEME_DIR.'/'.$theme.'/'.$file);
    }
    function delFile($theme,$file){
        return unlink(THEME_DIR.'/'.$theme.'/'.$file);
    }
    function saveTemplate($file,$theme,$content){
        $aData['template_source']=$content;
        $aData['theme']=$theme;
        $aData['template_name']=$file;
        if($rs=$this->db->query('select sdb_template_id from sdb_template where theme="'.$theme.'" and "template_name='.$file.'"')){
            if(!$this->db->GetUpdateSQL($rs,$aData)){
                 return false;
            }
        }else{
            if(!$this->db->GetInsertSQL($rs,$aData)){
                return false;
            }
        }
        return true;
    }
    function setContent($theme,$file,$content){
        if($content){
         return file_put_contents(THEME_DIR.'/'.$theme.'/'.$file,stripslashes($content));
        }else{
         return false;
        }
    }
    function getListName($name){
        $ctl = array(
            'default.html'=>'其它页面',
            'index.html'=>'首页',
            'article.html'=>'文章页',
            'product.html'=>'商品详细页',
            'comment.html'=>'商品评论/咨询页',
            'gallery.html'=>'商品列表页',
            'cart.html'=>'购物车页',
            'gift.html'=>'赠品页',
            'member.html'=>'会员中心页',
            'page.html'=>'站点栏目单独页',
            'passport.html'=>'注册/登录页',
            'search.html'=>'高级搜索页'
        );
        
        return $ctl[$name];
    }
    function templateList($theme){//默认模板列表
        include('shopctls.php');
        $ctl = array(
            'gallery'=>'商品列表页',
            'product'=>'商品详细页',
            'page'=>'站点栏目单独页',
            'article'=>'文章页',
            'gift'=>'赠品页',
            'cart'=>'购物车页',
            'comment'=>'商品评论/咨询页',
            'member'=>'会员中心页',
            'passport'=>'注册/登录页',
            'search'=>'高级搜索页'

        );
        $data = array(
            array('name'=>'首页','file'=>'index.html','available'=>file_exists(THEME_DIR.'/'.$theme.'/index.html')),
        );
        foreach($controllers as $c=>$a){
            if($ctl[$c]){
                $item = array('name'=>$ctl[$c],'items'=>array(),'file'=>($file=$c.'.html'),'available'=>file_exists(THEME_DIR.'/'.$theme.'/'.$file));
                foreach($a as $act=>$i){
                    $item['items'][] = array_merge(array(
                            'act'=>$act,
                            'file'=>($file = $c.'-'.$act.'.html'),
                            'available'=>file_exists(THEME_DIR.'/'.$theme.'/'.$file),
                        ),$i);
                }
                $data[]=$item;
            }
        }
        $data[]=array('name'=>'其它页面','file'=>'default.html','available'=>file_exists(THEME_DIR.'/'.$theme.'/default.html'));
        return $data;
    }

}


?>
