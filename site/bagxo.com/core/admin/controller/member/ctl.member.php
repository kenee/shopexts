<?php
include_once('objectPage.php');
class ctl_member extends objectPage {

    var $name='会员';
    var $workground = 'member';
    var $object = 'member/member';
    var $actionView = 'member/finder_action.html'; //默认的动作html模板,可以为null
    var $filterView = 'member/finder_filter.html'; //默认的过滤器html,可以为null
    var $editMode = true;
    var $batchEdit = true;
    var $disableGridEditCols = "member_id,order_num,interest,uname,reg_ip,reg_time,state,unreadmsg,cur,lang,advance";
    var $disableColumnEditCols = "member_id,order_num,interest,uname,reg_ip,reg_time,state,unreadmsg,cur,lang,advance";
    var $disableGridShowCols = "member_id,order_num,interest,reg_ip,reg_time,state,unreadmsg,cur,lang";

    function index(){
        $oLev = $this->system->loadModel("member/level");
        $this->pagedata['member_lv'] = $oLev->getMLevel();
        $messenger = &$this->system->loadModel('system/messenger');
        $this->pagedata['messenger'] = $messenger->getList();
        parent::index();
    }

    function detail($nMId) {
        $memattr = $this->system->loadModel("member/memberattr");
        $oMsg = $this->system->loadModel("resources/msgbox");
        $oComm= $this->system->loadModel("comment/comment");
        $oOrder = $this->system->loadModel("trading/order");
        $oMem = $this->system->loadModel("member/member");
        $this->pagedata['messagenum'] = $oMsg->getTotalMsg($nMId);
        $this->pagedata['discussnum'] = $oComm->getTotalNum($nMId,'discuss');
        $this->pagedata['asknum'] = $oComm->getTotalNum($nMId,'ask');
        $tmpMem = $oMem->getBasicInfoById($nMId);
        if ($tmpMem){
            foreach($tmpMem as $key => $val){
                if ($key == "remark"){
                    $tmpMem[$key]=str_replace("\n","<br>",$val);
                }
            }
        }
        //取自定义注册项
        $attr = $memattr->getCustomValueById($nMId);
        $memberattrvalue = $oMem->getMemberAttrvalue($nMId);
        for($i=0;$i<count($attr);$i++){

        for($j=0;$j<count($memberattrvalue);$j++){
            if($attr[$i]['attr_id'] == $memberattrvalue[$j]['attr_id']){
                $attr[$i]['value'] = $memberattrvalue[$j]['value'];
                   if($attr[$i]['attr_type'] =='cal'){
                        $attr[$i]['value'] =  date('Y-m-d',$memberattrvalue[$j]['value']);
                   }
                   if($attr[$i]['attr_type'] =='checkbox'){
                        $date = $oMem->getattrvalue($nMId,$attr[$i]['attr_id']);
                            $attr[$i]['value'] = '';
                        foreach($date as $k => $v){
                            $attr[$i]['value'] .=  $date[$k]['value'].';';
                        }
                   }
            }

        }


        }
        $this->pagedata['custom'] = $attr;
        $this->pagedata['mem'] = $tmpMem;
        $this->pagedata['ordernum'] = 4;//$oOrder->getOrderNumbyMemberId($nMId);
        $this->pagedata['member_id'] = $nMId;
        $this->setView('member/member_items.html');
        $this->output();
    }

    function showModifyPoint($nMId) {
        $oMemberPoint = $this->system->loadModel('trading/memberPoint');
        $this->pagedata['point'] = $oMemberPoint->getMemberPoint($nMId);
        $this->pagedata['member_id'] = $nMId;
        $this->setView('member/modify_point.html');
        $this->output();
    }

    function modifyPoint() {
        //$this->begin('index.php?ctl=member/member&act=index');
        $this->begin('index.php?ctl=member/member&act=detail&p[0]='.$_POST['member_id']);
        if ($_POST['modify_point']){
            if ($_POST['member_id'] > 0) {
                $oMemberPoint = $this->system->loadModel('trading/memberPoint');
                $this->end($oMemberPoint->chgPoint($_POST['member_id'], $_POST['modify_point'], 'operator_adjust', $this->op->opid), __('修改成功！'));
            }else{
                $this->end(false, __('会员ID参数丢失'));
            }
        }else{
            $this->end(false, __('输入积分无效'));
        }

    }

