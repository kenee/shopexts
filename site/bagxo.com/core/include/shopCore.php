<?php
require_once(CORE_DIR.'/kernel.php');
define('COOKIE_PFIX','S');
define('MAKE_DIR',true);

class shopCore extends kernel{

    var $member = null;
    var $is_shop = true;
    var $_err = array();
    var $ErrorSet = array();
    var $use_gzip = true;
    var $page;

    function shopCore(){

        parent::kernel();

        if(isset($_GET['_test_rewrite'])){
            echo '[*['.md5($_GET['s']).']*]';
            exit;
        }
        if(defined('MODE_SWITCHER')){
            $mode_switcher = MODE_SWITCHER;
            require_once(PLUGIN_DIR.'/functions/'.$switcher.'.php');
            $switcher = new $mode_switcher;
            if(!$switcher->test()){
                header('Content-type: text/html;charset=utf-8',true,503);
                readfile(HOME_DIR.'/notice.html');
            }
        }elseif(file_exists(HOME_DIR.'/notice.html')){
            header('Content-type: text/html;charset=utf-8',true,503);
            readfile(HOME_DIR.'/notice.html');
            exit;
        }

        if(file_exists(BASE_DIR.'/upgrade.php')){
            header('HTTP/1.1 503 Service Unavailable',true,503);
            require(CORE_DIR.'/func_ext.php');
            $smarty = &$this->loadModel('system/frontend');
            $smarty->display('shop:common/upgrade.html');
        }else{
            $this->run();
        }
    }

    function compactUrl($newurl){
        $this->_succ=true;
        header('Location: '.$newurl,true,301);
        exit;
    }

    /**
     * shop
     *
     * @access public
     * @return void
     */
    function run(){

        if(isset($_GET['gOo'])){
            $urlTools = &$this->loadModel('utility/url');
            if($url=$urlTools->oldVersionShopEx($_GET)){
                $this->compactUrl($url);
            }
        }

        ob_start();
        define('IN_SHOP',true);


        $_COOKIE = $_COOKIE[COOKIE_PFIX];

        $request = $this->parseRequest();
        $this->lang = $request['lang']?$request['lang']:DEFAULT_LANG;
        $request['money'] = $request['member_lv'].$request['cur'];

        $this->request = &$request;

        $GLOBALS['runtime'] = $request;
        if(isset($request['member'])){
            foreach($request['member'] as $k=>$v){
                $GLOBALS['runtime'][$k] = $v;
            }
        }

        $cacheAble = !(count($_POST)>0);

        if(defined('BLACKLIST')){
            $blackList = preg_split('/[\s,]+/',BLACKLIST);
            require_once(CORE_DIR.'/func_ext.php');
            if($this->match_network($blackList,remote_addr())){
                $this->_succ = true;
                header('Connection: close',true,401);
                echo '<h1>Access Denied</h1>';
                exit();
            }
        }

        if(isset($_GET['ctl'])){
            $page = &$this->_frontend($request,array(
                'controller'=>$_GET['ctl'],
                'method'=>isset($_GET['act'])?$_GET['act']:'index',
                'args'=>isset($_GET['p'])?$_GET['p']:null));
        }elseif(!$cacheAble || !$this->cache->get($ident = implode('|',$request),$page)){
            register_shutdown_function(array(&$this,'shutdown'));
            $this->co_start();
            $page = &$this->_frontend($request);
            if($cacheAble && $page['cache']){
                $this->cache->set($ident,$page,$this->co_end());
            }
        }

        $this->display($page);
        exit();
    }

    function match_network ($nets, $ip, $first=false) {
        $return = false;
        if (!is_array ($nets)) $nets = array ($nets);

        foreach ($nets as $net) {
            $rev = (preg_match ("/^\!/", $net)) ? true : false;
            $net = preg_replace ("/^\!/", "", $net);

            $ip_arr  = explode('/', $net);
            $net_long = ip2long($ip_arr[0]);
            $x        = ip2long($ip_arr[1]);
            $mask    = long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
            $ip_long  = ip2long($ip);

            if ($rev) {
                if (($ip_long & $mask) == ($net_long & $mask)) return false;
            } else {
                if (($ip_long & $mask) == ($net_long & $mask)) $return = true;
                if ($first && $return) return true;
            }
        }
        return $return;
    }

