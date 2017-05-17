<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no"/>
    <title><?php echo C('WEBSITE_NAME');?></title>
    <?php if(!preg_match('/(MSIE 7.0)/', $_SERVER['HTTP_USER_AGENT'])){ echo '<link href="//cdn.bootcss.com/pace/1.0.2/themes/orange/pace-theme-flash.css" rel="stylesheet">'; echo '<script src="//cdn.bootcss.com/pace/1.0.2/pace.min.js"></script>'; } ?>
    <link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/2.3.2/css/bootstrap.min.css" />
	<link rel="stylesheet" href="//cdn.bootcss.com/font-awesome/3.2.1/css/font-awesome.min.css" />
    <link rel="stylesheet" href="/css/admin.css" />
    <script src="//cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
	<!-- 顶部 -->
	<div class="sys-header">
		<div class="inner">
			<div class="system-name"><?php echo session('user.shop_name'); ?></div>
			<div class="box_message">
				<ul class="top_tool">
					<!-- <li class="message_all"><a><i class="icon-envelope icon-white"></i> 消息<span>(0)</span></a></li> -->
                    <?php $shopId = session('user.shop_id'); $aliAccessToken = M('alibaba_token')->find($shopId); if(!empty($aliAccessToken)){ $aliAccessToken['refresh_day'] = floor((strtotime($aliAccessToken['refresh_token_timeout']) - time())/86400); if(!empty($aliAccessToken)){ echo '<li class="set_all" title="'.$aliAccessToken['refresh_token_timeout'].'"><a href="/admin/index/aliOAuth"><i class="icon-time icon-white"></i> 1688授权有效期：'.$aliAccessToken['refresh_day'].'天</a></li>'; } } ?>
					<li class="set_all"><a href="javascript:win.modal('/admin/index/modifySwitch')"><i class="icon-retweet"></i> 切换店铺</a></li>
					<li class="set_all"><a href="javascript:win.modal('/admin/index/modifyPwd')"><i class="icon-lock icon-white"></i> 修改密码</a></li>
					<li class="exit_soft"><a href="/admin/login/out"><i class="icon-off icon-white"></i> 退出</a></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="body">
		<div class="col-side">
	    	<?php \Common\Common\Auth::get()->showMenuHtml() ?>
    	</div>
	    <div class="col-main">
	    	<div class="navbar menu-group">
			    <ul class="nav">
	    		  <?php echo \Common\Common\Auth::get()->showMenuGroup() ?>
			    </ul>
			</div>
			<div class="content-container"><div id="toolbar" class="toolbar" data-module="/admin/difference"><?php \Common\Common\Auth::get()->showTollbar('admin', 'difference', 'index') ?><form id="order_search" class="form-horizontal">
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">订单ID</label>
				<div class="controls">
					<input type="text" name="tid" style="width:348px;">
				</div>
			</div>
			
			<div class="control-group">
				<label class="control-label" style="width: 120px;">下单时间</label>
				<div class="controls" style="margin-left:125px;">
					<div class="input-append date" data-format="yyyy-MM-dd">
						<input type="text" name="start_date" value="<?php echo ($start_date); ?>">
						<span class="add-on"><i class="icon-th"></i></span>
					</div>
					至
					<div class="input-append date" data-format="yyyy-MM-dd">
						<input type="text" name="end_date" value="<?php echo ($end_date); ?>" >
						<span class="add-on"><i class="icon-th"></i></span>
					</div>
				</div>
			</div>
		</div>
		
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">收益人手机号</label>
				<div class="controls">
					<input type="text" name="mobile" data-rule-mobile="true">
				</div>
			</div>
			
			<div class="control-group">
					<label class="control-label">是否结算</label>
					<div class="controls">
						<select name="checkout" >
							<option value="1">已结算</option>
							<option value="0">未结算</option>
						</select>
					</div>
			</div>
		</div>
	</form></div>

<!--表单 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __CONTROLLER__; ?>" data-toolbar="#toolbar">
    <thead>
		<tr>
			<th data-field="tid" data-width="110">订单编号</th>
			<th data-field="buyer_nick" data-width="100">下单人</th>
			<th data-field="nickname" data-width="100">收益人</th>
			<th data-field="mobile" data-width="110" data-align="center">收益人手机号</th>
			<th data-field="diff_price" data-width="60" data-align="center">差价</th>
			<th data-field="num" data-width="40" data-align="center">数量</th>
			<th data-field="total_fee" data-width="60" data-align="center">收益</th>
			<th data-field="checkout" data-width="60">结算</th>
            <th data-field="product_id" data-formatter="formatter_title">商品</th>
		</tr>
	</thead>
</table>
<script>
  function formatter_title(val, row, index){
  	return '<div><a href="/h5/goods?id='+row.goods_id+'" target="_blank">'+row.title+'</a></div><div>'+row.sku_json+'</div>'
  }  
</script></div>
	    </div>
	</div>
	<div class="footer">
		<div class="copyright">
			<div class="ft-copyright"></div>
		</div>
	</div>
	<div class="back-to-top">
	    <a href="javascript:;" class="js-back-to-top"><i class="icon-chevron-up"></i>返回顶部</a>
	</div>
	<script src="/js/common.js"></script>
	<script src="/js/gridview.js"></script>
	<script src="//cdn.bootcss.com/bootstrap/2.3.2/js/bootstrap.min.js"></script>
</body>
</html>