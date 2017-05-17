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
			<div class="content-container"><style>
.tags{}
.tags .checkbox{width:100px;margin:0}
.tags .radio.inline+.radio.inline, .tags .checkbox.inline+.checkbox.inline{margin:0}
</style>
<form action="<?php echo __ACTION__; ?>" class="form-horizontal edit-form" data-validate="true" data-submit="ajax" method="post" data-success="back">
	<input name="banner_id" value="<?php echo ($data["id"]); ?>" type="hidden">
	<div class="form-group">
		<div class="form-item">
			<div class="control-group ">
				<label class="control-label must">标题</label>
				<div class="controls">
					<input type="text" name="title" class="required" value="<?php echo ($data["title"]); ?>" maxlength="100" placeholder="100个字符以内">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">跳转链接</label>
				<div class="controls">
					<input name="url" type="text" value="<?php echo ($data["url"]); ?>">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">排序</label>
				<div class="controls">
					<input name="sort" type="text" class="number" value="<?php echo ($data["sort"]); ?>">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">是否显示</label>
				<div class="controls">
					<label class="radio inline"><input type="radio" checked="checked" name="is_show" value="1">显示</label>
					<label class="radio inline"><input type="radio" <?php echo ($data['is_show'] == '0'?'checked="checked"':''); ?> name="is_show" value="0">隐藏</label>
				</div>
			</div>
		</div>
		<div class="form-item">
			<div class="control-group">
			  	<label class="control-label">图片</label>
			  	<div class="controls">
					<input class="hide" type="hidden" name="img_url" value="<?php echo ($data["img_url"]); ?>">
					<img id="img_url" src="<?php echo ($data["img_url"]); ?>" class="img-polaroid btn-up" alt="封面" style="width: 120px; height: 120px;cursor:pointer">
				</div>
		  	</div>
			<div class="control-group">
				<label class="control-label">首页展示</label>
				<div class="controls">
					<label class="radio inline"><input type="radio" checked="checked" name="home" value="1">顶部</label>
					<label class="radio inline"><input type="radio" <?php echo ($data['home'] == '2'?'checked="checked"':''); ?> name="home" value="2">底部</label>
					<label class="radio inline"><input type="radio" <?php echo ($data['home'] == '0'?'checked="checked"':''); ?> name="home" value="0">隐藏</label>
				</div>
			</div>
		</div>
		
		<div class="control-group">
			<label class="control-label">其他位置</label>
			<div class="controls tags">
				<label class="checkbox inline" style="color:#f60">
					<input type="checkbox" value="1" name="personal"<?php echo ($data['personal'] ? ' checked="checked"' : ''); ?>>个人中心
				</label>
				<?php if(is_array($tags)): foreach($tags as $key=>$tag): ?><label class="checkbox inline">
						<input type="checkbox" value="<?php echo ($tag["id"]); ?>" name="seat[]"<?php in_array($tag['id'],explode(',',$data['seat'])) && print('checked="checked"'); ?>><?php echo ($tag["name"]); ?>
					</label><?php endforeach; endif; ?>
			</div>
		</div>
	</div>
	<div class="form-actions">
	  <button type="button" class="btn btn-back">返回</button>
	  <button type="submit" class="btn btn-primary">保存</button>
	</div>
</form>
<script type="text/javascript" src="/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/ueditor/ueditor.all.min.js"></script>
<div style="display: none;" id="editor" class="edui-default"></div>
<script>
$(function(){
	var editor = UE.getEditor('editor',{isShow: false});
	var $img = $('#img_url');
	editor.addListener('beforeInsertImage', function (t, list) {
		$img.prev().val(list[0]['src']);
		$img.attr('src', list[0]['src']);
    });
	$img.on('click', function(){
		editor.getDialog("insertimage").open();
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