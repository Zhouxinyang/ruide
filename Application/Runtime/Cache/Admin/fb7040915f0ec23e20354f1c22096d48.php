<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-continue="true"
data-submit="ajax" class="form-horizontal modal modal-small hide fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3>添加用户</h3>
  </div>
  <div class="modal-body">
		<div class="control-group">
			<label class="control-label must">姓名</label>
			<div class="controls">
				<input type="text" name="data[username]"
					placeholder="最多8个字符" maxlength="8" class="required">
			</div>
		</div>
	  <div class="control-group">
		  <label class="control-label must">身份证号码</label>
		  <div class="controls">
			  <input type="text" name="data[card]"
					 placeholder="请输入标准身份证号码" maxlength="18" class="required cardid">
		  </div>
	  </div>
		<div class="control-group">
			<label class="control-label must">微信号</label>
			<div class="controls">
				<input type="text" name="data[wechat]"
					placeholder="至少6位" minlength="6" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">手机号码</label>
			<div class="controls">
				<input type="text" name="data[mobile]"
					   placeholder="请输入11位" maxlength="11" class="required mobile">
			</div>
		</div>

	</div>
  <div class="modal-footer">
  	<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
  	<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
  </div>
</form>