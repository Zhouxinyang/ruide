<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-submit="ajax" class="form-horizontal">
	<div class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	    <h3>编辑角色</h3>
	  </div>
	  <div class="modal-body">
	  	<input type="hidden" name="id" value="<?php echo ($data["id"]); ?>">
		<div class="control-group">
			<label class="control-label must">角色名称</label>
			<div class="controls">
				<input type="text" name="name" value="<?php echo ($data["name"]); ?>" placeholder="最多8个字符" maxlength="8" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">状态</label>
			<div class="controls">
				<select name="status">
					<option value="1">启用</option>
					<option value="0"<?php echo ($data['status'] == 0 ? ' selected' : ''); ?>>禁用</option>
				</select>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">备注</label>
			<div class="controls">
				<input type="text" name="remark" placeholder="50个字符以内" value="<?php echo ($data["remark"]); ?>" />
			</div>
		</div>
	  </div>
	  <div class="modal-footer">
	  	<button type="button" class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
	  	<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
	  </div>
  </div>
</form>