<?php
/**
 * mdl_url 
 * 
 * @uses modelFactory
 * @package 
 * @version $Id$
 * @copyright 2003-2007 ShopEx
 * @author Wanglei <flaboy@shopex.cn> 
 * @license Commercial
 */
class mdl_url extends modelFactory{

    /**
     * map 
     * 读取plugins/function/urlmap
     * 
     * @param mixed $url 
     * @access public
     * @return void
     */
    function map($url){
        $map = null;
        if(include(PLUGIN_DIR.'/functions/urlmap.php')){
            if(is_array($map) && ($url!=($result = preg_replace(array_keys($map),$map,$url)))) {
                $result = explode('|',$result);
                if(count($result)>2){
                    $ctl = array_shift($result);
                    $act = array_shift($result);
                    return $this->system->mkUrl($ctl,$act,$result);
                }else{
                    return $this->system->mkUrl($result[0],$result[1]);
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * oldVersionShopEx 
     * gOo的处理
     * 
     * @param mixed $action 
     * @access public
     * @return void
     */
    function oldVersionShopEx($action){

        if($ret = $this->getGOO($action)){
            return $ret;
        }else{
            $action['gOo'] = base64_decode($action['gOo']);
            if($ret = $this->getGOO($action)){
                return $ret;
            }else{
                return $this->system->mkUrl('sitemap','index');
            }
        }

    }

    function getGOO($action){
        switch($action['gOo']){

            case 'goods_search_list.dwt':
            case 'goods_category.dwt':
                return $this->system->mkUrl('gallery',$this->system->getConf('gallery.default_view'),array($action['gcat'],null,0,null,$action['p']));

            case 'goods_details.dwt':
                return $this->system->mkUrl('product','index',array($action['goodsid']));

            case 'article_list.dwt':
                return $this->system->mkUrl('artlist','index',array($action['acat']));

            case 'article_details.dwt':
                return $this->system->mkUrl('article','index',array($action['articleid']));

            case 'register.dwt':
                return $this->system->mkUrl('passport','signup');

            case 'logout_act.do':
                return $this->system->mkUrl('passport','logout');

            case 'forget.dwt':
                return $this->system->mkUrl('passport','forget');

            case 'discuz_reply.do':
                include_once(CORE_DIR."/func_ext.php");
                return $this->system->mkUrl('passport','callback',array('discuz')).'?action='.http_build_query($action);


//      case 'shopbbs.dwt':
//        break;
//      case 'linkmore.dwt':
//        break;

            case 'logout_act.do':
                return $this->system->mkUrl('passport','logout');

            default :
                return false;
        }

    }

}
?>
