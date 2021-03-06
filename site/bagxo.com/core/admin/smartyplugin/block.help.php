<?php
function smarty_block_help(&$params, $content, &$smarty)
{
    if(null!==$content){
        $help_types = array(
                'info'=>array('size'=>18,'icon'=>'images/bundle/tips_info.gif'),
                'dialog'=>array('size'=>18,'icon'=>'images/bundle/tips_info.gif','dialog'=>1),
                'link'=>array('size'=>15,'icon'=>'images/bundle/tips_help.gif'),
                'link-mid'=>array('size'=>14,'icon'=>'images/bundle/tips_help_mid.gif'),
                'link-small'=>array('size'=>12,'icon'=>'images/bundle/tips_help_small.gif'),
            );
        $params['dom_id'] = $smarty->new_dom_id();
        if($content=trim($content)){
            $params['text'] = preg_replace('/\n/', '', $content);
        }
        $params['type'] = isset($help_types[$params['type']])?$help_types[$params['type']]:$help_types['info'];
        $vars = $smarty->_tpl_vars;
        $smarty->_tpl_vars = $params;
        $smarty->_smarty_include(array( 'smarty_include_tpl_file' => 'helper.html', 'smarty_include_vars' => $params));
        $smarty->_tpl_vars = $vars;
    }
}

?>