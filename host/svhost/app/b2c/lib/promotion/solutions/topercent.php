<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.com/license/gpl GPL License
 */
 
/**
 * 单商品价格减去固定折扣出售
 * $ 2010-05-04 17:06 $
 */
class b2c_promotion_solutions_topercent implements b2c_interface_promotion_solution
{
    
    
    public $name = "以固定折扣出售"; // 名称
    public $type = array('prefilter','cart'); // 应用范围 目前只有[预过滤(prefilter),购物车(cart)]
    public $desc_pre = '价格乘以';
    public $desc_post = '%折扣出售';
    private $description = '';

    /**
     * 优惠方案模板
     * @param array $aConfig // 设置信息(修改的时候传入)
     * @return string // 返回要输出的模板html
     */
    public function config($aData = array()) {
        return <<<EOF
{$this->desc_pre}<input name="action_solution[b2c_promotion_solutions_topercent][percent]" vtype='required&&unsigned' value="{$aData['percent']}" />{$this->desc_post}
EOF;
    }

    /**
     * 优惠方案应用
     *
     * @param array $object  // 引用的一个商品信息
     * @param array $aConfig // 优惠的设置
     * @param array $cart_object // 购物车信息(预过滤的时候这个为null)
     * @return void // 引用处理了,没有返回值
     */
    public function apply(&$object,$aConfig,&$cart_object = null) {
        $rulePercent = max(0, $aConfig['percent']);
        $rulePercent = min($rulePercent, 100);

        if(is_null($cart_object)) { // 商品预过滤
            $object['obj_items']['products'][0]['price']['buy_price'] *= $rulePercent / 100 ;
        } else {// 购物车里的处理
            $productQuantity = $object['obj_items']['products'][0]['quantity'];
            $goodsQuantity = $object['quantity'];
            $qty = $productQuantity * $goodsQuantity;
            $productPrice = $object['obj_items']['products'][0]['price']['buy_price'];
            $discountAmount = ($qty * $productPrice) * (1 - ($rulePercent /100));
            $object['discount_amount_order'] += $discountAmount;
            //$this->desc_pre = '总价格乘以';
        }
        
        $this->setString($aConfig);
    }
    
    
    
    /**
     * 优惠方案应用
     *
     * @param array $object  // 引用的一个商品信息
     * @param array $aConfig // 优惠的设置
     * @param array $cart_object // 购物车信息(预过滤的时候这个为null)
     * @return void // 引用处理了,没有返回值
     */
    public function apply_order(&$object, &$aConfig,&$cart_object = null) {
        if(is_null($cart_object)) return false;
        
        $rulePercent = max(0, $aConfig['percent']);
        $rulePercent = min($rulePercent, 100);
        
        $object['discount_amount_order'] += $cart_object['subtotal'] * (1 - ($rulePercent /100));
        $this->desc_pre = '订单总价格乘以';
        $this->setString($aConfig);
    }
    
    
    
    
    
    
    public function setString($aData) {
        $this->description = $this->desc_pre . $aData['percent'] . $this->desc_post;
    }
    
    public function getString() {
        return $this->description;
    }
    
    
    
    public function get_status() {
        return true;
    }
    
    
}
