<?php
class mdl_goods extends modelFactory{

    function getFieldById($goodsId, $aField=array('*')){
        return $this->db->selectrow("SELECT ".implode(",", $aField)." FROM sdb_goods WHERE goods_id='{$goodsId}'");
    }

    function getGoods($goods_id, $levelid=0){
        $goods = $this->db->selectrow('SELECT * FROM sdb_goods WHERE goods_id='.intval($goods_id));
        if(!$goods) return false;
        $catsObj = $this->system->loadModel('goods/gtype');
        $prototype = $catsObj->getTypeDetail($goods['type_id']);
        if($prototype['params'] && $prototype['setting']['use_params']){
            $goods['params'] = unserialize($goods['params']);
            $aTmp = array();
            foreach($prototype['params'] as $kz => $items){
                foreach($items as $k => $v){
                    $aTmp[$kz][$k] = $goods['params'][$kz][$k];
                }
            }
            $goods['params'] = $aTmp;
        }else{
            unset($goods['params']);
        }
        $goods['prototype'] = &$prototype;
        $goods['prototype']['spec'] = array_reverse( $goods['prototype']['spec'] , true );
        $goods['spec'] = unserialize($goods['spec']);
        if($goods['pdt_desc'] = unserialize($goods['pdt_desc'])){    //如果是多物品商品
            $s = array_keys($goods['pdt_desc']);
            asort($s);
            foreach($this->db->select('SELECT * FROM sdb_products WHERE product_id in ('.implode(',',$s).') AND goods_id='.$goods_id.' ORDER BY product_id') as $product){
                $product['props'] =  unserialize($product['props']);
                foreach($product['props']['spec'] as $k => $v){
                    $product['props']['spec'][$k] = stripslashes(trim($v));
                    $product['props']['spec_value_id'][$k] = stripslashes(trim($product['props']['spec_value_id'][$k]));
                    $product['props']['spec_private_value_id'][$k] = stripslashes(trim($product['props']['spec_private_value_id'][$k]));
                }
//                foreach($product['props'] as $pk => $pv){
//                    $product['props'][$pk] =  array_reverse($pv , true );
//                }
                $product = array_merge($product, $this->getMemberPrice($goods_id, $product['product_id'], $levelid));
                $product['sale_price'] = $product['price'];
                if($levelid && isset($product['mprice'][$levelid])){
                    $product['price'] = $product['mprice'][$levelid];
                }
                $goods['products'][] = $product;
                /*
                foreach($product['props']['spec'] as $k=>$v){
                    $goods['specinfo'][$k][$v] = 1;
                }
                */
            }
        }else{
            $aRet = $this->db->selectrow('SELECT product_id,bn,props, store, freez FROM sdb_products WHERE goods_id='.intval($goods_id));
            if($aRet){
                $goods['product_id'] = $aRet['product_id'];
                $goods['product_bn'] = $aRet['bn'];
                $goods['store'] = $aRet['store'];
                $goods['freez'] = $aRet['freez'];
                $goods['props'] = unserialize($aRet['props']);
                $goods = array_merge($goods, $this->getMemberPrice($goods_id, $goods['product_id']));
                $goods['sale_price'] = $goods['price'];
                if($levelid && isset($goods['mprice'][$levelid])){
                    $goods['price'] = $goods['mprice'][$levelid];
                }
            }
        }
        if(isset($prototype['use_brand']) && $prototype['use_brand']){
            $brandObj = $this->system->loadModel('goods/brand');
            $goods['brandList'] = $brandObj->getTypeBrands($prototype['type_id']);
        }
        $goods['seo'] = unserialize($this->getGoodsMemo($goods_id,'seo_info'));
        $goods['adjunct'] = unserialize($this->getGoodsMemo($goods_id,'adjunct'));

        //新规格
        $goods['spec_desc_str'] = urlencode($goods['spec_desc'] );
        $spec_desc=unserialize($goods['spec_desc']);
        $goods['spec_desc'] = $spec_desc;
        $tmpGoods=array();
        $specDefaultPic = $this->system->getConf('spec.default.pic');
        if (is_array($spec_desc)){
            $specValue = $this->system->loadModel('goods/specification');
            foreach($spec_desc as $key => $val){
                $tmpRow = $specValue->getFieldById($key,array('spec_name','spec_type','spec_show_type'));
                $tmpGoods['spec_desc'][$key]['name']=$tmpRow['spec_name'];
                foreach($val as $k=>$v){
                    if (empty($val[$k]['spec_image'])||is_null($val[$k]['spec_image'])){
                        $tValue=$specValue->getValueById($val[$k]['spec_value_id'],array('spec_image'));
                        $val[$k]['spec_image'] = $tValue['spec_image'];
                    }
                    if (empty($val[$k]['spec_image'])||is_null($val[$k]['spec_image'])){
                        $val[$k]['spec_image'] = $specDefaultPic;
                    }
                    $goods['spec_desc'][$key][$k]['spec_type'] = $tmpRow['spec_type'];
                    $val[$k]['spec_type'] = $tmpRow['spec_type'];
                    $val[$k]['spec_goods_images']=$key."@".$v['spec_goods_images']."|".$k;
                    $spec_type = $tmpRow['spec_type'];
                }
                $tmpGoods['spec_desc'][$key]['value']=$val;
                if ($tmpRow['spec_show_type']=="select"){
                    $tmpSelGoods['spec_desc'][$key]['name'] =$tmpRow['spec_name'];
                    $tmpSelGoods['spec_desc'][$key]['spec_type'] = $spec_type;
                    $tmpSelGoods['spec_desc'][$key]['value']=$val;
                }
                elseif ($tmpRow['spec_show_type']=="flat"){
                    $tmpFlatGoods['spec_desc'][$key]['name'] =$tmpRow['spec_name'];
                    $tmpFlatGoods['spec_desc'][$key]['spec_type']=$spec_type;
                    $tmpFlatGoods['spec_desc'][$key]['value']=$val;
                }
            }
        }
        $goods['specVdesc'] = $tmpGoods['spec_desc'];
        $goods['FlatSpec'] = $tmpFlatGoods['spec_desc'];
        $goods['SelSpec'] = $tmpSelGoods['spec_desc'];
        $product2spec = array();
        $spec2product = array();
        $cur=$this->system->loadModel('system/cur');
        foreach( $goods['products'] as $pid =>$pitem ){
            $price=$cur->changer($pitem['price'],'',true);
            $product2spec[$pitem['product_id']] = array(
                'bn'=>$pitem['bn'],
                'price'=>$price,
//                'cost'=>$pitem['cost'],
                'store'=>($pitem['store'] === null?'9999':($pitem['store']-$pitem['freez'])),
                'weight'=>$pitem['weight'],
                'mprice'=>$pitem['mprice']
            );
            $apvid = array();
            foreach( $pitem['props']['spec_private_value_id'] as $sid => $pvid ){
                $apvid[] = $pvid;
                $spec2product[$pvid]['product_id'][] = $pitem['product_id'];
                $spec2product[$pvid]['images'] = json_decode( '['.$goods['spec_desc'][$sid][$pvid]['spec_goods_images'].']' );
                if (is_array($goods['FlatSpec'][$sid]['value'][$pvid]))
                    $goods['FlatSpec'][$sid]['value'][$pvid]['display']="block";
                if (is_array($goods['SelSpec'][$sid]['value'][$pvid]))
                    $goods['SelSpec'][$sid]['value'][$pvid]['display']="block";
            }
            $product2spec[$pitem['product_id']]['spec_private_value_id'] = json_decode('["'.implode('","',$apvid).'"]');
        }
        $goods['product2spec'] = $product2spec;
        $goods['spec2product'] = $spec2product;
        //-----
        return $goods;
    }

