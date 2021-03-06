<?php
$constants = array(
        'OBJ_PRODUCT'=>1,
        'OBJ_ARTICLE'=>2,
        'OBJ_SHOP'=>0,
        'MIME_HTML'=>'text/html',
        'P_ENUM'=>1,
        'P_SHORT'=>2,
        'P_TEXT'=>3,
        'SCHEMA_DIR'=>PLUGIN_DIR.'/schema/',
        'HOOK_BREAK_ALL'=>-1,
        'HOOK_FAILED'=>0,
        'HOOK_SUCCESS'=>1,
        'MSG_OK'=>true,
        'MSG_WARNING'=>E_WARNING,
        'MSG_ERROR'=>E_ERROR,
        'MNU_LINK'=>0,
        'MNU_BROWSER'=>1,
        'MNU_PRODUCT'=>2,
        'MNU_ARTICLE'=>3,
        'MNU_ART_CAT'=>4,
        'MNU_TAG'=>5,
        'TABLE_REGEX'=>'([]0-9a-z_\:\"\`\.\@\[-]*)',
        'PMT_SCHEME_PROMOTION'=>0,
        'PMT_SCHEME_COUPON'=>1,
        'APP_ROOT_PHP'=>'',
        'SET_T_STR'=>0,
        'SET_T_INT'=>1,
        'SET_T_ENUM'=>2,
        'SET_T_BOOL'=>3,
        'SET_T_TXT'=>4,
        'SET_T_FILE'=>5,
        'SET_T_DIGITS'=>6,
        'LC_MESSAGES'=>6,
        'BASE_LANG'=>'zh_CN',
        'DEFAULT_LANG'=>'zh_CN',
        'DEFAULT_INDEX'=>'',
        'ACCESSFILENAME'=>'.htaccess',
        'DEBUG_TEMPLETE'=>false,
        'PRINTER_FONTS'=>'',
        'PHP_SELF'=>(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']),
        'APP_WLTX_ID'=>'se_001',
        'APP_WLTX_VERSION'=>'ve_001',
        'APP_WLTX_URL'=>'http://service.shopex.cn/api.php'
    );

foreach($constants as $k=>$v){
    define($k,$v);
}

$_SERVER['REQUEST_URI'] = 'http://'.$_SERVER['HTTP_HOST'].$phpself.($_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'');
?>