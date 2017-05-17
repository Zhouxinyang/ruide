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
				<input type="text" name="data[nick]"
					placeholder="最多8个字符" maxlength="8" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">店铺</label>
			<div class="controls">
				<select name="data[shop_id]" data-search="true">
					<?php if(is_array($shop)): foreach($shop as $key=>$vo): ?><option value="<?php echo ($vo["id"]); ?>" <?php if($vo['id'] == $my_shop): ?>selected='selected'<?php endif; ?>><?php echo ($vo["name"]); ?></option><?php endforeach; endif; ?>
				</select>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">登陆账号</label>
			<div class="controls">
				<input type="text" name="data[username]"
					placeholder="最多16个字符" maxlength="16" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">登陆密码</label>
			<div class="controls">
				<input id="password" type="text" name="data[password]" placeholder="最多32个字符"
					maxlength="32" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">确认密码</label>
			<div class="controls">
				<input type="text" name="password2" placeholder="最多32个字符" maxlength="32" class="required equalto" data-equal-to="#password" data-msg-equalto="密码不一致">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">状态</label>
			<div class="controls">
				<select name="data[status]">
					<option value="1">启用</option>
					<option value="0">禁用</option>
				</select>
			</div>
		</div>
	</div>
  <div class="modal-footer">
  	<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
  	<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
  </div>
</form>