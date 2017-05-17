<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true"
data-submit="ajax" class="form-horizontal modal modal-small hide fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3>修改我的密码</h3>
  </div>
  <div class="modal-body">
		<div class="control-group">
			<label class="control-label must">新密码</label>
			<div class="controls">
				<input id="password" type="password" name="password" placeholder="最多32个字符" maxlength="32" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">确认密码</label>
			<div class="controls">
				<input type="password" name="password2" placeholder="最多32个字符" maxlength="32" class="required equalto" data-equal-to="#password" data-msg-equalto="密码不一致">
			</div>
		</div>
	</div>
  <div class="modal-footer">
  	<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
  	<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
  </div>
</form>