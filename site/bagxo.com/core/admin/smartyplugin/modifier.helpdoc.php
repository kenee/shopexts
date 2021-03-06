<?php 
/* 
* Smarty plugin 
* ------------------------------------------------------------- 
* File:     modifier.t.php 
* Type:     modifier 
* Name:     capitalize 
* Purpose:  capitalize words in the string 
* ------------------------------------------------------------- 
*/ 
function smarty_modifier_helpdoc($string,$arc='') 
{ 
    $system = &$GLOBALS['system'];
    $version = $system->version();
    if($arc!=''){
        if($arc[0]=='#') $helpfile = str_replace('/','-',$_GET['ctl']).'_'.$_GET['act'].'.html'.$arc;
        elseif(substr($arc,0,7)=='http://'||substr($arc,0,8)=='https://') 
            return '<a href="'.$arc.'" target="_blank">'.$string.'</a>';
        else
            $helpfile = $arc;
    }else{
        $helpfile = str_replace('/','-',$_GET['ctl']).'_'.$_GET['act'].'.html';
    }
    return '<a href="http://docs.shopex.cn/bi_shop/'.$version['ver'].'/'.$helpfile.'" target="_blank">'.$string.'</a>';
} 
?> 
