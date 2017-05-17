var $btnPay = $addressTip = null;
require(['jquery', 'order/address'], function($, address){
	// 填写收货地址 - 开始
	var $edit_address = $('.js-edit-address');
	$edit_address.on('click', function(){
		address.edit({
			id: '',
			user_name: _data.address.receiver_name,
			mobile: _data.address.receiver_mobile,
			province_name: _data.address.receiver_province,
			city_name: _data.address.receiver_city,
			county_name: _data.address.receiver_county,
			detail: _data.address.receiver_detail,
			zip_code: _data.address.receiver_zip
		});
		return false;
	});
	
	address.onSelect = function(data){
		if($edit_address.hasClass('empty-address')){
			$edit_address.removeClass('empty-address');
		}
		
		var html = '';
		html += '<li class="clearfix">';
		html += '	<span class="name"> 收货人： '+data.receiver_name+'</span>';
		html += '	<span class="tel">'+data.receiver_mobile+'</span>';
		html += '</li>';
		html += '<li class="address-detail">收货地址：'+data.receiver_province+data.receiver_city+data.receiver_county+data.receiver_detail+' </li>';
	
		$edit_address.find('.express-detail').html(html);

		_data.address = $.extend(true, {}, data);
		
		// 更新数据
		getFreightFee();
	}
	
	$btnPay = $('#confirm-pay-way-opts .btn-pay');
	$btnPay.on('click', function(){
		return addOrder();
	});
	
	// 配送方式改变
	$('.js-select-express select').on('change', function(){
		expressChanged($(this));
		resetData();
		return false;
	});
	
	resetPromotions(true);
	resetData();
	
	$addressTip = $('.js-logistics-tips');
});

// 配送方式改变
function expressChanged($select, express){
	var seller = $select.parent().attr('id').split('-')
	   ,sellerId = seller[1]
	   ,freightTid = seller[2]
	   ,$rightStr = $select.siblings('.pull-right')
	   ,$container = $select.parent();
	
	if(!express){
		var $selected = $select.find(':selected');
		express = {id: $selected.val(), name: $selected.text(), money: $selected.data('money')}
	}
	
	if(express.has_error){
		$container.addClass('error');
		$rightStr.html(express.error_msg);
	}else{
		$container.removeClass('error');
		var html = '<span class="express_name">'+express.name+'</span> ';
		if(express.money*1 == 0){
			html += '免邮';
		}else{
			html += '¥' + express.money.bcFixed(2);
		}
		$rightStr.html(html);
	}

	var trade = _data.groups[sellerId]['trades'][freightTid];
	trade.express_id = express.id;
	trade.express_name = express.name;
	trade.post_fee = express.money;
	return false;
}

// 下单成功后续操作
function orderSuccessed(wxpay){
	window.onbeforeunload = null;
	$('#confirm-pay-way-opts').unbind('click').find('button').html('微信支付成功');
	
	//支付成功后发送提醒消息
	$.ajax({
		url: '/h5/pay/order_notify',
		data: {order_no: wxpay.order_no, trades: wxpay.trades.join(',')},
		type: 'post',
		complete: function(){
			successBack(wxpay);
		}
	});
}

// 下单成功跳转提示
function successBack(wxpay){
	if(confirm('下单成功！')){
		if(wxpay.trades.length == 1){
			window.location.href = "/h5/order/detail?tid=" + wxpay.trades[0];
		}else{
			window.location.href = "/h5/order";
		}
	}else{
		location.href = document.referrer;
	}
}

