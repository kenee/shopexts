<?php
function smarty_function_setting($params, &$smarty){

    if(!$GLOBALS['_settingList']){
        $system = &$GLOBALS['system'];
        $GLOBALS['_settingList'] = &$system->__setting->source();
    }

    smarty_core_load_plugins(array('plugins' => array(array('function', 'input',$smarty->_current_file,$smarty->_current_line_no, 20, false))),$smarty);

    $system = &$GLOBALS['system'];
    $params = array_merge($params,$GLOBALS['_settingList'][$params['key']]);
    $params['value'] = $system->getConf($params['key']);
    
    if($params['key'] == 'site.tax_ratio') $params['value'] *= 100;    //ever add 20080327
    
    if($params['type']==SET_T_INT){
        $params['type']= 'number';
    }elseif($params['type']==SET_T_ENUM){
        $params['type']= 'select';
    }elseif($params['type']==SET_T_BOOL){
        $params['type']= 'bool';
    }elseif($params['type']==SET_T_TXT){
        $params['type']= 'textarea';
    }elseif($params['type']==SET_T_FILE){
        $params['type']= 'file';
    }elseif($params['type']==SET_T_DIGITS){
        $params['type']= 'digits';
    }else{
        $params['type']= 'text';
    }
    if(!$params['id'])$params['id'] = 'el_'.substr(md5(rand(0,time())),0,6);
    $params['name'] = ($params['namespace']?$params['namespace']:'setting').'['.$params['key'].']';
    $key =$params['key']; 
    unset($params['desc']);
    unset($params['key']);
    return $html.smarty_function_input($params,$smarty).'<input type="hidden" name="_set_['.$key.']" value="'.$params['type'].'" />';
}
?>