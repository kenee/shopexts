<?php
class ctl_setting extends adminPage{
    var $workground ='setting';

    function welcome(){
        $this->page('setting/welcome.html');
    }

    function config(){
        $this->path[] = array('text'=>'参数表管理');
        $this->page('setting/config/index.html',true);
    }

    function shoppingbasic(){
        $this->path[] = array('text'=>'购物显示设置');
        $this->page('setting/shopping_basic.html',true);
    }

    function guide_status($getable=false){

        if($getable){
            echo $this->system->getConf("system.guide",$_POST['check']);
        }
        else{
            return $this->system->setConf("system.guide",$_POST['check']);
        }
    }
    function settingData(){
        $setting = $this->system->loadModel('system/setting');
        $setlist = $setting->getList();
        foreach($setlist as $k=>$v){
            $data['main'][]=array('key'=>$k,'value'=>$value,'type'=>$v['type'],'default'=>$default);
        }
        $data['total']=count($setlist);
        $this->system->output(json_encode($data));
    }
    function settingDetail($id){
        include_once('setting.php');
        $oPayment=$this->system->loadModel('trading/payment');
        $this->pagedata['id']=$id;
        switch($setting[$id]['type']){
        case 1:
            $type='number';
            break;
        case 2://todo select框需设置option项 20082008-6-30-2-20
            $type='select';
            break;
        case 3:
            $type='bool';
            break;
        case 4:
            $type='textarea';
            break;
        case 0:
        default:
            $type='text';
        }
        $this->pagedata['type']=$type;
        $this->pagedata['value']=$this->system->getConf($id);

        $this->setView('setting/config/detail.html');
        $this->output();
    }
    //商家信息
    function basicinfo(){
        $this->path[] = array('text'=>'商家信息');
        $this->pagedata['shopname'] = addslashes($this->system->getConf('system.shopname'));
        $this->pagedata['shoptitle'] = addslashes($this->system->getConf('system.shopname')).' - Powered By ShopEx';
        $this->page('setting/site_basic.html');
    }
    function basicinfoEdit(){
        $this->begin('index.php?ctl=system/setting&act=basicinfo');
        $this->end($this->settingEdit(),__('修改成功'));
    }
    //基本设置
    function siteBasic(){
        if(defined('SAAS_MODE')&&SAAS_MODE){
            $this->pagedata['saas_mode'] = true;
        }
        $this->pagedata['auth_type']=$this->system->getConf('certificate.auth_type');
        $this->path[] = array('text'=>'基本设置');
        $this->page('setting/site_basic.html');
    }
    function siteBasicEdit(){

        $this->begin('index.php?ctl=system/setting&act=siteBasic');
        if($_POST['setting']['system.shopname'] && !$this->system->getConf('system.index_title')){
            $this->system->setConf('system.index_title',$_POST['setting']['system.shopname']);
        }
        $_POST['setting']['site.tax_ratio'] = $_POST['setting']['site.tax_ratio']/100;
        $storager = $this->system->loadModel('system/storager');
        if(!$_POST['setting']['site.logo']){
            unset($_POST['setting']['site.logo']);
        }
        if($_FILES ){
            foreach($_FILES['setting']['name'] as $k=>$name){
                $file = array(
                    'name'=>$name,
                    'type'=>$_FILES['setting']['type'][$k],
                    'tmp_name'=>$_FILES['setting']['tmp_name'][$k],
                    'error'=>$_FILES['setting']['error'][$k],
                    'size'=>$_FILES['setting']['size'][$k],
                );
                $_POST['setting'][$k] = $storager->save_upload($file,'default','logo');
                if(!$_POST['setting'][$k])unset($_POST['setting'][$k]);
            }
        }
        
        $this->end($this->settingEdit(),__('修改成功'));

        //$this->end($this->settingEdit(),__('修改成功'));
    }

    function _modified($src,$key){
        if(isset($src[$key]) && ($src[$key]!=$this->system->getConf($key))){
            return true;
        }else{
            return false;
        }
    }