// 获取运费
function getFreightFee(sellerId, freightTid){
	if(!_data.address.receiver_name){
		return false;
	}
	
	var postData = {
			address: {
				receiver_name: _data.address.receiver_name,
				receiver_mobile: _data.address.receiver_mobile,
				receiver_province: _data.address.receiver_province,
				receiver_city: _data.address.receiver_city,
				receiver_county: _data.address.receiver_county,
				receiver_detail: _data.address.receiver_detail,
				province_code: _data.address.province_code,
				city_code: _data.address.city_code,
				county_code: _data.address.county_code,
				receiver_zip: _data.address.receiver_zip,
			},
			products: {}
		};
	
	if(sellerId && freightTid){
		var trade = _data.groups[sellerId].trades[freightTid];
		for(var i=0; i<trade.orders.length; i++){
			postData.products[trade.orders[i].product_id] = {price: trade.orders[i].discount_price, num: trade.orders[i].num, postage: trade.orders[i].postage};
		}
	}else{
		for(var seller_id in _data.groups){
			for(var freight_tid in _data.groups[seller_id].trades){
				var trade = _data.groups[seller_id].trades[freight_tid];
				for(var i=0; i<trade.orders.length; i++){
					postData.products[trade.orders[i].product_id] = {price: trade.orders[i].discount_price, num: trade.orders[i].num, postage: trade.orders[i].postage, freight_tid: trade.orders[i].freight_tid, attach_postage: trade.orders[i].attach_postage};
				}
			}
		}
	}
	
	$.ajax({
		url: '/h5/pay/freightFee',
		type: 'post',
		dataType: 'json',
		data: postData,
		success: function(result){
			updateFreightFee(result);
		}
	});
	
	return true;
}

// 投放运费显示
function updateFreightFee(result){
	var has_error = false;
	for(var sellerId in result){
		for(var freightTid in result[sellerId]){
			var list = result[sellerId][freightTid]
			   ,$container = $('#express-'+sellerId+'-'+freightTid)
			   ,$select = $container.children('select')
			   ,groups = {}
			   ,html = ''
			   ,$selected;
		
			if(list[0].has_error){
				express = list[0];
				html += '<option value="" data-money="">'+ express.error_msg +'</option>';
			}else{
				// 按照钱进行分割(美观)
				for(var i=0; i<list.length; i++){
					if(!groups[list[i].money]){
						groups[list[i].money] = [];
					}
					groups[list[i].money].push(list[i]);
				}
				
				var express = null;
				for(var money in groups){
					html += '<optgroup label="¥'+money+'">';
					var list = groups[money];
					
					if(express == null){
						express = list[0];
					}
					for(var i=0; i<list.length; i++){
						if(list[i].checked){
							express = list[i];
						}
						
						html += '<option value="'+list[i].id+'"'+(list[i].checked ? 'selected="selected"' : '')+' data-money="'+list[i].money+'">'+ list[i].name +'</option>';
					}
					html += '</optgroup>';
				}
			}
			
			$select.html(html);
			expressChanged($select, express);
			
			if(express.has_error){
				has_error = true;
			}
		}
	}
	
	if(has_error){
		$addressTip.removeClass('hide');
		$btnPay.attr('disabled', 'disabled');
	}else{
		$addressTip.addClass('hide');
		$btnPay.removeAttr('disabled');
	}
	
	resetData();
}

// 填写个人质料
function editMyInfo(){
	_data.buyer.nickname = '';
	toast.show('必须完善基础信息后方可下单！');
	
	require(['view/personal/edit'], function(view){
		view.show(_data.buyer, function(data){
			_data.buyer = $.extend(_data.buyer, data);
			$.ajax({
				url: '/h5/personal/save',
				data: {data:data},
				type: 'post',
				datatype: 'text',
				error: function(){
					_data.buyer.mobile = '';
					view.show(_data.buyer);
				}
			});
		})
	});
}

