<?php
class mdl_status extends modelFactory {

    function getList(){
        $return = array();
        foreach($this->db->select('select * from sdb_status where date_affect="0000-00-00"')  as $row){
            $return[$row['status_key']] = $row['status_value'];
        }
        return $return;
    }

    function add($key,$value=1,$skip_history=false){
        $key = strtoupper($key);
        if(!$skip_history){
            $this->_add_value($key,date('Y-m-d'),$value);
        }
        $this->_add_value($key,'0000-00-00',$value);
        return true;
    }

    function _add_value($key,$date,$value){
        if(false!==$this->get($key,$date)){
            $this->db->exec('update sdb_status set status_value=status_value+'.floatval($value).' where status_key=\''.$key.'\' and date_affect="'.$date.'"');
        }else{
            $this->set($key,$this->get($key,$date)+$value,$date);
        }
    }

    function set($key,$value,$date='0000-00-00'){
        $key = strtoupper($key);
        $rs = $this->db->exec('select * from sdb_status where status_key=\''.$key.'\' and date_affect="'.$date.'"');
        $sql = $this->db->getUpdateSQL($rs,array('status_key'=>$key,'status_value'=>$value,'last_update'=>time(),'date_affect'=>$date),true);
        return $this->db->exec($sql);
    }

    function get($key,$date='0000-00-00'){
        $key = strtoupper($key);
        if($row = $this->db->selectrow('select status_value from sdb_status where status_key=\''.$key.'\' and date_affect="'.$date.'"')){
            return $row['status_value'];
        }else{
            return false;
        }
    }

    function cleanup(){
        return $this->db->exec('delete from sdb_status where date_affect < "'.date('Y-m-d',time()-24*30).'"');
    }

    function update($force_count=false){
        $in_lib = $this->getList();
        foreach(get_class_methods($this) as $func){
            if(substr($func,0,6)=='count_'){
                if($force_count || !isset($in_lib[strtoupper(substr($func,6))])){
                    $this->$func();
                }
            }
        }
    }

    function _update_count($func,$count){
        return $this->set(substr($func,6),$count);
    }

    //未处理订单
    function count_order_new(){
        $oOrder = &$this->system->loadModel('trading/order');
        $filter['status'] = 'active';
        $filter['pay_status'] = array(0);
        $filter['ship_status'] = array(0);
        $filter['disabled'] = 'false';
        $filter['confirm'] = 'N';
        $oOrder->getList('', $filter, 0, -1, $count);
        return $this->_update_count(__FUNCTION__,$count);
    }

    //未处理缺货登记
    function count_gnotify(){
        $oNotify = &$this->system->loadModel('goods/goodsNotify');
        $filter['status'] = 'ready';
        $filter['disabled'] = 'false';
        $oNotify->getList('', $filter, 0, -1, $count);
        return $this->_update_count(__FUNCTION__,$count);
    }

    //商品库存报警
    function count_galert(){
        $oProduct = &$this->system->loadModel('goods/finderPdt');
        $oGoods = &$this->system->loadModel('goods/products');
        $filter_p['store_alarm'] = $this->system->getConf('system.product.alert.num');
        foreach($oProduct->getList('goods_id', $filter_p, 0, -1) as $row){
            $filter['goods_id'][] = $row['goods_id'];
        }
        if(empty($filter['goods_id'])) $filter['goods_id'] = null;
        return $this->_update_count(__FUNCTION__,count($filter['goods_id']));
    }

    //未处理商品评论
    function count_gdiscuss(){
        $oDiscuss = &$this->system->loadModel('comment/discuss');
        $filter['adm_read_status'] = 'false';
        $oDiscuss->getList('', $filter, 0, -1, $count);
        return $this->_update_count(__FUNCTION__,$count);
    }

    //未处理购买咨询
    function count_gask(){
        $oGask = &$this->system->loadModel('comment/gask');
        $filter['adm_read_status'] = 'false';
        $filter['disabled'] = 'false';
        $oGask->getList('', $filter, 0, -1, $count);
        return $this->_update_count(__FUNCTION__,$count);
    }

    //未处理商店留言
    function count_messages(){
        $oBBS = &$this->system->loadModel('resources/shopbbs');
        $filter['unread'] = 0;
        $oBBS->getList('', $filter, 0, -1, $count);
        return $this->_update_count(__FUNCTION__,$count);
    }

    //待付款订单
    function count_order_to_pay(){
        $sales = &$this->system->loadModel('utility/salescount');
        $count=$sales->orderWithoutPay();
        return $this->_update_count(__FUNCTION__,$count);
    }

    //已付款待发货订单
    function count_order_to_dly(){
        $sales = &$this->system->loadModel('utility/salescount');
        $count=$sales->playWithoutDeliever();
        return $this->_update_count(__FUNCTION__,$count);
    }

    //上架商品
    function count_goods_online(){
        $oGoods = &$this->system->loadModel('goods/products');
        $count=$oGoods->getMarketGoods('true');
        return $this->_update_count(__FUNCTION__,$count);
    }

    //已下架商品
    function count_goods_hidden(){
        $oGoods = &$this->system->loadModel('goods/products');
        $count=$oGoods->getMarketGoods('false');
        return $this->_update_count(__FUNCTION__,$count);
    }

}
?>