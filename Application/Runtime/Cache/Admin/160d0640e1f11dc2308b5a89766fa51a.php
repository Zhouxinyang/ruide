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
			<div class="content-container"><div id="toolbar" class="toolbar" data-module="/admin/alibaba"><?php \Common\Common\Auth::get()->showTollbar('admin', 'alibaba', 'goodses') ?><form class="edit-form form-horizontal">
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">店铺名称</label>
				<div class="controls">
					<input type="text" name="name" data-search="true">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">阿里商品ID</label>
				<div class="controls">
					<input type="text" name="tao_id" data-search="true">
				</div>
			</div>
		</div>
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">阿里标题</label>
				<div class="controls">
					<input type="text" name="key" data-search="true">
				</div>
			</div>
		</div>
	</form></div>

<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __ACTION__; ?>" data-toolbar="#toolbar"  data-page-list="[1, 10, 25]" data-page-size="10">
	<thead>
		<tr>
			<th data-width="85" data-field="images" data-formatter="formatter_img">商品</th>
			<th data-field="subject" data-formatter="formatter_title">摘要</th>
			<th data-width="95" data-field="price" data-formatter="formatter_price">属性</th>
			<th data-width="135" data-field="last_sync" data-formatter="formatter_sync" data-align="center">同步时间</th>
			<th data-width="135" data-formatter="formatter_do">操作</th>
		</tr>
	</thead>
</table>

<script>
function formatter_img(val, row, index){
	var images = eval(row.images);
	return '<a target="_blank" href="https://detail.1688.com/offer/'+row.tao_id
		+'.html"><img src="'+images[0]+'" style="width:64px; height:64px;"></a>';
}

function formatter_title(val, row, index){
	return (row.title?row.title+'<span class="label'+(row.is_display==1?' label-warning'
		:' label-important')+'">'+(row.is_display==1?'售卖中':'已下架')+'</span>':'')
		+'<br/><a target="_blank" href="https://amos.alicdn.com/getcid.aw?v=3&uid='
		+encodeURI(row.seller_nick)+'&site=cnalichn&groupid=0&s=1&charset=UTF-8'
		+'"><img border="0" src="http://amos.alicdn.com/realonline.aw?v=2&uid='
		+encodeURI(row.seller_nick)+'&site=cntaobao&s=1&charset=utf-8" alt="点'
		+'击这里给我发消息" /> '+row.seller_nick+'</a><br/>阿里商品ID：'+row.tao_id
		+'<br/>'+row.subject;
}

function formatter_price(val, row, index){
	var status = '<span style="color:#f00;">不可售</span>';
	if (row.status == 'published') {
		status = '可售';
	}
	return '<i class="icon-inbox"></i> '+row.stock+' '+row.unit
		+'<br/><i class="icon-dashboard"></i> '+row.weight+' kg'
		+'<br/><i class="icon-yen"></i> '+row.price+' 元'
		+'<br/><i class="icon-glass"></i> '+status;
}

function formatter_sync(val, row, index){
	return row.last_sync+'<br/><span style="color:#aaa;"><i class="icon-arrow-up"></i>'
		+'阿里 平台<i class="icon-arrow-down"></i></span><br/>'
		+(row.last_update?row.last_update:'无同步时间');
}

function formatter_do(val, row, index){
	var action = [];
	action.push('<a class="js-alibaba" data-id="'+row.tao_id+'">详情</a>');
	if(row.title == undefined){ // 1688
		action.push('<a href="<?php echo __MODULE__; ?>/goods/add?tao_id='+row.tao_id+'">上架</a>');
	}else{
		action.push('<a href="<?php echo __MODULE__; ?>/goods/edit?id='+row.id+'">同步</a>');
	}

	return '<br/>'+action.join(' - ');
}

(function(){
	var $table = $('#table');
	$table.on('click', '.js-alibaba',function(){
		var id = $(this).data('id');
		$.get('<?php echo __CONTROLLER__; ?>/detail?tao_id='+id, function(html){
			$('body').append(html);
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