// 执行下单
function addOrder(){
	if(_data.address.receiver_name == '' || _data.address.receiver_mobile == ''){
		return toast.show('请填写收货地址'), false;
	}
	
	// 代理必须完善手机号方可下单
	if(_data.buyer.mobile == '' && _data.buyer.agent_level > 0){
		return editMyInfo(), false;
	}
	
	var postData = {
		address: _data.address,
		from: _data.from,
		groups: {}
	};
	
	for(var sellerId in _data.groups){
		postData.groups[sellerId] = {};
		for(var freightTid in _data.groups[sellerId]['trades']){
			var trade = _data.groups[sellerId]['trades'][freightTid]
			   ,data = {
					express_id: trade.express_id,
					post_fee: trade.post_fee,
					payment: trade.payment,
					discount_fee: trade.discount_fee,
					paid_balance: trade.paid_balance,
					paid_no_balance: trade.paid_no_balance,
					remark: $('#remark-'+sellerId+'-'+freightTid).val(),
					products: {}};
			data.remark = $.trim(data.remark);
			for(var i=0; i<trade.orders.length; i++){
				data.products[trade.orders[i].product_id] = {
					goods_id: trade.orders[i].goods_id, 
					num: trade.orders[i].num, 
					postage: trade.orders[i].postage,
					discount_details: trade.orders[i].discount_details
				};
			}
			
			postData.groups[sellerId][freightTid] = data;
		}
	}
	
	$.ajax({
		url: '/h5/pay/order',
		type: 'post',
		dataType: 'json',
		data: postData,
		success: function(wxpay){
			$('.js-goods-list-container .js-discount').unbind('click');
			$('.js-select-express select').remove();
			$('.js-edit-address').unbind('click');
			$btnPay.unbind('click');
			
			if(wxpay.total_fee > 0){
				var payment = wxpay.total_fee.bcFixed(2);
				toast.show('您还需要支付：' + payment + '元');
				window.onbeforeunload = function(event) { 
					return '订单未支付，确定离开吗？';
				}
				
				// 唤起支付
				require(['pay'], function(pay){
					$btnPay.on('click', function(){
						pay.callpay(wxpay.parameters, function(res){
							if(res.errcode == 0){
								orderSuccessed(wxpay);
							}
						});
						return false;
					}).trigger('click');
				});
			}else{
				orderSuccessed(wxpay);
			}
			
		}
	});
	return false;
}

// 限时折扣(未选中不更改数据)
function discounts(promotionList, trade, useDefault){
	var sellerId = trade.seller_id
	   ,discountList = {};
	
	for(var i=0; i<trade.orders.length; i++){
		var order = trade.orders[i],active = null;
		
		// 找到商品参与的满减
		for(var j=0; j<promotionList.length; j++){
			if(!!promotionList[j].goods[order.goods_id]){
				active = promotionList[j];
				break;
			}
		}
		if(!active){
			continue;
		}
		
		var discountFee = active.goods[order.goods_id] < 0 ? -active.goods[order.goods_id] : (1).bcsub(active.goods[order.goods_id]*0.1).bcmul(order.price, 2),
			discountPrice = order.price.bcsub(discountFee);
        if(discountPrice <= 0){
        	continue;
        }
		discountFee *= order.num;
           
        var discount = {
        		id: active.id,
        		type: active.type,
        		title: active.title,
        		single: active.single,
        		discount: active.goods[order.goods_id], 
        		discount_fee: discountFee,
        		checked: useDefault || trade.discount_key.indexOf(active.id) > -1,
        		description: '折后立减'+discountFee+'元'};
           
        // 保存更改数据
        if(discount.checked){
            order.discount_price = order.total_fee.bcsub(discountFee).bcdiv(order.num, 2);
            order.total_fee		 = order.discount_price.bcmul(order.num);
            order.payment		 = order.total_fee;
            // 不累加优惠金额
            order.discount_details.push({id: discount.id, discount_fee: discountFee});
            
            // 只能优惠一次
            if(discount.single){
            	order.discount_single = true
        	}
        }
        
        if(!discountList[discount.id]){
        	discountList[discount.id] = discount;
        }else{
        	discountList[discount.id].discount_fee = discountList[discount.id].discount_fee.bcadd(discountFee);
        	discountList[discount.id].description = '折后立减'+discountList[discount.id].discount_fee+'元';
        }
    }
	
	for(var i in discountList){
		trade.discount_details.push(discountList[i]);
	}
}

// 处理满减
function manjian(jian, tradeGoods){
    // 数组排序
    var list = [], index = 0, result = {}, total = 0, totalDiscount = 0, discount = 0;
    for(var id in tradeGoods){
    	tradeGoods[id] *= 1;
    	total = total.bcadd(tradeGoods[id]);
    	if(list.length == 0 || tradeGoods[id] < list[list.length - 1].money){
    		list.push({id: id, money: tradeGoods[id]});
    		continue;
    	}else if(tradeGoods[id] > list[0].money){
        	list.splice(0, 0, {id: id, money: tradeGoods[id]});
        	continue;
    	}
    	
		for(var i=0; i<list.length; i++){
			index = i;
    		if(list[i].money > tradeGoods[id]){
    			if(i+1 < list.length && tradeGoods[id] > list[i+1].money){
    				index++;
        			break;
        		}
    		}
    	}
    	list.splice(index, 0, {id: id, money: tradeGoods[id]});
    }

    var prec =  jian.bcdiv(total, 6);
    for(var i=0; i<list.length; i++){
        if(i == list.length - 1){
            discount = jian.bcsub(totalDiscount, 2);
        }else{
        	discount = list[i].money.bcmul(prec, 2);
        }
        result[list[i].id] = discount;
        totalDiscount = totalDiscount.bcadd(discount, 2);
    }

    return result;
}

