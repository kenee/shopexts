<?php
class mdl_databasecheck extends modelFactory{

    function doDatabaseCheck(){
        
        if(is_file(CORE_DIR.'/include/db_whole.php')){
            include(CORE_DIR.'/include/db_whole.php');
            foreach($db as $dvKey=>$dValue){
                foreach($dValue as $tablename=>$tablevalue){
                    $sql='';
                    if($construct=$this->getColumnConstruct($this->db_prefix($tablename))){
                        foreach($tablevalue['colums'] as $struct=>$columns){
                            if($columns['name']){
                                if(!is_array($construct[trim($columns['name'])])){
                                    $sql.=$this->alterDb($tablename,array($columns));
                                }
                            }
                        }
                        if($sql){
                            $result[]=array('table'=>$this->db_prefix($tablename),'sql'=>$sql,'status'=>'少字段');
                        }
                    }else{
                        $result[]=array('table'=>$this->db_prefix($tablename),'sql'=>$this->createDb($tablename,$tablevalue),'status'=>'少表');
                    }
                }
            }
        }
        return $result;
    }

    function getColumnConstruct($table){
        if($result = mysql_query("select * from ".$table)){
            $i = 0;
            while ($i < mysql_num_fields($result)) {
                $meta = mysql_fetch_field($result);
                if($meta){
                    $construct[$meta->name]=array(
                        'blob'=>$meta->blob,
                        'max_length'=>$meta->max_length,
                        'multiple_key'=>$meta->multiple_key,
                        'not_null'=>$meta->not_null,
                        'numeric'=>$meta->numeric,
                        'primary_key'=>$meta->primary_key,
                        'type'=>$meta->type,
                        'unique_key'=>$meta->unique_key,
                        'unsigned'=>$meta->unsigned,
                        'zerofill'=>$meta->zerofill
                    );
                }
                $i++;
            }
            mysql_free_result($result);
            return $construct;
            
        }else{
            return false;
        }
    }
    function alterDb($dbname,$data){
        $sql='';
        foreach($data as $k=>$v){
            if(DB_PREFIX!='sdb_'){
                    $dbname=str_replace('sdb_',DB_PREFIX,$dbname);
            }
            if((trim($v['extra'])=='AUTO_INCREMENT')){
                $sql.='ALTER TABLE `'.trim($dbname).'` DROP PRIMARY KEY;';
            }
            $sql.=trim('ALTER TABLE `'.trim($dbname).'` ADD `'.trim($v['name']).'` '.trim($v['construct']).' '.trim($v['null']).' '.trim($data['deault']?('DEFAULT '.$data['deault']):' ').' '.trim($v['extra']).''.((trim($v['extra'])=='AUTO_INCREMENT')?' PRIMARY KEY':'')).';';
        }
        return $sql;
    }
    function updateSql(){
        if($dbUpdate=$this->doDatabaseCheck()){
            foreach($dbUpdate as $k=>$v){
               foreach(explode(';',$v['sql']) as $querySql){
                    $this->db->exec($querySql);
               }
            }
        }
        return true;
    }
    function db_prefix($dbname){
        if(DB_PREFIX!='sdb_'){
            $dbname=str_replace('sdb_',DB_PREFIX,$dbname);
            return $dbname;
        }
        return $dbname;
    }
    function createDb($dbname,$data){
        $dbname=$this->db_prefix($dbname);
        $sql="CREATE TABLE `".trim($dbname)."`(";
        foreach($data['colums'] as $k=>$v){
                if(($v['pkey'][0])){
                    $v['pkey'][0]=str_replace(' ','',$v['pkey'][0]);
                    $key=explode(",",$v['pkey'][0]);
                    $key=implode($key,'`,`');
                }
                if($v['name']){
                    $columns[].="`".trim($v['name'])."` ".trim($v['construct'])." ".trim($v['null'])." ".trim($data['deault']?('DEFAULT '.$data['deault']):' ').' '.trim($v['extra']);
                }
        }
        if($key){
            $columns[]='PRIMARY KEY  (`'.$key.'`)';
        }
        $sql=$sql.implode($columns,',');
        return $sql.') '.($this->mysql_version_check($data[0]['options']?$data[0]['options']:''));
        
    }
    function mysql_version_check($sql){
        
        if($this->system->__db->dbver == 3){
            $sql = str_replace(SQL_MYISAM_SYNTAX,'',$sql);
            $sql = str_replace(SQL_HEAP_SYNTAX,'',$sql);
        }elseif($this->system->__db->dbver == 6){
            $sql = str_replace(SQL_MYISAM_SYNTAX,str_replace('type = ','engine = ',SQL_MYISAM_SYNTAX),$sql);
            $sql = str_replace(SQL_HEAP_SYNTAX,str_replace('type = ','engine = ',SQL_HEAP_SYNTAX),$sql);
        }
        return $sql;
    }
}
?>