    function checkGoodsValid(&$data){
        $aTmp = $this->db->selectrow('SELECT count(*) AS num FROM sdb_goods WHERE bn = \''.$data['bn'].'\' AND goods_id != '.intval($data['goods_id']));
        return $aTmp['num'];
    }

    //保存(新增/编辑)商品,物品,会员价格
    function save(&$aData){
        if(empty($aData['name'])){
            return false;
            exit;
        }
        $products = $aData['products'];
        $aData['pdt_desc'] = (is_array($products) ? array() : '');
        $aData['spec'] = (is_array($products) ? $aData['spec'] : '');
        $aData['uptime'] = time();
        $aData['cost'] = floatval($aData['cost']);
        $aData['last_modify'] = time();
        $aData['weight'] = (trim($aData['weight']) ? $aData['weight'] : 0);
        if(empty($aData['bn'])) $aData['bn'] = strtoupper(uniqid('g'));
        else{
            if($this->checkGoodsValid($aData)){
                trigger_error('保存失败：货号重复！',E_USER_ERROR);
                return false;
            }
        }
        unset($aData['products']);
        $aData['price'] = floatval($aData['price']);
        if(!$aData['mktprice']){
            $oMath = $this->system->loadModel('system/math');
            if($this->system->getConf('site.show_mark_price')){
                if($this->system->getConf('site.market_price') == '1')
                    $aData['mktprice'] = $this->system->getConf('site.market_rate')* $oMath->getOperationNumber( $aData['price'] );
                if($this->system->getConf('site.market_price') == '2')
                    $aData['mktprice'] = $this->system->getConf('site.market_rate')+$oMath->getOperationNumber( $aData['price'] );
            }
        }

        if($aData['brand_id'] && !$aData['brand']){
            $objBrand = $this->system->loadModel('goods/brand');
            $aBrand = $objBrand->getFieldById($aData['brand_id'], array('brand_name'));
            $aData['brand'] = $aBrand['brand_name'];
        }
        if($aData['goods_id']){
            $rs = $this->db->query('SELECT * FROM sdb_goods WHERE goods_id='.$aData['goods_id']);
            $sql = $this->db->GetUpdateSQL($rs, $aData);
            if($sql && !$this->db->exec($sql)){
                trigger_error('SQL Error:'.$sql,E_USER_NOTICE);
                return false;
            }
        }else{
            $aData['d_order'] = 50;
            $rs = $this->db->query('SELECT * FROM sdb_goods WHERE 0=1');
            $sql = $this->db->GetInsertSQL($rs, $aData);
            if($sql && !$this->db->exec($sql)){
                trigger_error('SQL Error:'.$sql,E_USER_NOTICE);
                return false;
            }
            $aData['goods_id'] = $this->db->lastInsertId();
            $this->db->exec('UPDATE sdb_goods SET p_order='.$aData['goods_id'].' WHERE goods_id='.$aData['goods_id']);
            $pData['uptime'] = time();
            $pData['last_modify'] = time();

            $status = $this->system->loadModel('system/status');
            $status->add('GOODS_ADD');
        }
        $this->setGoodsMemo($aData['goods_id'], 'seo_info', $aData['seo']);

        $pData['goods_id'] = $aData['goods_id'];
        $pData['cost'] = $aData['cost'];
        $pData['name'] = $aData['name'];
        $pData['weight'] = $aData['weight'];
        $pData['unit'] = $aData['unit'];

        //按规格插入货品
        $aMprice=array();
        $aPdt=array();

        $specIndexData = array();
        if(is_array($products)){    //多物品商品
            $iLoop = 0;
            $aIndex = array();
            $aPdtDesc['store'] = 0;
            foreach($products as $k => $aProduct){
                if(empty($aProduct['bn'])){
                    $aProduct['bn'] = $this->getPdtBn($aData['bn'], $products);        //生成物品编号
                    $products[$k]['bn'] = $aProduct['bn'];
                }
                $aProduct = array_merge($pData, $aProduct);
                if($newPid = $this->addProducts($aProduct)){

                    $newProductId = $this->db->selectrow('SELECT product_id FROM sdb_products WHERE bn = "'.$newPid.'"');
                    //插入规格索引表
                    foreach( $aProduct['props']['spec_private_value_id'] as $pubSpecId => $pSpecId ){
                        $specIndexData[] = array(
                                'type_id'=>$aData['type_id'],
                                'spec_id'=>$pubSpecId,
                                'spec_value_id'=>$aProduct['props']['spec_value_id'][$pubSpecId],
                                'goods_id'=>$aProduct['goods_id'],
                                'product_id'=>$newProductId['product_id']
                        );
                    }

                    $aPdt[] = $newPid;
                    if($aPdtDesc['store'] === '' || trim($aProduct['store']) === ''){
                        $aPdtDesc['store'] = '';
                    }else{
                        $aPdtDesc['store'] += $aProduct['store'];
                    }
                    if(is_array($aProduct['mprice'])){
                        $tmpPrice['goods_id'] = $aData['goods_id'];
                        $tmpPrice['product_id'] = $newPid;
                        $tmpPrice['price'] = $aProduct['mprice'];
                        $aMprice[] = $tmpPrice;
                        $tmpArr[$newPid] = $iLoop;
                        $iLoop++;
                    }
                    $aIndex['product_id'][] = $newPid;
                    $aIndex['spec'][] = $aProduct['props']['spec'];
                }
                $aProps[] = $aProduct['props'];
            }

            $usedPdt = array();
            foreach($this->db->select('select product_id,pdt_desc,bn from sdb_products where bn in(\''.implode('\',\'',$aPdt).'\') and goods_id='.$aData['goods_id']) as $r){
                $usedPdt[] = $r['product_id'];
                $aMprice[$tmpArr[$r['bn']]]['product_id'] = $r['product_id'];
                $aPdtDesc['pdt_desc'][$r['product_id']] = $r['pdt_desc'];

                foreach($aIndex['product_id'] as $k => $v){
                    if($v == $r['bn']){
                        $aIndex['product_id'][$k] = $r['product_id'];
                        break;
                    }
                }
            }
            $rs = $this->db->query('SELECT * FROM sdb_goods WHERE goods_id='.$aData['goods_id']);
            $sql = $this->db->GetUpdateSQL($rs, $aPdtDesc);
            if($sql) $this->db->exec($sql);

        }else{    //单物品商品
            $pData['bn'] = ($aData['product_bn']?$aData['product_bn']:$aData['bn']);
            $pData['store'] = $aData['store'];
            $pData['price'] = $aData['price'];
            $pData['props'] = $aData['props'];
            $newPid = $this->addProducts($pData);

            if(is_array($aData['mprice'])){
                $aMprice[0]['goods_id'] = $aData['goods_id'];
                $aMprice[0]['product_id'] = $newPid;
                $aMprice[0]['price'] = $aData['mprice'];
            }
            $aPdt[] = $newPid;
            $usedPdt = array();
            foreach($this->db->select('select product_id,pdt_desc from sdb_products where bn in(\''.implode('\',\'',$aPdt).'\') and goods_id='.$aData['goods_id']) as $r){
                $usedPdt[] = $r['product_id'];
                $aMprice[0]['product_id'] = $r['product_id'];
            }
        }
        $this->updateGoodsIndex( $aData['goods_id'], $specIndexData);
        $this->removePdt($aData['goods_id'], $usedPdt);

        //处理配件
        foreach($aData['adjunct']['name'] as $key => $name){
            $aItem['name'] = $name;
            $aItem['type'] = $aData['adjunct']['type'][$key];
            $aItem['min_num'] = $aData['adjunct']['min_num'][$key];
            $aItem['max_num'] = $aData['adjunct']['max_num'][$key];
            $aItem['set_price'] = $aData['adjunct']['set_price'][$key];
            $aItem['price'] = $aData['adjunct']['price'][$key];
            if($aItem['type'] == 'goods') $aItem['items']['product_id'] = $aData['adjunct']['items'][$key];
            else $aItem['items'] = $aData['adjunct']['items'][$key];//.'&dis_goods[]='.$aData['goods_id']
            $aAdj[] = $aItem;
        }
        $this->setGoodsMemo($aData['goods_id'], 'adjunct', $aAdj);

        if($aData['updateMprice']){
            $sql = 'DELETE FROM sdb_goods_lv_price WHERE goods_id='.$aData['goods_id'];
            $this->db->exec($sql);
        }else{
            if(count($aMprice))
                $this->addMemberPrice($aMprice);
        }
        if(!$status){
            $status = $this->system->loadModel('system/status');
        }
        $status->count_goods_online();
        $status->count_goods_hidden();
        $status->count_galert();

        return $aData['goods_id'];
    }

