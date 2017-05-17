<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no"/>
    <title><?php echo C('WEBSITE_NAME');?></title>
    <?php if(!preg_match('/(MSIE 7.0)/', $_SERVER['HTTP_USER_AGENT'])){ echo '<link href="//cdn.bootcss.com/pace/1.0.2/themes/orange/pace-theme-flash.css" rel="stylesheet">'; echo '<script src="//cdn.bootcss.com/pace/1.0.2/pace.min.js"></script>'; } ?>
    <link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/2.3.2/css/bootstrap.min.css" />
	<link rel="stylesheet" href="//cdn.bootcss.com/font-awesome/3.2.1/css/font-awesome.min.css" />
    <link rel="stylesheet" href="/css/admin.css" />
    <script src="//cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
	<!-- 顶部 -->
	<div class="sys-header">
		<div class="inner">
			<div class="system-name"><?php echo session('user.shop_name'); ?></div>
			<div class="box_message">
				<ul class="top_tool">
					<!-- <li class="message_all"><a><i class="icon-envelope icon-white"></i> 消息<span>(0)</span></a></li> -->
                    <?php $shopId = session('user.shop_id'); $aliAccessToken = M('alibaba_token')->find($shopId); if(!empty($aliAccessToken)){ $aliAccessToken['refresh_day'] = floor((strtotime($aliAccessToken['refresh_token_timeout']) - time())/86400); if(!empty($aliAccessToken)){ echo '<li class="set_all" title="'.$aliAccessToken['refresh_token_timeout'].'"><a href="/admin/index/aliOAuth"><i class="icon-time icon-white"></i> 1688授权有效期：'.$aliAccessToken['refresh_day'].'天</a></li>'; } } ?>
					<li class="set_all"><a href="javascript:win.modal('/admin/index/modifySwitch')"><i class="icon-retweet"></i> 切换店铺</a></li>
					<li class="set_all"><a href="javascript:win.modal('/admin/index/modifyPwd')"><i class="icon-lock icon-white"></i> 修改密码</a></li>
					<li class="exit_soft"><a href="/admin/login/out"><i class="icon-off icon-white"></i> 退出</a></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="body">
		<div class="col-side">
	    	<?php \Common\Common\Auth::get()->showMenuHtml() ?>
    	</div>
	    <div class="col-main">
	    	<div class="navbar menu-group">
			    <ul class="nav">
	    		  <?php echo \Common\Common\Auth::get()->showMenuGroup() ?>
			    </ul>
			</div>
			<div class="content-container"><link rel="stylesheet" href="/css/goods-edit.css">
