<?php
include_once('objectPage.php');
define('DPGB_TMP_MODE',1);
define('DPGB_HOME_MODE',2);
class ctl_delivery_printer extends objectPage{

    var $name = '快递单模板';
    var $workground = 'order';
    var $object='trading/dly_printer';
    var $actionView = 'order/dly_printer_action.html';
    var $actions= array(
        'edit_tmpl'=>'编辑',
        'add_same'=>'添加相似单据',
        'download'=>array('html'=>'<span class="sysiconBtnNoIcon" onclick="window.open(\'index.php?ctl=order/delivery_printer&act=download&p[0]=\'+$(this).getParent(\'.row\').getAttribute(\'item-id\'),\'download\' )">下载模板</span>'),
    );

    function ctl_delivery_printer(){
        parent::objectPage();
        $this->pagedata['dpi'] = intval($this->system->getConf('system.clientdpi'));
        if(!$this->pagedata['dpi']){
            $this->pagedata['dpi'] = 96;
        }
    }

    function do_print(){
        $data = $_POST['order'];
        $data['shop_name'] = $this->system->getConf('system.shopname');

        $obj_dly_center = &$this->system->loadModel('trading/dly_centers');
        $dly_center = $obj_dly_center->instance($_POST['dly_center']);
        $data['dly_name']=$dly_center['uname'];

        list($pkg,$regions,$region_id) = explode(':',$data['ship_area']);
        foreach(explode('/',$regions) as $i=>$region){
            $data['ship_area_'.$i]= $region;
        }

        if($dly_center['region']){
            list($pkg,$regions,$region_id) = explode(':',$dly_center['region']);
            foreach(explode('/',$regions) as $i=>$region){
                $data['dly_area_'.$i]= $region;
            }
        }

        $data['dly_address']=$dly_center['address'];
        $data['dly_tel']=$dly_center['phone'];
        $data['dly_mobile']=$dly_center['cellphone'];
        $data['dly_zip']=$dly_center['zip'];

        $t = time()+($GLOBALS['user_timezone']-SERVER_TIMEZONE)*3600;
        $data['date_y']=date('Y',$t);
        $data['date_m']=date('m',$t);
        $data['date_d']=date('d',$t);

        if(file_exists(HOME_DIR.'/upload/dly_bg_'.$_POST['dly_tmpl_id'].'.jpg')){
            $this->pagedata['tmpl_bg'] = 'index.php?ctl=order/delivery_printer&act=show_bg_picture&p[0]='.DPGB_HOME_MODE.'&p[1]='.$_POST['dly_tmpl_id'];
        }
        unset($data['ship_area']);
        $xmltool = $this->system->loadModel('utility/xml');
        $this->pagedata['data'] = addslashes($xmltool->array2xml($data,'data'));
        $this->pagedata['prt_tmpl'] = $this->model->instance($_POST['dly_tmpl_id'],'prt_tmpl_width,prt_tmpl_height,prt_tmpl_data');
        $this->setView('order/print_dly_job.html');

        $this->output();
    }

    function edit_tmpl($tmpl_id){
        if(PRINTER_FONTS){
            $this->pagedata['font'] = explode("|",PRINTER_FONTS);
        }
        if($this->pagedata['tmpl'] = $this->model->instance($tmpl_id)){
            if(file_exists(HOME_DIR.'/upload/dly_bg_'.$tmpl_id.'.jpg')){
                $this->pagedata['tmpl_bg'] = 'index.php?ctl=order/delivery_printer&act=show_bg_picture&p[0]='.DPGB_HOME_MODE.'&p[1]='.$tmpl_id;
            }
            $this->pagedata['elements'] = $this->model->getElements();
            $this->page('order/dly_printer_editor.html');
        }else{
            $this->splash('failed','index.php?ctl=order/delivery_printer&act=index','无效的快递单模板id');
        }
    }

    function import(){
        $this->page('order/dly_printer_import.html');
    }

    function do_upload_pkg(){
        $this->begin('index.php?ctl=order/delivery_printer&act=import');
        $file = $_FILES['package'];
        $extname = strtolower(ext_name($file['name']));
        $tar = $this->system->loadModel('utility/tar');
        if($extname=='.dtp'){
            if($tar->openTAR($file['tmp_name']) && $tar->containsFile('info')){
                if(!($info = unserialize($tar->getContents($tar->getFile('info'))))){
                    trigger_error('无法读取结构信息,模板包可能已损坏',E_USER_ERROR);
                }

                if($tpl_id=$this->model->insert($info)){
                    if($tar->containsFile('background.jpg')){ //包含背景图
                        file_put_contents(HOME_DIR.'/upload/dly_bg_'.$tpl_id.'.jpg',$tar->getContents($tar->getFile('background.jpg')));
                    }
                }

            }else{
                trigger_error('无法解压缩,模板包可能已损坏',E_USER_ERROR);
            }
        }else{
            trigger_error('必须是shopex快递单模板包(.dtp)',E_USER_ERROR);
        }
        $this->end(true,'快递单安装成功','index.php?ctl=order/delivery_printer&act=index');
    }

    function _actions(){
        return $params['tools'] = array(
            'class'=>'span-5'
        );
    }