    function updateGoodsIndex( $goods_id, $aData){
        $this->db->exec('DELETE FROM sdb_goods_spec_index WHERE goods_id = '.intval($goods_id));
        if( empty( $aData ) )
            return true;

        $sql = 'INSERT INTO sdb_goods_spec_index ( '.implode( ',' , array_keys($aData[0]) ).' ) VALUES ';
        $aSql = array();
        foreach( $aData as $v )
            $aSql[] = ' ( '.implode(',', $v ).' ) ';
        return $this->db->exec($sql.implode(',',$aSql));

    }

    /**
     *     addGoodsSpecIndex
     *     $data = arrray(
     *          'type_id'=>
     *          'spec_id'=>
     *          'spec_value_id'=>
     *          'goods_id'=>
     *          'product_id'=>
     *      )
     *     return bool
    function addGoodsSpecIndex( $data ){
         return $this->db->exec('INSERT INTO sdb_goods_spec_index ( '.implode( ',' , array_keys($data) ).' ) VALUES ( '.implode(',', $data ).' )');
    }
    */

    function getGoodsByIndex($specId, $valId){
        $aTmp = $this->db->select('SELECT DISTINCT(goods_id) FROM sdb_goods_spec_index WHERE spec_id = '.intval($specId).' AND spec_value_id ='.intval($valId));
        foreach($aTmp as $k => $v){
            $aRet[] = $v['goods_id'];
        }
        return $aRet;
    }

