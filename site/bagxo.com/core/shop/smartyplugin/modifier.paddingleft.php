<?php
function smarty_modifier_paddingleft($vol,$empty,$fill)
{
    return str_repeat($fill,$empty).$vol;
    
}
?>
