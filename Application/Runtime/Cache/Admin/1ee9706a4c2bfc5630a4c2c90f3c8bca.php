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
			<div class="content-container"> <div id="toolbar" class="toolbar" data-module="/admin/member"><?php \Common\Common\Auth::get()->showTollbar('admin', 'member', 'index') ?><form id="order_search" class="form-horizontal">
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">微信公众号</label>
				<div class="controls">
					<select name="appid">
					<?php if(is_array($wxlist)): foreach($wxlist as $appid=>$item): ?><option value="<?php echo ($appid); ?>"><?php echo ($item['name']); ?></option><?php endforeach; endif; ?>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">代理手机号</label>
				<div class="controls">
					<input type="text" name="mobile" data-rule-mobile="true">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">微信昵称</label>
				<div class="controls">
					<input type="text" name="nickname">
				</div>
			</div>
		</div>
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">代理级别</label>
				<div class="controls">
					<select name="agent_level" >
						<option value="">请选择</option>
						<?php if(is_array($levels)): foreach($levels as $k=>$vo): ?><option value="<?php echo ($k); ?>"><?php echo ($vo['title']); ?></option><?php endforeach; endif; ?>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">代理姓名</label>
				<div class="controls">
					<input type="text" name="name">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">代理ID</label>
				<div class="controls">
					<input type="text" name="mid" value="<?php echo ($mid); ?>">
				</div>
			</div>
		</div>
		<div class="filter-groups">
			<div class="control-group">
				<label class="control-label">省份</label>
				<div class="controls">
					<select name="province_id" id="province_id" data-city="#city_id" data-selected="">
						<option value="">请选择</option>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">城市</label>
				<div class="controls">
					<select name="city_id" id="city_id" data-county="#county_id" data-selected=""><option value="">请选择</option></select>
				</div>
			</div>
   		</div>
	</form></div>

<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __CONTROLLER__; ?>" data-toolbar="#toolbar"  data-page-list="[1, 10, 25, 50, All]">
    <thead>
		<tr>
			<th data-width="40" data-checkbox="true"></th>
			<th data-field="id" data-width="80">ID</th>
            <th data-field="nickname">微信昵称</th>
			<th data-field="mobile" data-width="110">手机号</th>
			<th data-field="balance" data-align="center">余额</th>
			<th data-field="agent_level" data-align="center" data-width="100">代理级别</th>
			<th data-field="sex" data-formatter="fomat_status" data-width="50">性别</th>
			<th data-field="reg_time" data-width="130">加入时间</th>
			<th data-field="city">城市</th>
			<th data-field="name">姓名</th>
		</tr>
	</thead>
</table>
<script type="text/javascript">
//格式化数据
function fomat_status(status, row, index){
	if(status == '1'){
		return '男';
	}else if(status == '2'){
		return '女';
	}else{
		return '保密';
	}
}
$(function(){
	$('#table').on('balance_list',function(e, gridview ,params){
		if(gridview.currentRow == null){
			alertMsg('请先选择会员！', 'warning');
			return false;
		}
		$.get('<?php echo __CONTROLLER__; ?>/balance_list?mid=' + gridview.currentRow.id, function(html){
			$('body').append(html);
		});
		return false;
	}).on('change_level',function(e, gridview ,params){
		var rows = $('#table').bootstrapTable('getSelections'); // 当前页被选中项(getAllSelections 所有分页被选中项)
		if(rows.length == 0){ 
			alertMsg('请先勾选要修改等级的会员', 'warning');
			return false; 
		}
		var ids = [];
		for(i=0;i<rows.length;i++){
			ids.push(rows[i]['id']);
		}
		params.url = '<?php echo __CONTROLLER__; ?>/change_level?id=' + ids;
	}).on('employee',function(e, gridview ,params){
		var rows = $('#table').bootstrapTable('getSelections'); // 当前页被选中项(getAllSelections 所有分页被选中项)
		if(rows.length == 0){ 
			alertMsg('请先勾选要修改的会员', 'warning');
			return false; 
		}
		var ids = [];
		for(i=0;i<rows.length;i++){
			ids.push(rows[i]['id']);
		}
		params.url = '<?php echo __CONTROLLER__; ?>/employee?id=' + ids;
	}).on('black_add',function(e, gridview ,params){
		var rows = $('#table').bootstrapTable('getSelections'); // 当前页被选中项(getAllSelections 所有分页被选中项)
		if(rows.length == 0){ 
			alertMsg('请先勾选要加入黑名单的会员', 'warning');
			return false; 
		}
		var ids = [];
		for(i=0;i<rows.length;i++){
			ids.push(rows[i]['id']);
		}
		params.url = '/mall/black/black_add?id=' + ids;
	}).on('order_list',function(e, gridview ,params){
		if(gridview.currentRow == null){
			alertMsg('请先选择会员！');
			return false;
		}
		params.url = '/mall/order?buyer_id=' + gridview.currentRow.id;
	}).on('reissue_score',function(e, gridview ,params){
		var row = gridview.currentRow; // 当前页被选中项(getAllSelections 所有分页被选中项)
		if(!row){ 
			alertMsg('请点击选择要补发积分的会员', 'warning');
			return false; 
		}
		params.url += '?id=' + row.id;
	}).on('show_cm',function(e, gridview ,params){
		if(gridview.currentRow == null){
			alertMsg('请先选择会员！', 'warning');
			return false;
		}
		
		params.url = '<?php echo __CONTROLLER__; ?>/show_cm?mid=' + gridview.currentRow.id;
	}).on('member_out',function(e, gridview ,params){
		var ids = gridview.currentRow.id
		params.url = '<?php echo __CONTROLLER__; ?>/member_out?change_mid=' + ids;
	});
});
</script>
<script src="/js/address.js"></script>
<script type="text/javascript">
	Address.bind("#province_id");
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