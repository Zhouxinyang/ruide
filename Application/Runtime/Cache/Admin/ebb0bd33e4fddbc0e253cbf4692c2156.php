<?php if (!defined('THINK_PATH')) exit();?><style>
.modal .pull-left{
	display:none;
}
</style>
<div id="scoreModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myscoreModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myscoreModalLabel">资金流水</h3>
	</div>
	<div class="modal-body" style="padding:0">
		<table class="table" data-url="<?php echo __MODULE__; ?>/api/getMemberBalance?mid=<?php echo ($_GET['mid']); ?>" data-height="300">
			<thead>
				<th data-field="money" data-width="70" data-align="center">金额</th>
				<th data-field="create_time" data-width="150" data-align="center">时间</th>
				<th data-field="balance" data-width="100" data-align="center">结余</th>
				<th data-field="reason">原因</th>
			</thead>
		</table>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
	</div>
	<script type="text/javascript">
	(function() {
		var $modal = $('#scoreModal')
			,$table = $modal.find('table');
		$table.gridView();
		
		$modal.modal();
		$modal.on('hidden', function(){
			$modal.remove();
			return false;
		});
	})(); 
	</script>
</div>