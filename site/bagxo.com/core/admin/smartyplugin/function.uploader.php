<?php
function smarty_function_uploader($params, &$smarty){
    $includeVars=$params;
    $smarty->_smarty_include(array('smarty_include_tpl_file' => 'system/tools/uploader.html','smarty_include_vars' =>$includeVars));
}
?>