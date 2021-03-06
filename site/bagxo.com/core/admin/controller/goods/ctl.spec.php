<?php
class ctl_spec extends adminPage{

    var $workground = 'goods';

    function enable(){
        $spec = array(
            'product_id'=>array(''),
            'store'=>array($_POST['store']),
            'price'=>array($_POST['price']),
            //        'vars'=>array('颜色','大小'),
            //        'val'=>array(
            //            array('红','红'),
            //          )
        );

        $this->pagedata['spec'] = $spec;
        $this->_output();
    }

    function addCol(){
        $specDesc =  unserialize(stripslashes(urldecode($_POST['goods_spec_desc'])));

        $objSpec = $this->system->loadModel('goods/specification');
        $aSpec = array();
        if(empty( $specDesc )){
            $aSpec = $objSpec->getListByTypeId($_GET['type_id']);
        }
        else{
            $aSpec = $objSpec->getListByIdArray( array_keys($specDesc) );
            foreach( $aSpec as $key => $rows ){
                $aSpec[$key]['sel_options'] = $specDesc[$rows['spec_id']];
            }
        }
        foreach($aSpec as $key => $rows){
            $aVal = $objSpec->getValueList($rows['spec_id']);
            $aSpec[$key]['options'] = $aVal ;
        }
        if( $_POST['goods'] ){
            $this->pagedata['goods_args'] = json_encode( array('goods_args'=>$_POST['goods']));
        }
        $this->pagedata['spec_default_pic'] = $this->system->getConf('spec.default.pic');
        $this->pagedata['specs'] = $aSpec;
        $this->pagedata['ctlType'] = $_POST['ctlType'];
        $this->setView('product/spec_addcol.html');
        $this->output();
    }

    function specValue($specId){
        $objSpec = $this->system->loadModel('goods/specification');
        $aSpec = $objSpec->getFieldById($specId, array('*'));
        $aVal = $objSpec->getValueList($specId);
        $aSpec['options'] = $aVal ;

        $this->pagedata['sItem'] = $aSpec;
        $this->pagedata['spec_default_pic'] = $this->system->getConf('spec.default.pic');
        $this->setView('product/spec_value.html');
        $this->output();
    }

    function doCreatePro( $pro, $spec , $goods_args ){
        if( empty( $spec ) ){
            $res = array();
            foreach( $pro as $pk => $pv ){
                $res[$pk]['sel_spec'] = $pv;
                foreach( $goods_args as $argsk => $argsv )
                    $res[$pk][$argsk] = $argsv;
            }
            return $res;
        }

        $firestSpec = array_shift( $spec );

        $rs = array();
        foreach( $firestSpec as $sitem ){
            foreach( $pro as $pitem ){
                $apitem = $pitem ;
                array_push( $apitem , $sitem );
                $rs[] = $apitem;
            }
            if( empty($pro) )
                $rs[] = array( $sitem );
        }
       return $this->doCreatePro( $rs, $spec , $goods_args );
    }

    function selAlbumsImg(){
        $this->pagedata['selImgs'] = explode(',',$_POST['selImgs']);
        $this->pagedata['img'] = $_POST['img'];
        $this->setView('product/spec_selalbumsimg.html');
        $this->output();
    }

    function doAddCol(){
            $memberLevel = $this->system->loadModel('member/level');
            $this->pagedata['mLevels'] = $memberLevel->getList('member_lv_id,dis_count');
            $this->pagedata['spec']['vars'] = $_POST['spec_vars'];
            $this->pagedata['goods']['spec_value_image'] = $_POST['spec_value_image'];
            $this->pagedata['goods']['spec_desc'] = $_POST['goods']['spec_desc'];
            $spec_vars = array();
            foreach( $_POST['spec_vars'] as $k =>$v )
                $spec_vars[$k]['spec_name'] = $v;
            $this->pagedata['specname'] = $spec_vars;
            $this->pagedata['goods']['spec_desc_str'] = urlencode(serialize($_POST['goods']['spec_desc']));
            if( $_POST['goods_args'] ){
                $this->pagedata['goods_args'] = json_encode( array( 'goods_args'=>$_POST['goods_args'] ) );
            }
            if( $_GET['create'] == 'true' ){
                $pro = array();
                $spec = array();

                $i = 1;
                foreach( $_POST['goods']['spec_desc'] as $sid => $sitem ){
                    $j = 1;
                    foreach( $sitem as $psid => $psitem ){
                        $spec[$i][$j] = array(
                            'spec_id'=>$sid,
                            'p_spec_value_id'=>$psid,
                            'spec_value'=>$psitem['spec_value'],
                            'spec_type'=>$psitem['spec_type'],
                            'spec_value_id'=>$psitem['spec_value_id'],
                            'spec_image'=>$psitem['spec_image'],
                            'spec_goods_images'=>$psitem['spec_goods_images']
                        );
                        $j++;
                    }
                    $i++;
                }
                $pro = $this->doCreatePro( $pro, $spec , $_POST['goods_args'] );
                $this->pagedata['fromType'] = 'create';
                $this->pagedata['goods']['products'] = $pro;

            }
            $this->pagedata['needUpValue'] = json_encode($_POST['needUpValue']);
            $this->pagedata['spec_default_pic'] = $this->system->getConf('spec.default.pic');
            $this->_output();
    }

