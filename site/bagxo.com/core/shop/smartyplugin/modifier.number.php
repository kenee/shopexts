<?php
/* 
* Smarty plugin 
* ------------------------------------------------------------- 
* File:     modifier.number.php 
* Type:     modifier 
* Name:     capitalize 
* Purpose:  transform the num value to readable string 
* ------------------------------------------------------------- 
*/ 

function smarty_modifier_number($num,$type=0){
    switch($type){
        case 0:
            $number = intval($num);
            break;
        case 1:
            if($num <1){
                $number='低于1';
            }else{
                $number= number_format($num,1,'','');
                if($number%10==0){
                    $number=$number/10;
                }
            }
            break;
        case 2:
            if($num<1){
                $number = '超过99';
            }else{
                $number = 100-intval($num);
              
            }
              break;
  }   
    return $number;
}
?>