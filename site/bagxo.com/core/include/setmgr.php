<?php
class setmgr{

    var $_cfg;

    function setmgr(){
        $this->system = &$GLOBALS['system'];
    }

    function delSetting($aKey){
        for($i=0;$i<count($aKey);$i++){
            $this->del($aKey[$i]);
        }
        return true;
    }

    //END

    /**
     * set 
     * 
     * @param mixed $key 
     * @param mixed $value 
     * @param mixed $immediately  立刻写入到数据库
     * @access public
     * @return void
     */
    function set($key,$value,$immediately=false){
        
        if($pos = strpos($key,'.')){
            
            $this->_pool[substr($key,0,$pos)][substr($key,$pos+1)] = $value;

            if($immediately){
                $this->_save();
            }else{
                if(!$this->_regSave){
                    register_shutdown_function(array(&$this,'_save'));
                    $this->_regSave = true;
                }
                return true;
            }
        }
    }

    function _save(){
        $db = &$this->system->database();
        $vary = array();
        foreach($this->_pool as $domain=>$values){
            $rs = $db->exec('select * from sdb_settings where s_name="'.$domain.'"');
            $row = $db->getRows($rs);
            $data = unserialize($row[0]['s_data']);
            $values = $data?array_merge($data,$values):$values;
            $sql = $db->getUpdateSql($rs,array('s_name'=>$domain,'s_data'=>$values,'s_time'=>time()),true);
            if($sql) $db->exec($sql);
            $vary[] = 'SETTING_'.$domain;
        }
        $this->system->cache->setModified($vary);
    }

    function setfile($key,$sfile){
        return $this->set($key,'sfile://'.$sfile['file_id'].':'.$sfile['file_name'].':'.$sfile['file_size']);
    }

    function get($key,&$var){


        if($pos = strpos($key,'.')){
            $domain = substr($key,0,$pos);
            $this->system->checkExpries('SETTING_'.$domain);
            $key = substr($key,$pos+1);

            if(isset($this->_pool[$domain][$key])){
                return $this->_pool[$domain][$key];
            }elseif(!isset($this->_cfg[$domain])){
                $this->_cfg[$domain] = null;
                if(!$this->system->cache->get('SETTING_'.$domain,$this->_cfg[$domain])){
                                        $db = &$this->system->database();
                    if(($row = $db->selectrow('select s_data from sdb_settings where s_name="'.$domain.'"')) && ($data = unserialize($row['s_data']))){
                        $this->_cfg[$domain] = &$data;
                    }else{
                        $this->_cfg[$domain] = array();
                    }
                    $this->system->cache->set('SETTING_'.$domain,$this->_cfg[$domain],array('SETTING_'.$domain));
                }
            }

            if(isset($this->_cfg[$domain][$key])){
                return $this->_cfg[$domain][$key];
            }else{
                    
                if(!isset($this->_setting)){
                   $this->_setting = &$this->source();
                }
                
                return $this->_setting[$domain.'.'.$key]['default'];
                
            }
        }
        
    }

    function del($key){
        $db = &$this->system->database();
        if($pos = strpos($key,'.')){
            $db->exec('delete from sdb_settings where s_name="'.substr($key,0,$pos).'"');
            return true;
        }else{
            $db->exec('delete from sdb_settings where s_name="'.$key.'"');
            return true;
        }
    }

    function &source(){
        include(dirname(__FILE__).'/setting.php');
        if (defined('CUSTOM_CORE_DIR')&&file_exists(CUSTOM_CORE_DIR.'/include/customsetting.php')){
            include(CUSTOM_CORE_DIR.'/include/customsetting.php');
            if (is_array($cumsetting)){
                $setting = array_merge($setting,$cumsetting);
            }
        }
        return $setting;
    }
}
?>