    function addSpecTab(){
        $objSpec = $this->system->loadModel('goods/specification');
        $spec = $objSpec->getFieldById($_POST['spec_id'], array('*'));
        $specValue = $objSpec->getValueList($_POST['spec_id']);
        $this->pagedata['spec'] = $spec;
        $this->pagedata['specValue'] = $specValue;
        $this->pagedata['spec_default_pic'] = $this->system->getConf('spec.default.pic');
        $this->setView('product/spec_addspectab.html');
        $this->output();
    }

    function addSpecValue(){
        foreach( $_POST['spec'] as $k => $v )
            $this->pagedata[$k] = $v;
        $this->pagedata['pSpecId'] = time().$_POST['sIteration'];
        $this->pagedata['spec_default_pic'] = $this->system->getConf('spec.default.pic');
        $this->setView('product/spec_addspecvalue.html');
        $this->output();
    }

    function addRow(){
        /*
        foreach($_POST['vars'] as $d=>$vs){
            $vars[] = array('id'=>$d,'name'=>$vs,'vars'=>json_encode(array_unique($_POST['val'][$d])));
        }
        */
        if( $_POST['bn'] ){
            $_POST['goods_args'] = array(
                'price' => $_POST['price'][0],
                'cost' => $_POST['cost'][0],
                'product_bn' => $_POST['bn'][0],
                'weight' => $_POST['weight'][0],
                'store' => $_POST['store'][0]
            );
            foreach( $_POST['mprice'] as $mpk => $mpv ){
                $_POST['goods_args']['mprice'][$mpk] = $mpv[0];
            }
        }
        $spec_desc = unserialize(stripslashes( $_POST['spec_desc'] ));
        $memberLevel = $this->system->loadModel('member/level');
        $this->pagedata['mLevels'] = $memberLevel->getList('member_lv_id,dis_count');
        $this->pagedata['goods']['spec_desc'] = $spec_desc;
        $this->pagedata['goods_args'] = $_POST['goods_args'];
        $this->pagedata['spec_default_pic'] = $this->system->getConf('spec.default.pic');
        $this->setView('product/spec_row.html');
        $this->output();
    }

    function delCol($varid){
        $spec = $_POST;
        $ns=array();
        foreach($_POST['product_id'] as $key=>$product_id){
                $n = '';
                foreach($_POST['val'] as $d=>$vs){
                    if($d!=$varid){
                        $n.=$vs[$key];
                    }
                }
                if(isset($ns[$n])){
                    $spec['price'][$ns[$n]]+=$spec['price'][$key];
                    unset($spec['product_id'][$key]);
                }else{
                    $ns[$n] = $key;
                }
        }
        unset($spec['vars'][$varid]);
        $this->pagedata['spec'] = $spec;
        $this->_output();
    }

//    function selectSpec($specid){
//        $objSpec = $this->system->loadModel('goods/specification');
//        $aSpecValue = $objSpec->getValueList($specid);
//        echo json_encode($aSpecValue);
//    }

    function doAddRow(){
        $spec = $_POST;
        $spec['product_id'][] = '';

        foreach($_POST['val'] as $d=>$vs){
            $spec['val'][$d][] = $_POST['_r_var'][$d];
        }
        
        $spec['price'][] = array();
        $this->pagedata['spec'] = $spec;
        $memberLevel = $this->system->loadModel('member/level');
        $this->pagedata['mLevels'] = $memberLevel->getList('member_lv_id,dis_count');

        $this->_output();
    }
    