    function getPdtBn($gBn, $aProduct){
        $iLoop = 1;
        $getMark = false;
        while(!$getMark){
            $exsitMark = false;
            foreach($aProduct as $k => $item){
                if($item['bn'] == $gBn.'-'.$iLoop){
                    $exsitMark = true;
                    break;
                }
            }
            if($exsitMark)
                $iLoop++;
            else
                $getMark = true;
        }
        return $gBn.'-'.$iLoop;
    }

    function setGoodsMemo($gid, $key, $content){
        $aData['goods_id'] = $gid;
        $aData['p_key'] = $key;
        $aData['p_value'] = $content;
        $rs = $this->db->query('SELECT * FROM sdb_goods_memo WHERE goods_id='.intval($gid).' AND p_key = \''.$key.'\'');
        $sql = $this->db->GetUpdateSQL($rs, $aData, true);
        if($sql && !$this->db->exec($sql)){ echo 'error:'.$sql; return false;}else{return true;}
    }

    function getGoodsMemo($gid, $key){
        $aRet = $this->db->selectrow('SELECT p_value FROM sdb_goods_memo WHERE goods_id='.intval($gid).' AND p_key = \''.$key.'\'');
        return $aRet['p_value'];
    }

    function addProducts(&$aData){
        $aData['price'] = floatval($aData['price']);
        $aData['weight'] = floatval($aData['weight']);
        $rs = $this->db->exec('SELECT * FROM sdb_products WHERE bn=\''.$aData['bn'].'\' and goods_id='.intval($aData['goods_id']));
        $sql = $this->db->GetUpdateSQL($rs,$aData,true);
        if (!$sql || $this->db->exec($sql)){
            return $aData['bn'];
        }else{
            return false;
        }
    }

