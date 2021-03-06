<?php
    class ctl_domainbind extends adminPage{
        var $workground ='setting';
        function index(){
            $this->path[] = array('text'=>'域名绑定');            
            $sass = $this->system->loadModel('service/saas');
            $result = $sass->get_info();
            $i=0;
            if(is_array($result)){
                foreach($result['alias'] as $k =>$v){
                    switch($v){
                        case 'wait':
                            $nowresult[$i]['alias'] = $k;
                            $nowresult[$i]['status']='待审核';
                            $i++;
                        break;
                        case 'verify':
                            $nowresult[$i]['alias'] = $k;
                            $nowresult[$i]['status']='已生效';
                            $i++;
                        break;
                        case 'false':
                            $nowresult[$i]['alias'] = $k;
                            $nowresult[$i]['status']='未通过验证';
                            $i++;
                        break;
                    }
                }
                $this->pagedata['host_name'] = $result['host_name'];
                $this->pagedata['result1'] = $nowresult[0];
                $this->pagedata['result2'] = $nowresult[1];
            }else{
                $this->pagedata['err_msg'] = $result;
            }
            $this->page('service/domainbind/index.html');
            $this->output();
        }

        function canceldomainBinding($alias){
            $this->begin('index.php?ctl=service/domainbind&act=index');
            $sass = $this->system->loadModel('service/saas');
            $message = $sass->del_alias($alias);
            $this->pagedata['message'] = $message;
            if($message===true){
                 $this->end(true,'取消绑定成功');
                 exit;
            }
            $this->end(false,$message);
        }

        function adddomainBinding($message=''){
            $this->setView('service/domainbind/add.html');
            if($message!=''){
                $this->pagedata['url'] = $message;
            }
            $this->output();
        }

        function result(){
            $sass = $this->system->loadModel('service/saas');
            if(!preg_match('/\//',$_POST['domain'])&&$_POST['domain']!=''){
                $result = $sass->add_alias($_POST['domain']);
                if($_POST['edit']=='true'){
                    $result = $sass->retry_alias($_POST['domain']);
                }               
                if($result===true){
                    $this->pagedata['result'] = 'true';    
                }else{
                    $this->pagedata['result'] = 'false';
                    $this->pagedata['msg'] = $result;
                };
            }else{
                $this->pagedata['result'] = 'false';
                $this->pagedata['msg'] = '对不起你的域名格式非法';
            }
            $this->setView('service/domainbind/result.html');
            $this->output();
        }







    }


?>