    function getSpecArray($i,$array,$result,&$aRet) {
        if (count($result) == count($array)){
        $aRet[] = $result;
            return ;
        }
    
        foreach ($array[$i] as $j => $v){
            array_push($result,$v);
            $this->getSpecArray($i+1, $array, $result, $aRet);
            array_pop($result);
        }
    }
    
    function selectSpec(){
        $objSpec = $this->system->loadModel('goods/specification');
        $aSpec = $objSpec->getArrayById();
        echo '规格：<select name="new_spec_id" onchange="selectSpec(this)">';
        echo '<option value="">- 请选择 -</option>';
        foreach($aSpec as $k => $v){
            echo '<option value="'.$k.'">'.$v.'</option>';
        }
        echo '</select>';
    }
    
    
    function selectVal($specid, $valid){
        $objSpec = $this->system->loadModel('goods/specification');
        $aValue = $objSpec->getValueList($specid);
        echo '<select name="value_id[]" onchange="selectValue(this,'.$specid.')">';
        echo '<option value="">- 请选择 -</option>';
        foreach($aValue as $v){
            echo '<option value="'.$v['spec_value_id'].'"'.($v['spec_value_id']==$valid ? ' selected' : '').'>'.$v['spec_value'].'</option>';
        }
        echo '</select>';
    }


/***
老方法 废弃
    function doAddCol(){
        $_POST = $_REQUEST;
        foreach(preg_split("/[\n]+/",trim($_POST['nvar']['values'])) as $k => $v){
            if(trim($v)){
                $newVal[] = trim($v);
                $newVal = array_unique($newVal);
            }
        }
        if($_POST['new_spec_id']){
            $objSpec = $this->system->loadModel('goods/specification');
            $aSpec = $objSpec->getFieldById($_POST['new_spec_id'], array('spec_name'));
            $_POST['vars'][$_POST['new_spec_id']] = $aSpec['spec_name'];
        }
        
        $doMark = 'edit';
        if(!$newSpecKey){
            if($_POST['nvar']['name']){
                $newSpecKey = max(array_keys($_POST['vars']))+1;
                $_POST['vars'][$newSpecKey] = $_POST['nvar']['name'];
                $doMark = 'new';
            }else{
                $newSpecKey = max(array_keys($_POST['vars']));
            }
        }

        if(($num = count($newVal)) == 0 
            || (isset($_POST['val'][$newSpecKey]) && !($newVal = array_diff($newVal, $_POST['val'][$newSpecKey])))){    //无规格值传入
            $spec = $_POST;
        }else{    //有规格值传入
            if(count($_POST['price'])<1){    //原先无物品项
                foreach($_POST['goods'] as $k=>$v){
                    if($k == 'mprice'){
                        foreach($v as $levid=>$v){
                            $_POST[$k][$levid] = array($v);
                        }
                    }else{
                        if($k != 'bn'){
                            $_POST[$k]=array($v);
                        }
                    }
                }
                foreach($_POST['idata'] as $k=>$v){
                    $_POST['idata'][$k] = array($v);
                }
            }
            
            //==========
            foreach($_POST['val'] as $key => $speVal){    //遍历规格项
                foreach($speVal as $j => $val){
                    $specExsit[$j] .= '|'.$val;
                }
                $aSpec[$key] = array_unique($speVal);
            }
            if($specExsit){
                $specExsit = array_flip($specExsit);
            }
            if($aSpec[$newSpecKey]){
                $aSpec[$newSpecKey] = array_merge($aSpec[$newSpecKey],$newVal);
            }else{
                $aSpec[$newSpecKey] = $newVal;
            }
            $this->getSpecArray(1,$aSpec,array(),$arr_new_spec);
            foreach($arr_new_spec as $rows){
                $p_rows = $rows;
                if($specExsit){
                    if($doMark == 'edit'){
                        $rowKey = '|'.implode('|', $rows);
                        if(array_key_exists($rowKey, $specExsit)){
                            $tmpVar = $specExsit[$rowKey];
                            unset($specExsit[$rowKey]);
                            $rowKey = $tmpVar;
                        }else{
                            $rowKey = false;
                        }
                    }else{
                        if(count($rows) > 1){
                            array_pop($rows);
                            $rowKey = '|'.implode('|', $rows);
                            if(array_key_exists($rowKey, $specExsit)){
                                $tmpVar = $specExsit[$rowKey];
                                unset($specExsit[$rowKey]);
                                $rowKey = $tmpVar;
                            }else{
                                $rowKey = false;
                            }
                        }else{
                            $rowKey = false;
                        }
                    }
                }else{
                    $rowKey = false;
                }
                if($rowKey !== false){
                        $spec['bn'][] = $_POST['bn'][$rowKey];
                        $spec['store'][] = $_POST['store'][$rowKey];
                        $spec['price'][] = $_POST['price'][$rowKey];
                        foreach($_POST['mprice'] as $nLev => $specVal){
                            $spec['mprice'][$nLev][] = $_POST['mprice'][$nLev][$rowKey];
                        }
                }else{
                        $spec['bn'][] = '';
                        $spec['store'][] = $_POST['goods']['store'];
                        $spec['price'][] = $_POST['goods']['price'];
                }
                
                foreach($p_rows as $j => $specVal){
                        $spec['val'][$j+1][] = $specVal;
                }
//                    foreach($_POST['idata'] as $ik=>$iv){
//                        $spec['idata'][$ik][] = $_POST['idata'][$ik][$key];
//                    }
            }
        }
        $spec['idataInfo'] = $_POST['idataInfo'];

        $memberLevel = $this->system->loadModel('member/level');
        $this->pagedata['mLevels'] = $memberLevel->getList('member_lv_id,dis_count');
        
        $spec['vars'] = $_POST['vars'];
        $this->pagedata['spec'] = $spec;
        $this->_output();
    }
    */
    