    function checkProductBn($bn, $gid=0){
        if(empty($bn)){
            return false;
        }
        if($gid){
            $sql = 'SELECT count(*) AS num FROM sdb_products WHERE bn = \''.$bn.'\' AND goods_id != '.$gid;
        }else{
            $sql = 'SELECT count(*) AS num FROM sdb_products WHERE bn = \''.$bn.'\'';
        }
        $aTmp = $this->db->selectrow($sql);
        return $aTmp['num'];
    }

    function addMemberPrice(&$aData){
        foreach($aData as $items){
            $data['goods_id'] = $items['goods_id'];
            $data['product_id'] = $items['product_id'];
            foreach($items['price'] as $levelid => $price){
                if($price!=='' && $price > 0){
                    $data['level_id'] = $levelid;
                    $data['price'] = $price;
                    $rs = $this->db->query('SELECT * FROM sdb_goods_lv_price WHERE goods_id='
                            .intval($data['goods_id']).' AND product_id='.intval($data['product_id']).' AND level_id='.intval($data['level_id']));
                    $sql = $this->db->GetUpdateSQL($rs, $data, true);
                }else{
                    $sql = 'DELETE FROM sdb_goods_lv_price WHERE goods_id='
                            .intval($data['goods_id']).' AND product_id='.intval($data['product_id']).' AND level_id='.intval($levelid);
                }
                if($sql) $this->db->exec($sql);
            }
        }
        return true;
    }

