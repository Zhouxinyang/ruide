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
			<div class="content-container"><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-continue="true" data-submit="ajax" class="form-horizontal edit-form">
  <div class="form-group">
	  <div class="form-item">
		  <div class="control-group">
		    <label class="control-label must">店铺名称</label>
		    <div class="controls">
		      <input class="required" type="text" name="name" maxlength="15" value="<?php echo ($data["name"]); ?>" placeholder="最多15个字符"<?php echo ($data['type'] == 2 ? ' disabled="disabled"' : ''); ?>>
		    </div>
		  </div>

	  	  <div class="control-group">
		    <label class="control-label must">客服电话</label>
		    <div class="controls">
		      <input type="text" name="hotline" required="required" value="<?php echo ($data["hotline"]); ?>" maxlength="13" placeholder="400在线客服">
		    </div>
	  	  </div>
	  	  <div class="control-group">
		  	<label class="control-label must">省份</label>
		    <div class="controls">
			    <select name="province_id" id="province_id" class="required" data-city="#city_id" data-selected="<?php echo ($data["province_id"]); ?>">
					<option value="">请选择</option>
				</select>
		    </div>
		  </div>

		  <div class="control-group">
		    <label class="control-label must">市区</label>
		    <div class="controls">
		        <select name="city_id" id="city_id"  class="required" data-county="#county_id" data-selected="<?php echo ($data["city_id"]); ?>" style="width:120px"><option value="">请选择</option></select>
		        <select name="county_id" id="county_id" data-selected="<?php echo ($data["county_id"]); ?>" style="width:100px"><option value="">请选择</option></select>
		    </div>
	  	  </div>

		  <div class="control-group">
		    <label class="control-label must">详细地址</label>
		    <div class="controls">
		       <input type="text" name="address" placeholder="最多120个字符" maxlength="120" class="required" value="<?php echo ($data["address"]); ?>">
		    </div>
	  	  </div>
	  </div>

	  <div class="form-item">
		  <div class="control-group">
		  	<label class="control-label">店铺 Logo</label>
		  	<div class="controls">
				<input class="hide" type="text" name="logo" value="<?php echo ($data["logo"]); ?>" readonly="readonly" data-msg-required="请上传logo">
				<img id="shop_logo" src="<?php echo ($data["logo"]); ?>" class="img-polaroid btn-up" alt="logo" style="width: 64px; height: 64px;">
			</div>
		  </div>
		  <div class="control-group">
		    <label class="control-label">联系人姓名</label>
		    <div class="controls">
		      <input type="text" name="contacts" maxlength="8" value="<?php echo ($data["contacts"]); ?>" placeholder="最多8个字符">
		    </div>
		  </div>
		  <div class="control-group">
		    <label class="control-label">联系人手机号</label>
		    <div class="controls">
		      <input type="text" name="mobile" class="mobile" maxlength="11" value="<?php echo ($data["mobile"]); ?>" placeholder="请输入手机号">
		    </div>
		  </div>
		  <div class="control-group">
		    <label class="control-label">QQ客服</label>
		    <div class="controls">
		      <input type="text" name="qq" maxlength="15" value="<?php echo ($data["qq"]); ?>" placeholder="QQ号码">
		    </div>
		  </div>
	  </div>
  </div>
 	<div class="form-group">
 		<div class="form-item">
			<div class="control-group">
			    <label class="control-label">店铺简介</label>
			    <div class="controls">
			      <textarea rows="5" style="width:704px;" name="desc" maxlength="300" placeholder="最多300个字符"><?php echo ($data["desc"]); ?></textarea>
			    </div>
			  </div>
 		</div>
	</div>
	<div class="form-actions">
	  <button type="submit" class="btn btn-primary">保存</button>
	  <button type="button" class="btn">取消</button>
	</div>
</form>
<script src="/js/address.js"></script>
<script type="text/javascript">
Address.bind("#province_id");
</script>
<script type="text/javascript" src="/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/ueditor/ueditor.all.min.js"></script>
<div style="display: none;" id="editor" class="edui-default"></div>
<script>
$(function(){
	var editor = UE.getEditor('editor',{isShow: false})
	$('#shop_logo').on('click', function(){
		var $img = $(this);
		var $input_url = $img.prev();
		editor.removeListener('beforeInsertImage');
		editor.addListener('beforeInsertImage', function (t, list) {
			$input_url.val(list[0]['src']);
			$img.attr('src', list[0]['src']);
        });

		editor.getDialog("insertimage").open();
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