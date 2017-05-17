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
			<div class="content-container"><div id="toolbar" class="toolbar" data-module="/admin/category"><?php \Common\Common\Auth::get()->showTollbar('admin', 'category', 'index') ?></div>
<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table" data-url="<?php echo __CONTROLLER__; ?>" data-toolbar="#toolbar" data-side-pagination="client" data-tree-view="true" data-pagination="false">
    <thead>
        <tr>
            <th data-width="40" data-checkbox="true"></th>
            <th data-width="150" data-field="name" data-tree="true">名称</th>
            <th data-width="80" data-field="icon" data-visible="false" data-align="center" data-formatter="formatter_icon">图标</th>
            <th data-width="100" data-field="sort" data-align="center">排序</th>
            <th data-field="created">创建时间</th>
            <th data-field="goods_num">商品数量</th>
            <th data-field="url" data-formatter="formatter_url">链接地址</th>
        </tr>
    </thead>
</table>
<script type="text/javascript">
//图标
function formatter_icon(val, row, index){
    return val == '' ? '' : '<img class="img-circle" src="'+val+'" style="width:32px; height:32px">';
}

//链接地址
function formatter_url(val, row, index){
    return '/h5/list?cat_id='+row.id;
}

// 详细视图
function formatter_view(index, row){
    var html = '<ul class="cat-container clearfix">';
    var items = row.items, padding = '0', border='', onclick = '';
    for(var i=0; i<items.length; i++){
        html += '<li class="cat-item ellipsis"><a class="js-edit" title="编辑" data-id="'+items[i].id+'">'+items[i].name+'</a><a class="js-delete cat-delete" title="删除" data-id="'+items[i].id+'"><i class="icon-trash"></i></a></li>';
    }
    html += '</ul>';
    return html;
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