// 满减活动
function promotions(promotionList, trade, useDefault){
	var sellerId = trade.seller_id
	   ,activeProducts = [];
	
	for(var j=0; j<promotionList.length; j++){
		var active = promotionList[j]
           ,selectedList = active.goods
		   ,totalFee = 0
           ,tradeGoods = {}
		   ,field = active.single ? 'total_fee' : 'payment'
		   ,canCheck = true;
		
        for(var i=0; i<trade['orders'].length; i++){
        	var order = trade['orders'][i];
        	// 商品只能优惠一次	或者不是满减商品    或者已参加满减
        	if(order.payment <=0 || order.discount_single || selectedList.indexOf(order['goods_id']) == -1 || activeProducts.indexOf(order.product_id) > -1){
        		continue;
            }
        	
        	// 如果此满减不能和其他优惠一起使用
        	if(active.single && order.discount_fee > 0){
        		canCheck = false;
        	}
        	
    		totalFee = totalFee.bcadd(order[field]);
            tradeGoods[order.product_id] = order[field];
        }

        // 判断是否已达满减要求
        if(totalFee < active['meet']){continue}

        var discount = {
	    		id: active.id,
	    		title: active.title,
	    		single: active.single,
	    		type: active.type,
	    		meet: active.meet};
        
        for(var meet in active.value){
            if(totalFee >= meet){
                discount['meet'] = meet;
            }
        }
		
        var detail = active.value[discount.meet];
        discount['postage']  = detail['postage'];
        discount['checked'] = canCheck && (useDefault || trade.discount_key.indexOf(discount.id) > -1);
        discount['discount_fee'] = detail['money'];
        discount['description'] = '满'+discount['meet']+'元减'+discount['discount_fee']+'元';

        if(discount['postage'] == 1){
        	var baoyou = Object.keys(tradeGoods).length == trade['orders'].length ? '包邮' : '部分免邮';
            if(discount['description'] == ''){
                discount['description'] = '满'+discount['meet']+'元'+baoyou;
            }else{
                discount['description'] += '('+baoyou+')';
            }
        }
        
        trade['discount_details'].push(discount);
        
        if(!discount.checked){continue}
        
     	// 计算满减差价
        if(discount['discount_fee'] > 0){
        	tradeGoods = manjian(discount['discount_fee'], tradeGoods);
        }

        for(var i=0; i<trade['orders'].length; i++){
        	var order = trade['orders'][i];
        	if(order.discount_single || selectedList.indexOf(order['goods_id']) == -1 || activeProducts.indexOf(order.product_id) > -1){
        		continue;
            }
        	activeProducts.push(order['product_id']); // 标记此商品已参加满减
        	
        	// 此商品本次参与的优惠信息
        	var discountInfo = {id: discount.id, discount_fee: 0, postage: 0};
        	
        	// 标记包邮
            if(discount.postage == 1){
            	order.postage = 1;
            	discountInfo.postage = 1;
            }
        	
        	// 增加折扣
        	if(discount.discount_fee > 0){
        		order.discount_fee = order.discount_fee.bcadd(tradeGoods[order.product_id]);
        		order.payment = order.total_fee.bcsub(order.discount_fee, 2);
        		discountInfo.discount_fee = tradeGoods[order.product_id];
        	}
        	order.discount_details.push(discountInfo);
        	
        	// 商品只能参加一种优惠
        	if(discount.single){
        		order.discount_single = true
    		}
        }
	}
}

