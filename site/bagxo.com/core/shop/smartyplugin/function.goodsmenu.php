<?php
function smarty_function_goodsmenu($params, &$smarty)
{
    if($GLOBALS['runtime']['member_lv']<0){
        $params['login'] = 'nologin';
    }
    $params['setting']['buytarget'] = $smarty->system->getConf('site.buy.target');
    $_smarty_tpl_vars = $smarty->_tpl_vars;
    $smarty->_tpl_vars=$params;

    $smarty->_smarty_include(array('smarty_include_tpl_file' => 'shop:product/menu.html', 'smarty_include_vars' => array()));
    $smarty->_tpl_vars = $_smarty_tpl_vars;
    unset($_smarty_tpl_vars);
}
?>
