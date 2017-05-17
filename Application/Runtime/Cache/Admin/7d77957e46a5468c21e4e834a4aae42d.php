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
			<div class="content-container"> <div id="toolbar" class="toolbar" data-module="/admin/feedback"><?php \Common\Common\Auth::get()->showTollbar('admin', 'feedback', 'index') ?><form id="level_search" class="form-horizontal">
		<div class="clearfix">
			<div class="filter-groups">
				<?php if($access == true): ?><div class="control-group">
						<label class="control-label">操作人</label>
						<div class="controls">
							<select name="user_id" id="sel_users" style="width:213px;">
							  <option value="">全部</option>
				              <?php if(is_array($users)): foreach($users as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>"><?php echo ($item["nick"]); ?>-<?php echo ($item["username"]); ?></option><?php endforeach; endif; ?>
							</select>
						</div>
					</div><?php endif; ?>
				<div class="filter-groups">
					<div class="control-group">
						<label class="control-label">商品名称</label>
						<div class="controls">
							<input type="text" name="title">
						</div>
					</div>
				</div>
			</div>
			<div class="pull-left">
				<div class="control-group">
					<label class="control-label" style="width: 120px;">操作时间</label>
					<div class="controls" style="margin-left:125px;">
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="start_date" value="<?php echo ($search["start_date"]); ?>"style="width: 130px;">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
						至
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="end_date" value="<?php echo ($search["end_date"]); ?>" style="width: 130px;">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
					</div>
				</div>
			</div>
	 	</div>
	</form></div>
<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __CONTROLLER__; ?>" data-toolbar="#toolbar"  data-page-list="[1, 10, 25, 50, All]">
    <thead>
		<tr>
			<th data-width="40" data-checkbox="true"></th>
			<th data-width="100" data-field="nick">操作人</th>
			<th data-width="100" data-field="username">操作账号</th>
			<th data-width="200" data-field="title">商品名称</th>
			<th data-width="150" data-field="created">操作时间</th>
			<th data-field="question">反馈信息</th>
		</tr>
	</thead>
</table>

<script type="text/javascript" src="/js/select2.min.js"></script>
<link rel="stylesheet" href="/css/select2.min.css">

<script type="text/javascript">
//多选
$("#sel_users").select2();
$("#sel_goods").select2();
</script>
</div>
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