    function shoppingBasicEdit(){
        $this->begin('index.php?ctl=system/setting&act=shoppingbasic');

        $_POST['setting']['site.tax_ratio'] = $_POST['setting']['site.tax_ratio']/100;
        $_POST['setting']['site.market_rate'] = $_POST['setting']['site.market_rate']?$_POST['setting']['site.market_rate']:0;
        $this->end($this->settingEdit(),__('修改成功'));
    }


    function settingEdit(){

        foreach($_POST['_set_'] as $key=>$type){
            if($type=='bool'){
                $_POST['setting'][$key] = $_POST['setting'][$key]?true:false;
            }
        }

        if($this->_modified($_POST['setting'],'site.stripHtml')){
            $frontend = $this->system->loadModel('system/frontend');
            $frontend->clear_compiled_tpl();
        }
        $this->system->setConf("readingGlass",$_POST['readingGlass']?1:0);

        if(isset($_POST['setting']['system.seo.emuStatic']) && $_POST['setting']['system.seo.emuStatic']){
            $svinfo = $this->system->loadModel('utility/serverinfo');

            $url = parse_url($this->system->base_url());
            $code = substr(md5(time()),0,6);
            $content = $svinfo->doHttpQuery($url['path']."/_test_rewrite=1&s=".$code."&a.html");

            if(!strpos($content,'[*['.md5($code).']*]')){

                if(false===strpos(strtolower($_SERVER["SERVER_SOFTWARE"]),'apache')){
                    trigger_error(__('您的服务器不是apache,无法使用htaccess文件。请手动启用rewrite，否则无法启用伪静态'),E_USER_ERROR);
                }

                if(file_exists(BASE_DIR.'/'.ACCESSFILENAME)){
                    trigger_error(__('您的系统存在无效的'.ACCESSFILENAME.', 无法启用伪静态'),E_USER_ERROR);
                }else{
                    if(($content = file_get_contents(BASE_DIR.'/root.htaccess'))){
                        $content = preg_replace('/RewriteBase\s+.*\//i','RewriteBase '.$url['path'],$content);
                        if(file_put_contents(BASE_DIR.'/'.ACCESSFILENAME,$content)){
                            $content = $svinfo->doHttpQuery($url['path']."/_test_rewrite=1&s=".$code."&a.html");
                            if(!strpos($content,'[*['.md5($code).']*]')){
                                unlink(BASE_DIR.'/'.ACCESSFILENAME);
                                trigger_error(__('您的系统不支持apache的'.ACCESSFILENAME.',启用伪静态失败.'),E_USER_ERROR);
                            }
                        }else{
                            trigger_error(__('无法自动生成'.ACCESSFILENAME.',可能是权限问题,启用伪静态失败'),E_USER_ERROR);
                        }
                    }else{
                        trigger_error(__('系统不支持rewrite,同时读取原始root.htaccess文件来生成目标'.ACCESSFILENAME.'文件,因此无法启用伪静态'),E_USER_ERROR);
                    }
                }
                trigger_error(__('不支持rewrite,放弃'),E_USER_ERROR);
            }
        }

        foreach($_POST['setting'] as $k=>$v){


            if(!$this->system->setConf($k,stripslashes($v))){

                trigger_error($k.__('设置错误'),E_USER_ERROR);
                return false;
            }

        }

        return true;
    }

    function settingRenew(){
        include_once('setting.php');
        foreach($setting as $k=>$v){
            $this->system->setConf($k,$v['default']);
        }
    }
    function addSettingPage(){
        $this->setView('setting/config/addSetting.html');
        $this->output();
    }
    function addSetting(){

        if($this->system->setConf($this->in['key'],$this->in['value'])){
            $this->splash('success','index.php?ctl=system/setting&act=config',__('添加成功'));
        }else{
            $this->splash('failed','index.php?ctl=system/setting&act=config',__('添加失败'));
        }
    }
    function delSetting(){
        $oSetting = $this->system->loadModel('system/setting');
        $list = $oSetting->finderResult($_POST['items']);
        if($oSetting->delSetting($list)){
            $this->splash('success','index.php?ctl=system/setting&act=config',__('删除成功'));
        }else{
            $this->splash('failed','index.php?ctl=system/setting&act=config',__('删除失败'));
        }
    }


