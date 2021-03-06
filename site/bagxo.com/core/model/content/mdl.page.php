<?php
require_once('plugin.php');
class mdl_page extends plugin{

    var $plugin_name = 'layout';
    var $plugin_type = 'dir';
    var $prefix='layout_';

    function getExists($ident){
        if($this->db->selectrow('select page_id from sdb_pages where page_name="'.$ident.'"')){
            return true;
        }else{
            return false;
        }
        return $this->db->selectrow('select page_id from sdb_pages where page_name="'.$ident.'"');
    }
    


    function set_tpl_content($aData){
       if($aData){
           $aData['edittime']=time();
           if(!($rs = $this->db->selectrow('select * from sdb_systmpl where tmpl_name="'.$aData['tmpl_name'].'"'))){
               $rs = $this->db->query('select * from sdb_systmpl where 0=1');
               $sqlString = $this->db->GetInsertSQL($rs, $aData);
               $this->db->exec($sqlString);
           }else{
               $rs = $this->db->query("SELECT * FROM sdb_systmpl WHERE tmpl_name='".$aData['tmpl_name']."'");
               $sql = $this->db->GetUpdateSQL($rs, $aData);
               $this->db->exec($sql);

           }
       }
       return true;
    }
    function get_tpl_content($file_name){
        if(!($rs = $this->db->selectrow('select  content from sdb_systmpl where tmpl_name="'.$file_name.'"'))){
            if(file_exists(CORE_DIR.'/html/pages/'.$ident.'.txt')){
                return file_get_contents(CORE_DIR.'/html/pages/'.$file_name.'.txt');
            }
        }else{
            return $rs['content'];
        }
    }
    function editor($ident,$layout,$theme){

        $rs = $this->db->exec('select page_name,page_content,page_time,page_title from sdb_pages where page_name="'.$ident.'"');
        $rows = $this->db->getRows($rs);
        if(!$rows){
            if(file_exists(CORE_DIR.'/html/pages/'.$ident.'.html')){
                
                $sql = $this->db->getUpdateSQL($rs,array(
                    'page_name'=>$ident,'page_time'=>time(),'page_title'=>$ident,
                    'page_content'=>addslashes('<{widgets}>')),true);
                $this->db->exec($sql);

                $this->db->exec('delete from sdb_widgets_set where base_file="page:'.$ident.'"');
                
                $rs = $this->db->exec('select * from sdb_widgets_set where 0=1');
                $sql = $this->db->getInsertSQL($rs,array(
                    'base_file'=>'page:'.$ident,
                    'base_slot'=>0,
                    'widgets_type'=>'html',
                    'widgets_order'=>0,
                    'border'=>'__none__',
                    'params'=>array('html'=>file_get_contents(CORE_DIR.'/html/pages/'.$ident.'.html'))
                    ));
                $this->db->exec($sql);

            }else{
                $sql = $this->db->getUpdateSQL($rs,array(
                    'page_name'=>$ident,'page_time'=>time(),'page_title'=>$ident,
                    'page_content'=>addslashes(file_get_contents(PLUGIN_DIR.'/layout/1-column/layout.html'))),true);
                $this->db->exec($sql);
            }
        }else{
            if($layout){

                if($sql = $this->db->getUpdateSQL($rs,array('page_content'=>addslashes(file_get_contents(PLUGIN_DIR.'/layout/'.$layout.'/layout.html')),'page_time'=>time()),true)){
                    $this->db->exec($sql);

                    $setting = $this->getParams($layout);
                    $setting['slotsNum'] = intval($setting['slotsNum']);
                    if($setting['slotsNum']>0){
                        $setting['slotsNum']--;
                        $this->db->exec("update sdb_widgets_set set base_slot={$setting['slotsNum']} where base_slot>{$setting['slotsNum']} and base_file='page:{$ident}'");
                    }
                }
            }
        }

        $smarty = &$this->system->loadModel('system/frontend');
        $this->widgets = &$this->system->loadModel('content/widgets');
        $smarty->assign('header',$this->_header($theme));
        $smarty->assign('footer',$this->_footer());
        $smarty->assign('include','page:'.$ident);
        $smarty->display('content/page_frame.html');
    }
    
    function _header($theme){
            $ret='<base href="'.$this->system->base_url().'"/>';
            $ret_themecss='<link rel="stylesheet" href="themes/'.$theme.'/images/css.css" type="text/css" />';
        if( defined('DEBUG_CSS') && DEBUG_CSS){
    $ret.= '<link rel="stylesheet" href="statics/framework.css" type="text/css" />';
    $ret.='<link rel="stylesheet" href="statics/shop.css" type="text/css" />';
    $ret.='<link rel="stylesheet" href="statics/widgets.css" type="text/css" />';
    $ret.=$ret_themecss;
    $ret.='<link rel="stylesheet" href="statics/widgets_edit.css" type="text/css" />';
        }elseif( defined('GZIP_CSS') && GZIP_CSS){
            $ret.= '<link rel="stylesheet" href="statics/style.zcss" type="text/css" />';
            $ret.=$ret_themecss;
            $ret.='<link rel="stylesheet" href="statics/widgets_edit.css" type="text/css" />';
        }else{
            $ret.= '<link rel="stylesheet" href="statics/style.css" type="text/css" />';
            $ret.=$ret_themecss;
            $ret.='<link rel="stylesheet" href="statics/widgets_edit.css" type="text/css" />';
        }
       
        if( defined('DEBUG_JS') && DEBUG_JS){
            $ret.= '<script src="'.dirname($_SERVER['PHP_SELF']).'/js/0.mootools.js"></script>
                    <script src="'.dirname($_SERVER['PHP_SELF']).'/DragDropPlus.js"></script>
                    <script src="'.dirname($_SERVER['PHP_SELF']).'/shopWidgets.js"></script>';
        }elseif( defined('GZIP_JS') && GZIP_JS){
            $ret.= '<script src="'.dirname($_SERVER['PHP_SELF']).'/widgets.jgz"></script>';
        }else{
            $ret.= '<script src="'.dirname($_SERVER['PHP_SELF']).'/widgets.js"></script>';
        }
        return $ret;
    }
    
      function _footer(){
       return "<div id='drag_operate_box' class='drag_operate_box' style='visibility:hidden;'>
       <div class='drag_handle_box'>
             <table cellpadding='0' cellspacing='0' width='100%'>
                                           <tr>
                                           <td><span class='dhb_title'>标题</span></td>
                                           <td width='40'><span class='dhb_edit'>编辑</span></td>
                                           <td width='40'><span class='dhb_del'>删除</span></td>
                                           </tr>
              </table>
              </div>
          </div>
          
          <div id='drag_ghost_box' class='drag_ghost_box' style='visibility:hidden'>
              
          </div>";
    }

}
?>
