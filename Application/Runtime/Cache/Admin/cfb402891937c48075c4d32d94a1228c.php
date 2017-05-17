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
			<div class="content-container"><div id="toolbar" class="toolbar">
	<form id="search_form" class="form-horizontal" style="margin:0">
		<div class="clearfix">
			<div class="filter-groups">
				<div class="control-group">
					<label class="control-label" style="width: 120px;">签收时间</label>
					<div class="controls" style="margin-left:125px;">
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="sign_start" style="width: 131px;" value="<?php echo date('Y-m-d 00:00:00', strtotime('-1 day'));?>">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
						至
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="sign_end" style="width: 131px;" value="<?php echo date('Y-m-d').' 23:59:59';?>">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" style="width: 120px;">导入时间</label>
					<div class="controls" style="margin-left:125px;">
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="created_start" style="width: 131px;">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
						至
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="created_end" style="width: 131px;">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
					</div>
				</div>
			</div>
			<div class="filter-groups">
				<div class="control-group">
					<label class="control-label">快递单号</label>
					<div class="controls">
						<input type="text" name="id" maxlength="18"　value="" placeholder="请输入完整的单号">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">操　　作</label>
					<div class="controls">
						<a href="#importModal" role="button" class="btn" data-toggle="modal">导入</a>
						<input type="button" class="btn btn-info" value="导出" id="btn-export">
						<input type="submit" class="btn btn-primary" value="搜索" style="margin-left:45px">
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __CONTROLLER__; ?>" data-toolbar="#toolbar"  data-page-list="[1, 10, 25, 50, All]">
	<thead>
		<tr>
			<th data-width="150" data-field="id">快递单号</th>
			<th data-width="150" data-field="created">发货时间</th>
			<th data-width="200" data-field="nickname">派件代理</th>
			<th data-width="150" data-field="mobile">手机号</th>
			<th data-width="150" data-field="sign_time">签收时间</th>
			<th data-width="100" data-field="amount">奖励金额</th>
			<th data-field="times">扫码次数</th>
		</tr>
	</thead>
</table>

<!-- 导入模态框 -->
<form id="importModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" method="post" action="/admin/handbag_express/import" enctype="multipart/form-data" target="_balank">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">导入运单号</h3>
  </div>
  <div class="modal-body">
  	<div class="text-center">
	    <input type="file" name="file">
  	</div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
    <button type="submit" class="btn btn-primary">开始导入</button>
  </div>
</form>

<script>
$(function(){
	$('#btn-export').on('click', function(){
		var data = $('#search_form').serializeArray();
		var url = '<?php echo __CONTROLLER__; ?>/export?';
		for(var i=0; i<data.length; i++){
			url += (i > 0 ? '&' : '')+data[i].name+'='+data[i].value;
		}
		window.location.href = url;
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