    function modifyDeposit() {
        //$this->begin('index.php?ctl=member/member&act=index');
        $this->begin('index.php?ctl=member/member&act=advance&p[0]='.$_POST['member_id']);
        if ($_POST['modify_advance']){
            if ($_POST['member_id'] > 0) {
                $oAdv = $this->system->loadModel('member/advance');
                if($_POST['modify_advance'] > 0){
                    $this->end($oAdv->add($_POST['member_id'],$_POST['modify_advance'],$_POST['modify_memo'],__('修改成功！'), '', '' ,'' ,$this->op->loginName.'代充值'));
                }else{
                    $this->end($oAdv->deduct($_POST['member_id'],abs($_POST['modify_advance']),$_POST['modify_memo'],__('修改成功！'), '', '' ,'' ,$this->op->loginName.'代扣费'));
                }
            }else{
                $this->end(false, __('会员ID参数丢失'));
            }
        }else{
            $this->end(false, __('输入预存款金额无效'));
        }
    }

    function showPointHistory($nMId) {
        $oPointHistory = $this->system->loadModel('trading/pointHistory');
        $this->pagedata['historys'] = $oPointHistory->getPointHistoryList($nMId);

        $this->setView('member/sub_point_history.html');
        $this->output();
    }

    function detailExtInfo($nMId){
        $oMem = $this->system->loadModel("member/member");
        $aItems = $oMem->getExtInfoById($nMId);
        $this->pagedata['mem'] = $aItems;
        $this->setView('member/sub_ext_info.html');
        $this->output();
    }

    function detailEdit($nMId){
        $oMem = $this->system->loadModel("member/member");
        $aItems = $oMem->getBasicInfoById($nMId);
        if($aItems){
            foreach($aItems as $key=>$val){
                switch($key){
                    case 'score_rate':
                        $aItems[$key]=dateFormat($val);
                    break;
                    default:
                    break;
                }
            }
        }
        $this->pagedata['member'] = $aItems;
        $this->pagedata['member']['birthday'] = $aItems['b_year'].'-'.$aItems['b_month'].'-'.$aItems['b_day'];
        $aLevel = array();
        $oLev = $this->system->loadModel("member/level");
        foreach($oLev->getMLevel() as $aItems){
            $aLevel[$aItems['member_lv_id']] = $aItems['name'];
        }
        $this->pagedata['member']['level'] = $aLevel;
        $Memattr = $this->system->loadModel("member/memberattr");

        $filter['attr_show'] = 'true';
        $attr = $Memattr->getList('*',$filter,0,-1,$count,array('attr_order','asc'));
        $memberinfo = $oMem->getMemberByid($nMId);
        $memberattrvalue = $oMem->getMemberAttrvalue($nMId);


        for($i=0;$i<count($attr);$i++){

        if($attr[$i]['attr_type'] =='checkbox'||$attr[$i]['attr_type'] =='select'){

            $attr[$i]['attr_option'] = unserialize($attr[$i]['attr_option']);

           }

        if($attr[$i]['attr_group'] == 'defalut'){

        switch($attr[$i]['attr_type']){
        case 'area':
        $attr[$i]['value'] = $memberinfo[0]['area'];
        $regionId=substr($memberinfo[0]['area'],strrpos($memberinfo[0]['area'],":")+1);
        $dArea=$this->system->loadModel('trading/deliveryarea');
        $row=$dArea->getById($regionId);
        if ($row)
            $attr[$i]['rStatus']=true;
        break;
        case 'name':
        $attr[$i]['value'] = $memberinfo[0]['name'];
        break;
        case 'mobile':
        $attr[$i]['value'] = $memberinfo[0]['mobile'];
        break;
        case 'tel':
        $attr[$i]['value'] = $memberinfo[0]['tel'];
        break;
        case 'zip':
        $attr[$i]['value'] = $memberinfo[0]['zip'];
        break;
        case 'addr':
        $attr[$i]['value'] = $memberinfo[0]['addr'];
        break;
        case 'sex':
        $attr[$i]['value'] = $memberinfo[0]['sex'];
        break;
        case 'date':
        $attr[$i]['value'] = $memberinfo[0]['b_year'].'-'.$memberinfo[0]['b_month'].'-'.$memberinfo[0]['b_day'];
        if($attr[$i]['value']=='--'){
            $attr[$i]['value']='';
        }
        break;
        case 'pw_answer':
        $attr[$i]['value'] = $memberinfo[0]['pw_answer'];
        break;
        case 'pw_question':
        $attr[$i]['value'] = $memberinfo[0]['pw_question'];
        break;
        }
        }else{


         for($j=0;$j<count($memberattrvalue);$j++){
            if($attr[$i]['attr_id'] == $memberattrvalue[$j]['attr_id']){
                $attr[$i]['value'] = $memberattrvalue[$j]['value'];
                   if($attr[$i]['attr_type'] =='cal'){
                        $attr[$i]['value'] =  intval($memberattrvalue[$j]['value']);
                   }
                   if($attr[$i]['attr_type'] =='checkbox'){
                        $date = $oMem->getattrvalue($nMId,$attr[$i]['attr_id']);
                        $attr[$i]['value'] =  $date;
                   }
              }

         }


         }

         }


        $this->pagedata['tree'] = $attr;
        $this->setView('member/sub_edit.html');
        $this->output();
    }

