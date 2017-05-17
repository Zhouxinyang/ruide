<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-continue="true"data-submit="ajax" class="form-horizontal modal modal-small hide fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">修改分组</h3>
  </div>
  <div class="modal-body" style="padding-left:25px">
  	<input type="hidden" name="id" value="<?php echo ($goods['id']); ?>">
    <?php if(is_array($tagList)): foreach($tagList as $key=>$vo): ?><label class="checkbox ellipsis" style="width:110px;display: inline-block;">
            <input type="checkbox" name="tag_id[]" value="<?php echo ($vo["id"]); ?>"<?php echo in_array($vo['id'], $goods['tag_id']) ? ' checked="checked"' : '';?>><?php echo ($vo["name"]); ?>
        </label><?php endforeach; endif; ?>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
    <button class="btn btn-primary">保存</button>
  </div>
</form>