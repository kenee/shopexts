<?php
/**
 * ctl_payment
 *
 * @uses pageFactory
 * @package
 * @version $Id: ctl.payment.php 1867 2008-04-23 04:00:24Z flaboy $
 * @copyright 2003-2007 ShopEx
 * @author Likunpeng <leoleegood@zovatech.com>
 * @license Commercial
 */
include_once('objectPage.php');
class ctl_payment extends objectPage {

    var $name='支付';
    var $workground ='setting';
    var $actionView = 'payment/finder_action.html';
    var $object = 'trading/paymentcfg';
    var $editMode = true;
    
    var $disableGridEditCols = "id";
    var $disableColumnEditCols = "id";
    var $disableGridShowCols = "id";
    /**
    * main
    *
    * @access public
    * @return void
    */
    
    
    function detail($id){
        $this->path[] = array('text'=>'友情链接编辑');
        $oPay = $this->system->loadModel('trading/payment');
        
        $aPay = $oPay->getPaymentById($id);
        $this->pagedata['pay'] = $aPay;
        
        $this->pagedata['pay_info'] = $this->_getPayOpt($aPay['pay_type'], $aPay['custom_name'], $aPay['fee'], $aPay['config']);

        $oPlu = $oPay->loadMethod($aPay['pay_type']);
        if($oPlu){
            $this->pagedata['html'] =  $oPlu->infoPad();
        }

        $this->pagedata['pay_id'] = $id;
        $this->pagedata['order'] = $aPay['orderlist'];
        $this->pagedata['old_pay_type'] = $aPay['pay_type'];
        $this->pagedata['pay_des'] = $aPay['des'];
        $this->pagedata['pay_name'] = $aPay['custom_name'];
        $this->pagedata['paylist'] = $oPay->getPluginsArr(true);
        $this->setView('payment/pay_edit.html');
        $this->output();
    }
    /**
    * main
    *
    * @access public
    * @return void
    */
    function getPayList(){
        $this->path[] = array('text'=>'支付方式');
        $oPay = $this->system->loadModel('trading/payment');
        $this->pagedata['items'] = $oPay->getMethods();
        $this->page('payment/pay_list.html');
    }
    
    function _getHtmlString($key,$val,$rs=array()){
        $sJS = '';
        switch($val['type']){
            case 'string':
                $sJS .= '<tr><th>'.$val['label'].'：</th><td><input type="text" name="'.$key.'"'.($rs[$key]?' value="'.$rs[$key].'"':'').' /></td></tr>';
            break;
            case 'select':
                $sJS .= '<tr><th>'.$val['label'].'：</th><td><select name="'.$key.'">';
                foreach($val['options'] as $k=>$v){

                    $sJS .= '<option value="'.$k.'" '.(($rs[$key]==$k)?'selected':'').'>'.$v.'</option>';
                }
                $sJS .= '</select></td></tr>';
            break;
            case 'number':
                $sJS .= '<tr><th>'.$val['label'].'：</th><td><input type="text" name="'.$key.'"'.($rs[$key]?',value="'.$rs[$key].'"':'').' /></td></tr>';
            break;
            case 'file':
                $sJS .= '<tr><th>'.$val['label'].'：</th><td><input type="file" name="'.$key.'" /></td></tr>';
            break;
            default:
                $sJS .= '<tr><th>'.$val['label'].'：</th><td><input type="text" name="'.$key.'"'.($rs[$key]?',value="'.$rs[$key].'"':'').' /></td></tr>';
            break;
        }
        return $sJS;
    }

