<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.com/license/gpl GPL License
 */
 
interface base_interface_router{

    function __construct($app);

    function gen_url($params=array());

    function dispatch($query);

}