    //配送部分
    function deliverList(){
        $oDly = $this->system->loadModel('trading/shipping');
        $aList['main'] = $oDly->getList();
        $aList['total'] = count($aList['main']);
        $this->system->output(json_encode($aList));
    }


    function watermark(){
        $this->path[] = array('text'=>'商品图片设置');
        $o=$this->system->loadModel('system/setting');
        $storager = $this->system->loadModel('system/storager');
        $ib=$this->system->loadModel('utility/magickwand');
        $this->system->setConf('system.watermark.lastcfg',time());
        if($ib->magickwand_loaded){
            $loaded = true;
        }else{
            $ib=$this->system->loadModel('utility/gdimage');
            $loaded = $ib->gd_loaded;
        }
        $this->pagedata['readingGlass'] = $this->system->getConf('site.reading_glass');
        $this->pagedata['readingGlassWidth'] = $this->system->getConf('site.reading_glass_width');
        $this->pagedata['readingGlassHeight'] = $this->system->getConf('site.reading_glass_height');
        $this->pagedata['thumbnail_pic_height'] = $this->system->getConf('site.thumbnail_pic_height');
        $this->pagedata['thumbnail_pic_width'] = $this->system->getConf('site.thumbnail_pic_width');
        $this->pagedata['small_pic_height'] = $this->system->getConf('site.small_pic_height');
        $this->pagedata['small_pic_width'] = $this->system->getConf('site.small_pic_width');
        $this->pagedata['big_pic_height'] = $this->system->getConf('site.big_pic_height');
        $this->pagedata['big_pic_width'] = $this->system->getConf('site.big_pic_width');
        $this->pagedata['default_thumbnail_pic'] = $storager->getUrl($this->system->getConf('site.default_thumbnail_pic'));
        $this->pagedata['default_small_pic'] = $storager->getUrl($this->system->getConf('site.default_small_pic'));
        $this->pagedata['default_big_pic'] = $storager->getUrl($this->system->getConf('site.default_big_pic'));
        if($loaded){
            $this->pagedata['gd_loaded'] = true;
            $this->pagedata['thumbnail_pic_height'] = $this->system->getConf('site.thumbnail_pic_height');
            $this->pagedata['wm_small_enable'] = $this->system->getConf('site.watermark.wm_small_enable');
            $this->pagedata['wm_small_text'] = $this->system->getConf('site.watermark.wm_small_text');
            $this->pagedata['wm_small_font'] = $this->system->getConf('site.watermark.wm_small_font');
            $this->pagedata['wm_small_font_size'] = $this->system->getConf('site.watermark.wm_small_font_size');
            $this->pagedata['wm_small_font_color'] = $this->system->getConf('site.watermark.wm_small_font_color');
            $this->pagedata['wm_small_pic'] = $storager->getUrl($this->system->getConf('site.watermark.wm_small_pic'));
            $this->pagedata['wm_small_loc'] = $this->system->getConf('site.watermark.wm_small_loc');
            $this->pagedata['wm_small_transition'] = $this->system->getConf('site.watermark.wm_small_transition');
            $this->pagedata['wm_big_enable'] = $this->system->getConf('site.watermark.wm_big_enable');
            $this->pagedata['wm_big_text'] = $this->system->getConf('site.watermark.wm_big_text');
            $this->pagedata['wm_big_font'] = $this->system->getConf('site.watermark.wm_big_font');
            $this->pagedata['wm_big_font_size'] = $this->system->getConf('site.watermark.wm_big_font_size');
            $this->pagedata['wm_big_font_color'] = $this->system->getConf('site.watermark.wm_big_font_color');
            $this->pagedata['wm_big_pic'] = $storager->getUrl($this->system->getConf('site.watermark.wm_big_pic'));
            $this->pagedata['wm_big_loc'] = $this->system->getConf('site.watermark.wm_big_loc');
            $this->pagedata['wm_big_transition'] = $this->system->getConf('site.watermark.wm_big_transition');
            $this->pagedata['enable_options'] = array('0'=>'关闭','1'=>'开启图片水印','2'=>'开启文字水印');
            $this->pagedata['wm_pos_options'] = array('0' => '居中','1' => '顶部居左','2' => '顶部居右','3' => '底部居右','4' => '底部居左','5' => '顶部居中','6' => '中部居右','7' => '底部居中','8' => '中部居左');
            $this->pagedata['wm_font_options'] = $o->getFontFile();
            $this->pagedata['spec_image_width'] = $this->system->getConf('spec.image.width');
            $this->pagedata['spec_image_height'] = $this->system->getConf('spec.image.height');
            $this->pagedata['spec_default_pic'] = $this->system->getConf('spec.default.pic');

        }else{
            $this->pagedata['gd_loaded'] = false;
        }
        $this->page('setting/site_watermark.html');
    }

