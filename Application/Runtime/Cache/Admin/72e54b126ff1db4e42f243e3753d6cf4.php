<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-continue="true"
	  data-submit="ajax" class="form-horizontal modal modal-small hide fade" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3>编辑用户</h3>
	</div>
	<div class="modal-body">
		<input type="text" class="hidden" name="data[id]" value="<?php echo ($data['id']); ?>">
		<div class="control-group">
			<label class="control-label must">姓名</label>
			<div class="controls">
				<input type="text" name="data[username]" value="<?php echo ($data["username"]); ?>"
					   placeholder="最多8个字符" maxlength="8" class="required">
			</div>
		</div>

		<div class="control-group">
			<label class="control-label must">身份证号码</label>
			<div class="controls">
				<input type="text" name="data[card]" value="<?php echo ($data["card"]); ?>"
					   placeholder="请输入身份证号码" length="18" class="required">
			</div>
		</div>

		<div class="control-group">
			<label class="control-label must">微信号</label>
			<div class="controls">
				<input type="text" name="data[wechat]" value="<?php echo ($data["wechat"]); ?>"
					   placeholder="至少6位" minlength="6" class="required">
			</div>
		</div>

		<div class="control-group">
			<label class="control-label must">手机号</label>
			<div class="controls">
				<input type="text" name="data[mobile]" value="<?php echo ($data["mobile"]); ?>"
					   placeholder="请输入手机号码" length="11" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">状态</label>
			<div class="controls">
				<select name="data[status]">
					<option value="1"<?php echo ($data['status']==1? ' selected' : ''); ?>>启用</option>
					<option value="0"<?php echo ($data['status']==0? ' selected' : ''); ?>>禁用</option>
				</select>
			</div>
		</div>

	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
		<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
	</div>
</form>