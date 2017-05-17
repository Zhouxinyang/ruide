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
			<div class="content-container"><div id="toolbar" class="toolbar" data-module="/admin/sale"><?php \Common\Common\Auth::get()->showTollbar('admin', 'sale', 'shopstat') ?><form id="order_search" class="form-horizontal">        	<div class="control-group">					<label class="control-label" style="width: 120px;">支付时间</label>					<div class="controls" style="margin-left:125px;">						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">							<input type="text" name="start_date" value="<?php echo ($start_date); ?>"style="width: 135px;">							<span class="add-on"><i class="icon-th"></i></span>						</div>						至						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">							<input type="text" name="end_date" value="<?php echo ($end_date); ?>" style="width: 135px;">							<span class="add-on"><i class="icon-th"></i></span>						</div>					</div>				</div>    </form></div><table id="table" data-toggle="gridview" class="table" data-url="<?php echo __ACTION__; ?>" data-toolbar="#toolbar"  data-show-columns="true" data-side-pagination="client" data-page-size="10" data-page-list="[6, 10, 25, 50, All]">    <thead>        <tr>            <th data-field="nick">店铺</th>            <th data-field="count" data-formatter="fomatter_num">笔数/人数/件数</th>            <th data-field="total_fee" >商品总额</th>            <th data-field="post_fee" >总邮费</th>             <th data-field="paid_balance" >零钱抵用</th>            <th data-field="paid_no_balance" >积分抵用</th>            <th data-field="discount_fee" >优惠总额</th>            <th data-field="payment" >微信支付</th>            <th data-field="wechat_fee">手续费</th>             <th data-field="trade_difference" >差价</th>            <th data-field="total_cost" >成本</th>            <th data-field="refund_fee" data-formatter="fomatter_refund_fee">退款总额/次</th>        </tr>    </thead></table><script type="text/javascript">function fomatter_num(val, row, index){    return row['count'] + '/' + row['buyer_id'] + '/' + row['total_num']} function fomatter_refund_fee(val, row, index){    return row['refund_fee'] + '/' + row['refund_num']}</script></div>
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