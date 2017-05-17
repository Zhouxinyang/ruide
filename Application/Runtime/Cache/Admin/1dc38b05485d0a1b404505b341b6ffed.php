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
.checkin-edit hr{}
.checkin-edit .delete_btn{margin-left:250px}
.checkin-edit .checkin-rule-list-opt a.js-add{margin-left:50px}
.checkin-rule-list-opt{padding:10px 10px 0 10px;text-align:center;border-top:1px solid #ddd}
.sign-item{width:225px;float:left;text-align:center;padding:10px 0;margin-right:10px;position:relative;border:1px solid transparent}
.sign-remove{position:absolute;top:-8px;right:-7px;border:1px solid #ccc;border-radius:50%;width:15px;height:15px;line-height:15px;background-color:#ddd;color:#fff;display:none}
.sign-item:hover{background-color:#eee;border:1px solid #ddd}
.sign-item:hover .sign-remove{display:inline-block}
</style>
<div class="checkin-edit">
    <form method="post" action="<?php echo __CONTROLLER__; ?>/save" class="form-horizontal edit-form" data-validate="true">
    	<input type="hidden" name="id" value="<?php echo ($data["id"]); ?>">
        <div class="form-group">
        	<div class="form-title">活动信息</div>
			<div class="control-group form-item">
			    <label class="control-label must">活动名称</label>
			    <div class="controls">
			        <input class="required" type="text" name="title" value="<?php echo ($data["title"]); ?>" maxlength="25">
			    </div>
			</div>
			<div class="control-group form-item">
				<label class="control-label must">每日赠送</label>
				<div class="controls">
				    <input class="required number" min="0" type="text" name="money" max="1" value="<?php echo ($data["money"]); ?>" placeholder="最多1元" style="width:180px"> 积分
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">活动说明</label>
				<div class="controls">
				    <textarea name="notice" maxlength="300" style="margin-left: 0px; margin-right: 0px; width: 700px;"><?php echo ($data["notice"]); ?></textarea>
				</div>
			</div>
			<div class="control-group form-item">
			    <label class="control-label must">活动状态</label>
			    <div class="controls">
			       	<label class="radio inline"><input type="radio" name="enabled" value="1"<?php echo ($data['enabled'] == 1 ? ' checked="checked"' : ''); ?>> 启用</label>
			       	<label class="radio inline"><input type="radio" name="enabled" value="0"<?php echo ($data['enabled'] == 1 ? '' : ' checked="checked"'); ?>> 禁用</label>
			    </div>
			</div>
			<?php if(!empty($data['id'])): ?><div class="control-group form-item">
				<div class="controls" style="font-size: 14px;color: #f60;margin-left: 0;text-align: right;width: 400px;line-height: 30px;">
				    已发<?php echo ($data["sended_fee"]); ?>元，累计签到<?php echo ($data["played_uv"]); ?>人/<?php echo ($data["played_pv"]); ?>次
				</div>
			</div><?php endif; ?>
        </div>
        
        <div class="form-group">
        	<div class="form-title">设置连续签到</div>
        	<ul class="js-rule-container clearfix"></ul>
            <p class="checkin-rule-list-opt"><a href="javascript:;" class="js-add">增加一条</a></p>
        </div>
        <?php if($canSave): ?><div class="form-actions">
          <button type="submit" class="btn btn-primary">保存</button>
        </div><?php endif; ?>
    </form>
</div>

<?php if($canSave): ?><script type="text/javascript">
	var days = <?php echo ($days); ?>;
	var totalScore = 0;
	var rules = <?php echo ($data['rules']); ?>;
	var $ruleContainer = null;
	
	function resetTotal(){
		totalScore = 0;
		$ruleContainer.find('.js-score').each(function(i){
			if(this.value != ''){
				totalScore += this.value * 1;
			}
		});
		alertMsg(totalScore.toFixed(2));
	}
	
	function appendRule(list){
		var html = '';
		for(var day in list){
			html += '<li class="sign-item">';
	        html += '	<input type="text" class="js-day input-mini text-center" name="" value="'+day+'" maxlength="3" placeholder="连续"> 天';
	       	html +=	'	<input type="text" class="js-score input-mini text-center" name="" value="'+list[day]+'" maxlength="4" placeholder="奖励"> 积分';
	       	html += '	<a class="js-sign-remove sign-remove">×</a>';
	        html += '</li>';
    	}
		
        $ruleContainer.append(html);
        resetTotal();
	}
	
    $(function(){
    	$ruleContainer = $('.js-rule-container');
    	
    	// 初始化数据
    	appendRule(rules);
    	
    	$ruleContainer.on('change', '.js-day',function(){
    		var value = this.value;
    		if(value == '' || !/^\d+$/.test(value) || value < 1){
    			value = '';
    		}else if(value > days){
    			value = days;
    		}
    		this.value = value;
    		return false;
    	}).on('change', '.js-score', function(){
    		var value = this.value;
    		if(value == '' || isNaN(value)){
    			value = '';
    		}else if(value < 0.01){
    			value = 0.01;
    		}else if(value > 10){
    			value = 10;
    		}
    		this.value = value;
    		resetTotal();
    		return false;
    	}).on('click', '.js-sign-remove', function(){
    		$(this).parent().remove();
    		resetTotal();
    		return false;
    	});
    	
        $(".js-add").on('click', function(){
        	var $children = $ruleContainer.children();
        	if($children.length >= days){
        		return alertMsg('最多'+days+'条'), false
        	}
        	
        	var $last = $children.last()
     	   		,day = $last.length == 0 ? 1 : $last.find('.js-day').val() * 1 + 1
            	,score = $last.length == 0 ? 0.1 : $last.find('.js-score').val() * 1 + 0.1;
        	
        	var data = {};
        	data[day] = score.toFixed(2)
            appendRule(data);
        });
        
        // 表单提交
		$('.edit-form').on('valid', function(e, data){
			rules = {};
			var $day = $ruleContainer.find('.js-day')
				,day = '', score = 0;
			$ruleContainer.find('.js-score').each(function(i){
				if(this.value != ''){
					score = this.value * 1;
					day = $day.eq(i).val();
					if(day != ''){
						rules[day] = score;
					}
				}
			});
			
			data.rules = rules;
    	});
    })
</script><?php endif; ?></div>
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