// 一种商品只能使用一张优惠券
var tradeCouponList = {};
function coupons(promotionList, trade, useDefault){
	var sellerId = trade.seller_id,
	    maxOne = {id: null, vid: null, diff: 999999999, prev: []},
	    discountList = [],
	    orders = trade.orders,
	    activeProducts = [];
	
	for(var j=0; j<promotionList.length; j++){
		var active = promotionList[j]
           ,selectedList = active.goods
           ,totalFee = 0
           ,tradeGoods = {};

        var canCheck = true, field = active.single ? 'total_fee' : 'payment';
		for(var i=0; i<orders.length; i++){
			var order = orders[i];
			// 只能参加一种优惠    或者不是此优惠券的商品    或者此商品已使用了优惠券
            if(order.payment <=0 || order.discount_single || selectedList.indexOf(order.goods_id) == -1 || activeProducts.indexOf(order.product_id) > -1){
            	continue;
            }
            
            if(canCheck && active.single && order.discount_fee > 0){	// 仅原价购买
        		canCheck = false;
        	}

        	totalFee = totalFee.bcadd(order[field]);
            tradeGoods[order.product_id] = order[field];
        }
        
        if(totalFee < active['meet']){
            continue;
        }
        
        for(var vid in active.value){
        	var discount = {
					id: active.id,
					vid: vid,
					type: active.type,
					title: active.title,
					single: active.single,
					meet: active.meet,
					discount_fee: active.value[vid],
					checked: false,
					index: j};
        	
        	var diff = discount.discount_fee >= totalFee ? 0 : totalFee.bcsub(discount.discount_fee);
        	if(diff <= 0){
        		continue;
        	}
        	if(canCheck && useDefault && diff < maxOne.diff){
        		maxOne.id = active.id;
        		maxOne.vid = vid;
        		maxOne.diff = diff;
        	}
        	
        	discount.checked = trade.discount_key.indexOf(discount.id + '.' + discount.vid) > -1;
        	discount.description = (active['meet'] > 0 ? '满'+active['meet']+'元优惠' : '下单优惠') + discount.discount_fee + '元';
        	discount.tradeGoods = tradeGoods;
    		discountList.push(discount);
    		
    		if(discount.checked){
    			useDefault = false;
    		}
        }
	}
	
	var couponGoods = {};
	for(var i=0; i<discountList.length; i++){
		var discount = discountList[i];
		if(useDefault && discount.id == maxOne.id && discount.vid == maxOne.vid){
			discount.checked = true;
		}
		
		if(discount.checked){
			delete promotionList[discount.index].value[discount.vid];
			if(Object.keys(promotionList[discount.index].value).length == 0){
				promotionList.splice(discount.index, 1);
			}
			var tradeGoods = manjian(discount.discount_fee, discount.tradeGoods);
			for(var pid in tradeGoods){
				couponGoods[pid] = {
					id: discount.id,
					vid: discount.vid,
					discount_fee: tradeGoods[pid],
					type: discount.type,
					single: discount.single
				};
			}
			
			// 从上面的订单中移除
			if(!!tradeCouponList[discount.vid]){
				var prev = tradeCouponList[discount.vid],
				tradeDiscountDetails = _data.groups[prev.sellerId].trades[prev.freightTid].discount_details;
				tradeDiscountDetails.splice(prev.index, 1);
			}
		}
		tradeCouponList[discount.vid] = {sellerId: trade.seller_id, freightTid: trade.freight_tid, index: trade.discount_details.length};
		trade.discount_details.push(discount);
	}
	
	for(var i=0; i<orders.length; i++){
		if(!!couponGoods[orders[i].product_id]){
			var discount = couponGoods[orders[i].product_id];
			orders[i].discount_details.push({id: discount.id, vid: discount.vid, discount_fee: discount.discount_fee});
			if(discount.single){
				orders[i].discount_single = discount.single;
			}
			orders[i].discount_fee = orders[i].discount_fee.bcadd(discount.discount_fee);
			orders[i].payment = orders[i].total_fee.bcsub(orders[i].discount_fee);
		}
	}
}