    function detailMsg($nMId){
        $oMsg = $this->system->loadModel('resources/msgbox');
        $aMsg = $oMsg->getMsgListByMemId($nMId);
        $this->pagedata['items'] = $aMsg;
        $this->pagedata['member_id'] = $nMId;
        $this->setView('member/sub_message.html');
        $this->output();
    }

    function detailComm($nMId, $item){
        $oComm = $this->system->loadModel('comment/comment');
        $aComm = $oComm->getCommListByMemId($nMId, $item);
        $this->pagedata['items'] = $aComm;
        $this->pagedata['member_id'] = $nMId;
        $this->setView('member/sub_review.html');
        $this->output();
    }

    function detailOrders($nMId){
        $oOrder = $this->system->loadModel("trading/order");
        $aOrder = $oOrder->getOrderListByMemberId($nMId);
        $this->pagedata['items'] = $aOrder;
        $this->pagedata['member_id'] = $nMId;
        $this->setView('member/sub_orders.html');
        $this->output();
    }

    function advance($nMId){
        $oAdv = $this->system->loadModel('member/advance');
        $advList = $oAdv->getFrontAdvList($nMId,0,10);

        $this->pagedata['itemstotal'] = $advList['total'];
        $this->pagedata['items'] = $advList['data'];
        $oMem = $this->system->loadModel("member/member");
        $this->pagedata['member'] = $oMem->getBasicInfoById($nMId);
        $this->setView('member/advance_list.html');
        $this->output();
    }

