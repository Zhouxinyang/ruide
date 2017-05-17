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
.goods-title {
    max-height: 28px;
    word-break: break-all;
    overflow: hidden;
    margin-bottom: 4px;
    line-height: 14px;
}
</style>
<div id="toolbar" class="toolbar">
	<div class="btn-group">
		<button type="button" data-name="export" class="btn btn-default" data-event-type="custom" ><i class=""></i> 导出excel</button>
	</div>
	 <form class="search-box" novalidate="novalidate">
	  <div class="filter-groups">
			<div class="control-group">
			<div class="controls">
				<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss" >
					<input type="text" name="start_date" value="<?php echo ($start_date); ?>" style="width:78px;">
					<span class="add-on"><i class="icon-th"></i></span>
				</div>
				至
				<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
					<input type="text" name="end_date" value="<?php echo ($end_date); ?>" style="width:78px;">
					<span class="add-on"><i class="icon-th"></i></span>
				</div>
			</div>
			</div>
	 </div>
		<?php if(!empty($allShop)): ?><select name="seller_id" data-search="true" style="margin-bottom: 0;width: auto;">
			<option value="">所有店铺</option>
			<?php if(is_array($allShop)): foreach($allShop as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>"><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
		</select><?php endif; ?>
		 <select name="refund_state" data-search="true" style="margin-bottom: 0;width: auto;">
			<option value="">所有状态</option>
            <option value="1" selected="selected">退款申请中</option>
            <option value="2">待上传单号</option>
            <option value="2.1">等待退款</option>
            <option value="3">已退款</option>
            <option value="4">拒绝退款</option>
            <option value="5">已取消退款</option>
		</select>
		<input type="text" name="tid" value="" placeholder="订单号/退款运单号" style="width:120px">
		<button type="button" data-name="search" class="btn btn-default" data-event-type="default" data-target="modal">
			<i class="icon-search"></i>
		</button> 
	</form> 
</div>

<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __ACTION__; ?>" data-toolbar="#toolbar" data-page-list="[1, 10, 25, 50, All]">
	<thead>
		<tr>
			<th data-field="title" data-formatter="formatter_title">商品</th>
			<th data-width="110" data-formatter="format_url" data-field="tid">订单号</th>
			<th data-width="100" data-field="refund_state" data-formatter="formatter_refund_state">退款状态</th>
			<th data-width="100" data-field="refund_fee" data-align="right" data-formatter="formatter_refund_fee">金额/邮补/数量</th>
			<th data-width="100" data-field="refund_reason_str">退款原因</th>
			<th data-width="120" data-field="refund_express">快递单号</th>
			<th data-width="85" data-field="refund_created" data-align="right">申请时间</th>
		</tr>
	</thead>
</table>

<script>
var refund_state = {"1":"退款申请中", "2":"待上传单号", "2.1":"等待退款", "3":"已退款", "4":"拒绝退款", "5":"已取消退款"};
function formatter_title(val, row, index){
	var html = '<a href="'+row.pic_url+'" target="_blank" style="float:left;"><img src="'+row.pic_url+'" style="width:64px; height:64px;"></a>';
	html += '<div style="height:64px;margin-left: 74px;overflow:hidden;">';
	html += '<p class="goods-title"><a href="/h5/goods?id='+row.goods_id+'" target="_blank">'+row.title+'</a></p>';
	html += '<p>'+row.spec+' <a class="js-goods_feedback" data-gid="'+row.goods_id+'" title="反馈"><i class="icon-pencil"></i></a></p>';
	return html+'</div>';
}

function formatter_refund_fee(val, row, index){
	return '<span style="color:#f60">'+row.refund_fee + '</span><br><span style="color:#08C">' + row.refund_post + '</span><br>' + '<span style="">'+row.refund_num+'<span>'; 
}

function formatter_refund_state(val, row, index){
	return '<a href="javascript:;" class="js-detail" data-tid="'+row.tid+'">'+refund_state[val]+'</a>';
}

function format_url(val, row, index){
	if(val != ''){
		return '<a href="/admin/order/detail?tid='+val+'" target="_blank">'+val+'</a>';
	}else{
		return val;
	}
}

$(function(){
	//退款
	var $table = $('#table');
	$table.on('click', '.js-detail', function(){
		var tid = $(this).attr('data-tid');
		$.get('<?php echo __MODULE__; ?>/refund/detail?tid='+tid, function(html){
			$('body').append(html).unbind('refund').on('refund', function(){
				$('body').data('refunded', false);
				$table.bootstrapTable('refresh');
				return false;
			});
		});
		return false;
	}).on("export", function(e, gridview ,params){
		var url = '<?php echo __CONTROLLER__; ?>/export';
		var array = $('#toolbar form').serializeArray();
		for(var i=0; i<array.length; i++){
			url += (i == 0 ? '?' : '&') + array[i].name + '=' + array[i].value;
		}
		window.open(url);
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