    function watermarkEdit(){
        $this->begin('index.php?ctl=system/setting&act=watermark');
        $o=$this->system->loadModel('system/setting');
        $this->end($o->saveWatermarkCfg($_POST),'商品图片设置完成');
    }

    function watermarkPreview($tag){
        $ib=$this->system->loadModel('utility/magickwand');
        if($ib->magickwand_loaded){
            $loaded = true;
        }else{
            $ib=$this->system->loadModel('utility/gdimage');
            $loaded = $ib->gd_loaded;
        }
        $storager = $this->system->loadModel('system/storager');
        $ib->src_image_name = '../images/default/wm_sample.jpg';
        $ib->wm_image_name = '../'.$storager->getFile($this->system->getConf('site.watermark.wm_'.$tag.'_pic'));
        $ib->wm_image_transition = $this->system->getConf('site.watermark.wm_'.$tag.'_transition');
        $ib->wm_text =$this->system->getConf('site.watermark.wm_'.$tag.'_text');
        $ib->wm_text_size=$this->system->getConf('site.watermark.wm_'.$tag.'_font_size');
        $ib->wm_text_font = $this->system->getConf('site.watermark.wm_'.$tag.'_font');
        $ib->wm_text_color = $this->system->getConf('site.watermark.wm_'.$tag.'_font_color');
        $ib->wm_image_pos = $this->system->getConf('site.watermark.wm_'.$tag.'_loc');
        $enable = $this->system->getConf('site.watermark.wm_'.$tag.'_enable');
        $height = $this->system->getConf('site.'.$tag.'_pic_height');
        $width = $this->system->getConf('site.'.$tag.'_pic_width');

        switch($enable){
        case 0:
            header("Content-Type: text/html; charset=utf-8");
            exit('您还未设置水印，暂时无法查看效果。');
        case 1:
            $ib->jpeg_quality = 90;             //jpeg图片质量
            $ib->wm_text = '';
            $ib->makeThumbWatermark($width,$height);
            break;
        case 2:
            $ib->jpeg_quality = 90;             //jpeg图片质量
            $ib->wm_image_name = "";
            $ib->makeThumbWatermark($width,$height);
            break;
        }
    }

    function cert() {
        $this->path[] = array('text'=>'备案证书');
        $this->page('setting/cert.html');
    }

    function doCert() {
        $this->begin('index.php?ctl=system/setting&act=cert');
        $this->system->setConf('site.certtext', $_POST['setting']['site.certtext']);
        $this->end(true ,__('修改成功'));
    }

    function previewImg(){
        echo '<iframe name="_previewImg" id="previewImg" width="100%" height="100%" scrolling="yes" frameborder="0" border="0" marginwidth="0" marginheight="0"></iframe>';
    }

