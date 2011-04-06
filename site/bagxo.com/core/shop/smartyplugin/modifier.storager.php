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
function smarty_modifier_storager($ident) 
{ 
    if(!$GLOBALS['storager']){
        $system = &$GLOBALS['system'];
        $GLOBALS['storager']=$system->loadModel('system/storager');
    }
    $storager = &$GLOBALS['storager'];
    return $storager->getUrl($ident);
} 
?> 