    function errorHandler($errno, $errstr, $errfile, $errline){
        $this->_errArr[] = array('no'=>$errno,'msg'=>$errstr,'file'=>$errfile,'line'=>$errline);
        if($errno == ((E_ERROR | E_USER_ERROR) & $errno)){
            $this->shutdown(true);
        }
        return true;
    }

    function shutdown($halt=false){
        if($halt || !isset($this->_succ)){
            $this->_succ=true;
            $data = array('html'=>'','body'=>'','date'=>date("Y-m-d H:i:s (T)"),'fatal'=>null);
            $errorlevels = array(
                2048 => 'Error',
                1024 => 'Notice',
                512 => 'Warning',
                256 => 'Error',
                128 => 'Warning',
                64 => 'Error',
                32 => 'Warning',
                16 => 'Error',
                8 => 'Notice',
                4 => 'Error',
                2 => 'Warning',
                1 => 'Error');

            while(ob_get_level()>0){
                $data['body'] .= ob_get_contents();
                ob_end_clean();
            }

            if(!$halt){
                if($pos = strrpos($data['body'],'Fatal error')){
                    $data['fatal'].=substr($data['body'],$pos);
                }elseif($pos = strrpos($data['body'],'Parse error')){
                    $data['fatal'].=substr($data['body'],$pos);
                }
            }else{
                $err = array_pop($this->_errArr);
                $data['fatal'].= "<li class=\"err_{$err['no']}\"><b class=\"no\">{$errorlevels[$err['no']]}:</b> <span class=\"body\">{$err['msg']}<span>{$err['file']}:{$err['line']}</li>";
            }

            foreach($this->_errArr as $err){
                $data['html'].= "<li class=\"err_{$err['no']}\"><b class=\"no\">{$errorlevels[$err['no']]}:</b> <span class=\"body\">{$err['msg']}<span>{$err['file']}:{$err['line']}</li>";
            }

            if($data['fatal']){
                $this->responseCode(500);
                $data['msg'] = is_file(HOME_DIR.'/upload/error500.html')?file_get_contents(HOME_DIR.'/upload/error500.html'):'系统暂时发生错误，请回到首页重新访问';
                $html  = file_get_contents(CORE_DIR.'/shop/view/page/system-error.html');
                foreach($data as $k=>$v){
                    $html = str_replace('%'.$k.'%',$v,$html);
                }
                echo $html;
            }else{
                echo $data['html'].$data['body'];
            }

            if(function_exists('debug_backtrace')){
                echo '<ol>';
                $lastfile = null;
                foreach(debug_backtrace() as $trace){
                    if(isset($trace['file']) && $trace['file']!=$lastfile){
                        $lastfile = $trace['file'];
                        echo '<div style="padding-top:10px;color:#999">'.$trace['file'].':'.$trace['line'].'</div>';
                    }
                    echo '<li style="font-weight:bold">'.(isset($trace['class'])?$trace['class']:'php').'::'.$trace['function'].'()</li>';
                }
                echo '</ol>';
            }


            exit();
        }
    }

    function setCookie($name,$value,$expire=false,$path=null){
        if(!$this->_cookiePath){
            $cookieLife = $this->getConf('system.cookie.life');
            $this->_cookiePath = substr(PHP_SELF, 0, strrpos(PHP_SELF, '/')).'/';
            $this->_cookieLife = $cookieLife;

        }
        $this->_cookieLife = ($this->_cookieLife>0)?$this->_cookieLife:315360000;
        setCookie(COOKIE_PFIX.'['.$name.']',$value,($expire===false)?(time()+$this->_cookieLife):$expire,$this->_cookiePath);
        $_COOKIE[$name] = $value;
    }

