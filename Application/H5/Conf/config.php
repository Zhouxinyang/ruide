<?php
return array(
	//'配置项'=>'配置值'
    'HTML_CACHE_ON'     =>    false, // 开启静态缓存
    'HTML_CACHE_TIME'   =>    60,   // 全局静态缓存有效期（秒）
    'HTML_FILE_SUFFIX'  =>    '.shtml', // 设置静态缓存文件后缀
    'HTML_CACHE_RULES'  =>     array(  // 定义静态缓存规则
         // 商城首页
         'mall:index'    =>     array('h5/mall_index', 600), 
         // 全部商品
         'goods:all'    =>     array('h5/goods_all_{tag}', 1800), 
         // 商品详情
         'goods:index'    =>     array('h5/goods_{id}', 600), 
         // 购物车
         'cart:index'    =>     array('h5/cart', 1800), 
         // 订单
         'order:index'    =>     array('h5/order',  1800), 
    )
);