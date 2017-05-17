<?php if (!defined('THINK_PATH')) exit();?><form method="post" action="<?php echo __ACTION__; ?>" data-validate="true" data-submit="ajax" class="form-horizontal">
	<div id="catEditModal" class="modal modal-small hide fade" tabindex="-1" role="dialog" aria-hidden="true">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3>类目管理</h3>
	  </div>
	  <div class="modal-body">
	   <input type="hidden" name="id" value="<?php echo ($da["id"]); ?>">
	   <?php if($showParent == 1):?>
	 <div class="control-group">
			<label class="control-label must">类目选择</label>
			<div class="controls">
				<select name="pid" onchange="change(this)">
					<option value="0" >一级类目</option>
					<?php foreach ($data as $key =>$value):?>
                	<option value="<?php echo ($value["id"]); ?>" <?php echo ($value['id'] == $da['pid'] ? ' selected="selected"' : ''); ?>><?php echo ($value["name"]); ?></option>
                	<?php endforeach ?>
                </select>
			</div>
		</div>  
		<div class="control-group">
			<label class="control-label">子类目选择</label>
			<div class="controls">
				<select name="eid" id="er">
                	<option value="">请选择</option>
                </select>
			</div>
		</div> 
		<?php else: ?>
		 <input type="hidden" name="pid" value="<?php echo ($da["pid"]); ?>">
		<?php endif ?>
		
		<div class="control-group">
			<label class="control-label must">类目名称</label>
			<div class="controls">
				<input type="text" name="name" value="<?php echo ($da["name"]); ?>" placeholder="最多6个字符" maxlength="6" required="required">
			</div>
		</div>
		
	     <div class="control-group">
		  	<label class="control-label">图标</label>
		  	<div class="controls">
				<input class="hide" type="text" name="icon" value="<?php echo ($da["icon"]); ?>" readonly="readonly"  data-msg-required="请上传图标">
				<img id="shop_logo" src="<?php echo ($da["icon"]); ?>" class="img-polaroid btn-up" alt="logo" style="width: 64px; height: 64px;">
			</div>
		  </div> 
		
		<div class="control-group">	
			<label class="control-label must">排序</label>
			<div class="controls">
			<?php if($da):?>
				<input type="text"  value="<?php echo ($da["sort"]); ?>"  class="required number" name="sort" placeholder="数字越大越靠前" maxlength="6">
			<?php else:?>
				<input type="text"  value="0"  class="required number" name="sort" placeholder="数字越大越靠前" maxlength="6">
			<?php endif ?>
			</div>
		</div>
	  </div>
	  <div class="modal-footer">
	  	<button type="button" class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
	  	<button type="submit" class="btn btn-primary" aria-hidden="true">保存</button>
	  </div>
  </div>
</form>

<script type="text/javascript" src="/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/ueditor/ueditor.all.min.js"></script>
<div style="display: none;" id="editor" class="edui-default"></div>
<style>.modal{z-index:600}.modal-backdrop{z-index:500}</style>
<script>
$(function(){
	var editor = UE.getEditor('editor',{isShow: false})
	$('#shop_logo').on('click', function(){
		var $img = $(this);
		var $input_url = $img.prev();
		editor.removeListener('beforeInsertImage');
		editor.addListener('beforeInsertImage', function (t, list) {
			$input_url.val(list[0]['src']);
			$img.attr('src', list[0]['src']);
        });
		
		editor.getDialog("insertimage").open();
	});
});

function change(a){
	var id = a.value;
	if(id==0){
		var html = "<option value='"+id+"'>请选择</option>";
		 $("#er").html(html);  
	}else{
		$.ajax({
			 url: '<?php echo __CONTROLLER__; ?>/edit',
			 type: 'get',
			 dataType: 'json',
			 data: {eid:id},
			 success: function(msg){
		 			 var html = "<option value='"+id+"'>二级类目</option>";
	                 $.each(eval(msg), function(i, item) {  
	                     html+= "<option value='" + item.id + "'>" + item.name + "</option>";
	                 });
	                 $("#er").html(html);  
		    }
		    });
	}
		
} 
</script>