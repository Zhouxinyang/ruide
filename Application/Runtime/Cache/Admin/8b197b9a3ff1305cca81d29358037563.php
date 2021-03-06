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
			<div class="system-name">瑞德健康授权管理系统</div>
			<div class="box_message">
				<ul class="top_tool">
					<!-- <li class="message_all"><a><i class="icon-envelope icon-white"></i> 消息<span>(0)</span></a></li> -->
                    <?php $shopId = session('user.shop_id'); $aliAccessToken = M('alibaba_token')->find($shopId); if(!empty($aliAccessToken)){ $aliAccessToken['refresh_day'] = floor((strtotime($aliAccessToken['refresh_token_timeout']) - time())/86400); if(!empty($aliAccessToken)){ echo '<li class="set_all" title="'.$aliAccessToken['refresh_token_timeout'].'"><a href="/admin/index/aliOAuth"><i class="icon-time icon-white"></i> 1688授权有效期：'.$aliAccessToken['refresh_day'].'天</a></li>'; } } ?>
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
			<div class="content-container"><div id="toolbar" class="toolbar" data-module="/admin/ruide"><?php \Common\Common\Auth::get()->showTollbar('admin', 'ruide', 'index') ?><form class="form-horizontal">
		<input type="text" class="hidden" name="data[id]">
		<div class="control-group span6">
			<label class="control-label">姓名</label>
			<div class="controls">
				<input type="text" name="data[username]" data-search="true" placeholder="最多8个字符" maxlength="8">
			</div>
		</div>
		<div class="control-group span6">
			<label class="control-label">身份证号码</label>
			<div class="controls">
				<input type="text" name="data[card]" data-search="true" placeholder="请输入身份证号码" maxlength="18">
			</div>
		</div>
		<div class="control-group span6">
			<label class="control-label">微信号</label>
			<div class="controls">
				<input type="text" name="data[wechat]" data-search="true" placeholder="请输入微信号" maxlength="20">
			</div>
		</div>
		<div class="control-group span6">
			<label class="control-label">手机号码</label>
			<div class="controls">
				<input type="text" name="data[mobile]" data-search="true" placeholder="请输入手机号码" maxlength="11">
			</div>
		</div>
	</form></div>

<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __CONTROLLER__; ?>" data-toolbar="#toolbar" data-side-pagination="client">
    <thead>
		<tr>
			<th data-width="40" data-align="center" data-checkbox="true"></th>
			<th data-width="150" data-field="username">姓名</th>
			<th data-width="250" data-field="card">身份证号码</th>
			<th data-width="150" data-field="wechat">微信号</th>
			<th data-width="200"  data-field="mobile">手机号</th>

		</tr>
	</thead>
</table>

<script type="text/javascript">

$(function() {
    $('#table').on('password', function (e, gridview, params) {
        if (gridview.currentRow == null) {
            alertMsg('请先选择要修改密码的用户');
            return false;
        }
        params.data.id = gridview.currentRow.id;
    }).on('check',function(e, gridview ,params){
        if(gridview.currentRow == null){
            alertMsg('请先选择要查看的用户！');
            return false;
        }
        params.url = '<?php echo __CONTROLLER__; ?>/check?id=' + gridview.currentRow.id;
    });
});
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