// 重置所有优惠
function resetPromotions(useDefault){
	tradeCouponList = {};
	var promotionList = $.extend(true, {}, _data.promotionList);
	
	for(var sellerId in _data.groups){
		var trades = _data.groups[sellerId].trades;
		for(var freightTid in trades){
			var trade = trades[freightTid];
			trade.seller_id = sellerId;
			trade.freight_tid = freightTid;
			resetPromotion(promotionList, trade, useDefault);
		}
	}
	
	// 相同金额的优惠券只显示一个
	var type = 0;
	for(var sellerId in _data.groups){
		var trades = _data.groups[sellerId].trades;
		for(var freightTid in trades){
			var trade = trades[freightTid], values = [], result = [];
			
			for(var i=0; i<trade.discount_details.length; i++){
				type = trade.discount_details[i].type;
				if(type > 3){ // 不是优惠券类
					result.push(trade.discount_details[i]);
					continue;
				}
				
				if(values.indexOf(type+'_'+trade.discount_details[i].discount_fee) > -1){ // 已存在相同金额
					continue;
				}else{
					result.push(trade.discount_details[i]);
					values.push(type+'_'+trade.discount_details[i].discount_fee);
				}
			}
			
			delete trade.discount_details;
			trade.discount_details = result;
		}
	}
}

// 重置某个订单的优惠
function resetPromotion(promotionList, trade, useDefault){
	var sellerId = trade.seller_id;
	
	// 获取默认选中的优惠
	trade.discount_key = [];
	var key = '';
	for(var i=0; i<trade.discount_details.length; i++){
		var data = trade.discount_details[i];
		if(data.checked){
			if(data.type < 4){
				key = data.id + '.' + data.vid;
			}else{
				key = data.id;
			}
			trade.discount_key.push(key);
		}
	}
	trade.discount_details = [];
	
	// 恢复订单详情
	for(var i=0; i<trade.orders.length; i++){
		var order = trade.orders[i];
		order.discount_price = order.price;
		order.total_fee = order.price.bcmul(order.num);
		order.payment = order.total_fee;
		order.discount_fee = 0;
		order.discount_details = [];
		order.discount_single = false;
		order.postage = 0;
	}
	
	// 1.限时折扣
	discounts(promotionList.discount, trade, useDefault);
	
	// 2.叠加满减
	promotions(promotionList.promotion, trade, useDefault);

	// 3.优惠券类
	coupons(promotionList.coupon, trade, useDefault);
}

// 店铺优惠
require(['model_pay_coupon'], function(model){
	var prev = {sellerId: '', freightTid: '', postage: []};
	$('.js-goods-list-container .js-discount').on('click', function(){
		var id = this.id,
			data = id.split('-');
		prev.postage = [],
		prev.sellerId = data[1],
		prev.freightTid = data[2];
		var trade = _data.groups[prev.sellerId].trades[prev.freightTid],
		orders = trade.orders;
		
		// 是否有包邮产品(标记是否需要重新计算运费)
		for(var i=0; i<orders.length; i++){
			if(orders[i].postage){
				prev.postage.push(orders[i].product_id);
			}
		}
		model.show(trade.discount_details);
	});
	
	model.onSelected = function(list, close){
		resetPromotions();	// 重新计算所有优惠
		var trade = _data.groups[prev.sellerId].trades[prev.freightTid],
		// 是否需要重新计算运费
		needResetFreight = false,
		postage = [],
		orders = trade.orders;
		for(var i=0; i<orders.length; i++){
			if( (prev.postage.indexOf(orders[i].product_id) == -1 && orders[i].postage) ||
			    (prev.postage.indexOf(orders[i].product_id) > -1 && !orders[i].postage)	
			){
				needResetFreight = true;
				break;
			}
		}
		
		if(needResetFreight){
			var result = getFreightFee(prev.sellerId, prev.freightTid);
			if(!result){
				resetData();
			}
		}else{
			resetData();
		}
		
		if(!close){
			model.reset(trade.discount_details);
		}
	}
});

