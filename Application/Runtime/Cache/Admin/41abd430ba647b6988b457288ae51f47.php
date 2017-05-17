<?php if (!defined('THINK_PATH')) exit();?><form id="reissueScoreModal" method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-submit="ajax" class="form-horizontal">
	<div class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3 id="myModalLabel">补发积分</h3>
	  </div>
	  <div class="modal-body">
	  	<input type="hidden" name="mid" value="<?php echo ($id); ?>">
		<div class="control-group">
			<label class="control-label must">可提现金额</label>
			<div class="controls">
                  <input type="text" class="required number" value="0" name="balance" max="200" maxlength="5">
			</div>
		</div>
		
		<div class="control-group">
			<label class="control-label must">不可提现金额</label>
			<div class="controls">
            	<input type="text" class="required number" value="0"  name="no_balance" max="200" maxlength="5">
			</div>
		</div>
		
		<div class="control-group">
			<label class="control-label must">类型</label>
			<div class="controls">
				 <select name="type">
                	<option value="gszs" selected="selected">平台赠送</option>
                	<option value="order_refunded">退款补偿</option>
                </select>
			</div>
		</div>
		
		<div class="control-group">
			<label class="control-label must">详细描述</label>
			<div class="controls">
                  <input type="text" class="required"  name="reason" placeholder="最多输入25字" maxlength="25">
			</div>
		</div>
	  </div>
	  <div class="modal-footer">
	  	<span style="color:red;font-size:12px;float:left;margin-top:8px">仅用于微量补偿用户退款金额，请勿乱使用</span>
	    <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
	    <button class="btn btn-primary">确定补发</button>
	  </div>
	</div>
	<script>
	(function(){
		$('#reissueScoreModal').on('valid', function(){
			if(!confirm('确定补发积分吗？')){
				return false;
			};
		});
	})();
	</script>
</form>