    function save(){
        $oMem = $this->system->loadModel("member/member");
        foreach($_POST as $key=>$val){
            switch($key){
                case 'regtime':
                    $this->in[$key]=strtotime($val);
                break;
                case 'score_rate':
                    $this->in[$key]=strtotime($val);
                break;
                case 'pay_time':
                    $this->in[$key]=strtotime($val);
                break;
                default:
                break;
            }

        }
         $this->begin('index.php?ctl=member/member&act=detailEdit&p[0]='.$_POST['member_id']);
        if($_POST['member_id'] > 0){
         if($_POST['birthday']){
                $aTmp = explode('-', $_POST['birthday']);
                $_POST['b_year'] = $aTmp[0];
                $_POST['b_month'] = $aTmp[1];
                $_POST['b_day'] = $aTmp[2];
            }
          $allkeys = array_keys($_POST);
          $count = 0;
          for($i=0;$i<count($allkeys);$i++){
          if(is_numeric($allkeys[$i])){
          if(!is_array($_POST[$allkeys[$i]])){
          $memattr[$count]['member_id'] = $_POST['member_id'];
          $memattr[$count]['attr_id'] = $allkeys[$i];
          $memattr[$count]['value'] = htmlspecialchars($_POST[$allkeys[$i]]);
          $oMem->updateMemAttr($_POST['member_id'],$allkeys[$i],$memattr[$count]);
          $count++;

           }else{
          $tmp = $_POST[$allkeys[$i]];
          $oMem->deleteMattrvalues($allkeys[$i],$_POST['member_id']);
          for($j=0;$j<count($tmp);$j++){
          $tmpdate['member_id'] = $_POST['member_id'];
          $tmpdate['attr_id'] = $allkeys[$i];
          $tmpdate['value'] = htmlspecialchars($tmp[$j]);
          $oMem->saveMemAttr($tmpdate);

           }
          }
          }
          }
            $this->end($oMem->save($_POST['member_id'],$_POST), __('修改成功！'));
        }else{
            $this->end(false, __('会员信息丢失，保存失败！'));
        }
    }

    //客户留言
    function message(){
        $this->pagedata['options'] =array('to_type'=>1,'to_id'=>$this->op->opid,'for_id'=>0);
        $this->pagedata['to_type'] =1;
        $this->pagedata['to_id'] = $this->op->opid;
        $this->pagedata['for_id'] =0;
        $this->page('member/message/msg_list.html');
    }

    function delMessage(){
        $oMsg = $this->system->loadModel("resources/msgbox");
        $aMid = $oMsg->finderResult($_POST['items']);
        if($oMsg->delMessage($aMid)){
            $this->splash('success','index.php?ctl=member/msgbox&act=index',__('删除成功！'));
        }else{
            $this->splash('success','index.php?ctl=member/msgbox&act=index',__('删除成功！'));
        }
    }

    function msgList(){
        $sKeyword=$this->in['keyword'];
        $oMsg = $this->system->loadModel("resources/msgbox");
        $aMsgList = $oMsg->getAdminMsgList($sKeyword);
        $this->system->output(json_encode($aMsgList));
    }

    function msgItems($nMsgId){
        //exit($nMsgId.'dd');
        $oMsg = $this->system->loadModel("resources/msgbox");
        $aData = $oMsg->getMsgByIdBGM($nMsgId);
        $this->pagedata['user'] = $aData;
        $this->pagedata['revert'] = $aData['revert'] != ''?$oMsg->getRevertById($nMsgId):'';
        $this->pagedata['msg_id'] = $nMsgId;
        $this->setView('member/message/msg_items.html');
        $this->output();
    }

    function msgHtmlItems($nMsgId){
        //exit($nMsgId.'dd');
        $oMsg = $this->system->loadModel("resources/msgbox");
        $aData = $oMsg->getMsgByIdBGM($nMsgId);
        $this->pagedata['user'] = $aData;
        $this->pagedata['revert'] = $aData['revert'] != ''?$oMsg->getRevertById($nMsgId):'';
        $this->pagedata['msg_id'] = $nMsgId;
        $this->page('member/message/msg_html_items.html');
    }

    function toReply($nMsgId){
        $oMem = $this->system->loadModel("member/member");
        $ok = $oMem->insertReply($_POST['reply'],$nMsgId,$message);
        if($ok){
            $this->message = array('string'=>__('回复成功！'),'type'=>MSG_OK);
        }else{
            $this->message = array('string'=>__('回复失败！'),'type'=>MSG_ERROR);
        }
        //$this->ajax();
    }

    function showNew(){
        $oLev = $this->system->loadModel("member/level");
        $this->pagedata['memLv'] = $oLev->getMLevel();
        $this->pagedata['defLv'] = $oLev->getDefauleLv();

        $mematt = $this->system->loadModel('member/memberattr');
        $filter['attr_show'] = 'true';
        $tmpdate =$mematt->getList('*',$filter,0,-1,$count,array('attr_order','asc'));

        for($i=0;$i<count($tmpdate);$i++){
            if($tmpdate[$i]['attr_type'] == 'select'||$tmpdate[$i]['attr_type'] == 'checkbox'){
                $tmpdate[$i]['attr_option'] = unserialize($tmpdate[$i]['attr_option']);
            }
        }
        $this->pagedata['tree'] = $tmpdate;
        $this->path[] = array('text'=>'添加会员');
        $this->page('member/member_new.html');
    }

