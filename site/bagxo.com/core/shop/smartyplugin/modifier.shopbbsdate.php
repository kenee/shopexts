<?php
function smarty_modifier_shopbbsdate($timestamp)
{
    return mydate("y-m-d h:i",$timestamp);
}
?>
