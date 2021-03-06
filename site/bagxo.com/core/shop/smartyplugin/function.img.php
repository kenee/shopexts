<?php
function smarty_function_img($params, &$smarty){

    if($params['path']) $params['src'] = 'statics/'.$params['path'].'/'.$params['src'];
    $params['src'] = str_replace('//','/',$params['src']);
    unset($params['path']);

    $imgLib = array(
/*- begin -*/

'statics/icons/arrow_1.gif'=>'width:11px;height:11px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -0px;',
'statics/icons/arrow_10.gif'=>'width:9px;height:9px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -11px;',
'statics/icons/arrow_11.gif'=>'width:12px;height:8px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -20px;',
'statics/icons/arrow_2.gif'=>'width:7px;height:7px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -28px;',
'statics/icons/arrow_3.gif'=>'width:7px;height:7px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -35px;',
'statics/icons/arrow_4.gif'=>'width:9px;height:9px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -42px;',
'statics/icons/arrow_5.gif'=>'width:11px;height:11px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -51px;',
'statics/icons/arrow_6.gif'=>'width:17px;height:7px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -62px;',
'statics/icons/arrow_7.gif'=>'width:5px;height:5px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -69px;',
'statics/icons/arrow_8.gif'=>'width:18px;height:14px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -74px;',
'statics/icons/arrow_9.gif'=>'width:11px;height:11px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -88px;',
'statics/icons/btn_adj_buy.gif'=>'width:64px;height:25px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -99px;',
'statics/icons/btn_goods_gallery.gif'=>'width:101px;height:26px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -124px;',
'statics/icons/btn_pkg_buy.gif'=>'width:64px;height:24px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -150px;',
'statics/icons/icon_asc.gif'=>'width:13px;height:12px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -174px;',
'statics/icons/icon_asc_gray.gif'=>'width:13px;height:12px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -186px;',
'statics/icons/icon_delete.gif'=>'width:13px;height:12px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -198px;',
'statics/icons/icon_desc.gif'=>'width:13px;height:12px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -210px;',
'statics/icons/icon_desc_gray.gif'=>'width:13px;height:12px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -222px;',
'statics/icons/pic6.gif'=>'width:30px;height:30px;background-image:url(statics/icons.gif);background-repeat:no-repeat;background-position:0 -234px;',

'statics/bundle/arrow-down.gif'=>'width:11px;height:11px;background-image:url(statics/bundle.gif);background-repeat:no-repeat;background-position:0 -0px;',
'statics/bundle/cart.gif'=>'width:16px;height:16px;background-image:url(statics/bundle.gif);background-repeat:no-repeat;background-position:0 -11px;',
'statics/bundle/ico_comment_1.gif'=>'width:10px;height:10px;background-image:url(statics/bundle.gif);background-repeat:no-repeat;background-position:0 -27px;',
'statics/bundle/ico_comment_2.gif'=>'width:10px;height:10px;background-image:url(statics/bundle.gif);background-repeat:no-repeat;background-position:0 -37px;',
'statics/bundle/spacer.gif'=>'width:2px;height:2px;background-image:url(statics/bundle.gif);background-repeat:no-repeat;background-position:0 -47px;',
'statics/bundle/wheel.gif'=>'width:24px;height:27px;background-image:url(statics/bundle.gif);background-repeat:no-repeat;background-position:0 -49px;',

/*- end -*/
    );

    if(isset($imgLib[$params['src']])){
        $system = &$GLOBALS['system'];
        $params['style'] = $imgLib[$params['src']].$params['style'];
        $params['src'] = $system->base_url().'/statics/transparent.gif';
    }
    return buildTag($params,'img border="none"');
}
?>
