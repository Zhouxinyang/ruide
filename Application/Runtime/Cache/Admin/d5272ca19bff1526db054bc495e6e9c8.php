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
			<div class="content-container"><style>
.toolbar .form-horizontal .filter-groups .control-label{width:100px}
.toolbar .form-horizontal .filter-groups .controls{margin-left:105px}
</style>
<div id="toolbar" class="toolbar">
	<form id="order_search" class="form-horizontal" style="margin:0">
		<div class="clearfix">
			<div class="filter-groups">
				<div class="control-group">
					<label class="control-label">订单号</label>
					<div class="controls">
						<input type="text" name="tid" value="<?php echo ($search["tid"]); ?>" style="width: 300px" maxlength="20" placeholder="本系统号或阿里订单号">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">收货人</label>
					<div class="controls">
						<input type="text" name="receiver_mobile" value="<?php echo ($search["receiver_mobile"]); ?>" placeholder="手机号" maxlength="11" style="width: 135px">
						<input type="text" name="receiver_name" value="<?php echo ($search["receiver_name"]); ?>" placeholder="姓名" style="width: 150px">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">下单人</label>
					<div class="controls">
						<input type="text" name="buyer_id" maxlength="11" placeholder="会员ID" style="width: 135px" value="<?php echo ($_GET['buyer_id']); ?>">
                        <input type="text" name="buyer_mobile" maxlength="11" placeholder="手机号" style="width: 150px">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">商品名</label>
					<div class="controls">
						<input type="text" name="title" placeholder="只会检索到全匹配的子订单"style="width:300px">
					</div>
				</div>
			</div>
			<div class="filter-groups" style="float:right;margin-right:50px">
				<div class="control-group">
					<label class="control-label">下单时间</label>
					<div class="controls">
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="start_date" value="<?php echo ($search["start_date"]); ?>"style="width: 130px">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
						至
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="end_date" value="<?php echo ($search["end_date"]); ?>" style="width: 130px">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">付款时间</label>
					<div class="controls">
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="pay_start_date" value=""style="width: 130px">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
						至
						<div class="input-append date" data-format="yyyy-MM-dd hh:mm:ss">
							<input type="text" name="pay_end_date" value="" style="width: 130px">
							<span class="add-on"><i class="icon-th"></i></span>
						</div>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">订单状态</label>
					<div class="controls">
						<select name="status" class="js-status" style="width: <?php echo ($shops ? '120' : '360'); ?>px">
							<option value="">全部</option>
							<?php if(is_array($order_status)): foreach($order_status as $sid=>$sname): ?><option value="<?php echo ($sid); ?>" <?php if($search['status'] == $sid): ?>selected="selected"<?php endif; ?>><?php echo ($sname); ?></option><?php endforeach; endif; ?>
                            <option value="error1688">1688异常订单</option>
						</select>
						<?php if(!empty($shops)): ?><select name="shop_id" style="width: 235px" id="all_shop">
							<option value="all">全部店铺</option>
							<?php if(is_array($shops)): foreach($shops as $key=>$vo): ?><option value="<?php echo ($vo["id"]); ?>"<?php echo ($currentShopId == $vo['id'] ? ' selected="selected"' : ''); ?>><?php echo ($vo["name"]); ?></option><?php endforeach; endif; ?>
						</select><?php endif; ?>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">维权状态</label>
					<div class="controls">
						<select name="refund_state" style="width: 360px;">
							<option value="">全部</option>
							<option value="no_refund">无退款</option>
							<option value="refunding">处理中</option>
							<option value="refunded">已处理</option>
							<optgroup label="详细状态">
								<option value="partial_refunding">部分退款中</option>
								<option value="partial_refunded">已部分退款</option>
								<option value="partial_failed">部分退款失败</option>
								<option value="full_refunding">全额退款中</option>
								<option value="full_refunded">已全额退款</option>
								<option value="full_failed">全额退款失败</option>
							</optgroup>
						</select>
					</div>
				</div>
			</div>
			<div style="position: absolute;top: 25px;bottom: 52px;left: 50%;margin-left: -15px;border-left: 1px dashed #ddd"></div>
		</div>
		<div class="text-center">
			<input class="btn" type="submit" value="查询">
			<?php if($access == true): ?><button type="button" id="print_and_send" class="btn btn-danger">出库发货</button><?php endif; ?>  
			<button type="button" id="printOrder" class="btn btn-primary">导出订单</button>
		</div>
	</form>