<form id="goods_edit_form" class="form-horizontal fm-goods-info" method="post" action="<?php echo __ACTION__; ?>?id=<?php echo ($data["id"]); ?>">
	<input type="hidden" name="tao_id" value="<?php echo ($data["tao_id"]); ?>">
	<input type="hidden" name="shop_id" value="<?php echo ($data["shop_id"]); ?>">
	<div class="tabbable">
		<ul class="nav nav-tabs" id="myTab">
			<li<?php echo ($data['id'] ? '' : ' class="active"'); ?>><a href="#tab1" data-toggle="tab">1.选择商品类目</a></li>
			<li<?php echo ($data['id'] ? ' class="active"' : ''); ?>><a href="#tab2" data-toggle="tab">2.编辑基本信息</a></li>
			<li><a href="#tab3" data-toggle="tab">3.编辑属性详情</a></li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane<?php echo ($data['id'] ? '' : ' active'); ?>" id="tab1">
				<div id="class-info-region" class="goods-info-group">
					<div class="class-block">
						<div class="control-group">
							<div class="controls">
								<div class="widget-goods-klass" id="cat_list">
									<?php if(is_array($categorys[0])): foreach($categorys[0] as $key=>$level1): ?><div class="widget-goods-klass-item js-cat-item <?php echo isset($categorys[$level1['id']])?' has-children':'';?>" data-id="<?php echo ($level1["id"]); ?>" data-name="<?php echo ($level1["name"]); ?>">
                                        <div class="widget-goods-klass-name"><?php echo ($level1["name"]); ?><i class="cover-down"></i></div>
                                        <?php if(!empty($categorys[$level1['id']])): ?><ul class="widget-goods-klass-children">
                                            <?php if(is_array($categorys[$level1['id']])): foreach($categorys[$level1['id']] as $key=>$level2): ?><li data-pid="<?php echo ($level2['id']); ?>">
                                                    <label class="radio<?php echo isset($categorys[$level2['id']])?'':' js-cat-item';?>" data-id="<?php echo ($level2["id"]); ?>"><input type="radio" name="js-cat-sub" <?php echo isset($categorys[$level2['id']])?'disabled="disabled"':'';?>><?php echo ($level2["name"]); ?></label>
                                                </li>
                                                <?php if(is_array($categorys[$level2['id']])): foreach($categorys[$level2['id']] as $key=>$level3): ?><li data-pid="<?php echo ($level3['pid']); ?>">
                                                    <label class="radio js-cat-item" data-id="<?php echo ($level3["id"]); ?>"><input type="radio" name="js-cat-sub"><?php echo ($level3["name"]); ?></label>
                                                </li><?php endforeach; endif; endforeach; endif; ?>
                                        </ul><?php endif; ?>
                                    </div><?php endforeach; endif; ?>
									<div class="widget-goods-klass-item">
										<a class="widget-goods-klass-name" style="color: #999;border-style: dashed;background-color: #fff;" href="<?php echo __MODULE__; ?>/category">添加</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="app-actions">
				    <div class="form-actions text-center">
				        <button data-next-step="2" class="btn btn-primary js-switch-step" type="button">下一步</button>
				    </div>
				</div>
			</div>
			<div class="tab-pane<?php echo ($data['id'] ? ' active' : ''); ?>" id="tab2">
				<table class="fm-goods-info goods-info-group">
					<tr>
						<th>基本信息</th>
						<td>
							<div class="control-group">
								<label class="control-label">商品类目</label>
								<div class="controls">
									<a data-next-step="1" class="static-value js-switch-step" id="js-tag-step">未选择</a>
									<input type="hidden" name="cat_id" value="" required="required" data-msg-required="请选择商品类目">
								</div>
							</div>
							<div class="control-group">
								<label class="control-label">商品分组</label>
								<div class="controls">
									<select id="goods_tag"></select>
									<span class="gray">（最多支持3个）</span>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label">商品类型</label>
								<div class="controls js_is_virtual">
									<label class="radio inline">
										<input type="radio" name="is_virtual" value="0" checked="checked">实物商品<span class="gray">（物流发货）</span>
									</label>
									<label class="radio inline">
										<input type="radio" <?php echo ($data['is_virtual']?'checked="checked"':''); ?> name="is_virtual" value="1"<?php echo ($data['tao_id'] ? 'disabled="disabled"' : ''); ?>>虚拟商品<span class="gray">（无需物流）</span>
									</label>
								</div>
							</div>
							<input type="hidden" name="pay_type" value="1">
						</td>
					</tr>
					<tr>
						<th>库存/规格</th>
						<td>
							<div class="control-group">
								<label class="control-label">商品规格</label>
								<div class="controls">
									<select id="goods_sku_list" style="display: none;width:100px;">
										<option selected="selected"></option>
										<?php if(is_array($sku_list)): foreach($sku_list as $sku_id=>$sku_text): ?><option value="<?php echo ($sku_id); ?>"><?php echo ($sku_text); ?></option><?php endforeach; endif; ?>
									</select>
									<div id="goods_sku_content" class="sku-group" style="<?php echo ($data['tao_id'] ? 'display:none' : ''); ?>">
										<div id="add_goods_sku">
										    <h3 class="sku-group-title">
										        <button type="button" class="btn" id="add_goods_sku">添加商品规格</button>
										    </h3>
										</div>
									</div>
									
									<div id="product_list" style="display: none">
										<div id="stock-region" class="sku-stock">
											<table class="table-sku-stock">
												<thead><tr><th class="th-price">价格（元）</th><th class="th-stock">库存</th><th class="th-code">商家编码</th><th>销量</th></tr></thead>
												<tbody><tr><td colspan="4">&nbsp;</td></tr></tbody>
												<tfoot>
													<tr>
														<td colspan="6">
															<div class="batch-opts">
																批量设置：
																<span class="js-batch-type">
																	<a class="js-batch-price" href="javascript:;">价格</a> &nbsp;&nbsp; 
																	<a class="js-batch-stock" href="javascript:;">库存</a>
																</span>
																<span class="js-batch-form" style="display: none;">
																	<input type="text" class="js-batch-txt input-mini" placeholder="">
																	<a class="js-batch-save" href="javascript:;">保存</a>
																	<a class="js-batch-cancel" href="javascript:;">取消</a>
																	<p class="help-desc"></p>
																</span>
															</div>
														</td>
													</tr>
												</tfoot>
											</table>
										</div>
									</div>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label must">总库存</label>
								<div class="controls">
									<input type="text" value="<?php echo ($data["stock"]); ?>" required="required" name="stock" id="total_stock" data-rule-digits="digits" class="input-small">
									<label class="checkbox inline">
					                    <input type="checkbox"<?php echo ($data['hide_stock']==1?'checked="checked"':''); ?> value="1" name="hide_stock">页面不显示商品库存
					                </label>
					                <p class="help-desc">总库存为 0 或单品库存为0时，会上架到『已售罄的商品』列表里</p>
					                <p class="help-desc hide">发布后商品同步更新，以库存数字为准</p>
								</div>
							</div>
							<div class="control-group">
					            <label class="control-label">商家编码</label>
					            <div class="controls">
					                <input type="text" class="input-small" name="outer_id" value="<?php echo ($data["outer_id"]); ?>">
					            </div>
					        </div>
						</td>
					</tr>
					<tr>
						<th>商品信息</th>
						<td>
							<div class="control-group">
								<label class="control-label must">商品名</label>
								<div class="controls">
									<input class="input-xxlarge" required="required" type="text" name="title" maxlength="30" placeholder="建议30个字符以内" value="<?php echo ($data["title"]); ?>">
								</div>
							</div>
							<div class="control-group">
								<label class="control-label must">价格</label>
								<div class="controls">
									<div style="margin-right: 50px;">
  										<table class="table-sku-stock">
  											<tr>
  												<?php if(is_array($agentList)): foreach($agentList as $key=>$agent): ?><th class="must"><?php echo ($agent["title"]); ?>(元)</th><?php endforeach; endif; ?>
												<th>原价(元)</th>
												<th class="must">成本(元)</th>
  												<th class="must">重量(kg)</th>
  											</tr>
  											<tr class="js_price_list">
  												<?php if(is_array($agentList)): foreach($agentList as $key=>$agent): ?><td class="input-td">
														<input type="text" name="<?php echo ($agent["price_field"]); ?>" value="<?php echo $data[$agent['price_field']]; ?>" required="required" class="js-agent<?php echo ($agent["level"]); ?>_price input-mini" maxlength="10" data-rule-min="0.01" data-msg-min="不能小于0.01元" aria-required="true" aria-invalid="false">
													</td><?php endforeach; endif; ?>
												<td class="input-td">
													<input type="text" name="original_price" value="<?php echo ($data["original_price"]); ?>" class="js-original_price input-mini" maxlength="10" data-rule-min="0.01" data-msg-min="不能小于0.01元" aria-required="true" aria-invalid="false">
												</td>
												<td class="input-td">
													<input type="text" name="cost" value="<?php echo ($data["cost"]); ?>" required="required" class="js-cost input-mini" maxlength="10" data-rule-min="0.01" data-msg-min="不能小于0.01元" aria-required="true" aria-invalid="false">
												</td>
  												<td class="input-td">
  													<input type="text" name="weight" value="<?php echo ($data["weight"]); ?>" required="required" class="js-weight input-mini" aria-required="true" aria-invalid="false">
  												</td>
										</table>
									</div>
								</div>
							</div>
							<div class="control-group">
					            <label class="control-label must">商品图</label>
					            <div class="controls">
					            	<div class="picture-list ui-sortable">
					                    <ul class="js-picture-list app-image-list clearfix">
					                    <?php if(is_array($data[images])): foreach($data[images] as $key=>$vo): ?><li class="sort"><a href="<?php echo ($vo); ?>" target="_blank">
						                    	<img src="<?php echo ($vo); ?>"></a><a class="js-delete-picture close-modal small hide">×</a>
						                    	<input type="hidden" name="images[]" value="<?php echo ($vo); ?>">
						                    </li><?php endforeach; endif; ?>
											<li>
						                        <a href="javascript:;" class="add-goods js-add-picture">+加图</a>
						                    </li>
					                    </ul>
					                </div>
					            	<p class="help-desc">建议尺寸640 x 640 像素，第一张图将作为主图在列表中展示</p>
					            </div>
					        </div>
						</td>
					</tr>
					<tr>
						<th>限购</th>
						<td style="padding: 0;">
							<div style="width: 380px;height: 165px;padding-top: 15px;border-right: 2px solid #f8f8f8;float: left;">
								<div class="control-group">
						            <label class="control-label">会员级别</label>
						            <div class="controls">
						            	<?php if(is_array($allAgentList)): foreach($allAgentList as $key=>$agent): if(($agent['level']) != "0"): ?><label class="checkbox inline disabled"><input type="checkbox" disabled="disabled" checked="checked"><?php echo ($agent["title"]); ?></label>
						            	<?php else: ?>
						            	<label class="checkbox inline"><input type="checkbox" name="visitors_quota" value="1"<?php echo ($data['visitors_quota'] ? ' checked="checked"':''); ?>><?php echo ($agent["title"]); ?></label><?php endif; endforeach; endif; ?>
						            </div>
						        </div>
								<div class="control-group">
						            <label class="control-label">每人限购</label>
						            <div class="controls">
						                <input type="text" name="buy_quota" value="<?php echo ($data["buy_quota"]); ?>" class="input-small" data-rule-digits="digits" min="0">
						                <span class="gray">0 代表不限购</span>
						            </div>
						        </div>
						        <div class="control-group">
						            <label class="control-label">每日限购</label>
						            <div class="controls">
						                <input type="text" name="every_quota" value="<?php echo ($data["every_quota"]); ?>" class="input-small" data-rule-digits="digits" min="0">
						                <span class="gray">0 代表不限购</span>
						            </div>
						        </div>
						        <div class="control-group">
						            <label class="control-label">每日限售</label>
						            <div class="controls">
						                <input type="text" name="day_quota" value="<?php echo ($data["day_quota"]); ?>" class="input-small" data-rule-digits="digits" min="0">
						                <span class="gray">0 代表不限购</span>
						            </div>
						        </div>
					        </div>
					        <div class="remote-area">
	                            <div class="control-group">
	                                <label class="control-label">运费模板</label>
	                                <div class="controls">
	                                    <select name="freight_tid" id="freight_tid">
	                                        <?php if(is_array($freightList)): foreach($freightList as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>"<?php echo ($data['freight_tid'] == $item['id'] ? 'selected="selected"' : ''); ?> data-send="<?php echo ($item["send_id"]); ?>"><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
	                                    </select>
	                                    <a href="javascript:alert('正在开发中，请联系开发者创建模板');">新建</a>
	                                </div>
	                            </div>
					        	<div class="control-group">
						            <label class="control-label">偏远地区</label>
						            <div class="controls">
						                <label class="checkbox inline"><input type="checkbox" <?php echo strpos($data['remote_area'],'650000') > -1 ? 'checked="checked"' : '';?> value="650000" name='remote_area[]'>新疆</label>
										<label class="checkbox inline"><input type="checkbox" <?php echo strpos($data['remote_area'],'150000') > -1 ? 'checked="checked"' : '';?> value="150000" name='remote_area[]'>内蒙古</label>
						            </div>
						        </div>
						        <div class="control-group">
						            <div class="controls">
										<label class="checkbox inline"><input type="checkbox" <?php echo strpos($data['remote_area'],'540000') > -1 ? 'checked="checked"' : '';?> value="540000" name='remote_area[]'>西藏</label>
						                <label class="checkbox inline"><input type="checkbox" <?php echo strpos($data['remote_area'],'620000') > -1 ? 'checked="checked"' : '';?> value="620000" name='remote_area[]'>甘肃省</label>
						            </div>
						        </div>
						        <div class="control-group">
						            <div class="controls">
						                <label class="checkbox inline"><input type="checkbox" <?php echo strpos($data['remote_area'],'640000') > -1 ? 'checked="checked"' : '';?> value="640000" name='remote_area[]'>宁夏</label>
										<label class="checkbox inline"><input type="checkbox" <?php echo strpos($data['remote_area'],'630000') > -1 ? 'checked="checked"' : '';?> value="630000" name='remote_area[]'>青海省</label>
						            </div>
						            <div class="controls help-block">被勾选地区无法下单</div>
						        </div>
					        </div>
					    </td>
					</tr>
					<tr>
						<th>其他</th>
						<td>
					        <input type="hidden" name="post_fee" value="0">
					        <div class="control-group">
					            <label class="control-label">开售时间</label>
					            <div class="controls">
					                <label class="radio inline">
					                    <input type="radio" name="sold_time" value="0" checked="checked">立即开售
					                </label>
					                <label class="radio inline">
					                    <input type="radio" name="sold_time" value="<?php echo ($data['sold_time'] == 0 ? date('Y-m-d H:i:s') : $data['sold_time']); ?>" <?php echo ($data['sold_time']==0?'':'checked="checked"'); ?>>定时开售
					                </label>
									<div class="input-append inline <?php echo ($data['sold_time']==0?'hide':''); ?>" id="sold_time">
										<input type="text" readonly="readonly" value="<?php echo ($data['sold_time']>0?$data['sold_time']:date('Y-m-d H:i:s')); ?>" class="input-medium"/>
										<span class="add-on"><i class="icon-th icon-calendar"></i></span>
									</div>
									<p class="help-desc">开售时间决定商品是否可购买，并且影响新品排序</p>
					            </div>
					        </div>
					        <div class="control-group hide" id="join_discount">
					            <label class="control-label">会员折扣</label>
					            <div class="controls">
					                <label class="checkbox inline">
					                    <input type="checkbox" name="member_discount" value="1"<?php echo ($data['member_discount']==1?'checked="checked"':''); ?>>参加会员折扣
					                </label>
					                <label class="checkbox inline" style="margin: 0;padding-left: 0;">
						            	<span class="gray">若勾选会员折扣，有可能最终出售价格低于成本价，造成亏损，请参考成本价合理设置折扣</span>
					                </label>
					            </div>
					        </div>
					        <div class="control-group">
					            <label class="control-label">售后保障</label>
					            <div class="controls">
					                <label class="checkbox inline">
					                    <input type="checkbox" name="invoice" value="1"<?php echo ($data['invoice']==1?' checked="checked"':''); ?>>发票
					                </label>
					                <label class="checkbox inline">
					                    <input type="checkbox" name="warranty" value="1"<?php echo ($data['warranty']==1?' checked="checked"':''); ?>>保修
					                </label>
					                <label class="checkbox inline">
					                    <input type="checkbox" name="returns" value="1"<?php echo ($data['returns']==1?' checked="checked"':''); ?>>退换
					                </label>
					            </div>
					        </div>
                            <div class="control-group">
                                <label class="control-label">积分抵用</label>
                                <div class="controls">
                                    <input type="text" class="input-small" name="score" value="<?php echo ($data["score"]); ?>" data-rule-range="0,100">%
                                    <span class="gray">0表示不参与积分抵用</span>
                                </div>
                            </div>
						</td>
					</tr>
				</table>
			
				<div class="app-actions">
				    <div class="form-actions text-center">
				        <button data-next-step="3" class="btn btn-primary js-switch-step" type="button">下一步</button>
				    </div>
				</div>
			</div>
			<div class="tab-pane" id="tab3">
				<div class="goods-info-group">
					<div class="app-design">
						<div class="app-sidebar">
							<div class="app-sidebar-inner goods-sidebar-goods-template js-goods-sidebar-sub-title hide" style="display: block;">
					        	<div class="form-horizontal">
						            <div class="control-group">
						                <label class="control-label">商品页模板</label>
						                <div class="controls">
						                    <select name="template_id">
						                    	<option value="0">默认模板</option>
						                    	<option value="1"<?php echo ($data['template_id'] == 1 ? ' selected="selected"' : ''); ?>>简洁流畅版</option>
						                    </select>
						                </div>
						            </div>
						        </div>
						    </div>
						    <div class="app-sidebar-inner goods-sidebar-sub-title js-goods-sidebar-sub-title hide" style="display: block;">
						        <p >商品简介(选填，微信分享给好友时会显示这里的文案)</p>
						        <textarea class="js-sub-title input-sub-title" style="width: 404px; max-width: 380px; margin: 0px; height: 100px;" name="digest" maxlength="200"><?php echo (htmlspecialchars($data['digest'])); ?></textarea>
						    </div>
						    <div class="app-sidebar-inner goods-sidebar-sub-title js-goods-sidebar-sub-title hide" style="display: block;">
						        <p >产品参数<a class="js-add-attr" style="float:right;font-size:12px" data-total="<?php echo count($data['parameters']);?>">添加</a></p>
						        <div style="height: 306px;background-color: #fff;border: 1px solid #d1d1d1;overflow-y:auto;">
						        	<table class="attr-table">
						        		<tbody>
						        			<tr>
							        			<th>发货地区</th>
							        			<td style="padding:0" id="send_place">
							        				<input type="hidden" name="send_place" value="<?php echo ($data["send_place"]); ?>">
							        				<div class="js-send-place-name" style="height:36px;line-height:36px;padding: 0 8px;">不显示</div>
							        			</td>
						        			</tr>
							        		<tr>
							        			<th>参数名称</th>
							        			<td>参数值</td>
							        		</tr>
						        			<?php foreach ($data['parameters'] as $key =>$value):?>
						        			<tr>
							        			<th><input type="text" style="width:60px" name="parameters[<?php echo ($key); ?>][key]" value="<?php echo ($value[0]); ?>" maxlength="8"></th>
							        			<td><a class="delete-attr label label-warning">删除</a><input type="text" name="parameters[<?php echo ($key); ?>][value]" value="<?php echo ($value[1]); ?>" maxlength="128"></td>
						        			</tr>
						        			<?php endforeach ?>
							        	</tbody>
						        	</table>
						        </div>
						    </div>
						</div>
						<div class="app-sidebar">
						    <div class="app-sidebar-inner goods-sidebar-sub-title js-goods-sidebar-sub-title hide" style="display: block;">
						        <p >图文详情</p>
						        <script id="image_text_container" name="detail" type="text/plain"><?php echo ($data["detail"]); ?></script>
						    </div>
						</div>
					</div>
				</div>
				<div class="app-actions">
				    <div class="form-actions text-center">
				        <button data-next-step="2" class="btn js-switch-step" type="button">上一步</button>
				        <button class="btn btn-primary js-submit" type="submit" data-display="1">上架</button>
				        <button class="btn js-submit" type="submit" data-display="0">下架</button>
				        <?php if(ACTION_NAME == 'edit'): ?><button class="btn btn-primary js-submit" type="submit" data-display="<?php echo ($data["is_display"]); ?>">保存</button><?php endif; ?>
				    </div>
				</div>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript" src="//cdn.bootcss.com/select2/4.0.3/js/select2.min.js"></script>
<script type="text/javascript" src="/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/ueditor/ueditor.all.min.js"></script>
<script type="text/javascript" src="//cdn.bootcss.com/jquery-validate/1.15.0/jquery.validate.min.js"></script>
<script type="text/javascript" src="/js/goods-edit.js"></script>
<link rel="stylesheet" href="//cdn.bootcss.com/select2/4.0.3/css/select2.min.css">
<script type="text/javascript">
$(function(){
	EditGoods.agentList = <?php echo json_encode($agentList);?>;
	EditGoods.init(<?php echo json_encode($data);?>);
	EditGoods.setFreightTemplates(<?php echo json_encode($freightList);?>);
});
</script></div>
	    </div>
	</div>
	<div class="footer">
		<div class="copyright">
			<div class="ft-copyright"></div>
		</div>
	</div>
	<div class="back-to-top">
	    <a href="javascript:;" class="js-back-to-top"><i class="icon-chevron-up"></i>返回顶部</a>
	</div>
	<script src="/js/common.js"></script>
	<script src="/js/gridview.js"></script>
	<script src="//cdn.bootcss.com/bootstrap/2.3.2/js/bootstrap.min.js"></script>
</body>
</html>