<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.com/license/gpl GPL License
 */
 
class b2c_promotion_conditions_order_subtotalselectgoods{
    public $tpl_name = "当订单商品总价满X,对指定的商品优惠";

    public function getConfig($aData = array()) {
        /////////////////////////////////////// conditions ////////////////////////////////////////////////
        $aConfig['conditions']['type'] = 'html';
        $aConfig['conditions']['info'] = '';

        ///////////////////////////////// action_conditions ///////////////////////////////////////////////
        $aConfig['action_conditions']['type'] = 'config';
        $aConfig['action_conditions']['info'] = array(
                                                'aggregator'=>'all',
                                                'value'=>1,
                                                'conditions'=>array(
                                                                array(
                                                                    'type'=>'b2c_sales_order_item_goods',
                                                                    'attribute'=>'goods_goods_id'
                                                                )
                                                              )
                                             );
        return $aConfig;
    }

    public function getTemplate($aData = array(),$type = 'conditions') {

        switch($type) {
            case 'conditions':
                return  <<<EOF
        订单金额满
    <input type="hidden" name="conditions[type]" value="b2c_sales_order_aggregator_combine" />
    <input type="hidden" name="conditions[aggregator]" value="all" />
    <input type="hidden" name="conditions[value]" value="1" />
    <input type="hidden" name="conditions[conditions][0][type]" value="b2c_sales_order_item_order" />
    <input type="hidden" name="conditions[conditions][0][attribute]" value="order_subtotal" />
    <input type="hidden" name="conditions[conditions][0][operator]" value=">=" />
    <input type="text" name="conditions[conditions][0][value]" size="3" vtype="required&&digits" value="{$aData['conditions']['conditions'][0]['value']}" />
EOF;
                break;
        }
    }
}
?>
