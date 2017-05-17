<?php if (!defined('THINK_PATH')) exit();?><div id="news-page-<?php echo ($page); ?>" style="display: inline-block;width: 100%;">
<div class="media_preview_area" style="margin-right: 20px;">
	<?php if(is_array($list1)): $i = 0; $__LIST__ = $list1;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$r): $mod = ($i % 2 );++$i; if(count($r['items']) == 0): ?><div class="js-item appmsg" data-media_id="">
				<div class="appmsg_content">
					<h4 class="js-item appmsg_title js_title">
						<a href="<?php echo ($r['link']); ?>" target="_blank"><?php echo ($r['title']); ?></a>
					</h4>
					<div class="appmsg_info">	
						<em class="appmsg_date"><?php echo ($r['created']); ?></em>
					</div>
					<div class="appmsg_thumb_wrp">	
						<img src="<?php echo ($r['cover_url']); ?>" data-src="<?php echo ($r['cover_url']); ?>" alt="封面" class="appmsg_thumb">
					</div>
					<p class="appmsg_desc"><?php echo ($r['digest']); ?></p>
					<div style="margin-bottom:10px;">
						<a href="<?php echo __CONTROLLER__; ?>/edit/id/<?php echo ($r['id']); ?>">编辑</a>
						<a href="javascript:if(confirm('确认删除吗?'))window.location='<?php echo __CONTROLLER__; ?>/delete/id/<?php echo ($r['id']); ?>'" style="float:right;">删除</a>
					</div>
				</div>
			</div>
		<?php else: ?>
			<div class="js-item appmsg" data-media_id="">
				<div class="appmsg_content">
					<div class="appmsg_item js_appmsg_item has_thumb">
						<img class="js_appmsg_thumb appmsg_thumb" src="<?php echo ($r['cover_url']); ?>" data-src="<?php echo ($r['cover_url']); ?>">
						<h4 class="appmsg_title">
							<a href="<?php echo ($r['link']); ?>" target="_blank"><?php echo ($r['title']); ?></a>
						</h4>
					</div>
					<?php if(is_array($r['items'])): $i = 0; $__LIST__ = $r['items'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><div class="appmsg_item js_appmsg_item has_thumb">
							<img class="js_appmsg_thumb appmsg_thumb" src="<?php echo ($v['cover_url']); ?>" data-src="<?php echo ($v['cover_url']); ?>">
							<h4 class="appmsg_title">
								<a href="<?php echo ($v['link']); ?>" target="_blank"><?php echo ($v['title']); ?></a>
							</h4>
						</div><?php endforeach; endif; else: echo "" ;endif; ?>
					<div style="margin-bottom:10px;">
						<a href="<?php echo __CONTROLLER__; ?>/edit/id/<?php echo ($r['id']); ?>">编辑</a>
						<a href="javascript:if(confirm('确认删除吗?'))window.location='<?php echo __CONTROLLER__; ?>/delete/id/<?php echo ($r['id']); ?>'" style="float:right;">删除</a>
					</div>
				</div>
			</div><?php endif; endforeach; endif; else: echo "" ;endif; ?>
</div>
<div class="media_preview_area" style="margin-right: 20px;">
	<?php if(is_array($list2)): $i = 0; $__LIST__ = $list2;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$r): $mod = ($i % 2 );++$i; if(count($r['items']) == 0): ?><div class="js-item appmsg" data-media_id="">
				<div class="appmsg_content">
					<h4 class="js-item appmsg_title js_title">
						<a href="<?php echo ($r['link']); ?>" target="_blank"><?php echo ($r['title']); ?></a>
					</h4>
					<div class="appmsg_info">	
						<em class="appmsg_date"><?php echo ($r['created']); ?></em>
					</div>
					<div class="appmsg_thumb_wrp">	
						<img src="<?php echo ($r['cover_url']); ?>" data-src="<?php echo ($r['cover_url']); ?>" alt="封面" class="appmsg_thumb">
					</div>
					<p class="appmsg_desc"><?php echo ($r['digest']); ?></p>
					<div style="margin-bottom:10px;">
						<a href="<?php echo __CONTROLLER__; ?>/edit/id/<?php echo ($r['id']); ?>">编辑</a>
						<a href="javascript:if(confirm('确认删除吗?'))window.location='<?php echo __CONTROLLER__; ?>/delete/id/<?php echo ($r['id']); ?>'" style="float:right;">删除</a>
					</div>
				</div>
			</div>
		<?php else: ?>
			<div class="js-item appmsg" data-media_id="">
				<div class="appmsg_content">
					<div class="appmsg_item js_appmsg_item has_thumb">
						<img class="js_appmsg_thumb appmsg_thumb" src="<?php echo ($r['cover_url']); ?>" data-src="<?php echo ($r['cover_url']); ?>">
						<h4 class="appmsg_title">
							<a href="<?php echo ($r['link']); ?>" target="_blank"><?php echo ($r['title']); ?></a>
						</h4>
					</div>
					<?php if(is_array($r['items'])): $i = 0; $__LIST__ = $r['items'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><div class="appmsg_item js_appmsg_item has_thumb">
							<img class="js_appmsg_thumb appmsg_thumb" src="<?php echo ($v['cover_url']); ?>" data-src="<?php echo ($v['cover_url']); ?>">
							<h4 class="appmsg_title">
								<a href="<?php echo ($v['link']); ?>" target="_blank"><?php echo ($v['title']); ?></a>
							</h4>
						</div><?php endforeach; endif; else: echo "" ;endif; ?>
					<div style="margin-bottom:10px;">
						<a href="<?php echo __CONTROLLER__; ?>/edit/id/<?php echo ($r['id']); ?>">编辑</a>
						<a href="javascript:if(confirm('确认删除吗?'))window.location='<?php echo __CONTROLLER__; ?>/delete/id/<?php echo ($r['id']); ?>'" style="float:right;">删除</a>
					</div>
				</div>
			</div><?php endif; endforeach; endif; else: echo "" ;endif; ?>
</div>
<div class="media_preview_area" style="margin-right: 20px;">
	<?php if(is_array($list3)): $i = 0; $__LIST__ = $list3;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$r): $mod = ($i % 2 );++$i; if(count($r['items']) == 0): ?><div class="js-item appmsg" data-media_id="">
				<div class="appmsg_content">
					<h4 class="js-item appmsg_title js_title">
						<a href="<?php echo ($r['link']); ?>" target="_blank"><?php echo ($r['title']); ?></a>
					</h4>
					<div class="appmsg_info">	
						<em class="appmsg_date"><?php echo ($r['created']); ?></em>
					</div>
					<div class="appmsg_thumb_wrp">	
						<img src="<?php echo ($r['cover_url']); ?>" data-src="<?php echo ($r['cover_url']); ?>" alt="封面" class="appmsg_thumb">
					</div>
					<p class="appmsg_desc"><?php echo ($r['digest']); ?></p>
					<div style="margin-bottom:10px;">
						<a href="<?php echo __CONTROLLER__; ?>/edit/id/<?php echo ($r['id']); ?>">编辑</a>
						<a href="javascript:if(confirm('确认删除吗?'))window.location='<?php echo __CONTROLLER__; ?>/delete/id/<?php echo ($r['id']); ?>'" style="float:right;">删除</a>
					</div>
				</div>
			</div>
		<?php else: ?>
			<div class="js-item appmsg" data-media_id="">
				<div class="appmsg_content">
					<div class="appmsg_item js_appmsg_item has_thumb">
						<img class="js_appmsg_thumb appmsg_thumb" src="<?php echo ($r['cover_url']); ?>" data-src="<?php echo ($r['cover_url']); ?>">
						<h4 class="appmsg_title">
							<a href="<?php echo ($r['link']); ?>" target="_blank"><?php echo ($r['title']); ?></a>
						</h4>
					</div>
					<?php if(is_array($r['items'])): $i = 0; $__LIST__ = $r['items'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><div class="appmsg_item js_appmsg_item has_thumb">
							<img class="js_appmsg_thumb appmsg_thumb" src="<?php echo ($v['cover_url']); ?>" data-src="<?php echo ($v['cover_url']); ?>">
							<h4 class="appmsg_title">
								<a href="<?php echo ($v['link']); ?>" target="_blank"><?php echo ($v['title']); ?></a>
							</h4>
						</div><?php endforeach; endif; else: echo "" ;endif; ?>
					<div style="margin-bottom:10px;">
						<a href="<?php echo __CONTROLLER__; ?>/edit/id/<?php echo ($r['id']); ?>">编辑</a>
						<a href="javascript:if(confirm('确认删除吗?'))window.location='<?php echo __CONTROLLER__; ?>/delete/id/<?php echo ($r['id']); ?>'" style="float:right;">删除</a>
					</div>
				</div>
			</div><?php endif; endforeach; endif; else: echo "" ;endif; ?>
</div>
</div>

<div id="pagination" style="text-align: right;" data-page="<?php echo ($page); ?>" data-total="<?php echo ($total); ?>" data-offset="<?php echo ($offset); ?>"></div>