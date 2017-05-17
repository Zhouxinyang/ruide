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
			<div class="content-container"><div id="toolbar" class="toolbar" data-module="/admin/recharge"><?php \Common\Common\Auth::get()->showTollbar('admin', 'recharge', 'index') ?><form class="form-horizontal">
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">代理手机号</label>
				<div class="controls" style="position: relative;">
					<input type="text" name="mobile" style="width:160px">
					<label class="checkbox inline" style="position: absolute;right: 0;top: 0;border-left: 1px solid #ddd;bottom: 0;padding-right: 10px;">
						<input type="checkbox" checked="checked" value="success" name="status" style="margin-left: -10px;">仅成功
					</label>
				</div>
			</div>
		</div>
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label" style="width: 120px;">操作时间</label>
				<div class="controls" style="margin-left:125px;">
					<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
						<input type="text" name="start_date" value="<?php echo ($start_date); ?>">
						<span class="add-on"><i class="icon-th"></i></span>
					</div>
					至
					<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
						<input type="text" name="end_date" value="<?php echo ($end_date); ?>" >
						<span class="add-on"><i class="icon-th"></i></span>
					</div>
					<input type="button" id="btn_export" value="导出" class="btn" style="margin-left:50px">
					<input type="submit" value="搜索" class="btn btn-primary">
				</div>
			</div>
   		</div>
	</form></div>

<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-toolbar="#toolbar" data-url="<?php echo __CONTROLLER__; ?>" data-unique-id="tid">   
    <thead>
		<tr>
			<th data-width="150" data-field="nickname">代理姓名</th>
			<th data-width="100" data-field="once_amount" data-align="center">充值金额</th>
			<th data-field="self_amount" data-align="center">赠送货款</th>
			<th data-field="parent1" data-align="center" data-formatter="formater_parent">一级代理收益</th>
			<th data-field="parent2" data-align="center" data-formatter="formater_parent">二级代理收益</th>
			<th data-field="parent3" data-align="center" data-formatter="formater_parent">三级代理收益</th>
			<th data-field="status_str" data-width="80" data-align="center">状态</th>
			<th data-field="created" data-width="135">操作时间</th>
		</tr>
	</thead>
</table>
<script>
function formater_parent(val, row, index){
	return !val ? null : '<a href="<?php echo __MODULE__; ?>/member?mid='+val.id+'" target="parent">'+val.money+val.agent_title+'</a>';
}
$(function(){
	$('#btn_export').on('click', function(){
		var url = '<?php echo __CONTROLLER__; ?>/export';
		var array = $('#toolbar form').serializeArray();
		for(var i=0; i<array.length; i++){
			url += (i == 0 ? '?' : '&') + array[i].name + '=' + array[i].value;
		}

		window.open(url);
		return false;
	});
});
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