    /**
    * savePayment
    *
    * @access public
    * @return void
    */
    function savePayment(){
        if($_POST['pay_id']){
            $this->begin('index.php?ctl=trading/payment&act=detail&p[0]='.$_POST['pay_id']);
        }else{
            $this->begin('index.php?ctl=trading/payment&act=index');
        }
        $oPay = $this->system->loadModel('trading/payment');
        $_POST['fee'] = $_POST['fee'] / 100;
        if ($_FILES){//是否有文件上传
            $file=$this->system->loadModel("system/sfile");
            foreach($_FILES as $key => $val){
                if (intval($val['size'])>0){
                    $_POST[$key]=$val['name'];
                    switch ($_POST['pay_type']){
                        case "ICBC"://工商银行
                            if ($key=="keyFile"){//商户私钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="key"){
                                    trigger_error(__('商户私钥文件格式有误,请上传key格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            elseif ($key == "certFile"||$key =="icbcFile"){//商户公钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="crt"){
                                    if($key=="certFile")
                                        trigger_error(__('商户公钥文件格式有误,请上传crt格式文件'),E_USER_ERROR);
                                    else
                                        trigger_error(__('工行公钥文件格式有误,请上传crt格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            break;
                        case "HYL"://广东好易联
                            if ($key == "keyFile"){//私钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="pem"){
                                    trigger_error(__('私钥文件格式有误,请上传key格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            elseif ($key == "certFile"){//公钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="cer"){
                                    trigger_error(__('公钥文件格式有误,请上传cer格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            break;
                        default:
                            break;

                    }
                    $file->UploadPaymentFile($val,$_POST['pay_type']);//上传支付相关文件
                }
            }
        }
        $this->end($oPay->updatePay($_POST), __('保存成功！'));
    }

    /**
    * addpayment
    *
    * @access public
    * @return void
    */
    function addPayment(){
        $this->begin('index.php?ctl=trading/payment&act=index');
        $oPay = $this->system->loadModel('trading/payment');
        $_POST['fee'] = $_POST['fee'] / 100;
        if ($_FILES){//是否有文件上传
            $file=$this->system->loadModel("system/sfile");
            foreach($_FILES as $key => $val){
                if (intval($val['size'])>0){
                    $_POST[$key]=$val['name'];
                    switch ($_POST['pay_type']){
                        case "ICBC"://工商银行
                            if ($key=="keyFile"){//商户私钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="key"){
                                    trigger_error(__('文件格式有误,请上传key格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            elseif ($key == "certFile"||$key =="icbcFile"){//商户公钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="crt"){
                                    trigger_error(__('文件格式有误,请上传crt格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            break;
                        case "HYL"://广东好易联
                            if ($key == "keyFile"){//私钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="pem"){
                                    trigger_error(__('文件格式有误,请上传pem格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            elseif ($key == "certFile"){//公钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="cer"){
                                    trigger_error(__('文件格式有误,请上传cer格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            break;
                        case "skypay":
                            if ($key=="keyFile" || $key =="certFile"){//私钥文件
                                if(substr($val['name'],strrpos($val['name'],".")+1,strlen($val['name']))!="key"){
                                    trigger_error(__('文件格式有误,请上传key格式文件'),E_USER_ERROR);
                                    exit;
                                }
                            }
                            break;
                        default:
                            break;

                    }
                    $file->UploadPaymentFile($val,$_POST['pay_type']);//上传支付相关文件
                }
            }
        }
        $this->end($oPay->insertPay($_POST,$msg),$msg);
    }

    /**
    * addpayment
    *
    * @access public
    * @return void
    */
    function delPayment($sId){
        $this->begin('index.php?ctl=trading/payment&act=index');
        $oPay = $this->system->loadModel('trading/payment');
        $this->end($oPay->deletePay($sId),__('删除成功！'));
    }
    /**
    * newPayment
    *
    * @access public
    * @return void
    */
    function newPayment(){
        $this->path[] = array('text'=>'添加支付方式');
        $oPay = $this->system->loadModel('trading/payment');
        $this->pagedata['paylist'] = $oPay->getPluginsArr(true);
        $this->page('payment/pay_new.html');
    }
    
    /**
    * detailPayment
    *
    * @access public
    * @return void
    */
    function detailPayment($id){
        $this->path[] = array('text'=>'支付方式配置');
        $oPay = $this->system->loadModel('trading/payment');
        //$oPay->getPluginsArr(true);
        $aPay = $oPay->getPaymentById($id);
        $this->pagedata['pay'] = $aPay;
        $this->pagedata['pay_info'] = $this->_getPayOpt($aPay['pay_type'], $aPay['custom_name'], $aPay['fee'], $aPay['config']);
        $this->pagedata['pay_id'] = $id;
        $this->pagedata['order'] = $aPay['orderlist'];
        $this->pagedata['old_pay_type'] = $aPay['pay_type'];
        $this->pagedata['pay_des'] = $aPay['des'];
        $this->pagedata['pay_name'] = $aPay['custom_name'];
        $this->pagedata['paylist'] = $oPay->getPluginsArr(true);
        $this->page('payment/pay_edit.html');
    }
    
    /**
    * getPayOpt
    *
    * @access public
    * @return void
    */
    function getPayOpt($sType, $sPayName=''){
        header('Content-Type: text/html;charset=utf-8');
        if(!$sType){
            echo ' ';
        }else{

            echo $this->_getPayOpt($sType, $sPayName);
        }
    }
    
    function _getPayOpt($sType, $sPayName='', $nFee='', $config=''){
        $sStr = '';
        $sHtml = '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><th>支付方式名称：</th><td><input type="text" name="custom_name" value="'.$sPayName.'" /></td>';
        $oPay = $this->system->loadModel('trading/payment');
        $oPlu = $oPay->loadMethod($sType);
        if($aThisPayCur = $oPay->getSupportCur($oPlu)){
            if($aThisPayCur['DEFAULT']){
                $sStr = '商店默认货币';
            }else{
                $oCur = $this->system->loadModel('system/cur');
                $aCurLang = $oCur->getSysCur();
                if($aThisPayCur['ALL']){
                    $aThisPayCur = $aCurLang;
                }
                foreach($aThisPayCur as $k=>$v){
                    $sStr .= $aCurLang[$k].",&nbsp;";
                }
            }
        }
        $sHtml .= '<tr><th>支持交易货币：</th><td>'.($sStr?rtrim($sStr,',&nbsp;'):'').'</td></tr>';
        if($oPlu){
            $aTemp = unserialize($config);
            if($aTemp){
                foreach($aTemp as $key=>$val){
                    $aPay[$key]=$val;
                }
            }
            $aField = $oPlu->getfields();
            foreach($aField as $key=>$val){
                $sHtml .= $this->_getHtmlString($key,$val,$aPay);
            }
        }
        return $sHtml .='<tr><th>支付费率：</th><td><input type="text" name="fee" style="width:50px" value="'.($nFee*100).'" />%<span class="notice_inline">说明：顾客将承担的订单支付手续费比率</span></td></tr></table>';
    }

    function infoPad($pid){
        header('Content-Type: text/html;charset=utf-8');
        if(!$pid){
            echo ' ';
        }else{
            $oPay = $this->system->loadModel('trading/payment');
            $oPlu = $oPay->loadMethod($pid);
            $infoPad = '';
            if($oPlu){
                $infoPad = $oPlu->infoPad();
            }
            echo $infoPad;
        }
    }
}
?>