    function createPreviewImg($tag, $upload=false){
        header('Content-type: text/html;charset=utf-8');
        if($upload){
            if($_FILES['default_preview_pic']['tmp_name']){
                $pic = MEDIA_DIR.'/default/default_preview_pic.jpg';
                $upTemp=move_uploaded_file($_FILES['default_preview_pic']['tmp_name'],$pic);
                chmod($pic, 0755);
            }
echo '<form action="" method="post" enctype="multipart/form-data">';
echo '<div style="border:1px solid #bec6ce; font-size:12px; margin:5px;"><div style="margin:5px; margin-top:5px; padding:10px; background:#e2e8eb;"><div align=center style="height:30px; line-height:30px;">上传一张自己的商品图片查看效果：<input autocomplete="off" name="default_preview_pic" size="10" style="" class="_x_ipt file" vtype="file" type="file"><input type="submit" value="上传"/></div>';
echo '<input type="hidden" name="'.$tag.'_pic_width" value="'.$_POST[$tag.'_pic_width'].'"/>';
echo '<input type="hidden" name="'.$tag.'_pic_height" value="'.$_POST[$tag.'_pic_height'].'"/>';
echo '<div align=center style="font-weight:bold;height:30px; line-height:30px;">预览：</div>';
echo '<div align=center style="padding:10px;"><img border=1 src="../images/default/default_preview_pic.jpg?'.time().'" width='.$_POST[$tag.'_pic_width'].' height='.$_POST[$tag.'_pic_height'].'/></div></div></div>';
echo '</form>';
        }
        else{
            $ib=$this->system->loadModel('utility/magickwand');
            if($ib->magickwand_loaded){
                $loaded = true;
            }else{
                $ib=$this->system->loadModel('utility/gdimage');
                $loaded = $ib->gd_loaded;
            }
            $storager = $this->system->loadModel('system/storager');
            $ib->src_image_name = BASE_DIR.'/images/default/wm_sample.jpg';
            $ib->wm_image_name = ($_FILES['wm_'.$tag.'_pic']['tmp_name']?$_FILES['wm_'.$tag.'_pic']['tmp_name']:$storager->getFile($this->system->getConf('site.watermark.wm_'.$tag.'_pic')));                                                         // '../'.$storager->getFile($this->system->getConf('site.watermark.wm_'.$tag.'_pic'));
            $ib->wm_image_transition = $_POST['wm_'.$tag.'_transition'];     // $this->system->getConf('site.watermark.wm_'.$tag.'_transition');
            $ib->wm_text = $_POST['wm_'.$tag.'_text'];                              //$this->system->getConf('site.watermark.wm_'.$tag.'_text');
            $ib->wm_text_size=$_POST['wm_'.$tag.'_font_size'];                 //$this->system->getConf('site.watermark.wm_'.$tag.'_font_size');
            $ib->wm_text_font = $_POST['wm_'.$tag.'_font'];                      // $this->system->getConf('site.watermark.wm_'.$tag.'_font');
            $ib->wm_text_color = $_POST['wm_'.$tag.'_font_color'];            // $this->system->getConf('site.watermark.wm_'.$tag.'_font_color');
            $ib->wm_image_pos = $_POST['wm_'.$tag.'_loc'];         //$this->system->getConf('site.watermark.wm_'.$tag.'_loc');
            $enable = $_POST['wm_'.$tag.'_enable'];                                    // $this->system->getConf('site.watermark.wm_'.$tag.'_enable');
            $height = $_POST[$tag.'_pic_height'];                                       //$this->system->getConf('site.'.$tag.'_pic_height');
            $width = $_POST[$tag.'_pic_width'];                                         //$this->system->getConf('site.'.$tag.'_pic_width');

            switch($enable){
            case 0:
                header("Content-Type: text/html; charset=utf-8");
                exit('您还未设置水印，暂时无法查看效果。');
            case 1:
                $ib->jpeg_quality = 90;             //jpeg图片质量
                $ib->wm_text = '';
                $ib->makeThumbWatermark($width,$height);
                break;
            case 2:
                $ib->jpeg_quality = 90;             //jpeg图片质量
                $ib->wm_image_name = "";
                $ib->makeThumbWatermark($width,$height);
                break;
            }
        }

    }
}
?>
