<?php
/**
 * AloneDB{ 
 * 
 * @package 
 * @version $Id: AloneDB.php 1214 2008-03-26 10:18:46Z flaboy $
 * @copyright 2003-2007 ShopEx
 * @author Bryant <bryant@zovatech.com> 
 */
define('PAGELIMIT', 20);
define('SQL_MYISAM_SYNTAX','type = MyISAM DEFAULT CHARACTER SET utf8');
define('SQL_HEAP_SYNTAX','type = HEAP DEFAULT CHARACTER SET utf8');

class AloneDB{

    var $__rw_instance = null;
    var $__ro_instance = null;
    var $_lastErr = null;
    var $_instance = null;
    var $prefix = null;
    var $_cache_ident = null;
    var $table = array();

    function AloneDB(){
        $this->system = &$GLOBALS['system'];
    }

    function info(){
        $return['rw'] = &$this->_info($this->__rw_instance);

        if($this->__ro_instance){
            $return['ro'] = &$this->_info($this->__ro_instance);
        }
        return $return;
    }

    function &_info($rs){
        $rs = $rs->execute('show status');
        $ret = array();
        foreach($rs->GetArray() as $row){
            $ret[$row['Variable_name']] = $row['Value'];
        }
        return $ret;
    }

    function &rwInstance($noAlert=false){
        if(!$this->__rw_instance){
            if(defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_HOST')){
                $this->__rw_instance = &$this->instance(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
                if(!$this->__rw_instance && !$noAlert ){
                    trigger_error($this->error,E_USER_ERROR);
                }
            }else{
                trigger_error('Error rw_instance',E_USER_ERROR);
            }
        }
        return $this->__rw_instance;
    }

    function &roInstance(){
        if(defined('DB_SLAVE_HOST')){
            if(!$this->__ro_instance){
                if($this->__ro_instance = &$this->instance(DB_SLAVE_HOST,DB_SLAVE_USER,DB_SLAVE_PASSWORD,DB_SLAVE_NAME)){
                    return $this->__ro_instance;
                }else{
                    trigger_error('error slave database,using master database<'.DB_HOST.':'.DB_NAME.'>',E_USER_NOTICE);
                }
            }
        }
        return $this->rwInstance();
    }

    function affect_row(){
         return $this->_instance->Affected_Rows();
    }

    /**
     * &instance 
     * 
     * @param string $host 
     * @param string $username 
     * @param string $password 
     * @param string $database 
     * @param mixed $forcenew 
     * @access public
     * @return void
     */
    function &instance( $host = "", $username = "", $password = "", $database = "", $forcenew = false) {
        $db = ADONewConnection();
        if (!$db->Connect($host , $username , $password , $database)) {
            $this->error = $db->ErrorMsg();
            $db = false;
        }else{
            if(preg_match('/[0-9\.]+/is',mysql_get_server_info(),$match)){
                $dbver = $match[0];
                if(version_compare($dbver,'4.1.1','<')){
                    $this->dbver = 3;
                }else{
                    $db->execute('SET NAMES \''.DB_CHARSET.'\'');
                    if(!version_compare($dbver,'6','<')){
                        $this->dbver = 6;
                    }
                }
            }
        }
        return $db;
    }

    function select_b($queryString, $pageLimit=null) {/*PERPAGE*/
        if (defined('__ADMIN__')) {
            $_data['total'] = $this->_count($queryString);
            if($pageLimit==null) {
                $_data = &$this->select($queryString);
            } else {
                $pageStart = intval($_POST['start']);
                $_data['main'] = $this->selectLimit($queryString, $pageLimit, $pageStart);
            }
        }else{
            $_data = &$this->select($queryString);
        }
        return $_data;
    }

    function select_f($queryString,$pageStart=null,$pageLimit=null) {
        $_data['total'] = $this->_count($queryString);
        $_data['page'] = ceil($_data['total']/$pageLimit);
        if($pageLimit==null) {
            $_data = &$this->select($queryString);
        } else {
            $_data['data'] = $this->selectLimit($queryString, $pageLimit, $pageStart*$pageLimit);
        }
        return $_data;
    }

    /**
     * selectrow 
     * 
     * @param mixed $queryString 
     * @param int $timeout 
     * @param mixed $ident 
     * @access public
     * @return void
     */
    function selectrow($queryString,$noCache=true,$rw=false){
        return $this->_fetch($queryString,$noCache,$rw);
    }

    /**
     * GetInsertSQL 
     * 
     * @param mixed $result 
     * @param mixed $data 
     * @param mixed $autoup 
     * @access public
     * @return void
     */
    function GetInsertSQL(&$result, $data,$autoup=false) {

        if(!$result){

        }

        if(is_object($data)){
            $data = get_object_vars($data);
        }

        $this->_instance = &$this->rwInstance();

        foreach($data as $key=>$value){
            $data[strtolower($key)]=$value;
        }

        if (preg_match('/FROM\s+'.TABLE_REGEX.'/is', $this->_instance->sql, $tableName))
            $tableName = $tableName[1];

        if($autoup){
            $keyColumn = $result->FetchField(0);
            if(!$data[strtolower($keyColumn['name'])]){
                $rs = $this->exec('SELECT MAX('.$keyColumn['name'].') AS keyid FROM '.$tableName);
                if ($rs->Move())
                {
                    $row = $rs->fields();
                }
                $data[$keyColumn['name']]= $row['keyid'] + 1;
            }
        }

        for($i=0;$i<$result->FieldCount();$i++){
            $column = $result->FetchField($i);
            if(isset($data[$column->name])){
                $insertValues[$column->name] = $this->_quotevalue($data[$column->name],$column->type,$this->_instance);
            }
        }

        $strValue = implode(',',$insertValues);
        $strFields = implode('`,`',array_keys($insertValues));
        return 'INSERT INTO `'.$tableName.'` ( `'.$strFields.'` ) VALUES ( '.$strValue.' )';
    }


    /**
     * GetUpdateSQL 
     * 
     * @param mixed $result 
     * @param mixed $data 
     * @param mixed $InsertIfNoResult 
     * @access public
     * @return void
     */
    function GetUpdateSQL(&$result, $data, $InsertIfNoResult = false,$insertData=null,$ignore=false) {

        if(is_object($data)){
            $data = get_object_vars($data);
        }

        $this->_instance = &$this->rwInstance();

        if(!$result){
            trigger_error('GetUpdateSQL: '.$this->_instance->sql.' error '.$this->errorInfo(),E_USER_ERROR);
        }

        if (preg_match('/FROM\s+'.TABLE_REGEX.'/is', $this->_instance->sql, $tableName))
            $tableName = $tableName[1];

        $result->Move(0);
        $row = $result->GetArray(1);

        if($InsertIfNoResult){
            if(count($row)==0){
                return $this->GetInsertSQL($result,$insertData?$insertData:$data);
            }
        }

        foreach($data as $key=>$value){
            $data[strtolower($key)]=$value;
        }

        $oldKey = '';
        for($i=0;$i<$result->FieldCount();$i++){
            $column = $result->FetchField($i);
            if(array_key_exists($column->name,$data) && ($ignore || $data[$column->name]!==$row[0][$column->name] || $column->type == 'bool')){
                if(is_array($data[$column->name]) || is_object($data[$column->name])){
                    if(serialize($data[$column->name])==$row[0][$column->name]){
                        continue;
                    }
                }
                $UpdateValues[] ='`'.$column->name.'`='.$this->_quotevalue($data[$column->name],$column->type,$this->_instance);
            }
        }

        if (@count($UpdateValues)>0) {

            $whereClause = $this->_whereClause($this->_instance->sql);

            $sql = 'UPDATE `'.$tableName.'` SET '.implode(',',$UpdateValues);
            if (strlen($whereClause) > 0) 
                $sql .= ' WHERE '.$whereClause;

            return $sql;

        } else {
            return false;
        }
    }

    /**
     * _fetch 
     * 
     * @param mixed $sql 
     * @access protected
     * @return void
     */
    function _fetch($sql,$noCache=true,$rw=false){
        $data = $this->selectLimit($sql,1,0,null,$rw,$noCache);
        if($data){
            return $data[0];
        }else{
            return false;
        }        
    }

    /**
     * selectLimit 
     * 
     * @param mixed $sql 
     * @param mixed $numrows 
     * @param mixed $offset 
     * @param mixed $inputarr 
     * @access protected
     * @return void
     */
    function selectLimit($sql,$numrows=-1,$offset=0,$inputarr=false,$rw=false,$noCache=true){
        if($rw)
            $this->_instance = &$this->rwInstance();
        else
            $this->_instance = &$this->roInstance();

        $cache = &$this->system->cache;
        $this->table = array();
        $sql = preg_replace_callback('/([`\s\(,])(sdb_)([a-z\_]+)([`\s\.]{0,1})/is',array(&$this,'_prepareSelect'),$sql);

        $rs = $this->_instance->selectLimit($sql,$numrows,$offset,$inputarr);
        if($rs){
            $data = $rs->GetArray();
            $rs = null;
        }else{
            trigger_error('SQL ERROR:'.$this->_instance->sql.' '.$this->_instance->ErrorMsg(),E_USER_WARNING);
            return false;
        }
        
        return $data;
    }

    function _prepareSelect($matches){
        $tableName = ($this->prefix?$this->prefix:'sdb_').$matches[3];
        if(defined('IN_SHOP')) $this->system->checkExpries($tableName);
        $this->table = array_merge($this->table,array(strtoupper($tableName)));
        return $matches[1].$tableName.$matches[4];
    }

    /**
     * getRows 
     * 
     * @param mixed $rs 
     * @access public
     * @return void
     */
    function getRows(&$rs,$num=-1){
        return $rs->GetArray($num);
    }

    function &_select($sql,$noCache=true,$rw=false){
        $rs = $this->exec($sql,$noCache,$rw);
        if($rs) {
            $data = &$rs->GetArray();
            $rs=null;
            return $data;
        }else{
            trigger_error('SQL ERROR:'.$this->_instance->sql.' '.$this->_instance->ErrorMsg(),E_USER_WARNING);
            return false;
        }
    }

    /**
     * select 
     * 
     * @param mixed $sql 
     * @access protected
     * @return void
     */
    function &select($sql,$noCache=true,$rw=false) {
        $this->_instance = &$this->roInstance();
        $this->table = array();
        $sql = preg_replace_callback('/([`\s\(,])(sdb_)([a-z\_]+)([`\s\.]{0,1})/is',array(&$this,'_prepareSelect'),$sql);
        $row = &$this->_select($sql,$noCache,$rw);
        return $row;
    }


    /**
     * _count 
     * 
     * @access protected
     * @return void
     */
    function _count($sql) {
        $sql = preg_replace(array(
            '/(.*\s)LIMIT .*/i',
            '/select\s+(.+?)\bfrom\b/is'
        ),array(
            '\\1',
            'select count(*) as c from'
        ),$sql);
        $rs = $this->select($sql);
        return intval($rs[0]['c']);
    }

    function quote($string){
        $db = &$this->roInstance();
        return $db->qstr($string,true);
    }

    /**
     * _quotevalue 
     * 
     * @param mixed $value 
     * @param mixed $valuedef 
     * @access protected
     * @return void
     */
    function _quotevalue($value,$valuedef,&$db){

        if(null===$value){
            return 'null';
        }

        switch($valuedef){
        case 'bool':
            return (strtolower($value)!='false' && $value || (is_int($value) && $value>0))?'true':'false';
            break;

        case 'int':
        case 'real':
            $value = trim($value);
            return (!$value && !($value===0 || $value==="0" || $value===floatval(0)))?'null':$value;
            break;

        default:
            if(isset($value)){
                if(is_array($value)){
                    return $db->qstr(addslashes(serialize($value)),true);
                }else{
                    return $db->qstr($value,true);
                }
            }else{
                return 'null';
            }
            break;
        }
    }

    /**
     * _whereClause 
     * 
     * @param mixed $queryString 
     * @access protected
     * @return void
     */
    function _whereClause($queryString){

        preg_match('/\sWHERE\s(.*)/is', $queryString, $whereClause);

        $discard = false;
        if ($whereClause) {
            if (preg_match('/\s(ORDER\s.*)/is', $whereClause[1], $discard));
            else if (preg_match('/\s(LIMIT\s.*)/is', $whereClause[1], $discard));
            else preg_match('/\s(FOR UPDATE.*)/is', $whereClause[1], $discard);
        } else
            $whereClause = array(false,false);

        if ($discard)
            $whereClause[1] = substr($whereClause[1], 0, strlen($whereClause[1]) - strlen($discard[1]));
        return $whereClause[1];
    }

    function fixTableName($sql){
        if($this->prefix)
            return preg_replace('/([`\s\(,])(sdb_)([a-z\_]+)([`\s\.]{0,1})/is',"\${1}".$this->prefix."\\3\\4",$sql);
        else
            return $sql;
    }

    function &exec( $sql , $skipModifiedMark = false,$rw=null){
        if(empty($sql)) return true;    //if sql is empty, return true
        if(!is_null($rw)){
            if($rw){
                $this->_instance = &$this->rwInstance();
            }else{
                $this->_instance = &$this->roInstance();
            }
        }elseif(!$this->_instance){
            $this->_instance = &$this->rwInstance();
        }

        $sql = $this->fixTableName($sql);

        if(!$skipModifiedMark){
            if(preg_match("/^\s*(delete|insert)\s+(from|into)\s*".TABLE_REGEX.".*/is", $sql, $_matches)){
                $this->modified($_matches[3]);
            }elseif (preg_match("/^\s*(update)\s+".TABLE_REGEX."\s+set.*/is",$sql,$_matches)){
                $this->modified($_matches[2]);
            }
        }

        if($ret = $this->_instance->execute($sql)){
            return $ret;
        }else{
            trigger_error('SQL ERROR:'.$this->_instance->sql.' '.$this->_instance->ErrorMsg(),E_USER_WARNING);
            return false;
        }
    }

    function modified($tables){
        if(substr($tables,-11)!='op_sessions'){
            $this->system->cache->setModified(strtoupper(trim(str_replace('`','',str_replace('"','',str_replace("'",'',$tables))))));
        }
    }

    function splitSql($sql) {
        $ret = array();
        $sql          = trim($sql);
        $sql_len      = strlen($sql);
        $char         = '';
        $string_start = '';
        $in_string    = FALSE;

        $sql = trim($sql);
        if($this->dbver == 3){
            $sql = str_replace(SQL_MYISAM_SYNTAX,'',$sql);
            $sql = str_replace(SQL_HEAP_SYNTAX,'',$sql);
        }elseif($this->dbver == 6){
            $sql = str_replace(SQL_MYISAM_SYNTAX,str_replace('type = ','engine = ',SQL_MYISAM_SYNTAX),$sql);
            $sql = str_replace(SQL_HEAP_SYNTAX,str_replace('type = ','engine = ',SQL_HEAP_SYNTAX),$sql);
        }

        for ($i = 0; $i < $sql_len; ++$i) {
            $char = $sql[$i];

            // We are in a string, check for not escaped end of strings except for
            // backquotes that can't be escaped
            if ($in_string) {
                for (;;) {
                    $i         = strpos($sql, $string_start, $i);
                    // No end of string found -> add the current substring to the
                    // returned array
                    if (!$i) {
                        $ret[] = $sql;
                        return $ret;
                    }
                    // Backquotes or no backslashes before quotes: it's indeed the
                    // end of the string -> exit the loop
                    else if ($string_start == '`' || $sql[$i-1] != '\\') {
                        $string_start      = '';
                        $in_string         = FALSE;
                        break;
                    }
                    // one or more Backslashes before the presumed end of string...
                    else {
                        // ... first checks for escaped backslashes
                        $j                     = 2;
                        $escaped_backslash     = FALSE;
                        while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
                            $j++;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if ($escaped_backslash) {
                            $string_start  = '';
                            $in_string     = FALSE;
                            break;
                        }
                        // ... else loop
                        else {
                            $i++;
                        }
                    } // end if...elseif...else
                } // end for
            } // end if (in string)

            // We are not in a string, first check for delimiter...
            else if ($char == ';') {
                // if delimiter found, add the parsed part to the returned array
                $ret[]      = substr($sql, 0, $i);
                $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
                $sql_len    = strlen($sql);
                if ($sql_len) {
                    $i      = -1;
                } else {
                    // The submited statement(s) end(s) here
                    return $ret;
                }
            } // end else if (is delimiter)

            // ... then check for start of a string,...
            else if (($char == '"') || ($char == '\'') || ($char == '`')) {
                $in_string    = $ret;
                $string_start = $char;
            } // end else if (is start of string)

            // ... for start of a comment (and remove this comment if found)...
            else if ($char == '#'
                || ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
                    // starting position of the comment depends on the comment type
                    $start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
                    // if no "\n" exits in the remaining string, checks for "\r"
                    // (Mac eol style)
                    $end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
                        ? strpos(' ' . $sql, "\012", $i+2)
                        : strpos(' ' . $sql, "\015", $i+2);
                    if (!$end_of_comment) {
                        // no eol found after '#', add the parsed part to the returned
                        // array if required and exit
                        if ($start_of_comment > 0) {
                            $ret[]    = trim(substr($sql, 0, $start_of_comment));
                        }
                        return $ret;
                    } else {
                        $sql          = substr($sql, 0, $start_of_comment)
                            . ltrim(substr($sql, $end_of_comment));
                        $sql_len      = strlen($sql);
                        $i--;
                    } // end if...else
                } // end else if (is comment)
        } //end for

        // add any rest to the returned array
        if (!empty($sql) && ereg('[^[:space:]]+', $sql)) {
            $ret[] = $sql;
        }

        return $ret;
    }

    /**
     * &query 
     * 
     * @param mixed $sql 
     * @access public
     * @return void
     */
    function &query($sql, $skipModifiedMark = false){
        return $this->exec($sql, $skipModifiedMark);
    }

    function errorInfo(){
        return $this->_instance->ErrorMsg();
    }

    /**
     * lastInsertId 
     * 
     * @param mixed $string 
     * @access public
     * @return void
     */
    function lastInsertId(){
        return $this->_instance->Insert_ID();
    }

}