    function removeSpec($id){
        $objSpec = $this->system->loadModel('goods/specification');
        $objSpec->toRemove($id);
        
        $aSpec = $objSpec->getList(0, 1000);
        foreach($aSpec['spec'] as $key => $rows){
            $aVal = $objSpec->getValueList($rows['spec_id']);
            $opt = array();
            foreach($aVal as $vals){
                $opt[] = $vals['spec_value'];
            }
            $aSpec['spec'][$key]['options'] = implode(',', $opt);
        }

        $this->pagedata['specs'] = $aSpec['spec'];
        $this->setView('product/spec_select.html');
        $this->output();
    }

    function addSpec($typeId = 0) {
        $objSpec = $this->system->loadModel('goods/specification');
        $aSpec = array();
        if($typeId)
            $aSpec = $objSpec->getListByTypeId($typeId);
        else
            $aSpec = $objSpec->getListByIdArray();
        $this->pagedata['specs'] = $aSpec;
        $this->setView('product/spec_select.html');
        $this->output();
    }
    
    function saveSpec($id){
        $objSpec = $this->system->loadModel('goods/specification');
        $objSpec->toRemove($id);
        
        $aSpec = $objSpec->getList(0, 1000);
        foreach($aSpec['spec'] as $key => $rows){
            $aVal = $objSpec->getValueList($rows['spec_id']);
            $opt = array();
            foreach($aVal as $vals){
                $opt[] = $vals['spec_value'];
            }
            $aSpec['spec'][$key]['options'] = implode(',', $opt);
        }

        $this->pagedata['specs'] = $aSpec['spec'];
        $this->setView('product/spec_select.html');
        $this->output();
    }
    
    function removeValue($specid, $val){
        $objSpec = $this->system->loadModel('goods/specification');
        $objSpec->toRemoveValue($val, $specid);
        
        $aSpec = $objSpec->getList(0, 1000);
        foreach($aSpec['spec'] as $key => $rows){
            $aVal = $objSpec->getValueList($rows['spec_id']);
            $opt = array();
            foreach($aVal as $vals){
                $opt[] = $vals['spec_value'];
            }
            $aSpec['spec'][$key]['options'] = implode(',', $opt);
        }

        $this->pagedata['specs'] = $aSpec['spec'];
        $this->setView('product/spec_select.html');
        $this->output();
    }
    
    function saveValue($id){
        
    }

    function _output(){
        $memberLevel = $this->system->loadModel('member/level');
        $this->pagedata['mlevels'] = $memberLevel->getList('member_lv_id,dis_count');
        $this->setView('product/spec.html');
        $this->output();
    }
}