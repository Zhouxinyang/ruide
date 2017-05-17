<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-continue="true"
data-submit="ajax" class="form-horizontal modal modal-small hide fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3>编辑代理升级条件</h3>
  </div>
  <div class="modal-body">
		<input type="text" class="hidden" name="id" value="<?php echo ($data['id']); ?>">
		<div class="control-group">
			<label class="control-label">代理级别</label>
			<div class="controls">
				<input type="text" name="level" value="<?php echo ($data["level"]); ?>" disabled>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">代理名称</label>
			<div class="controls">
				<input type="text" name="title" value="<?php echo ($data["title"]); ?>"
					placeholder="最多8个字符" maxlength="8" class="required">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">售价标题</label>
			<div class="controls">
				<input type="text" name="price_title" value="<?php echo ($data["price_title"]); ?>"
					placeholder="最多8个字符" maxlength="8" class="required"}>
			</div>
		</div>
		<?php if($data['level'] > 1): ?><div class="control-group">
			<label class="control-label must">一次性充值</label>
			<div class="controls">
				<input type="text" name="once_amount" value="<?php echo ($data["once_amount"]); ?>" maxlength="10" min="0.00" class="required number">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label must">赠送货款</label>
			<div class="controls">
				<input type="text" name="self_amount" value="<?php echo ($data["self_amount"]); ?>" maxlength="10" min="0.00" class="required number">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">推荐人</label>
			<div class="controls">
				<input type="text" name="parent1_amount" value="<?php echo ($data["parent1_amount"]); ?>" placeholder="一级" maxlength="10" min="0.00" class="number" style="width:50px">
				<input type="text" name="parent2_amount" value="<?php echo ($data["parent2_amount"]); ?>" placeholder="二级" maxlength="10" min="0.00" class="number" style="margin-left:15px; width:50px">
				<input type="text" name="parent3_amount" value="<?php echo ($data["parent3_amount"]); ?>" placeholder="三级" maxlength="10" min="0.00" class="number" style="margin-left:15px; width:50px">
			</div>
		</div><?php endif; ?>
	</div>
  <div class="modal-footer">
  	<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
  	<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
  </div>
</form>