<?php
function smarty_modifier_cur($money,$currency=null,$basicFormat = false,$chgval=true)
{
    $system = &$GLOBALS['system'];
    $cur = &$system->loadModel('system/cur');
    return $cur->changer($money,$currency,$basicFormat,$chgval);
}
?>