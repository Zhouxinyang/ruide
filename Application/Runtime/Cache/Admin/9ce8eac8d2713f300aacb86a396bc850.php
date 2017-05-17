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
			<div class="content-container">
<div id="toolbar" class="toolbar" data-module="/admin/goods"><?php \Common\Common\Auth::get()->showTollbar('admin', 'goods', 'index') ?><form class="search-box">
		<?php if($all_shop == true): ?><select name="shop_id" class="js-select2" style="margin-bottom: 0;width: auto;">
				<option value="">全部店铺</option>
				<?php if(is_array($shop)): foreach($shop as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>" <?php if($item["id"] == $_GET['shop_id']): ?>selected<?php endif; ?>><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
			</select><?php endif; ?>
		<select name="tag" style="margin-bottom: 0;width: auto;">
			<option value="">所有分组</option>
			<?php if(is_array($goods_tag)): foreach($goods_tag as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>" <?php if($item["id"] == $tag): ?>selected<?php endif; ?>><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
		</select>
		<input type="text" name="title" value="<?php echo ($title); ?>" placeholder="商品名称">
		<input type="hidden" name="action" value="<?php echo ACTION_NAME;?>">
		<button type="button" data-name="search" class="btn btn-default" data-event-type="default">
			<i class="icon-search"></i>
		</button>
	</form></div>

<!-- 表格 -->
<table id="table" data-toggle="gridview" class="table table-hover" data-url="<?php echo __ACTION__; ?>" data-toolbar="#toolbar">
	<thead>
		<tr>
			<th data-width="40" data-checkbox="true"></th>
			<th data-field="id" data-width="40">ID</th>
            <th data-field="title" data-formatter="formatter_title" data-sortable="true">商品</th>
			<th data-width="70" data-field="agent3_price" data-formatter="formatter_agent_price">代理价</th>
			<th data-width="90" data-field="pv" data-formatter="formatter_pv">访问量</th>
			<th data-width="90" data-field="stock" data-align="center" data-sortable="true">库存</th>
			<th data-width="90" data-field="sold_num" data-align="center" data-sortable="true" data-formatter="formatter_num" >总销量</th>
			<th data-width="80" data-field="created" data-sortable="true">创建时间</th>
			<th data-width="50" data-field="sort" <?php echo ($sort_access ? 'data-formatter="format_sort"' : ''); ?> data-sortable="true">排序</th>
			<th data-width="80" data-formatter="formatter_action" data-align="right">操作</th>
		</tr>
	</thead>
</table>

<script type="text/javascript">
	//格式化数据
	function format_sort(sort, row, index) {
			return '<input type="text" class="sort" data-id="'+row.id+'" style="width:40px; margin:0;text-align:center;padding: 0 3px;" value="'+sort+'" />'+
			'<input type="hidden" class="sort_hidden" data-id="'+row.id+'" value="'+sort+'" />';
	}
	
	function formatter_title(val, row, index){
		var pic_url = row.pic_url.indexOf("cbu01.alicdn.com") ? row.pic_url : row.pic_url;
		var html = '<a href="'+row.pic_url+'" target="_blank" style="float:left;"><img src="'+pic_url+'" style="width:64px; height:64px;"></a>';
		html += '<div style="height:64px;margin-left: 74px;overflow:hidden;"><p class="goods-title"><a href="/h5/goods?id='+row.id+'" target="_blank">'+row.title+'</a></p>';
		html += (row.tao_id?'<span style="float:right;color:#888;">'+row.tao_id+'</span>':'')+'<span class="goods-price">¥'+row.price+'</span>';
		return html+'</div>';
	}
	
	function formatter_pv(val, row, index){
		return '<div>UV:'+row.uv+'</div><div>PV:'+row.pv+'</div>';
	}
	
	function formatter_action(val, row, index){
		var id = row.id;
		var html = '<p>' +
				   '	<a href="<?php echo __MODULE__; ?>/goods/edit?id='+id+'">编辑</a>' +
				   '	<span>-</span>' +
				   '	<a href="javascript:;" class="js-delete">删除</a>'+
				   '</p>'+
				   '<p class="other-action">' +
				   '	<a href="javascript:;" class="js-goods_feedback">反馈</a>' +
				   '</p>';
		return html;
	}
		
	function formatter_agent_price(val, row, index){
		return row.agent2_price+'<br>'+row.agent3_price+'<br><span title="成本" style="color:#FF6600;">'+row.cost+'<span>';
	}
	function formatter_num(val,row,index){
		return row.sold_num+'<br><span title="昨日销量">'+row.yesterday+'</span><br><span title="七日销量">'+row.sevenday+'<span>';
	}
	$(function(){
		//商品入库
		$('#table').on('click','.js-stocks', function(){
			var id = $(this).parents('tr').attr('data-uniqueid');
			$.get('<?php echo __MODULE__; ?>/goods/storage?id=' + id, function(html){
				$('body').append(html);
			});
		})
		.on('takedown', function(){
			var rows = $('#table').bootstrapTable('getSelections'); // 当前页被选中项(getAllSelections 所有分页被选中项)
			if(rows.length == 0){ 
				alertMsg('请勾选要下架的商品', 'warning');
				return; 
			}
			var ids = [];
			for(i=0;i<rows.length;i++){
				ids.push(rows[i]['id']);
			}
			// 弹出下架提示
			alertConfirm({
				title: '提示',
				content: '确定要下架吗？',
				ok: function(){
						$.ajax({
							url:'<?php echo __CONTROLLER__; ?>/takeDown',
							type:'post',
							dataType:'json',
							waitting: '正在下架中...',
							data: {'ids':ids.join(',')},
							success:function(data){
								alertMsg('下架成功！'); 
								$('#table').bootstrapTable('refresh');
								return false; 
							}
						})
					},
				cancel: function(){},
				backdrop: true
			});
		})
		.on('kefu', function(e, gridview, parameters){
			var rows = $(this).bootstrapTable('getSelections'); 
			if(rows.length == 0){
				return alertMsg('请先勾选商品'), false;
			}
			
			var goods = '';
			for(var i=0; i<rows.length; i++){
				goods += ','+rows[i].id;
			}
			goods = goods.substr(1);
			parameters.url += '?goods='+goods;
		})
		 //会员折扣
		.on('discount', function(){
			var rows = $("#table").bootstrapTable('getSelections');
			if(rows.length == 0){ 
				alertMsg('请勾选要修改会员折扣的商品', 'warning');
				return false; 
			}
			
			var doSave = function(join){
				var id = [];
				for(var i=0; i<rows.length; i++){
					id.push(rows[i].id);
				}
				
				$.ajax({
					url:'<?php echo __CONTROLLER__; ?>/discount',
					type:'post',
					dataType:'json',
					waitting: '正在下架中...',
					data: {'id': id.join(','), join: join},
					success:function(data){
						
					}
				})
			}
			alertConfirm({
				title: '会员折扣',
				content: '<div class="text-left">若参与会员折扣，有可能最终出售价格低于成本价，造成亏损，请参考成本价合理设置折扣.</div>',
				okValue: '参与',
				cancelValue: '不参与',
				ok: function(){doSave(1)},
				cancel: function(){doSave(0)},
				backdrop: true
			});
			return false;
		}).on('savetag',function(e, gridview ,params){ //修改分组
			var row = gridview.currentRow;
			if(!row){ 
				alertMsg('请选则商品', 'warning');
				return false; 
			}
			params.url += '?id=' + row.id;
		})
		.on('click','.js-delete', function(){
			var id = $(this).parents('tr').attr('data-uniqueid');
			// 弹出删除提示
			alertConfirm({
				title: '提示',
				content: '确定要删除吗？',
				okValue: '确定',
				cancelValue: '取消',
				ok: function(){
						$.ajax({
							url:'<?php echo __CONTROLLER__; ?>/delete',
							type:'post',
							dataType:'json',
							waitting: '正在删除中...',
							data: {id:id},
							success:function(data){
								alertMsg('删除成功！'); 
								$('#table').bootstrapTable('refresh');
								return false; 
							}
						})
					},
				cancel: function(){},
				backdrop: true
			});
			return false;
		})
		.on('click','.js-goods_feedback', function(){
			var id = $(this).parents('tr').attr('data-uniqueid');
			$.get('<?php echo __CONTROLLER__; ?>/feedback?goods_id='+id, function(html){
				var $html = $(html);
				$html.appendTo('body');
			});
			return false;
		})
		.on('change','.sort', function(){
			var id = $(this).parents('tr').attr('data-uniqueid');
			var sort = $(this).val();
			var sort_hidden = $(this).parents('tr').find('.sort_hidden').val();	
			if(!(!isNaN(sort) && sort > 0)){
				alertMsg("输入的排序数字必须为>=0的数字");
			}
			if(sort == sort_hidden){
				return false;
			}
			var list = {};
			list[id] = sort;
			$.ajax({
				url : '<?php echo __CONTROLLER__; ?>/saveSort',
				data : {
					list : list
				},
				dataType : "json",
				type : 'post',
				success:function(data){
					alertMsg('已保存排序！'); 
					return false; 
				},
				error : function() {
					alertMsg('排序失败！');
				}
			})
			return false;
		});
	})
</script>
<style>
.table td{vertical-align: top}
.table td.bs-checkbox{vertical-align: middle}
.goods-title{max-height: 40px;word-break: break-all;overflow: hidden; margin-bottom: 4px}
.goods-price{color: #f60}
.table .other-action{display: none}
.table tr:hover .other-action{display: block}
.bootstrap-table .fixed-table-body .table td:nth-child(2){vertical-align: middle;}
</style></div>
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