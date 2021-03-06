<?php

/**
 * pagefactory 
 * 
 * @package 
 * @version $Id: pageFactory.php 1867 2008-04-23 04:00:24Z flaboy $
 * @copyright 2003-2007 ShopEx
 * @author Wanglei <flaboy@zovatech.com> 
 * @license Commercial
 */
class pagefactory{
    
    var $__tmpl;
    var $__initTime;
    var $config;
    var $pagedata;
    

    /**
     * output 
     * 
     * @param int $expired_time 
     * @param mixed $fetch 
     * @access public
     * @return void
     */
    function output($expired_time=0,$fetch=false){

        $output = &$this->system->loadModel('system/frontend');
        $output->clear_all_assign();

        if($this->pagedata){
            foreach ($this->pagedata as $key=>$data){
                $output->assign($key,$data);
            }
        }

        if($fetch){
            return $output->fetch($this->__tmpl);
        }else{
            $this->system->output($output->fetch($this->__tmpl));
        }
    }


    /**
     * set_view 
     * 
     * @param mixed $fname 
     * @access public
     * @return void
     */
    function setView($fname){
        $this->__tmpl=$fname;
    }

    /**
     * set_view 
     * 
     * @param mixed $fname 
     * @access public
     * @return void
     */
    function _setView($fname){
        $this->__tmpl=$fname;
    }

}
?>
