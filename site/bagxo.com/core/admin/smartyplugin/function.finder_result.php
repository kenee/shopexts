<?php 
/*
*    'iptname' => name
*    from => value
*    <input type="object" object=goods name="xx" value="xx"
*/
function smarty_function_finder_result($params, &$smarty){
    include_once('shopObject.php');
    $objects = shopObject::objects();
    $return['_finder'] = &$params;

    $_smarty_tpl_vars = $smarty->_tpl_vars;
    $system = &$GLOBALS['system'];
    if(!($mod = $objects[$params['type']]) || !($o = &$system->loadModel($mod))){
        $smarty->trigger_error('Wrong finder tfype: "'.$mod.'"',E_USER_ERROR);
    }

    $params['id'] = $o->idColumn;
    $params['params'] = serialize($params['params']);

    $params['controller'] = $o->adminCtl;

    $params['domid'] = 'sel_'.substr(md5(rand(0,time())),0,6);

    if(is_string($params['value'])){
        $params['value'] = explode(',',$params['value']);
    }
    $cols = $params['cols']?$params['cols']:$o->textColumn;
    $return['items'] = &$o->getFinder($cols,count($params['value'])>0?array($o->idColumn=>$params['value']):-1,0,-1,$count);
    $return['items']['custom_name'] = "商品";
    if($params['type']=='gift'){
        $return['items']['custom_name'] = "赠品";
    }
    if($params['type']=='coupon'){
        $return['items']['custom_name'] = "优惠券";
    }
    $smarty->_smarty_include(array( 'smarty_include_tpl_file' => 'finder/input.html', 'smarty_include_vars' => $return));
     $smarty->_tpl_vars = $_smarty_tpl_vars;
    unset($_smarty_tpl_vars);
}
?>