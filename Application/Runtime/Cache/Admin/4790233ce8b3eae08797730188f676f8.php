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
				<input type="text" name="data[nick]" value="<?php echo ($data["nick"]); ?>"
					placeholder="最多8个字符" maxlength="8" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">主店铺</label>
			<div class="controls">
				<select name="data[shop_id]" id="zhu">
					<?php if(is_array($shop)): foreach($shop as $key=>$vo): ?><option value="<?php echo ($vo["id"]); ?>" <?php if($vo['id'] == $data['shop_id']): ?>selected='selected'<?php endif; ?>><?php echo ($vo["name"]); ?></option><?php endforeach; endif; ?>
				</select>
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
		<div class="control-group">
			<label class="control-label">关联店铺</label>
			<div class="controls">
				<?php if(is_array($shop)): foreach($shop as $key=>$item): ?><label class="checkbox inline" style="width:145px;margin-left:-3px;">
			    <input id="fu" name="sid[]" type="checkbox" value="<?php echo ($item["id"]); ?>" <?php echo in_array($item['id'], $sid) ? ' checked' : '';?>  /><?php echo ($item["name"]); ?>
			    </label><?php endforeach; endif; ?>
			</div>
		</div>
		<!--
		<div class="control-group">
			<label class="control-label">用户类型</label>
			<div class="controls">
				<select name="data[type]" data-search="true">
					<?php if(is_array($user_type_list)): foreach($user_type_list as $key=>$value): ?><option value="<?php echo ($key); ?>"<?php echo ($data['type'] == $key ? ' selected' : ''); ?>><?php echo ($value); ?></option><?php endforeach; endif; ?>
				</select>
				<i class="icon-question-sign"></i>
			</div>
		</div>
		 -->
	</div>
  <div class="modal-footer">
  	<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
  	<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
  </div>
</form>