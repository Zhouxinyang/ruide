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
			<div class="content-container"><div id="toolbar" class="toolbar" data-module="/admin/shop"><?php \Common\Common\Auth::get()->showTollbar('admin', 'shop', 'all') ?><form class="edit-form form-horizontal">
		<input type="hidden" name="pid" class="js-pid">
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">店铺名称</label>
				<div class="controls">
					<input type="text" name="name" data-search="true">
				</div>
			</div>
		</div>
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">店铺类别</label>
				<div class="controls">
					<select name="type">
						<option value="">全部</option>
						<?php if(is_array($allType)): foreach($allType as $id=>$name): ?><option value="<?php echo ($id); ?>"><?php echo ($name); ?></option><?php endforeach; endif; ?>
					</select>
				</div>
			</div>
		</div>
	</form></div>

<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __ACTION__; ?>" data-toolbar="#toolbar">
    <thead>
		<tr>
			<th data-width="40" data-checkbox="true"></th>
			<th data-width="200" data-field="name">店铺名称</th>
			<th data-width="80" data-field="type" data-formatter="formatter_type">店铺类别</th>
			<th data-width="100" data-field="contacts">联系人</th>
			<th data-width="110" data-field="mobile">联系电话</th>
			<th data-field="address" data-formatter="formatter_address">店铺地址</th>
			<th data-width="200" data-align="right" data-formatter="formatter_action">操作</th>
		</tr>
	</thead>
</table>

<script src="__CDN__/js/address.js"></script>
<script>
var shopType = <?php echo json_encode($allType);?>;
function formatter_type(val){
	return shopType[val];
}

function formatter_address(val, row){
	return Address.get(row.city_id) + ' ' + Address.get(row.county_id)
}

var access = [];
var $access = $('#toolbar>.btn-list').find('button[data-name="edit"],button[data-name="delete"]');
for(var i=0; i<$access.length; i++){
	access.push($access.eq(i).attr('data-name'));
}
function formatter_action(val, row, index){
	var action = [];
	
	if(access.indexOf('edit') > -1){
		action.push('<a href="<?php echo __CONTROLLER__; ?>/edit?id='+row.id+'">编辑</a>');
	}

	if(access.indexOf('delete') > -1){
		action.push('<a href="javascript:;" class="js-delete" data-id="'+row.id+'">删除</a>');
	}
	
	action.push('<a href="<?php echo __MODULE__; ?>/goods?shop_id='+row.id+'">商品</a>');
	return action.join(' - ');
}
(function(){
	var $table = $('#table');
	$table.on('click', '.js-delete',function(){
		if(!confirm('操作不可恢复，确定删除吗？')){
			return false;
		}

		var id = $(this).data('id');
		$.ajax({
			url: '<?php echo __CONTROLLER__; ?>/delete',
			type: 'post',
			datatType: 'json',
			data: {id: id},
			success: function(){
				$table.gridView('refresh');
			}
		});
		return false;
	});
})();
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