    //参数levelid,暂时不进行查询
    function getMemberPrice($goods_id, $product_id=0, $levelid=0){
        $aLevel = $this->db->select('SELECT * FROM sdb_member_lv WHERE disabled = "false"');    //读取所有会员等级
        if(count($aLevel)){
            //读取数据库中的会员等级价格（存在）
            if($product_id){
                $aTmp = $this->db->select('SELECT * FROM sdb_goods_lv_price WHERE goods_id = '.intval($goods_id).' AND product_id='.intval($product_id));
            }else{
                $aTmp = $this->db->select('SELECT * FROM sdb_goods_lv_price WHERE goods_id = '.intval($goods_id));
            }
            foreach($aTmp as $rows){
                $aRet['mprice'][$rows['level_id']] = $rows['price'];
            }
            unset($aTmp);
            if($product_id){
                $aTmp = $this->db->selectrow('SELECT price FROM sdb_products WHERE product_id='.intval($product_id));
            }else{
                $aTmp = $this->db->selectrow('SELECT price FROM sdb_goods WHERE goods_id='.intval($goods_id));
            }
            $oMath = $this->system->loadModel('system/math');
            foreach($aLevel as $rows){
                if(!isset($aRet['mprice'][$rows['member_lv_id']])){
                    $aRet['mprice'][$rows['member_lv_id']] = ($rows['dis_count'] > 0 ? $oMath->getOperationNumber($aTmp['price'])*$rows['dis_count'] : $aTmp['price']);
                    $aRet['autoset'][$rows['member_lv_id']] = 1;
                }else{
                    $aRet['autoset'][$rows['member_lv_id']] = 0;
                }
                $aRet['nullprice'][$rows['member_lv_id']] = ($rows['dis_count'] > 0 ? $oMath->getOperationNumber($aTmp['price'])*$rows['dis_count'] : $aTmp['price']);
            }
        }else{
            $aRet = array('mprice'=>array(),'autoset'=>array());
        }
        return $aRet;
    }

    function toRemove($goodsid){
        $gimage = $this->system->loadModel('goods/gimage');
        $gimage->remove_by_goods_id($goodsid);

        $sql = 'DELETE FROM sdb_goods WHERE goods_id = '.intval($goodsid);
        $this->db->exec($sql);
        $sql = 'DELETE FROM sdb_products WHERE goods_id = '.intval($goodsid);
        $this->db->exec($sql);
        $sql = 'DELETE FROM sdb_goods_memo WHERE goods_id = '.intval($goodsid);
        $this->db->exec($sql);
        $sql = 'DELETE FROM sdb_goods_lv_price WHERE goods_id = '.intval($goodsid);
        $this->db->exec($sql);
        $sql = 'DELETE FROM sdb_package_product WHERE goods_id = '.intval($goodsid);
        $this->db->exec($sql);
        $this->updateGoodsIndex( intval( $goodsid ), null );
        return true;
    }

    function removePdt($goodsid, $aId){
        $strId = implode(',', $aId);
        $sql = 'DELETE FROM sdb_products WHERE goods_id = '.intval($goodsid).' AND product_id NOT IN ('.$strId.')';
        $this->db->exec($sql);
        $sql = 'DELETE FROM sdb_product_memo WHERE goods_id = '.intval($goodsid).' AND product_id NOT IN ('.$strId.')';
//        $this->db->exec($sql);
        $sql = 'DELETE FROM sdb_goods_lv_price WHERE goods_id = '.intval($goodsid).' AND product_id NOT IN ('.$strId.')';
        $this->db->exec($sql);
        $sql = 'DELETE FROM sdb_package_product WHERE goods_id = '.intval($goodsid).' AND product_id NOT IN ('.$strId.')';
//        $this->db->exec($sql);
    }