</div>
<style>
.toolbar{margin-bottom:15px}
.ui-table-order{width:100%;font-size:12px;text-align:left;margin-bottom:0}
.ui-table-order .separation-row{border:none;height:10px}
.ui-table-order .separation-row td{padding:0}
.ui-table-order .header-row{background:#fff;height:30px}
.ui-table-order tr{border:1px solid #f2f2f2}
.ui-table-order .header-row td{padding:5px 10px}
.ui-table-order th,.ui-table-order td{padding:10px;vertical-align:top;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}
.ui-table-order .header-row td{padding:5px 10px}
.ui-table-order .remark-row.buyer-msg{background:#FDEEEE;color:#ED5050}
.ui-table-order .remark-row.buyer-msg td, .ui-table-order .remark-row.seller-msg td{padding:5px 10px}
.ui-table-order .content-row .aftermarket-cell,.ui-table-order .content-row .customer-cell,.ui-table-order .content-row .time-cell,.ui-table-order .content-row .status-cell,.ui-table-order .content-row .pay-price-cell{border-left:1px solid #f2f2f2}
.ui-table-order .aftermarket-cell,.ui-table-order .customer-cell,.ui-table-order .time-cell,.ui-table-order .status-cell,.ui-table-order .pay-price-cell{text-align:center}
.ui-table-order .aftermarket-cell{width:100px}
.ui-table-order .content-row .image-cell{width:60px;height:60px;text-align:center;padding-right:0}
.ui-table-order .title-cell .goods-title{width:297px}
.ui-table-order .price-cell,.ui-table-order .number-cell{text-align:right}
.ui-table-order p{margin:0}
.ui-table-order th, .content-row, .seller-msg{background-color:#fff}
.send-modal{}
.send-modal .control-label{width: 80px; text-align: left;}
.send-modal .controls{margin-left: 80px;}
.send-modal input[disabled]{border: none;background-color: #fff;box-shadow: none;padding-top: 6px;}
.send-modal .control-group{margin-bottom: 0px;}
.send-modal form{margin:0}
.send-modal table{ margin-bottom: 10px; border-bottom: 1px solid #ddd;}
/*弹窗中表格的样式*/
.order-price-table {margin-bottom:0;}
.order-price-table thead tr>th {background-color: #f5f5f5;}
.c-gray{color:#999;}
.final p{margin:0;}
.order-no{color:#333}
.order-no-1688{display:inline-block}
.order-no-1688 a{margin-left:5px;}
.seller-send-all{float:right}
</style>
<div id="order_list">
	<table class="ui-table-order" style="padding: 0px">
		<thead class="js-list-header-region tableFloatingHeaderOriginal" style="position: static; top: 0px; margin-top: 0px; left: 150px; z-index: 1; width: 849px">
			<tr class="widget-list-header">
				<th colspan="2" style="width: 224px">商品</th>
				<th class="price-cell" style="width: 130px">单价/数量</th>
				<th class="aftermarket-cell" style="width: 100px">售后</th>
				<th class="customer-cell" style="width: 95px">买家</th>
				<th class="time-cell" style="width: 80px">下单时间</th>
				<th class="status-cell" style="width: 100px">订单状态</th>
				<th class="pay-price-cell" style="width: 120px">实付金额</th>
			</tr>
		</thead>
		<tbody>
			<tr class="header-row">
				<td colspan="8" class="text-center">正在加载中...</td>
			</tr>
		</tbody>
	</table>
</div>

<div id="sellerSendModal" class="modal hide fade modal-middle" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="sendModalLabel">运单信息</h3>
	<span>运单格式为<span style="color:red">快递公司:运单号</span>，多笔运单使用分号或换行分割。</span>
  </div>
  <div class="modal-body text-center">
		<textarea class="js-send" placeholder="顺丰:12345678;圆通:98765432" maxlength="256" style="width: 507px; margin: 0px 0px 10px; height: 123px"></textarea>
		<input type="hidden" class="js-tid">
		<button class="btn btn-primary" style="width: 150px">保存</button>
  </div>
</div>

<div id="sellerRemarkModal" class="modal hide fade modal-middle" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="myModalLabel">卖家备注</h3>
  </div>
  <div class="modal-body text-center">
		<textarea class="js-remark" placeholder="最多可输入256个字符" maxlength="256" style="width: 507px; margin: 0px 0px 10px; height: 123px"></textarea>
		<input type="hidden" class="js-tid">
		<button class="btn btn-primary" style="width: 150px">保存</button>
  </div>
</div>

<!-- 调价弹窗 -->
<div id="changeOrderPrice" class="modal hide fade modal-middle" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="margin-top: -172px;width:800px;margin-left:-400px">
  <div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="changeModalLabel"></h3>
  </div>
  <div class="modal-body text-center">
	<table class="table order-price-table" id="tableChangePrice">
		<thead>
		<tr>
			<th class="tb-name">商品</th>
			<th class="tb-price">单价（元）</th>
			<th class="tb-score">积分</th>
			<th class="tb-num">数量</th>
			<th class="tb-total">小计（元）</th>
			<th class="tb-coupon">店铺优惠</th>
			<th class="tb-discount">涨价或减价</th>
			<th class="tb-postage">运费（元）</th>
		</tr>
		</thead>
		<tbody>
		
		</tbody>
	</table>
	<div class="clearfix" style="background-color:#f5f5f5;padding: 10px">
		<div class="final js-footer text-left pull-left">
			<div id="buyerChangePrice">
			
			</div>
		</div>
		<button class="btn btn-primary" id="change_price" style="width: 60px;float:right">保存</button>
	</div>
  </div>
</div>

<script src="/js/pagination.js"> </script>
<script type="text/javascript">
$(function(){
	var $searchForm = $('#order_search');
	 $searchForm.on('submit', function(){
		 $('#page').val(1);
		 var data = $(this).serializeArray();
		 data.push({name: 'page', value: 1});
		 getOrderList(data);
		 return false;
	 }).trigger('submit');
	 
	 // 初始化卖家备注模态框
	 $('#sellerRemarkModal').modal({show: false}).find('.btn-primary').on('click', function(){
		 var $this = $(this);
		 var tid = $this.prevAll('.js-tid').val();
		 var remark = $this.prevAll('.js-remark').val();
		 
		 $.ajax({
			 url: '<?php echo __CONTROLLER__; ?>/remark',
			 type: 'post',
			 dataType: 'json',
			 data: {tid: tid, remark: remark},
			 success: function(){
				 var $tbody = $('#order_list').find('tbody[data-tid="'+tid+'"]');
				 var $remark = $tbody.find('tr.seller-msg');
				 if($remark.length == 0){
					 $tbody.append('<tr class="remark-row seller-msg"><td colspan="8">卖家备注： '+remark+'</td></tr>');
				 }else{
					 $remark.html('<td colspan="8">卖家备注： '+remark+'</td>');
				 }
				 
				 $('#sellerRemarkModal').modal('hide');
			 }
		 });
	 });
	 
	 // 初始化运单修改模态框
	 $('#sellerSendModal').modal({show: false}).find('.btn-primary').on('click', function(){
		 var $this = $(this);
		 var tid = $this.prevAll('.js-tid').val();
		 var send = $this.prevAll('.js-send').val();
		 
		 $.ajax({
			 url: '<?php echo __CONTROLLER__; ?>/sendOne',
			 type: 'post',
			 dataType: 'json',
			 data: {tid: tid, send: send},
			 success: function(){
				$searchForm.trigger('submit');
				$('#sellerSendModal').modal('hide');
			 }
		 });
	 });
	 
	//修改运费
	$("#tableChangePrice").on('blur','#changePostage',function(){
		var old_total_fee = parseFloat($("#oldTotalFee").val()).toFixed(2);//原总金额
		var new_total_fee;//修改后总金额
		var old_adjust_fee = parseFloat($("#oldAdjustFee").val()).toFixed(2);//原调价金额
		var new_adjust_fee = $("#changeAdjustFee").val();//修改后调价金额
		var old_postage = parseFloat($("#oldPostage").val()).toFixed(2);//原运费金额
		var new_postage = $("#changePostage").val();//修改后运费金额
		
		if(!isNaN(new_adjust_fee)){
			new_adjust_fee = parseFloat(new_adjust_fee).toFixed(2);
		}else{
			new_adjust_fee = parseFloat(0).toFixed(2);//0.00
		}
		
		if(!isNaN(new_postage) && new_postage > 0){
			new_postage = parseFloat(new_postage).toFixed(2);
		}else{
			new_postage = parseFloat(0).toFixed(2);//0.00
		}
		
		new_total_fee = old_total_fee - (old_postage-new_postage) - (old_adjust_fee-new_adjust_fee);
		
		$("#changeTotalPostage").text(new_postage);
		$("#changeTotalFee").text(parseFloat(new_total_fee).toFixed(2));
	})
	
	//涨价或减价
	$("#tableChangePrice").on('blur','#changeAdjustFee',function(){
		var old_total_fee = parseFloat($("#oldTotalFee").val()).toFixed(2);//原总金额
		var new_total_fee;//修改后总金额
		var old_adjust_fee = parseFloat($("#oldAdjustFee").val()).toFixed(2);//原调价金额
		var new_adjust_fee = $("#changeAdjustFee").val();//修改后调价金额
		var old_postage = parseFloat($("#oldPostage").val()).toFixed(2);//原运费金额
		var new_postage = $("#changePostage").val();//修改后运费金额
		
		if(!isNaN(new_adjust_fee)){
			new_adjust_fee = parseFloat(new_adjust_fee).toFixed(2);
		}else{
			new_adjust_fee = parseFloat(0).toFixed(2);//0.00
		}
		
		if(!isNaN(new_postage) && new_postage > 0){
			new_postage = parseFloat(new_postage).toFixed(2);
		}else{
			new_postage = parseFloat(0).toFixed(2);//0.00
		}
		
		new_total_fee = old_total_fee - (old_postage-new_postage) - (old_adjust_fee-new_adjust_fee);
		
		if(new_adjust_fee >= 0){
			new_adjust_fee = '+'+new_adjust_fee;
		}
		
		$("#changeTotalAdjustFee").text(new_adjust_fee);
		$("#changeTotalFee").text(parseFloat(new_total_fee).toFixed(2));
	});
	
	// 打单并发货
	var $status = $searchForm.find('.js-status');
	$('#print_and_send').hover(function(){
		$(this).data('status', $status.val());
		$status.val('tosend').css('opacity', '.3');
		return false;
	}, function(){
		var status = $(this).data('status');
		$status.val(status).css('opacity', '1');
		return false;
	}).on('click', function(){
		var $all_shop = $('#all_shop')
		   ,tip = '';
		if($all_shop.length == 1){
			var shop_id = $all_shop.val();
			if(shop_id == '' || shop_id == 'all'){
				return alert('请选择要出库的店铺'), false;
			}
			tip = '【' + $all_shop.find(':selected').text() + '】';
		}
		
		if(!confirm(tip + '确定要出库吗？')){
			return false;
		}
		
		var data = $searchForm.serializeArray();
		var url = '<?php echo __CONTROLLER__; ?>/print_and_send?';
		for(var i=0; i<data.length; i++){
			url += (i > 0 ? '&' : '')+data[i].name+'='+data[i].value;
		}
		window.location.href = url;
		return false;
	});
	
	// 导出订单
	$('#printOrder').on('click', function(){
		var data = $searchForm.serializeArray();
		var url = '<?php echo __CONTROLLER__; ?>/printOrder?';
		for(var i=0; i<data.length; i++){
			url += (i > 0 ? '&' : '')+data[i].name+'='+data[i].value;
		}
		window.open(url);
		return false;
	});
	initEvent();
});

function initEvent(){
	var $searchForm = $('#order_search');
	// 取消订单
	$('#order_list').on('click', '.js-cancel-order', function(){
		var $this = $(this);
		var t = $this.data('popover');
		if(t){
			return false;
		}
		var tid = $this.parents('tbody:first').attr('data-tid');
		var content = '';
		content += '<div class="text-center">';
		content += '	<p>';
		content += '		<select id="close_reason">';
		content += '	        <option value="">请选择一个取消订单理由</option>';
		content += '	        <option value="buyer_cancel">买家主动取消(-1%)</option>';
		content += '	        <option value="no_stock">已经缺货无法交易</option>';
		content += '	        <option value="10">无法联系上买家</option>';
		content += '	        <option value="11">买家误拍或重拍了</option>';
		content += '	        <option value="12">买家无诚意完成交易</option>';
		content += '	    </select>';
		content += '	</p>';
		content += '	<button class="btn btn-primary btn-mini js-submit-cancel" onclick="cancel_order(\''+tid+'\', this)">提交</button>';
		content += '</div>';
		
		$this.popover({
			title: '取消订单： ' + tid,
			placement: 'top',
			html: true,
			content: content,
		}).popover('show');
			
		return false;
	})
	// 卖家订单备注
	.on('click', '.js-set-seller-remark', function(){
		var $tbody = $(this).parents('tbody');
		var remark = $tbody.find('tr.seller-msg td').html();
		if(remark){
			remark = remark.substring(6);
		}else{
			remark = '';
		}

		var tid = $tbody.data('tid');
		var $modal = $('#sellerRemarkModal');
		$modal.find('.js-remark').val(remark);
		$modal.find('.js-tid').val(tid);
		$modal.modal('show');
		return false;
	})
	// 独立运单信息维护
	.on('click', '.js-set-send', function(){
		var $tbody = $(this).parents('tbody');
		var send = ''; 
		var $a = $tbody.find('.seller-send-all a');
		$a.each(function(i, item){
			send += this.title+':'+this.innerText + (i==$a.length-1 ? '' : ';');
		});

		var tid = $tbody.data('tid');
		var $modal = $('#sellerSendModal');
		if(send){
			$modal.find('.js-send').val(send);
		}else{
			$modal.find('.js-send').val('');
		}
		$modal.find('.js-tid').val(tid);
		$modal.modal('show');
		return false;
	})
	// 退款
	.on('click', '.js-btn-cancel', function(){
		var tid = $(this).attr('data-tid');
		$.get('<?php echo __MODULE__; ?>/refund/detail?tid='+tid, function(html){
			$('body').append(html).unbind('refund').on('refund', function(){
				$('body').data('refunded', false);
				$searchForm.trigger('submit');
				return false;
			});
		});
	})
	// 反馈
	.on('click', '.js-goods_feedback',function(){
		var $this = $(this)
		   ,id = $this.data('gid')
		   ,tid = $this.parents('tbody:first').data('tid');
		$.get('<?php echo __MODULE__; ?>/goods/feedback?goods_id='+id+'&tid='+tid, function(html){
			var $html = $(html);
			$html.appendTo('body');
		});
        return false;
	})
	// 填写外部单号
	.on('click', '.js-order-no', function(){
		var $this = $(this),
			$tbody = $this.parents('tbody:first'),
		    tid = $tbody.data('tid');
		
		$.get('/admin/order/setOutTradeNo?tid=' + tid, function(html){
			$('body').append(html)
			.on('out_trade_no_change', function(e, data){
				$searchForm.trigger('submit');
			});
		});
		return false;
	});
}

// 取消订单
function cancel_order(tid, ele){
	var reason = $('#close_reason').val();
	if(reason == ""){
		alertMsg('请选择取消原因');
		return;
	}
	
	$(ele).parents('.popover').remove();
	
	$.ajax({
		url: '<?php echo __MODULE__; ?>/order/cancel',
		type: 'post',
		data: {tid: tid, reason: reason},
		dataType: 'json',
		success: function(){
			var html = '<p class="js-order-status">已取消</p>';
			var $order = $('#order_list tbody[data-tid="'+tid+'"]');
			$order.find('td.status-cell').html(html);
			var $cancel = $order.find('.js-cancel-order');
			$cancel.next().remove();
			$cancel.remove();
		}
	});
}

// 获取订单列表
function getOrderList(data){
	$.ajax({
		url: '<?php echo __ACTION__; ?>',
		data: data,
		success: function(html){
			$('#order_list').html(html);
			
			var $pagination = $('#pagination');
			var page = $pagination.attr('data-page');
			$('#pagination').pagination({
				itemsCount: $pagination.attr('data-total'),
				pageSize: 10,
				displayPage: 10,
				currentPage: page,
				showCtrl: true,
				onSelect: function (page) {
					data[data.length - 1].value = page;
					getOrderList(data);
				}        
			})
		}
	});
}
</script>
<script src="/js/address.js"></script>
<script type="text/javascript">
Address.bind("#province_id");
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