    function display(&$pageObj){

        $this->_succ = true;
        $header_sent = headers_sent();
        //    header('Runtime: '.($this->microtime() - $this->_start));
        header('Connection: close');

        if($pageObj['cache']){
            header("Cache-Control: private");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//            header("Expires: " .gmdate("D, d M Y H:i:s", time() + (isset($pageObj['cachettl'])?$pageObj['cachettl']:1)). " GMT");
        }else{
            header("Cache-Control: no-cache, no-store, must-revalidate"); // 强制更新
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Pragma: no-cache");
        }

        if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $pageObj['header']['Etag']){
            header('Etag: '.$pageObj['header']['Etag'],true,304);
            exit(0);
        }

        foreach($pageObj['header'] as $k=>$v){
            header($k.': '.$v);
        }

        if($pageObj['gziped'] && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && !$header_sent){
            if(strpos(" ".$_SERVER['HTTP_ACCEPT_ENCODING'],"gzip")){
                header('Content-Encoding: gzip');
                header('Content-Length: '.$pageObj['gziped-size']);
                if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') exit(0);
                echo $pageObj['gziped'];
            }elseif(strpos(" ".$_SERVER['HTTP_ACCEPT_ENCODING'],"x-gzip")){
                header('Content-Encoding: x-gzip');
                header('Content-Length: '.$pageObj['gziped-size']);
                if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') exit(0);
                echo $pageObj['gziped'];
            }else{
                header('Content-Length: '.$pageObj['size']);
                if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') exit(0);
                echo $pageObj['body'];
            }
        }else{
            header('Content-Length: '.$pageObj['size']);
            if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') exit(0);
            echo $pageObj['body'];
        }
        exit();
    }

    function mkUrl($ctl,$act='index',$args=null,$extName = 'html'){
        return $this->realUrl($ctl,$act,$args,$extName,$this->request['base_url']);
    }

    function parseUrl($query){
        return ($query=='index.html')
            ?array('controller'=>'page','method'=>'index','args'=>array(),'type'=>'html')
            :$this->call($this->getConf('system.seo.parselink'),array($query));
    }

    function &_frontend($request,$action=null){
        require_once(CORE_DIR.'/func_ext.php');
        ob_start();
        if(!$action)$action = $this->parseUrl($request['query']);
        $this->request['action'] = &$action;

        require_once('shopPage.php');
        $controller = &$this->getController($action['controller']);
        $controller->action = &$action;
        $this->ctl = &$controller;

        if(!is_object($controller))$this->error(404);

        $this->use_gzip = (function_exists('gzencode') && ($this->getConf('system.use_gzip') === true  ||  $this->getConf('system.use_gzip')!='false'));
        $controller->_header = &$page['header'];

        if(!$this->callAction($controller,$action['method'],$action['args'])){
            $urlTools = &$this->loadModel('utility/url');
            if($newurl = $urlTools->map($_SERVER['QUERY_STRING'])){
                $this->compactUrl($newurl);
            }else{
                $this->error(404);
            }
        }
        $page = array(
            'header'=>array('Content-Language'=>'utf-8'),
            'cache'=>!$controller->noCache,
            'body'=>'',
            'size'=>0,
        );

        $this->_succ = true;
        $ob_length=ob_get_level()-1;
        while(ob_get_level()>0){
            if($ob_length==ob_get_level()){
                break;
            }else{
                $ob_length=ob_get_level();
            }
            $page['size'] += ob_get_length();
            $page['body'] .= ob_get_contents();
            ob_end_clean();
        }

        if(isset($controller->cachettl)) $page['cachettl'] = $controller->cachettl; //
        if(isset($this->_expiresTime)) $page['expires'] = $this->_expiresTime;

        $page['header']['Etag'] = crc32($page['body']);
        $page['header']['Last-Modified'] = gmdate('D, d M Y H:i:s').' GMT';

        $this->_debugger['runtime'] = $this->microtime() - $this->_start;
        $this->_debugger['gzip'] = $this->use_gzip;

        $page['header']['Content-type'] = $controller->contentType;
        if($this->use_gzip){
            if($page['gziped'] = @gzencode($page['body'], 3)){
                $page['gziped-size'] = strlen($page['gziped']);
            }
        }

        return $page;
    }

    function setExpries($time){
        if($time>time()){
            $this->_expiresTime = isset($this->_expiresTime)?min($time,$this->_expiresTime):$time;
        }
        return true;
    }

    function &getController($mod){

        $object = false;
        $fname = CORE_DIR.'/shop/controller/'.dirname($mod).'/ctl.'.basename($mod).'.php';
        if (defined('CUSTOM_CORE_DIR')){
            $cusfname = CUSTOM_CORE_DIR.'/shop/controller/'.dirname($mod).'/cct.'.basename($mod).'.php';
            if (file_exists($fname))
                require($fname);
            if (file_exists($cusfname)){
                require($cusfname);
                $mod_name='cct_'.basename($mod);
            }
            else{
                $mod_name = 'ctl_'.basename($mod);
            }
            if(class_exists($mod_name)){
                $object = new $mod_name($this);
                return $object;
            }else{
                $this->error(404);
            }
            
        }
        else{
            if(!is_file($fname)){
                $this->error(404);
            }else{
                require($fname);
                $mod_name = 'ctl_'.basename($mod);
                $object = new $mod_name($this);
                return $object;
            }
        }

    }

    function error($code){
        if($code==404){
            $this->responseCode(404);
            $this->_succ=true;
            header("Content-Type: text/html; charset=utf-8");
            echo $this->getConf('errorpage.p404');
        }else{
            $this->responseCode(500);
            header("Content-Type: text/html; charset=utf-8");
            echo $this->getConf('errorpage.p500');
        }
        die();
    }

    function _build_post($d,$path=null){
        $m='';
        foreach($d as $k=>$v){
            $p = $path?$path.'['.$k.']':$k;
            if(is_array($v)){
                $m .= $this->_build_post($v,$p);
            }else{
                $m .='<input type="hidden" name="'.$p.'" value="'.$v.'" />';
            }
        }
        return $m;
    }

    /*对请求进行解析
    返回数组:
    设置_GET
     */
    function parseRequest($query = null){

        if(!$query){
            $query = $_SERVER["QUERY_STRING"];
            if(!($REQUEST_URI = getenv('REQUEST_URI'))){
                if(isset($_SERVER['HTTP_X_REWRITE_URL'])){
                    $REQUEST_URI = $_SERVER['HTTP_X_REWRITE_URL']?$_SERVER['HTTP_X_REWRITE_URL']:$_SERVER['REQUEST_URI'];
                }else{
                    $REQUEST_URI = $_SERVER['REQUEST_URI'];
                }
            }
        }

        $get = null;
        if($p = strpos($query,'?')){
            $get = substr($query,$p+1);
            $query = substr($query,0,$p);
        }else{
            $p = parse_url($REQUEST_URI);
            if(isset($p['query']))$get = $p['query'];
        }
        if($get!=$query){
            parse_str($get,$get);
            $_GET = array_merge($_GET,$get);
        }

        $url_prefix = $this->base_url();

        return array(
            'base_url'=>$url_prefix,
            'member_lv'=>isset($_COOKIE['MLV'])?$_COOKIE['MLV']:-1,
            'query'=>$query?$query:'index.html',
            'cur'=>isset($_COOKIE['CUR'])?$_COOKIE['CUR']:null,
            'lang'=>isset($_COOKIE['LANG'])?$_COOKIE['LANG']:null
        );
    }

    function location($url){
        if($_POST){
            $html="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"
                \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
                <html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\" dir=\"ltr\">
                <head></header><body>Redirecting...";
            $html .= '<form id="splash" action="'.$url.'" method="post">'.$this->_build_post($_POST);
            $html.=<<<EOF
</form><script language="javascript">
document.getElementById('splash').submit();
</script></html>
EOF;
            echo $html;
            exit();
        }else{
            header('Location: '.$url);
            exit();
        }
    }
}
?>
