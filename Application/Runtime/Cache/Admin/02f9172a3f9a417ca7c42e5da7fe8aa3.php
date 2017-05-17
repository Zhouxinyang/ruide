<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-submit="ajax" data-validate="true" class="form-horizontal modal modal-small hide fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3>授权</h3>
  </div>
  <div class="modal-body role-list">
    <input type="text" class="hidden" name="user_id" value="<?php echo ($user_id); ?>">
    <?php if(is_array($role_list)): foreach($role_list as $key=>$role): ?><label class="checkbox inline"><input name="role_id[]" type="checkbox" value="<?php echo ($role["id"]); ?>" <?php echo in_array($role['id'], $my_role) ? ' checked' : '';?> /><?php echo ($role["name"]); ?></label><?php endforeach; endif; ?>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
    <button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
  </div>
  <style>
   .role-list .checkbox{width:100px}
   .role-list .radio.inline+.radio.inline, .checkbox.inline+.checkbox.inline{margin-left:0}
  </style>
</form>