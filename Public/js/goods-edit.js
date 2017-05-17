/**
 * 编辑商品
 */
var EditGoods = {
	data: {sku_json: [], products: []},
	goods: {},
	agentList: {},
	init: function(goods){
		var t = this;
		t.goods = goods;
		
		// 下一步
		$('.js-switch-step').on('click', function(){
			var step = this.getAttribute('data-next-step') - 1;
			$('#myTab a:eq('+step+')').tab('show');
		});
		
		// 选择类目
		$('#class-info-region .js-cat-item').on('click', function(){
			t.catChanged(this);
			return false;
		});
		if(EditGoods.goods.cat_id){
			t.catChanged($('#cat_list .js-cat-item[data-id="'+EditGoods.goods.cat_id+'"]'));
		}
		
		// 点击添加商品规格按钮
		$('#add_goods_sku').on('click', function(){
			t.addGoodsSku();
		});

		// 计算总库存、价格、积分
		$('#stock-region table').on('change', 'input',function(){
			var $this = $(this);
			if($this.hasClass('js-stock-num')){ // 库存
				if(this.value != '' && !/^\d{1,9}$/.test(this.value)){
					this.value = parseInt(this.value);
				}
			}else if($this.hasClass('js-price') || $this.hasClass('js-original_price') || $this.hasClass('js-weight')){
				if(this.value != '' && !isNaN(this.value)){
					var value = parseFloat(this.value);
					if(value < 0){ value = -value }
					this.value = value.toFixed(2);
				}else{
					this.value = '';
				}
			}
			 
			t.resetPriceAndStock();
			return false;
		}).on('dblclick', 'input',function(){
			var $this = $(this),
				$tr = $this.parent().parent();
			var value = $this.val();
			$tr.nextAll().find('input[class="'+$this.attr('class')+'"]').val(value);
			t.resetPriceAndStock();
			return false;
		});

		// 原价不能小于售价
		$('.js_price_list').on('change', function(){
			if(this.value == '' || isNaN(this.value)){
				return false;
			}
			
			var val = parseFloat(this.value);
			val = val.toFixed(2);
			this.value = val;
			
			var $tr = $(this).parent();
			var price = $tr.find('.js-price').val();
			$tr.find('.js-original_price').data('ruleMin', price);
			$tr.find('.js-agent2_price,.js-agent3_price').data('ruleMax', price);
			return false;
		});
		
		// 初始化SKU列表
		if(t.goods.sku_json && !t.goods.tao_id){
			for(var i=0; i<t.goods.sku_json.length; i++){
				t.addGoodsSku(goods.sku_json[i]);
			}
		}

		t.initGoodsTag();
		t.initForm();
		t.initSoldTime();
		t.resetSKUStore();
		t.initImg();
		t.initAttr();
		$.getScript('/js/address.js', function(){
			t.initSendPlace();
		});
		
		$('#cat_list .widget-goods-klass-children li').on('mouseover', function(){
			var $li = $(this),
				pid = $li.data('pid'),
				$siblings = $li.siblings();
			$li.addClass('hover');
			$siblings.each(function(i){
				if($siblings.eq(i).data('pid') == pid){
					$siblings.eq(i).addClass('hover')
				}else{
					$siblings.eq(i).removeClass('hover')
				}
			});
			return false;
		});
	}
	,catChanged: function(target){
		var t = this
			,$this = $(target)
		   ,$item = null
		   ,$siblings = null
		   ,name = ''
		   ,catId = $this.attr('data-id');
		
		// 一级类目
		if($this.hasClass('widget-goods-klass-item')){
			if($this.hasClass('has-children')){
				return false;
			}
			name = $this.children('.widget-goods-klass-name').text();
			$item = $this;
		}else{
			$this.children().prop('checked', true);
			name = $this.text();
			$item = $this.parents('.widget-goods-klass-item:first');
			$item.children(':first').html(name + '<i class="cover-down"></i>');
			name = $item.data('name') + ' - ' + name;
		}
		$siblings = $item.siblings('.widget-goods-klass-item');
		$siblings.each(function(i, item){
			if(i == $siblings.length - 1){
				return false;
			}
			var $cat = $siblings.eq(i);
			$cat.removeClass('current');
			$cat.children(':first').html($cat.data('name') + '<i class="cover-down"></i>');
		});
		
		$item.addClass('current');
		$('#js-tag-step').html(name).next().val(catId);
		EditGoods.goods.cat_id = catId;
		
		var is_hongbao = false;
		// 如果是红包商品 - 虚拟商品
		$('.js_is_virtual input[type="radio"]').each(function(i, item){
			if(is_hongbao){
				if(this.value == 0){
					this.disabled = true;
				}else if(this.value == 1){
					this.checked = true;
					this.disabled = false;
				}
			}else{
				this.disabled = false;
			}
		});
		
		// 如果是红包商品 - 只许积分支付
		var payTypeElement = null;
		$('.js-pay-type input[type="radio"]').each(function(i, item){
			if(is_hongbao){
				if(this.value == 2){
					this.checked = true;
					this.disabled = false;
				}else{
					this.disabled = true;
				}
			}else{
				this.disabled = false;
			}
			
			if(this.checked){
				payTypeElement = item;
			}
		});
		
		/*
		$(payTypeElement).trigger('change');
		
		// 会员折扣处理
		var discount = !is_hongbao;
		var $discount = $('#join_discount');
		$discount.css('display', discount ? 'block' : 'none');
		if(!discount){
			$discount.prop('checked', false );
		}
		*/
		$('#myTab a:eq(1)').tab('show');
	}
	// 初始化商品分组
	,initGoodsTag: function(){
		var options = {
				tags: true,
				placeholder: "非必填项",
				allowClear: true,
				multiple: true
			};
		
		var $select = $('#goods_tag');
		$select.on('select2:selecting', function(ev, aaaa){
			var data = ev.params.args.data;
			if(data.element){
				return true;
			}
			
			// 添加分组
			$.ajax({
				url: '/admin/tag/add',
				type: 'post',
				data: {name: data.text},
				dataType: 'json',
				success: function(tag){
					$select.find('[value="'+data.id+'"]').attr('value', tag.id).removeAttr('data-select2-tag');
				},
				error: function(){
					$select.find('[value="'+data.id+'"]').remove();
				}
			});
		}).select2(options);
	
		// 获取分组
		$.ajax({
			url: '/admin/api/tag',
			dataType: 'json',
			success: function(list){
				var html = '';
				for(var i=0; i<list.length; i++){
					html += '<option value="'+list[i].id+'">'+list[i].name+'</option>';
				}
				$select.html(html);
				
				if(EditGoods.goods.tag_id != ''){
					var tag_id = EditGoods.goods.tag_id.split(',');
					$select.val(tag_id).trigger("change");
				}
			}
		});
	}
	// 初始化form
	,initForm: function(){
		var t = this;
		zh_validator();
		var $form = $('#goods_edit_form');
		
		$form.find('.js-submit').on('click', function(){
			if(!$form.valid()){
				alertMsg('校验失败，请检查您填写的数据');
				return false;
			}
			
			// 合并数据
			var form_data = $form.serializeArray();
			var post_data = {};
			for(var i=0; i<form_data.length; i++){
				if(form_data[i].name == 'images[]'){
					if(!post_data['images']){
						post_data['images'] = [];
					}
					
					post_data['images'].push(form_data[i].value);
				}else if(form_data[i].name == 'remote_area[]'){
					if(!post_data['remote_area']){
						post_data['remote_area'] = form_data[i].value;
					}else{
						post_data['remote_area'] += ','+form_data[i].value;
					}
				}else{
					post_data[form_data[i].name] = form_data[i].value;
				}
			}
			
			if(!post_data.images){
				alertMsg('请至少上传一张商品图');
				return false;
			}
			
			post_data.is_display = $(this).data('display');
			post_data.sku_json = t.getSkuList();
			
			for(var i=0; i<t.data.products.length; i++){
				post_data['products['+i+'][sku_json]'] = t.data.products[i];
			}
			
			post_data.tag_id = '';
			var tags = $('#goods_tag').val();
			if(!!tags){
				if(tags.length > 3){
					return alert('商品分组最多支持3个'), false;
				}
				post_data.tag_id = tags ? tags.join(',') : '';
			}
			
			var $buttons = $(this).parent().children();
			$buttons.attr('disabled', 'disabled');
			$.ajax({
				url: $form.attr('url'),
				type: 'post',
				dataType: 'json',
				data: post_data,
				success: function(){
					win.back();
				},
				error: function(){
					$buttons.removeAttr('disabled');
				}
			});
			
			return false;
		});
		
		$form.validate({
	        errorClass: 'help-inline',
	        errorElement: "span",
	        ignore: ".ignore",
	        highlight: function (element, errorClass, validClass) {
	        	var $element = $(element);
	        	$element.parents('.control-group:first').addClass('error');
	        },
	        unhighlight: function (element, errorClass, validClass) {
	        	var $element = $(element);
	            if ($element.attr('aria-invalid') != undefined) {
	            	$element.parents('.control-group:first').removeClass('error');
	            }
	        },
	        errorPlacement: function($error, $element){
	        	var $parent = $element.parent();
	        	if($parent.hasClass('input-append')){
	        		$parent.after($error);
	        	}else{
	        		$error.insertAfter($element);
	        	}
	        },
	        submitHandler: function () {
	    		return false;
	        }
	    });
	}
	,initSoldTime: function(){
		var t = this;
		if(typeof $.fn.datetimepicker == 'undefined'){
			win.getStyle('/css/bootstrap-datetimepicker.min.css');
			win.getScript('/js/bootstrap-datetimepicker.min.js', function(){
				t.initSoldTime();
			});
			return;
		}
		
		$.fn.datetimepicker.dates['zh-CN'] = {
				days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
				daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
				daysMin:  ["日", "一", "二", "三", "四", "五", "六", "日"],
				months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
				monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
				today: "今天",
				suffix: [],
				meridiem: ["上午", "下午"]
		};
		
		var $sold_time = $('#sold_time'),
			$selectedTime = $sold_time.prev().find('input');
		$sold_time.datetimepicker({
			format : "yyyy-MM-dd hh:mm:ss",
			pickDate: true,
			pickTime: true,
			startDate: new Date(),
		    language: 'zh-CN',
		    pickPosition: 'top-right'
		}).on('changeDate', function(a, b){
			var sold_time = $sold_time.find('input').val();
			$selectedTime.val(sold_time).prop('checked', true);
		});
		
		$sold_time.parent().find('input[type="radio"]').on('change', function(){
			if(this.value == 0 || this.value == "0"){
				$sold_time.addClass('hide');
			}else{
				$sold_time.removeClass('hide');
			}
		});
	}
	,getSkuList: function(){
		if(this.goods.tao_id){
			return this.goods.sku_json || [];
		}
		
		var sku_list  = [];
		$('#goods_sku_content>.sku-sub-group').each(function(){
			var $this = $(this),
			    $select = $this.find('.sku-group-title>select'),
			    $atoms = $this.find('.sku-atom-list .sku-atom>span'),
	            sku_item = {};
			
			if($atoms.length > 0){
				sku_item.id = $select.val();
				sku_item.text = $select.find(':selected').text();
				sku_item.items = [];
				
				$atoms.each(function(i){
					var img = $atoms.eq(i).siblings('.upload-img-wrap').find('.js-img-preview').attr('src');
					sku_item.items.push({id: this.getAttribute('data-atom-id'), text: this.innerText, img: img});
				});

				sku_list.push(sku_item);
			}
		});
		return sku_list;
	}
	,resetSKUStore: function(sku_list){
		var sku_list = this.getSkuList(), th = '', t = this;

		t.data.sku_json = sku_list;
		t.data.products = [];
		
		// 计算跨行跨咧
		for(var i=0; i<sku_list.length; i++){
			th += '<th style="width:auto">'+sku_list[i].text+'</th>';
			var count = 1;
			for(var j = i+1; j < sku_list.length; j++){
				count = count * sku_list[j].items.length;
			}
			
			sku_list[i].count = count;
		}
		
		$('#goods_sku_content').data(sku_list);
		
		// 开始生成html
		var html = '<thead><tr>'+th;

		for(var level in t.agentList){
			html += '	<th class="must js-agent'+level+'_price">'+t.agentList[level].title+'</th>' ;
		}
		   
	    html += '<th class="js-original-price">原价</th><th class="must js-cost">成本</th><th class="must">库存</th>' + 
		       '	<th class="must" style="width:55px">重量(kg)</th>' +
			   '	<th class="th-code">商家编码</th>' +
			   '</tr></thead>';
		if(sku_list.length > 0){
			html += '<tbody>' + t.getTh(sku_list, 0 , []) + '</tbody>';
			
			// 禁止手动修改总库存
			$('#total_stock, .js_price_list input, #outer_id').attr('readonly', 'readonly');
			$('#product_list').show();
		}else{
			$('#total_stock, .js_price_list input, #outer_id').removeAttr('readonly');
			$('#product_list').hide();
		}

		var $table = $('#stock-region table');
		$table.find('thead, tbody').remove();
		$table.prepend(html);
		$('.js-pay-type :checked').trigger('change');
		
		if(sku_list.length > 0){
			t.resetPriceAndStock();
		}
		
		$table.find('th[data-toggle="tooltip"]').tooltip({container: 'body'});
	}
	,getSKUJSON: function(){
		var sku_list  = [];
		$('#goods_sku_content>.sku-sub-group').each(function(){
			var $this = $(this),
			    $select = $this.find('.sku-group-title>select'),
			    $atoms = $this.find('.sku-atom-list .sku-atom>span'),
	            sku_item = {};
			
			if($atoms.length > 0){
				sku_item.id = $select.val();
				sku_item.text = $select.find(':selected').text();
				sku_item.items = [];
				
				th += '<th>'+sku_item.text+'</th>';
				
				$atoms.each(function(){
					sku_item.items.push({id: this.getAttribute('data-atom-id'), text: this.innerText});
				});

				sku_list.push(sku_item);
			}
		});
		
		return sku_list;
	}
	,getTh: function(sku_list, index, current){
		var t=this,result = '';
		if(index == 0){
			result += '<tr>';
		}
		
		var items = sku_list[index].items;
		
		for(var j=0; j<items.length; j++){
			var product = $.extend([], current);
			product.push({kid: sku_list[index].id, vid: items[j].id, k: sku_list[index].text, v: items[j].text});
			
			var td = '';
			if(j > 0)
				td += '<tr>';
			
			
			td = '<td rowspan="'+sku_list[index].count+'">'+items[j].text+'</td>';
			if(index == sku_list.length - 1){
				td += t.getTd(product);
			}
			result += td;

			if(index + 1 < sku_list.length){
				var _temp = t.getTh(sku_list, index+1, product);
				result += _temp;
			}
		}

		return result;
	}
	,getTd: function(sku){
		var t = this, index = this.data.products.length;
		
		var product = {
				id: '',
				stock: '',
				price: '',
				score: '',
				outer_id: '',
				weight:'',
				sold_num: 0,
				original_price: '',
				agent0_price: '',
				agent1_price: '',
				agent2_price: '',
				agent3_price: '',
				agent4_price: '',
				cost: ''
		};
		
		var pipei_max = 0;
		$.each(EditGoods.goods.products, function(i, product2){
			var pipei_num = 0;
			var sku2 = product2.sku_json;
			for(var j=0; j<sku.length; j++){
				for(var h=0; h<sku2.length; h++){
					if(sku[j].vid == sku2[h].vid){
						pipei_num++;
					}
				}
			}
			
			if(pipei_num == sku.length){
				product = product2;
				return false;
			}
		});
		
		// 判断规格是否一致，否则删除产品id
		
		t.data.products.push(sku);
		
		var html ='';
		for(var i in t.agentList){
			html += '<td class="input-td">';
			html += '  	<input type="text" name="products['+index+']['+t.agentList[i].price_field+']" value="'+product[t.agentList[i].price_field]+'" class="input-mini js-price js-agent'+t.agentList[i].level+'_price" maxlength="10">';
			html += '</td>';
		}
		html += '<td class="input-td js-original-price">';
		html += '  	<input type="hidden" name="products['+index+'][id]" value="'+product.id+'">';
		html += '  	<input type="text" name="products['+index+'][original_price]" value="'+product.original_price+'" class="input-mini js-original_price" maxlength="10">';
		html += '</td>';
		html += '<td class="input-td">';
		html += '	<input type="text" name="products['+index+'][cost]" value="'+product.cost+'" class="js-cost input-mini" maxlength="10" required="required">';
		html += '</td>';
		html += '<td class="input-td">';
		html += '	<input type="text" name="products['+index+'][stock]" value="'+product.stock+'" class="js-stock-num input-mini" maxlength="10" required="required">';
		html += '</td>';
		html += '<td class="input-td">';
		html += '	<input type="text" name="products['+index+'][weight]" value="'+product.weight+'" class="js-weight input-small" maxlength="10" required="required">';
		html += '</td>';
		html += '<td class="input-td">';
		html += '	<input type="text" name="products['+index+'][outer_id]" value="'+product.outer_id+'" class="js-code input-small" maxlength="20">';
		html += '</td>';
		html += '</tr>';
		
		return html;
	}
	,resetPriceAndStock: function(){
		var min = {value: 0, index: 0}, val = 0, totalStock = 0;
		var $tbody = $('#stock-region>table>tbody'),
			 $selector = $tbody.find('.js-agent0_price');
		if($selector.length == 0){
			return;
		}
		
		$selector.each(function(i, ele){
			if(this.value == ''){
				return true;
			}
			
			val = parseFloat(this.value);
			if(i > 0 && val > min.value){
				return true;
			}
			
			if(i == 0 || val < min.value){
				min.value = val;
				min.index = i;
				return true;
			}
			
			var $nextAll = $selector.eq(i).parent().nextAll().find('.js-price,.js-original_price');
			if($nextAll.length == 0){
				return true;
			}
			var $prevAll = $selector.eq(min.index).parent().nextAll().find('.js-price,.js-original_price');
			
			for(var j=0; j<$nextAll.length; j++){
				var prev = $prevAll.eq(j).val();
				if(prev == ''){
					return true;
				}
				prev = parseFloat(prev);
				
				var next = $nextAll.eq(j).val();
				if(next == ''){
					min.index = i;
					return true;
				}
				next = parseFloat(next);
				
				if(next < prev){
					min.index = i;
					return true;
				}else if(prev < next){
					return true;
				}
			}
		});
		
		var $values = $selector.eq(min.index).parents('tr:first').find('input[type]');
		var $list = $('.js_price_list');
		var filed = '';
		$values.each(function(i){
			if($values.eq(i).hasClass('js-agent0_price')){
				filed = 'js-agent0_price';
			}else if($values.eq(i).hasClass('js-agent1_price')){
				filed = 'js-agent1_price';
			}else if($values.eq(i).hasClass('js-agent2_price')){
				filed = 'js-agent2_price';
			}else if($values.eq(i).hasClass('js-agent3_price')){
				filed = 'js-agent3_price';
			}else if($values.eq(i).hasClass('js-original_price')){
				filed = 'js-original_price';
			}else if($values.eq(i).hasClass('js-stock-num')){
				filed = 'js-stock-num';
			}else if($values.eq(i).hasClass('js-weight')){
				filed = 'js-weight';
			}else if($values.eq(i).hasClass('js-cost')){
				filed = 'js-cost';
			}else if($values.eq(i).hasClass('js-code')){
				$('#outer_id').val($values.eq(i).val());
				return true;
			}else{
				return true;
			}
			
			$list.find('.'+filed).val($values.eq(i).val());
		});
		
		$tbody.find('.js-stock-num').each(function(i){
			if(this.value != ''){
				totalStock += parseInt(this.value);
			}
		});
		$('#total_stock').val(totalStock);
	}
	// 获取最小数值
	,getMinMax: function(seletor){
		var min = 0, val = 0, max = 0, sum = 0, $min = null, $max = null;
		var $selector = $(seletor);
		$selector.each(function(i, ele){
			if(this.value != '' && !isNaN(this.value)){
				val = parseFloat(this.value);
				if(i== 0 || val < min){
					min = val;
					$min = $selector.eq(i); 
				}
				
				if(i== 0 || val > max){
					max = val;
					$max = $selector.eq(i); 
				}
				
				sum += val;
			}
		});
		
		return {min: min, max: max, sum: sum, $min: $min, $max: $max};
	}
	,editor: null
	,initImg: function(){
		var t = this, editor = UE.getEditor('image_text_container');
		t.editor = editor;

		var afterhidepop = function(){
			editor.removeListener('afterhidepop', afterhidepop);
			setTimeout(function(){
				editor.removeListener('beforeInsertImage', beforeInsertImage);
			}, 600);
		}
		var beforeInsertImage = function(t, list){
			var html = '';
	    	for(var i=0; i<list.length; i++){
	    		html += '<li><a href="'+list[i]['src']+'" target="_blank"><img src="'+list[i]['src']+'"></a><a class="js-delete-picture close-modal small hide">×</a><input type="hidden" name="images[]" value="'+list[i]['src']+'"></li>';
	    	}
	    	$('.js-picture-list .js-add-picture').parent().before(html);
	    	
	    	afterhidepop();
	    	
	    	return true;
		}
		
		// 弹出图片上传框
		$('.js-picture-list .js-add-picture').on('click', function(){
			editor.addListener('afterhidepop', afterhidepop);
			editor.addListener('beforeInsertImage', beforeInsertImage);
			editor.getDialog("insertimage").open();
		});

		$('.js-picture-list').on('click', '.js-delete-picture', function(){
			$(this).parent().remove();
		});
	}
	// 添加规格
	,addGoodsSku: function(defaultData){
		var html = '';
		html += '<div class="sku-sub-group">';
		html += '	<h3 class="sku-group-title">';
		html += '		<select style="width:150px;" class="js-sku-list">'+$('#goods_sku_list').html()+'</select>';
		html += '		<a class="js-remove-sku-group remove-sku-group">&times</a>';
		html += '	</h3>';
		html += '	<div class="js-sku-atom-container sku-group-cont">';
		html += '		<div class="js-sku-atom-list sku-atom-list"></div>';
		html += '		<a href="javascript:;" class="js-add-sku-atom add-sku">+添加</a>';
		html += '	</div>';
		html += '</div>';
		
		var t = this, $html = $(html),
		showImg = (defaultData && defaultData.text.indexOf("颜色") == defaultData.text.length - 2);
		$html.insertBefore('#add_goods_sku');
		var $selectSKU = $html.find('.sku-group-title>select');
		if(defaultData){
			$selectSKU.val(defaultData.id);
		}
		var $atomlist = $html.find('.js-sku-atom-container>.js-sku-atom-list');
		$selectSKU.select2({tags: true, placeholder: "请选择"});
		
		$selectSKU.on('select2:close', function(){
			if(this.value == ''){
				$html.remove();
			}
		}).on('select2:selecting', function(ev){
			var data = ev.params.args.data;
			
			var id = ev.params.args.data.id;
			if($selectSKU.val() == id){
				return true;
			}
			
			// 判断是否重复选择
			var exists = false;
			$('#goods_sku_content .js-sku-list').each(function(){
				if(this.value == id){
					exists = true;
					return false;
				}
			});
			
			if(exists){
				alertMsg('请勿重复选择规格！');
				return false;
			}
			
			showImg = data.text.indexOf("颜色") == data.text.length - 2;
			// 添加sku
			if(!ev.params.args.data.element){
				$.ajax({
					url: '/admin/api/addsku',
					type: 'post',
					dataType: 'json',
					async: false,
					data: {text: data.text},
					waitting: '正在添加规格',
					success: function(newData){
						$selectSKU.find('[value="'+data.id+'"]').attr('value', newData.id).removeAttr('data-select2-tag');
						$selectSKU.val([newData.id]).trigger("change");
						$selectSKU.select2('close');
					},
					error: function(){
						$selectSKU.find('[value="'+data.id+'"]').remove();
					}
				});
			}
		});
		
		// 移除
		$html.find('.sku-group-title>.js-remove-sku-group').on('click', function(){
			$html.remove();
			t.resetSKUStore();
		});
		
		var $js_sku_atom_container = $html.find('.js-sku-atom-container').on('click', '.sku-atom>.js-remove-sku-atom', function(){
			var $this = $(this);
			$this.parent().remove();
			t.resetSKUStore();
		});
		
		var sku_options = '';
		
		// 获取规格值
		$selectSKU.on('change', function(){
			t.getSkuChildren(this.value, function(options){
				sku_options = options;
			});
			
			// 移除已选择的项
			$html.find('.js-sku-atom-container>.js-sku-atom-list').empty();
			$html.find('.js-add-sku-atom').popover('hide');
			t.resetSKUStore();
		});
		
		$html.find('.js-add-sku-atom').popover({
			html: true,
			placement: 'bottom',
			trigger: 'manual',
			content: '<select style="width:230px;" multiple="multiple"></select> <input type="button" class="btn btn-primary btn-ok" value="确定"> <input type="button" class="btn btn-cancel" value="取消">'
		}).on('click', function(){
			var $this = $(this);
			$this.popover('show');
			var $tip = $this.data('popover').$tip;
			var $select_sku_val = $tip.find('select');
			var $btn_ok = $tip.find('.popover-content>.btn-ok');
			$select_sku_val.html(sku_options).on('select2:selecting', function(ev){
				var temp_data = ev.params.args.data;
				if(temp_data.element){
					return true;
				}
				
				$btn_ok.attr('disabled', 'disabled');
				var $this = $(this);
				// ajax添加
				$.ajax({
					url: '/admin/api/addsku',
					type: 'post',
					dataType: 'json',
					data: {pid: $selectSKU.val(), text: temp_data.text},
					success: function(newData){
						sku_options += '<option value="'+newData.id+'">'+newData.text+'</option>';
						var $option = $select_sku_val.find('[value="'+newData.text+'"]');
						$option.attr('value', newData.id).removeAttr('data-select2-tag');
						var data = $option.data('data');
						data.id = newData.id;
						$option.data('data', data);
					},
					error: function(){
						return false;
					},
					complete: function(){
						$btn_ok.removeAttr('disabled');
					}
				});
			}).select2({
				tags: true,
				placeholder: "请选择"
			});
			
			// 关闭弹窗
			$tip.find('.popover-content>.btn').on('click', function(){
				if(this.classList.contains('btn-primary')){ // 确定
					var data = $select_sku_val.select2('data');
					var html = '';
					var sku_id = $selectSKU.val();
					var addImg = $('#js-addImg-function').prop('checked') ? true : false;
					for(var i in data){
						if(isNaN(data[i]['id'])){
							continue;
						}
						if($atomlist.find('span[data-atom-id="'+data[i]['id']+'"]').length == 0){
							html += t.getAtom(data[i], showImg);
						}
					}
					if(html != ''){
						$atomlist.append(html);
						t.resetSKUStore();
					}
				}
				$this.popover('hide');
			});
		});
		
		// 初始化数据
		if(defaultData){
			if(defaultData.items){
				var html = '';
				for(var i=0;i<defaultData.items.length;i++){
					html += t.getAtom(defaultData.items[i], showImg);
				}
				$atomlist.append(html);
			}
		}
		
		var _sku_id = $selectSKU.val();
		if(!_sku_id){
			$selectSKU.select2("open");
		}else{
			t.getSkuChildren(_sku_id, function(options){
				sku_options = options;
			});
		}
		
		// 插入图片
		var btnAddImg = null;
		var afterhidepop = function(){
			t.editor.removeListener('afterhidepop', afterhidepop);
			setTimeout(function(){
				t.editor.removeListener('beforeInsertImage', beforeInsertImage);
			}, 600);
		}
		var beforeInsertImage = function(t, list){
			btnAddImg.innerHTML = '<img src="'+list[0]['src']+'" class="js-img-preview">';
	    	afterhidepop();
	    	return true;
		}
		
		// 弹出图片上传框
		$html.find('.js-sku-atom-list').on('click', '.js-btn-add', function(){
			t.editor.addListener('afterhidepop', afterhidepop);
			t.editor.addListener('beforeInsertImage', beforeInsertImage);
			t.editor.getDialog("insertimage").open();
			
			btnAddImg = this;
			return false;
		});
	}
	,getAtom: function(data, addImg){
		var html = '';
		html += '<div class="sku-atom'+(addImg?' active':'')+'">';
		html += '	<span data-atom-id="'+data.id+'">'+data.text+'</span>';
		html += '	<div class="close-modal small js-remove-sku-atom">×</div>';
		html += '	<div class="upload-img-wrap">';
		html += '		<div class="arrow"></div>';
		html += '		<div class="js-upload-container" style="position:relative;">';
		html += '			<div class="add-image js-btn-add">' + (data.img ? '<img src="'+data.img+'" class="js-img-preview">' : '+') + '</div>';
		html += '		</div>';
		html += '	</div>';
		html += '</div>';
		return html;
	}
	// 获取sku
	,getSkuChildren: function(id, callback){
		$.ajax({
			url: '/admin/api/skutree?id=' + id,
			dataType: 'json',
			waitting: '正在获取数据',
			success: function(data){
				var options = '';
				for(var i in data){
					options += '<option value="'+i+'">'+data[i]+'</option>';
				}
				
				callback(options);
			}
		});
	},
	initAttr: function(){
		var $table = $('.attr-table tbody');
		var $addAttr = $('.js-add-attr');
		$addAttr.on('click', function(){
			var i = $addAttr.data('total') + 1;
			$addAttr.data('total',i);
			$table.append('<tr><th><input type="text" name="parameters['+i+'][key]" maxlength="8"></th><td><a class="delete-attr label label-warning">删除</a><input type="text" name="parameters['+i+'][value]" maxlength="128"></td></tr>');
			return false;
		});
		
		$table.on('click', '.delete-attr', function(){
			$(this).parent().parent().remove();
			return false;
		})
	},
	initSendPlace: function(){
		var $sendPlace = $('#send_place');
		var list = Address.list[1];

		var html = '<div class="send-place"><ul>';
		var index = 1;
		for(var code in list){
			html += '<li data-code="'+code+'"><a>'+list[code].sname+'<i></i></a></li>';
			if(index % 6 == 0){
				html += '<li class="send-city"></li>';
			}
			index++;
		}
		html += '</ul></div>';
		$sendPlace.append(html);
		
		var $container = $sendPlace.children('.send-place');
		var $name = $sendPlace.children('.js-send-place-name');
		
		$sendPlace.on('click', 'li[data-code]', function(){
			var $this = $(this);
			$this.addClass('active');
			$this.siblings().removeClass('active');
			
			if($this.parent().parent().hasClass('send-city')){
				$container.hide();
				var $actives = $container.find('.active');
				var text = $actives.eq(0).text() + ' ' + $actives.eq(1).text();
				$name.html(text);
				$name.siblings('input').val($actives.eq(1).data('code'));
			}else{
				var list = Address.list[$this.data('code')];
				var html = '<ul>';
				for(var code in list){
					html += '<li data-code="'+code+'"><a>'+list[code].sname+ '</a></li>';
				}
				html += '</ul>';
				$this.siblings('.send-city').hide();
				$this.nextAll('.send-city:first').html(html).show();
			}
			
			return false;
		}).on('click', function(){
			$container.css('display', $container.is(':hidden') ? 'block' : 'none');
			return false;
		});
		
		// 默认值
		if(this.goods.send_place > 0){
			var city = Address.find(this.goods.send_place);
			var $li = $sendPlace.find('li[data-code="'+city.pcode+'"]');
			$li.trigger('click');
			$li.nextAll('.send-city:first').find('li[data-code="'+this.goods.send_place+'"]').trigger('click');
		}
	},
	setFreightTemplates: function(list){
		var $template = $('#freight_tid');
		$template.popover({
			title: '运费详情',
			html: true,
			placement: 'top',
			content: '',
			trigger: 'hover'
		});
		
		$template.hover(function(){
			var template = null,
			    currentId = $template.val();;
			for(var i=0; i<list.length; i++){
				if(list[i].id == currentId){
					template = list[i];
					break;
				}
			}
			
			var $tip = $template.data('popover').$tip;
			$tip.find('.popover-title').html(template.name);
			$tip.find('.popover-content').html(template.describe);
		});
	}
}