    function addMemByAdmin(){

        $this->begin('index.php?ctl=member/member&act=index');
        $oMem = $this->system->loadModel("member/member");
        $_POST['sex'] = intval($_POST['sex']);
        if($_POST['birthday']){
                $aTmp = explode('-', $_POST['birthday']);
                $_POST['b_year'] = $aTmp[0];
                $_POST['b_month'] = $aTmp[1];
                $_POST['b_day'] = $aTmp[2];
        }
        $id = $oMem->addMemberByAdmin($_POST);
        if($id!=''){
        $allkeys = array_keys($_POST);
        $count = 0;
        for($i=0;$i<count($allkeys);$i++){
        if(is_numeric($allkeys[$i])){
            if(is_array($_POST[$allkeys[$i]])){
            $ar = $_POST[$allkeys[$i]];
            for($j=0;$j<count($ar);$j++){
            $arra[0]['member_id'] = $id;
            $arra[0]['attr_id'] = $allkeys[$i];
            $arra[0]['value'] = htmlspecialchars($ar[$j]);
            $oMem->saveMemAttr($arra[0]);
        }

        }else{
            $memattr[$count]['member_id'] = $id;
            $memattr[$count]['attr_id'] = $allkeys[$i];
            $memattr[$count]['value'] = htmlspecialchars($_POST[$allkeys[$i]]);
            $oMem->saveMemAttr($memattr[$count]);
     }


      $count++;
     }
     }

      $this->end(true, __('添加成功！'));
      }else{
      $this->end(false, __('添加成功！'));
      }
    }
    function Remark($nMId){
        $oMem=$this->system->loadModel("member/member");
        $tmpdata = $oMem->getRemark($nMId);
        $this->pagedata['member_id']=$nMId;
        $this->pagedata['remark'] = $tmpdata['remark'];
        $this->pagedata['remark_type'] = $tmpdata['remark_type'];
        $this->setView('member/remark.html');
        $this->output();
    }
    function addRemark($nMId){
        //$this->begin('index.php?ctl=member/member&act=index');
        $this->begin('index.php?ctl=member/member&act=Remark&p[0]='.$nMId);
        $oMem=$this->system->loadModel("member/member");
        $this->end($oMem->addRemark($nMId,$_POST),__('添加成功！'));
    }
    function updatePassword($nMId,$email,$uname,$name){
        $this->pagedata['member_id'] = $nMId;
        $this->pagedata['email'] = $email;
        $this->pagedata['uname'] = $uname;
        $this->pagedata['name'] = $name;
        $this->setView('member/sub_password.html');
        $this->output();
    }
    function toUpdatePassword($nMId,$email,$uname,$name){
        $name = $_POST['name'];
        $errinfo = "";
        if (strlen($_POST['newPassword'])<4)
            $errinfo = "新密码不能小于4位";
        elseif (strlen($_POST['confirmPassword'])<4)
            $errinfo = "确认密码不能小于4位";
        elseif ($_POST['newPassword']<>$_POST['confirmPassword'])
            $errinfo = "您两次输入的密码不一样，请重新输入。";
        if(!empty($errinfo)){
            $this->splash('failed','index.php?ctl=member/member&act=updatePassword&p[0]='.$nMId.'&p[1]='.$email,__($errinfo));
            exit;
        }
        $oMem = $this->system->loadModel('member/account');
        if ($oMem->adminUpdateMemberPassword($nMId,array('password'=>md5(trim($_POST['newPassword'])),'passwd'=>$_POST['newPassword'],'email'=>$email,'uname'=>$uname,'name'=>$name),$_POST['sendemail'])){
            echo "<div class='success'>密码修改成功!</div><script>$('editMemberPassword-'+$nMId).retrieve('dialog').close.delay(300,$('editMemberPassword-'+$nMId).retrieve('dialog'))</script>";
        }
        else{
            $this->splash('failed','index.php?ctl=member/member&act=updatePassword&p[0]='.$nMId.'&p[1]='.$email,__('密码更新操作失败！'));
        }
    }

