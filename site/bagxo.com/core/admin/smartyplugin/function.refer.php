<?php
function smarty_function_refer($params, &$smarty){
    //return $params['id'].'<a href="'.$params['url'].'" target="_blank">'.$params['url'].'</a>';
    switch($params['show']){
        case "id":
            $return=$params['id'];
        break;
        case "url":
            $return='<a href="'.$params['url'].'" target="_blank">'.$params['url'].'</a>';
        break;

    }
    return $return;
} 
?>