    function add_tmpl(){

        $this->pagedata['tmpl'] = array(
            'prt_tmpl_title'=>'',
            'prt_tmpl_width'=>240,
            'prt_tmpl_height'=>135,
        );
        if(PRINTER_FONTS){
            $this->pagedata['font'] = explode("|",PRINTER_FONTS);
        }
        $this->pagedata['elements'] = $this->model->getElements();
        $this->page('order/dly_printer_editor.html');
    }

    function save(){
        $this->begin('index.php?ctl=order/delivery_printer&act=index');
        if($_POST['prt_tmpl_id']){
            if($this->model->update($_POST,array('prt_tmpl_id'=>$_POST['prt_tmpl_id']))){
                $tpl_id = $_POST['prt_tmpl_id'];
            }else{
                $tpl_id = false;
            }
        }else{
            $tpl_id = $this->model->insert($_POST);
        }

        $_POST['tmp_bg'] = trim($_POST['tmp_bg']);


        if($_POST['prt_tmpl_id']=='__none__'){
        }
        if($tpl_id && $_POST['tmp_bg']){

            if(file_exists(HOME_DIR.'/upload/dly_bg_'.$tpl_id.'.jpg')){
                unlink(HOME_DIR.'/upload/dly_bg_'.$tpl_id.'.jpg');
            }
            if(file_exists(HOME_DIR.'/tmp/'.$_POST['tmp_bg'])){
                file_rename(HOME_DIR.'/tmp/'.$_POST['tmp_bg'],HOME_DIR.'/upload/dly_bg_'.$tpl_id.'.jpg');
            }
        }

        $this->end(true,$tpl);
    }

    function add_same($tmpl_id){/*todo 背景图*/
        if($this->pagedata['tmpl'] = $this->model->instance($tmpl_id)){
            unset($this->pagedata['tmpl']['prt_tmpl_id']);
            $this->pagedata['elements'] = $this->model->getElements();
            $this->page('order/dly_printer_editor.html');
        }else{
            $this->splash('failed','index.php?ctl=order/delivery_printer&act=index','无效的快递单模板id');
        }
    }

    function download($tmpl_id){
        $tmpl = $this->model->instance($tmpl_id,'prt_tmpl_title,prt_tmpl_width,prt_tmpl_height,prt_tmpl_data');
        $tar = $this->system->loadModel('utility/tar');
        $tar->addFile('info',serialize($tmpl));

        if($bg = realpath(HOME_DIR.'/upload/dly_bg_'.$tmpl_id.'.jpg')){
            $tar->addFile('background.jpg',file_get_contents($bg));
        }

        $this->system->session->close();
        $charset = $this->system->loadModel('utility/charset');
        $name = $charset->utf2local($tmpl['prt_tmpl_title'],'zh');
        @set_time_limit(0);
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header('Content-type: application/octet-stream');
        header('Content-type: application/force-download');
        header('Content-Disposition: attachment; filename="'.$name.'.dtp"');
        $tar->getTar('output');
    }

    function upload_bg($printer_id){
        $this->pagedata['dly_printer_id'] = $printer_id;
        $this->setView('order/dly_printer_uploadbg.html');
        $this->output();
    }

    function do_upload_bg(){
        $this->begin('index.php?ctl=order/delivery_printer&act=do_upload_bg&p[0]=0');
        $extname = strtolower(ext_name($_FILES['background']['name']));
        if($extname=='.jpg' || $extname=='.jpeg'){
            $file = tempnam(HOME_DIR.'/tmp', 'dly_');
            unlink($file);
            $file.='.jpg';
            $rs = move_uploaded_file($_FILES['background']['tmp_name'],$file);
        }else{
            $this->splash('failed','index.php?ctl=order/delivery_printer&act=upload_bg','必须是.jpg的图片');
        }
        $this->end($rs,'快递单背景图片保存成功','index.php?ctl=order/delivery_printer&act=done_upload_bg&p[0]='.DPGB_TMP_MODE.'&p[1]='.basename($file));
    }

    function done_upload_bg($rs,$file){
        if($rs){
            $url = 'index.php?ctl=order/delivery_printer&act=show_bg_picture&p[0]='.$rs.'&p[1]='.$file;
            echo '<script>
                if($("dly_printer_bg")){
                    $("dly_printer_bg").value = "'.$file.'";
        }else{
            new Element("input",{id:"dly_printer_bg",type:"hidden",name:"tmp_bg",value:"'.$file.'"}).inject("dly_printer_form");
        }
        window.printer_editor.dlg.close();
        window.printer_editor.setPicture("'.$url.'");
            </script>';
        }else{
            var_dump(__LINE__,$file);
        }
    }

    function show_bg_picture($mode,$file){
        header('Content-Type: image/jpeg');
        if($mode==DPGB_TMP_MODE){
            $this->system->sfile(HOME_DIR.'/tmp/'.$file);
        }elseif($mode==DPGB_HOME_MODE){
            $this->system->sfile(HOME_DIR.'/upload/dly_bg_'.$file.'.jpg');
        }
    }

    function set_enable(){
        $this->begin('index.php?ctl=order/delivery_printer');
        $rs = $this->model->update(array('shortcut'=>'true'),$_POST);
        $this->end($rs);
    }

    function set_disabled(){
        $this->begin('index.php?ctl=order/delivery_printer');
        $rs = $this->model->update(array('shortcut'=>'false'),$_POST);
        $this->end($rs);
    }

}
