<?php
class mdl_task extends modelFactory{

    function init($model,$method,$onSuccess,$onFailed,$task,$title,$args){
        $_GET['_t'] = md5($this->system->session->sess_id.rand(0,time()));
        $_SESSION['task'][$_GET['_t']] = array(
            'model'=>$model,
            'method'=>$method,
            'task'=>$task,
            'title'=>$title,
            'args'=>$args,
            'succ'=>$onSuccess,
            'failed'=>$onFailed,
            'total'=>count($task)
        );
        $_GET['_s'] = 0;
        $smarty = &$this->system->loadModel('system/frontend');
        $smarty->assign('task',array(
            'title'=>$title,
            'total'=>count($task),
        ));
        $smarty->display('admin:task.html');
    }

    function run(){
        if(!$_GET['_t'] || !($task=$_SESSION['task'][$_GET['_t']])){
            unset($_SESSION['task'][$_GET['_t']]);
            exit('error taskid');
        }else{
            if($model = $this->system->loadModel($task['model'])){
                if($task['total']==$_GET['_s']-1){
                    if($task['succ']){
                        $task['message'] = call_user_func_array(array($model,$task['succ']),$task['args']);
                    }
                    unset($_SESSION['task'][$_GET['_t']]);
                }else{
                    $this->system->session->close();
                    array_unshift($task['args'],$task['task'][$_GET['_s']-1]);
                    call_user_func_array(array($model,$task['method']),$task['args']);
                }
                $smarty = &$this->system->loadModel('system/frontend');
                $smarty->assign('task',$task);
                $smarty->display('admin:task.html');
            }else{
                unset($_SESSION['task'][$_GET['_t']]);
                exit('error model');
            }
        }
    }

}
?>