      function finder($type,$view,$cols,$finder_id,$limit){

        set_error_handler(array(&$this,'finder_error_handler'));
        $finder_id = stripslashes($finder_id);

        $finder = &$this->model;
        $finder->setFinderCols(get_object_vars($this));

        $page = $_GET['page']?$_GET['page']:1;
        $cols = $this->op->get('view.'.$this->object);
        if(!$cols) $cols = $this->model->defaultCols;

        $orderBy = array($_GET['_finder']['orderBy'],$_GET['_finder']['orderType']);
        if($_GET['_finder']['disabledMark']=='recycle'){
            $finder->disabledMark = 'recycle';
        }

        $this->pagedata['items'] = $finder->getFinder($cols,$_REQUEST,($page-1)*$limit,$limit,$count,$orderBy,null,$_GET['_finder']['editMode']);

        if($colOrder = $this->op->get('listColOrder.'.$this->object)){
            array_multisort($this->pagedata['items']['allCols'],SORT_NUMERIC ,explode(',',$colOrder));
        }

        $this->pagedata['_finder'] = $_GET['_finder'];

        if(!$_GET['_finder']['without_rowselect'] && method_exists($this,'detail')){
            $this->pagedata['_finder']['rowselect'] = true;
        }

        if($_GET['_finder']['withTools']){
            $this->pagedata['_finder']['tools'] = $this->_actions();
        }

        if($finder->hasTag){
            $this->pagedata['_finder']['tagList'] = &$finder->tagList();
        }

        $this->pagedata['_finder']['pager']=array(
            'current'=>$page,
            'total'=>ceil($count/$limit),
            'link'=>'javascript:'.$finder_id.'.jumpTo.bind('.$finder_id.')(_PPP_)',
            'token'=>'_PPP_'
        );

        $this->pagedata['_finder']['listOnly'] = true;
        $this->pagedata['_finder']['view'] = $view;
        $this->pagedata['_finder']['var'] = $finder_id;
        $this->pagedata['_finder']['count'] = $count;
        $this->pagedata['_finder']['order'] = $orderBy;
        if($_GET['notools'])unset($this->pagedata['_finder']['tools']);
        $this->setView('finder/list.html');
        $this->output();
    }

    function recycle(){
        $memberHasAdvanceNums = $this->model->checkMemberHasAdvance($_POST);
        $_POST['minadvance'] = 0;
        $_POST['maxadvance'] = 0.001;
        $rs = $this->model->recycle($_POST);

        $returnStr = '';
        if( $memberHasAdvanceNums > 0 ){
            $returnStr = $memberHasAdvanceNums.'个会员预付款余额不为零，无法删除。请先扣除预付款，再删除会员。';
        }else{
            if($rs)
                $returnStr = '选定记录删入回收站';
            else
                $returnStr = '选定记录无法删入回收站';
        }
        echo $returnStr;
    }

    function delete() {
        $memberHasAdvanceNums = $this->model->checkMemberHasAdvance($_POST,'recycle');
        $_POST['minadvance'] = 0;
        $_POST['maxadvance'] = 0.001;
        $rs = $this->model->delete($_POST);

        $returnStr = '';
        if( $memberHasAdvanceNums > 0 ){
            $returnStr = $memberHasAdvanceNums.'个会员预付款余额不为零，无法删除。请先扣除预付款，再删除会员。';
        }else{
            if($rs){
                $returnStr = '选定记录已删除成功!';
            }else{
                $returnStr = '选定记录无法删除!';
            }
        }
        echo $returnStr;
    }

    function orderInfo($order_id){
        $this->pagedata['order_id'] = $order_id;
        $this->setView('member/member_ordertab.html');
        $this->output();
    }
}
?>