// 重新计算总费用
function resetData(){
	var payment = 0
	   ,total_fee = 0
	   ,total_freight_fee = 0
	   ,total_balance = 0
	   ,total_discount = 0
	   ,buyer_balance = _data.buyer.balance * 1
	   ,buyer_no_balance = _data.buyer.no_balance * 1
	   ,sum_html = '';

	for(var sellerId in _data.groups){
		var group = _data.groups[sellerId],
		trades = group['trades'];
		group.sumFee = 0;
		
		for(var freightTid in trades){
			var trade = trades[freightTid],
			orders = trade.orders,
			score = 0;
			
			// 计算总金额
			trade.discount_fee = 0;
			trade.paid_no_balance = 0;
			trade.total_fee = 0;
			for(var i=0; i<orders.length; i++){
				var order = orders[i], scoreIndex = order.discount_details.length - 1;
				// 扣除上次计算的积分
				if(scoreIndex > -1 && order.discount_details[scoreIndex].id == 0){
					order.discount_fee = order.discount_fee.bcsub(order.discount_details[scoreIndex].discount_fee);
					order.payment = order.total_fee.bcsub(order.discount_fee);
					order.discount_details.splice(scoreIndex, 1);
				}
	            $('#product-'+order.product_id).html('¥' + order.discount_price);
				trade.total_fee = trade.total_fee.bcadd(order.total_fee);
				trade.discount_fee = trade.discount_fee.bcadd(order.discount_fee);
	            
				// 积分抵用
				if(buyer_no_balance > 0 && order.score > 0 && order.payment > 0){
					score = order.score.bcmul(order.payment).bcmul(0.01, 2);
					if(score > 0){
						if(score > buyer_no_balance){
							score = buyer_no_balance;
						}
						
						order.discount_fee = order.discount_fee.bcadd(score);
						order.payment = order.total_fee.bcsub(order.discount_fee);
						trade.paid_no_balance = trade.paid_no_balance.bcadd(score);
						buyer_no_balance = buyer_no_balance.bcsub(score);
						order.discount_details.push({id: 0, discount_fee: score});
					}
				}
			}

			// 商品总额 + 总邮费 - 总优惠
			var totalAmount = trade.total_fee.bcadd(trade.post_fee).bcsub(trade.discount_fee);
			trade.payment = totalAmount.bcsub(trade.paid_no_balance);
			
			// 使用可提现金额进行抵用
			if(trade.payment > 0 && buyer_balance > 0){
				trade.paid_balance = trade.payment > buyer_balance ? buyer_balance : trade.payment;
				trade.payment = trade.payment.bcsub(trade.paid_balance);
				buyer_balance = buyer_balance.bcsub(trade.paid_balance);
			}
			
			trade.paid_fee = trade.paid_no_balance.bcadd(trade.paid_balance);
			total_fee = total_fee.bcadd(trade.total_fee);
			total_freight_fee = total_freight_fee.bcadd(trade.post_fee);
			total_balance = total_balance.bcadd(trade.paid_fee);
			total_discount = total_discount.bcadd(trade.discount_fee);
			
			group.sumFee = group.sumFee.bcadd(totalAmount);
			$('#discount-'+sellerId+'-'+freightTid+' .pull-right').html('¥' + trade.discount_fee.bcFixed(2));
		}
		
		$('#seller-'+sellerId+'-total').html('¥' + group.sumFee.bcFixed(2));
	}

	payment = total_fee.bcadd(total_freight_fee).bcsub(total_balance).bcsub(total_discount);
	sum_html  = '<div class="block-item order-total">';
	sum_html += '	<p><span>商品总价</span><span class="pull-right">¥'+total_fee.bcFixed(2)+'</span></p>';
	sum_html += '	<p><span>运费(快递)</span><span class="pull-right">+ ¥'+total_freight_fee.bcFixed(2)+'</span></p>';
	sum_html += '	<p><span>店铺优惠</span><span class="pull-right">- ¥'+total_discount.bcFixed(2)+'</span></p>';
	sum_html += '	<p><span>积分抵用</span><span class="pull-right">- ¥'+total_balance.bcFixed(2)+'</span></p>';
	sum_html += '</div>';
	sum_html += '<div class="block-item">';
	sum_html += '	<p><span>应付金额</span><span class="pull-right c-orange">¥'+payment.bcFixed(2)+'</span></p>';
	sum_html += '</div>';
	$('.js-order-total').html(sum_html);
	if(payment == 0){
		$btnPay.html('立即兑换').prev().addClass('hide');
	}else{
		$btnPay.html('微信支付').prev().removeClass('hide');
	}
}