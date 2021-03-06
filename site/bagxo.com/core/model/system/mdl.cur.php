<?php
class mdl_cur extends modelFactory {

    function mdl_cur($system){
        parent::modelFactory($system);
        $this->_money_format=array(
            'decimals'=>$this->system->getConf('system.money.operation.decimals'),
            'dec_point'=>$this->system->getConf('system.money.dec_point'),
            'thousands_sep'=>$this->system->getConf('system.money.thousands_sep'),
            'fonttend_decimal_type'=>$this->system->getConf('system.money.operation.carryset'),
            'fonttend_decimal_remain'=>$this->system->getConf('system.money.operation.decimals')
        );
    }

    /**
    * getSysCur
    *
    * @access privite
    * @return void
    */
    function getSysCur(){
        $CON_CURRENCY['CNY'] = "人民币";
        $CON_CURRENCY['USD'] = "美元";
        $CON_CURRENCY['EUR'] = "欧元";
        $CON_CURRENCY['GBP'] = "英磅";
        $CON_CURRENCY['CAD'] = "加拿大元";
        $CON_CURRENCY['AUD'] = "澳元";
        $CON_CURRENCY['RUB'] = "卢布";
        $CON_CURRENCY['HKD'] = "港币";
        $CON_CURRENCY['TWD'] = "新台币";
        $CON_CURRENCY['KRW'] = "韩元";
        $CON_CURRENCY['SGD'] = "新加坡元";
        $CON_CURRENCY['NZD'] = "新西兰元";
        $CON_CURRENCY['JPY'] = "日元";
        $CON_CURRENCY['MYR'] = "马元";
        $CON_CURRENCY['CHF'] = "瑞士法郎";
        $CON_CURRENCY['SEK'] = "瑞典克朗";
        $CON_CURRENCY['DKK'] = "丹麦克朗";
        $CON_CURRENCY['PLZ'] = "兹罗提";
        $CON_CURRENCY['NOK'] = "挪威克朗";
        $CON_CURRENCY['HUF'] = "福林";
        $CON_CURRENCY['CSK'] = "捷克克朗";
        $CON_CURRENCY['MOP'] = "葡币";
        return $CON_CURRENCY;
    }

    function curAdd($data){
        if ($data['def_cur']=='true') {
            $sql="select cur_code from sdb_currency where def_cur=1 and cur_code<>'{$data['cur_code']}'";
            $dat=$this->db->select($sql);
            if (!empty($dat[0]['cur_code'])) {
                $this->setError(2005001);
                trigger_error(__('不可重复设定默认货币'),E_USER_ERROR);
                return false;
            }
        }
        $rs=$this->db->query('select * from sdb_currency where 0=1');
        $sql=$this->db->GetInsertSQL($rs,$data);
        if(!$sql || $this->db->query($sql)){
            return true;
        }else{
            $this->setError(2005002);
            trigger_error(__('数据库插入失败'));
            return false;
        }
    }

    function curList(){
        return $this->db->select_b('select * from sdb_currency',50);
    }

    function curAll(){
        return $this->db->select('select * from sdb_currency');
    }

    function curDel($id){
        $sql = 'delete from sdb_currency where cur_code="'.$id.'"';
        if($this->db->exec($sql)){
            return true;
        }else{
            return false;
        }    
    }

    function getcur($id, $getDef=false){
        $aCur = $this->db->selectrow('select * FROM sdb_currency where cur_code="'.$id.'"');
        if($aCur['cur_code'] || !$getDef){
            return $this->_in_cur = $aCur;
        }else{
            return $this->_in_cur = $this->getDefault();
        }
    }
    
    function getDefault(){
        if($cur = $this->db->selectrow('select * from sdb_currency where def_cur=1')){
            return $cur;
        }else{    //if have no default currency, read the first currency as default value
            return $this->db->selectrow('select * FROM sdb_currency');
        }
    }

    function curEdit($id,$data){
        if ($data['def_cur']=='true') {
            $sql="select cur_code from sdb_currency where def_cur=1 and cur_code<>'{$data['cur_code']}'";
            $dat=$this->db->select($sql);
            if (!empty($dat[0]['cur_code'])) {
                $this->setError(2005003);
                trigger_error(__('不可重复设定默认货币'),E_USER_ERROR);
                return false;
            }
        }
        $rs=$this->db->query('select * from sdb_currency where cur_code="'.$data['cur_code'].'"');
        $sql=$this->db->GetUpdateSQL($rs,$data);
        if($sql){
            if($this->db->exec($sql)){
                return true;
            }else{
                trigger_error(__('输入参数有误'),E_USER_ERROR);
                return false;
            }
        }else return true;
    }

//一下方法需要调整

    function getFormat($cur){
        $ret = array();
        $cursign = $this->getcur($cur, true);
        
        $this->_money_format=array(
            'decimals'=>$this->system->getConf('system.money.operation.decimals'),
            'dec_point'=>$this->system->getConf('system.money.dec_point'),
            'thousands_sep'=>$this->system->getConf('system.money.thousands_sep'),
            'fonttend_decimal_type'=>$this->system->getConf('system.money.operation.carryset'),
            'fonttend_decimal_remain'=>$this->system->getConf('system.money.operation.decimals')
        );
        $ret = $this->_money_format;
        $ret['sign'] = $cursign['cur_sign'];
        return $ret;
    }

    function changer($money,$currency='',$basicFormat=false,$chgval=true){
        
        if(empty($currency)) $currency = $this->system->request['cur'];
        if($currency || empty($this->_in_cur['cur_rate'])){
            $this->_in_cur = $this->getcur($currency, true);
        }
        if($chgval){
            $money = $money*($this->_in_cur['cur_rate'] ? $this->_in_cur['cur_rate'] : 1);
        }
        
        $oMath = $this->system->loadModel('system/math');
        $money = $oMath->getOperationNumber($money);
        $money = $this->formatNumber($money);
        if($basicFormat){
            return $money;
        }
      
        //$decimal_digit = 
        //$decimal_type = $this->system->getConf('site.decimal_type');
        if($this->_money_format['fonttend_decimal_type']){
            $mul = 1;
            $mul = pow(10, $this->_money_format['decimals']);
            switch($this->_money_format['fonttend_decimal_type']){
                case 0:
                    $money = number_format(trim($money), $this->_money_format['decimals'], '.', '');
                break;
                case 1:
                    $money = ceil(trim($money)*$mul) / $mul;
                break;
                case 2:
                    $money = floor(trim($money)*$mul) / $mul;
                break;
            }
        }
            if($this->_money_format['fonttend_decimal_remain']>$this->_money_format['decimals']){
                return $this->_in_cur['cur_sign'].number_format($money,
                $this->_money_format['fonttend_decimal_remain'],
                $this->_money_format['dec_point'],
                '0');
            }else{
                 return $this->_in_cur['cur_sign'].number_format($money,
                $this->_money_format['fonttend_decimal_remain'],
                $this->_money_format['dec_point'],
                $this->_money_format['thousands_sep']);
            }
        
        /*
        return $this->_in_cur['cur_sign'].number_format(trim($money),
            $this->_money_format['decimals'],
            $this->_money_format['dec_point'],
            $this->_money_format['thousands_sep']);
        */
    }
    
    function formatNumber($number){        //取回格式化的数据，供运算使用
        return number_format(trim($number),
            $this->_money_format['decimals'],
            $this->_money_format['dec_point'],'');
    }
}
?>