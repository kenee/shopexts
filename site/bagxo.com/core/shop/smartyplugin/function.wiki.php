<?php 
/* 
 * Smarty plugin 
 * ------------------------------------------------------------- 
 * File:     compiler.tplheader.php 
 * Type:     compiler 
 * Name:     tplheader 
 * Purpose:  Output header containing the source file name and 
 *           the time it was compiled. 
 * ------------------------------------------------------------- 
 */ 
function smarty_compiler_view($tag_args, &$smarty) 
{ 
    $attrs = $smarty->_parse_attrs($tag_args);
    if(!$attrs['page']){
        return '';
    }else{
    }
    return $output;
}
?>