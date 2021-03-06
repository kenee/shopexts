<?php
class dazuiLog{
    var $firstLog = true;
    function dazuiLog(){
        $system = &$GLOBALS['system'];
        $map = array(
                'date'=>date('Y-m-d'),
                'worker'=>'shop',
                'controller'=>isset($system->request['action']['controller'])?$system->request['action']['controller']:'-',
                'method'=>isset($system->request['action']['method'])?$system->request['action']['method']:'-',
                'query'=>isset($system->request['query'])?$system->request['query']:'-',
                'ip'=>remote_addr()
            );

        foreach($map as $k=>$v){
            $find[] = '/\{'.$k.'\}/i';
        }
        $this->file = preg_replace($find,$map,LOG_FILE);
        if(!is_dir($dir = dirname($this->file))){
            mkdir_p($dir);
        }

        $this->logStr = create_function('$e', 'return "'.preg_replace(array_merge($find,array(
                    '/\{time\}/i',
                    '/\{gmt\}/i',
                    '/\{code\}/i',
                    '/\{msg\}/i',
                )),
                array_merge($map,array(
                    '".mydate(\'h:i:s\')."',
                    '[".mydate(\'r\')."]',
                    '".str_pad($e[\'code\'],4,0, STR_PAD_LEFT)."',
                    '".str_replace("\n",\'\n\',$e[\'msg\'])."',
                )),str_replace('"','\"',LOG_FORMAT)).'";');
    }

    function log($code,$message='-'){
        $logStr = $this->logStr;
        $log = "\n".$logStr(array('code'=>$code,'msg'=>$message));
        //�������request��ַ
        if($this->firstLog){
            $msg = $_SERVER['PHP_SELF'].(strlen($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'');
            $log = "\n".$logStr(array('code'=>0,'msg'=>$msg));
            error_log($log, 3, $this->file);
            $this->firstLog = false;
            if(defined('LOG_HEAD_TEXT') && !file_exists($this->file)){
                $log = LOG_HEAD_TEXT.$log;
            }
        }
        error_log($log, 3, $this->file);
    }

    function tail($name,$time=null){

        $actions=array();
        $fname = $this->path.'/'.$name.'.php';
        $handle = @fopen($fname,'r');
        if ($handle) {
            if(!$time)$time = time()-3600;

            $seekto = 0;
            $fsize = filesize($fname);
            $step = 512;

            while($fsize + $seekto - $step >0){
                fseek($handle,$seekto - $step,SEEK_END);
                fgets($handle);
                $buffer = fgets($handle);
                preg_match('#\[([0-9]{10})\]#',$buffer,$marches);
                if($marches[1]<$time){
                    break;
                }
                $seekto-=$step;
            }
            while (!feof($handle)) {
                    $buffer = fgets($handle, 4096);
                    preg_match('#\[([0-9]{10})\]\t#',$buffer,$marches);
                    if($marches[1]>=$time){
                        $actions[]=$buffer;
                    }
            }

            fclose($handle);
            return $actions;

        }else{
            return false;
        }
    }
}
?>