    //读取相关商品
    function getLinkList($goods_id){
        return $this->db->select('SELECT r.*, goods_id, bn, name FROM sdb_goods_rate r, sdb_goods
                WHERE ((goods_2 = goods_id AND goods_1='.intval($goods_id)
                .') OR (goods_1 = goods_id AND goods_2 = '.intval($goods_id)
                .' AND manual=\'both\')) AND rate > 99');
    }

//----EVER checked here--------

    function diff($gid,$error=0){

        if(!$gid) return array();
        foreach($gid as $t=>$v){
                $gid[$t]=intval($v);
        }
        $params = $this->db->select('select aGoods.*,aGimage.thumbnail from sdb_goods as aGoods
        LEFT JOIN sdb_gimages aGimage on aGoods.image_default=aGimage.gimage_id
        where aGoods.goods_id in ('.implode(',',$gid).')');
        $params2 = $this->db->select('select * from sdb_goods as A Left Join sdb_goods_type as B ON A.type_id = B.type_id where A.goods_id in ('.implode(',',$gid).')');
        $p_map = array();

        foreach($params2 as $i=>$p){
            $params2[$i]['props'] = unserialize($params2[$i]['props']);

            foreach($params2[$i]['props'] as $group=>$items){

                foreach($items as $p_name=>$v){
                    if($p_name=="name" or $p_name=="options"){
                        $tResult=$params2[$i]['props'][$group][options][$params2[$i]["p_".$group]];
                            if($tResult){
                                $p_map['基本属性'][$v][$p['goods_id']] = $tResult;

                            }else{
                                $p_map['基本属性'][$v][$p['goods_id']] =$params2[$i]["p_".$group];
                            }
                        }
                    }
            }
        }
        $objGoods = $this->system->loadModel('goods/products');
        $objGoods->getSparePrice($params, $GLOBALS['runtime']['member_lv']);
        foreach($params as $i=>$p){
            $params[$i]['params']=unserialize($params[$i]['params']);
            $params2[$i]['params']=unserialize($params2[$i]['params']);
            $params[$i]['pdt_desc']=$params[$i]['pdt_desc'];
            foreach($params[$i]['params'] as $group=>$items){
                    foreach($items as $p_name=>$v){
                        if(isset($params2[$i]['params'][$group][$p_name])){
                             $p_map[$group][$p_name][$p['goods_id']] = $v;
                        }
                    }

            }

        }
        return array('params'=>$p_map,'length'=>floor(80/count($gid)),'colp'=>count($gid)+1,'error'=>$error,'goods'=>$params,'cols'=>count($params)+1,'width'=>floor(100/(count($params)+1)).'%');
    }
    function updateGoods(&$aData){
        $rs = $this->db->query('SELECT * FROM sdb_goods WHERE goods_id='.$aData['goods_id']);
        $sql = $this->db->GetUpdateSQL($rs, $aData);
        return $this->db->query($sql);
    }/*}}}*/

    function getGoodsListByArray($aPara=array()){
        $whereSql = 'WHERE'.substr($whereSql, 4);
        if($sort) $sortSql=' ORDER BY '.$dir.' '.$sort;

        $sqlString = "SELECT * FROM sdb_goods WHERE goods_id IN (".implode(',', $aPara).")".$sortSql;
        $aRet = $this->db->select($sqlString);
        foreach($aRet as $key => $aRow){
            $aNew[$aRow['goods_id']] = $aRow;
        }
        return $aNew;
    }

    function getProducts($gid, $pid=0){
        $sqlWhere = '';
        if($pid > 0) $sqlWhere = ' AND A.product_id = '.$pid;
        $sql = "SELECT A.*,B.image_default FROM sdb_products AS A LEFT JOIN sdb_goods AS B ON A.goods_id=B.goods_id WHERE A.goods_id=".intval($gid).$sqlWhere;
        return $this->db->select($sql);
    }

    //获得会员的收藏的商品列表
    function getFavorite($nMemberId,$nPage){
        $aGid = $this->db->selectrow('SELECT addon FROM sdb_members WHERE member_id='.$nMemberId);
        if($aGid && $aGid['addon']!=''){
            $sSql = '';
            $aGid = unserialize($aGid['addon']);
            if($aGid['fav']){
                $params=$this->db->select_f('SELECT aGoods.*,aGimage.thumbnail FROM sdb_goods as aGoods
                left join sdb_gimages as aGimage on aGoods.image_default=aGimage.gimage_id
                WHERE aGoods.goods_id IN ('.implode(',',$aGid['fav']).')', $nPage, PERPAGE);
                $objGoods = $this->system->loadModel('goods/products');
                $result=$objGoods->getSparePrice($params['data'], $GLOBALS['runtime']['member_lv']);
                $params['data']=$result;
                return $params;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    function getNotify($nMemberId,$nPage){    //获取到货通知列表
        return $this->db->select_f("SELECT g.goods_id,g.cat_id,g.bn,g.mktprice,g.price,g.name,g.pdt_desc,g.score,g.store,
                    g.image_default,g.thumbnail_pic,gnotify_id,product_id FROM sdb_gnotify gn
                    LEFT JOIN sdb_goods g ON gn.goods_id=g.goods_id
                    WHERE g.name IS NOT NULL AND g.disabled='false' and gn.disabled='false' and gn.member_id=".$nMemberId, $nPage, PERPAGE);
    }

    function mEditFields($aGid){
        $sqlString = "SELECT distinct(g.type_id),g.type_id,t.props FROM sdb_goods g
            LEFT JOIN sdb_goods_type t ON t.type_id = g.type_id
             WHERE g.goods_id in (".implode(',',$aGid).")";
        $aRet = $this->db->select($sqlString);

        if(count($aRet)==1){
            return array(
                'type_id'=>$aRet[0]['type_id'],
                'props'=>unserialize($aRet[0]['props'])
                );
        }else{
            return false;
        }
    }

    function updateRank($gid, $item, $num=1){
        $weekMark = false;
        switch($item){
            case "comments_count":
            break;
//            $weekMark = 'view';
//            break;
            case "buy_count":
            $weekMark = 'buy';
            break;
            case "rank_count":
            break;
        }
        if($weekMark){
            $aGstat = $this->getFieldById($gid, array('count_stat'));
            $count_stat = unserialize($aGstat['count_stat']);
            $dayNum = day(time());
            $weekNum = $num;
            foreach($count_stat[$weekMark] as $day => $countNum){
                if($dayNum > $day+30) unset($count_stat[$weekMark][$day]);
                if($dayNum < $day+8) $weekNum += $countNum;
            }
            $count_stat[$weekMark][$dayNum] += $num;
            $sqlCol = ','.$weekMark.'_w_count='.intval($weekNum).', count_stat=\''.serialize($count_stat).'\'';
        }
        $sql = "UPDATE sdb_goods SET ".$item." = ".$item."+".intval($num).$sqlCol." WHERE goods_id =".intval($gid);
        return $this->db->exec($sql);
    }

    function addKeywords($gid, $keywords){
        $sql = 'INSERT INTO sdb_goods_keywords (goods_id, keyword) VALUES ';
        $keyword = array();
        foreach(array_unique($keywords) as $v){
            if(!trim($v))
                continue;
            $keyword[] = ' ('.$gid.', "'.$v.'") ';
        }
        $sql .= implode(',', $keyword);
        return $this->db->exec($sql);
    }

    function getKeywords($gid){
        return $this->db->select('SELECT keyword FROM sdb_goods_keywords WHERE goods_id = '.$gid);
    }

    function deleteKeywords($gid){
        return $this->db->exec('DELETE FROM sdb_goods_keywords WHERE goods_id = '.$gid);
    }

    function getAllKeywords(){
        return $this->db->select('SELECT keyword FROM sdb_goods_keywords GROUP BY keyword');
    }

    function getGoodsIdByKeyword($keywords){
        return $this->db->select('SELECT goods_id FROM sdb_goods_keywords WHERE keyword in ( "'.implode('","',$keywords).'" )');
    }
}
?>
