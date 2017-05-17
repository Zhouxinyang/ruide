"use strict";
var appUtils = {};
appUtils.preset = {
	errorModal: {
		content: {
			html: "糟糕，网络不给力"
		},
		confirm: {
			html: "重新进入",
			click: function() {
				appUtils.reload();
			}
		},
		cancel: "remove"
	}
},
appUtils.reload = function(){
	$('<form method="post"><input type="hidden" name="mobile" value="'+_apps_global.mobile+'"></form>').submit();
},
appUtils.modal = function() {
	function n() {
		o && o.remove(),
		o = $($("#apps-modal-tpl").html()),
		i = {};
		for (var n in a) i[n] = o.find(".js-apps-modal-" + n)
	}
	function t(t) {
		n();
		var e, o, c, a;
		for (e in t) if ("string" != typeof t[e]) for (o in t[e]) c = i[e][o],
		$.isFunction(c) && c.call(i[e], t[e][o]);
		else a = t[e],
		"remove" == a && i[e].remove()
	}
	function e(n) {
		return $.extend(!0, {},
		a, n)
	}
	var o, i, c = {
		open: function(n) {
			t(e(n)),
			$(document.body).append(o)
		},
		close: function(n) {
			n === !0 ? o.find(".apps-modal").remove() : o && o.remove()
		}
	},
	a = {
		content: {
			html: ""
		},
		confirm: {
			html: "确定",
			click: $.noop
		},
		cancel: {
			html: "取消",
			click: c.close
		}
	};
	return c
} (),
appUtils.process = function() {
	function n(n) {
		a.cancel.html = n,
		l.cancel.html = n
	}
	function t() {
		return 0 !== i ? 10999 == i ? (c.open(a), !1) : 10998 == i ? (c.open(l), !1) : (c.open(s), !1) : 0 !== o.costPoint && void 0 != o.costPoint ? (c.open(r), !1) : !0
	}
	var e, o = _apps_global,
	i = o.error_code,
	c = appUtils.modal,
	a = {
		content: {
			html: o.error_msg
		},
		confirm: {
			html: "登录",
			click: function() {
				location.href = o.login
			}
		},
		cancel: {
			html: "取消",
			click: function() {
				c.close()
			}
		}
	},
	l = {
		content: {
			html: o.error_msg+'<br><input id="mobile" type="tel" style="padding: 6px;margin-top: 10px;width: 80%;text-align: center;border: 1px solid #ccc;border-radius: 3px;">'
		},
		confirm: {
			html: "确定",
			click: function() {
				var tel = /^1[3|4|5|7|8]\d{9}$/,
				mobile = $('#mobile').val();
				if(tel.test(mobile)){
					$('<form method="post"><input type="hidden" name="mobile" value="'+mobile+'"></form>').submit();
				}else{
					alert('请输入正确的手机号码');
				}
			}
		},
		cancel: "remove"
	},
	r = {
		content: {
			html: '每次抽奖将消耗<span class="important"> ' + o.costPoint + "积分</span>"
		},
		confirm: {
			html: "赌一把",
			click: function() {
				c.close(),
				e.onconfirm && e.onconfirm()
			}
		},
		cancel: {
			html: "舍不得",
			click: function() {
				c.close()
			}
		}
	},
	s = {
		content: {
			html: o.error_msg
		},
		confirm: {
			html: "知道了",
			click: function() {
				c.close()
			}
		},
		cancel: "remove"
	};
	return e = {
		check: t,
		setCancelText: n,
		onconfirm: $.noop
	}
} (),
appUtils.atLeast = function(n, t) {
	function e() {
		c.resolve.apply(null, arguments)
	}
	var o, i = !1,
	c = {};
	return setTimeout(function() {
		i ? t.apply(null, o) : c.resolve = function() {
			t.apply(null, arguments)
		}
	},
	n),
	c.resolve = function() {
		o = arguments,
		i = !0
	},
	{
		resolve: e
	}
},
appUtils.randInt = function(n, t) {
	var e = n + Math.random() * (t - n);
	return parseInt(e, 10)
},
appUtils.format = function(n) {
	var t = Array.prototype.slice.call(arguments, 1);
	return n.replace(/{(\d+)}/g,
	function(n, e) {
		return "undefined" != typeof t[e] ? t[e] : n
	})
},
appUtils.getUrlParam = function(n, t) {
	var e = new RegExp("(^|&)" + n + "=([^&]*)(&|$)"),
	o = "router" === t ? window.location.href: window.location.search,
	i = o.substr(1).match(e);
	return null !== i ? window.unescape(i[2]) : null
},
function() {
	function n(n) {
		var t = Object.create(l);
		t.content = {
			html: n
		},
		a.open(t)
	}
	function t() {
		return $.ajax({
			url: i.post_url + '/getLucky',
			data: {
				id: i.id,
				mobile: i.mobile
			},
			cache: !1,
			type: "post",
			dataType: "json",
			timeout: 5e3
		})
	}
	function e() {
		s.clear(),
		a.open(appUtils.preset.errorModal)
	}
	function o() {
		$(".js-start-btn").click(p),
		c.init = function() {}
	}
	var i = _apps_global,
	c = {},
	a = appUtils.modal,
	l = {
		content: {
			html: ""
		},
		confirm: {
			html: "知道了",
			click: function() {
				a.close()
			}
		},
		cancel: "remove"
	},
	r = {
		content: {
			html: ""
		},
		cancel: {
			html: "继续抽奖",
			click: function() {
				appUtils.reload();
			}
		},
		confirm: {
			html: "查看奖品",
			click: function() {
				appUtils.reload();
			}
		}
	},
	s = function() {
		function n() {
			var n, t, e, o, c = i.prize, l;
			var $prizeList = $('.wheel');
			for (n in c) {
				t = c[n], 
				l = t.index.split(',');
				for(var ii=0; ii<l.length; ii++){
					e = $prizeList.find('[data-index="'+l[ii]+'"]').find(".wheel-icon"),
					o = t.image_url,
					o && o.length > 0 ? h(e, o) : t.point > 0 ? e.addClass("point-icon") : 0 === t.point && e.addClass("coupon-icon")
				}
			}
		}
		function t() {
			u = 0,
			d = !0,
			v = 60,
			f = function() {},
			l()
		}
		function e() {
			m.get(u).removeClass("active"),
			u++,
			u >= m.length && (u = 0),
			m.get(u).addClass("active"),
			p = setTimeout(e, v),
			f()
		}
		function o() {
			d = !1,
			clearTimeout(p)
		}
		function c() {
			d || (t(), e())
		}
		function a(n, t) {
			var e = 2 * m.length + (n - u);
			e > 2 * m.length && (e -= m.length),
			f = function() {
				v *= 1.08,
				e--,
				0 === e && (o(), t && setTimeout(t, 600), m.getSelected().addClass("pulse"))
			}
		}
		function l() {
			$(".wheel-block").removeClass("active")
		}
		function r() {
			o(),
			l()
		}
		function s(n, t) {
			a(m.selectByCategory(n), t)
		}
		var u, p, f, m, d = !1,
		v = 10;
		m = function() {
			var n, t, e = [];
			return n = {
				set: function(t, o) {
					e[t] = o,
					n.length = e.length
				},
				get: function(n) {
					return e[n]
				},
				selectByCategory: function(n) {
					t = i = n;
					return t;
					var o = e.filter(function(t) {
						return t.hasClass(n)
					}),
					i = appUtils.randInt(0, o.length);
					return t = o[i].data("index")
				},
				getSelected: function() {
					return e[t]
				},
				length: 0
			},
			n.splice = [].splice,
			n.constructor = Array,
			n
		} ();
		var h = function() {
			var n = {},
			t = function(n, t) {
				n.addClass("animated tada"),
				setTimeout(function() {
					n.addClass("custom-icon").removeClass("animated tada").css("backgroundImage", "url(" + t + ")")
				},
				1200)
			};
			return function(e, o) { / !\d / .test(o) || (o);
				var i = function() {
					t(e, o)
				};
				if (n[o]) i();
				else {
					n[o] = !0;
					var c = new Image;
					c.onload = i,
					c.src = o
				}
			}
		} ();
		return $(".wheel-block").each(function() {
			var n = $(this),
			t = n.data("index");
			n.addClass("animated"),
			m.set(t, n)
		}),
		n(),
		{
			start: c,
			clear: r,
			to: s
		}
	} (),
	u = function() {
		var t = '<span class="important">{0}！</span>',
		e = "" === i.failedInfo ? "哎呀，真可惜擦身而过!": i.failedInfo,
		o = '<br>手气这么好，再送您 <span class="important">{0}</span> 积分',
		c = "运气不错，您还可以再玩一次",
		l = '<br>送您 <span class="important">{0}</span> 积分';
		return function(u) {
			var p = "";
			var f = u.data;
			if (0 === u.code) {
				switch (f.type) {
					case 0:
						p = f.title;
						if(f.give_point > 0) p += appUtils.format(l, f.give_point);
						break;
					default:
						p = appUtils.format(t, '哇！抽中 ' + f.title);
					    if(f.give_point > 0) p += appUtils.format(o, f.give_point);
					break;
				}
				s.to(f.index,
				function() {
					if(f.type == 3){
						return _address.show(f), false
                    }
					
                    r.content.html = p;
                    if(f.detail_url && f.detail_url.length > 0){
                    	r.confirm.html = "立即领取";
                    	r.confirm.click = function(){
    						window.location.href = f.detail_url;
    					}
                    }else{
                        r.confirm = "remove"
                    }
                    a.open(r);
				})
			}else if(10998 == u.code){
				s.to(f.index, function() {
					r.cancel = "remove",
					r.confirm.html = "确定",
					r.content.html = u.msg,
					r.confirm.click = function(){
						a.close(),
						document.body.scrollTop = $('.qrcode').offset().top
					},
					a.open(r)
				})
			}else 2 == i.lotteryAgain ? (p = u.msg || c, s.to(f.index,
			function() {
				n(p)
			})) : (p = u.msg, s.to(f.index,
			function() {
				n(p)
			})),
			void 0 != i.givePoint && 0 != i.givePoint && (p += appUtils.format(l, i.givePoint))
		}
	} (),
	p = function() {
		var n = !1;
		return function() {
			if (!n) {
				n = !0,
				s.start();
				var o = appUtils.atLeast(1500, u),
				i = appUtils.atLeast(1500, e);
				t().done(o.resolve).fail(i.resolve)
			}
		}
	} ();
	c.init = o,
	window.gameIns = c
} (),
$(function() {
	var n = $(".js-start-btn");
	appUtils.process.check() ? gameIns.init() : n.on("click",
	function(n) {
		appUtils.process.check()
	}),
	appUtils.process.onconfirm = function() {
		n.unbind("click"),
		gameIns.init()
	},
	_apps_global.has_point || $(".js-activity-note").remove()
},
!1);
var toast={timer:null,$dialog:null,$tip:null,$content:null,init:function(){var paddingTop=document.body.style.paddingTop==''?0:document.body.style.paddingTop+'px';$('body').append('<div id="toast-view"><div class="ext-tips" style="top:'+paddingTop+'"><span></span></div></div></div>'),this.timer=null,this.$dialog=$('#toast-view'),this.$tip=this.$dialog.children(),this.$content=this.$tip.children()},show:function(msg){if(!this.$dialog){toast.init()}this.$dialog.attr('class',''),this.$content.html(msg),this.$tip.addClass('show'),window.clearTimeout(toast.timer),toast.timer=setTimeout(function(){toast.$tip.removeClass('show')},2500)},warning:function(msg){toast.show(msg),toast.$dialog.attr('class','warning')},loading:function(msg){if(msg===false){$('#loading_modal').remove()}else{$('body').append('<div id="loading_modal"class="loading-wrapper"><div class="mask"></div><div class="inner"></div><div class="text">请稍后</div><div class="bubblingG"><span id="bubblingG_1"></span><span id="bubblingG_2"></span><span id="bubblingG_3"></span></div></div>')}}};window.toast=toast;

$(function(){
	$('.js-goods-list .js-cancel').on('click', function(){
		if(!confirm('确定取消吗？')){
			return false;
		}

		var $parent = $(this).parent();
		var postData = {id: $parent.data('id'), mobile: _apps_global.mobile};
		toast.loading();
		$.ajax({
			url: _apps_global.post_url+'/cancel?active_id='+_apps_global.id,
			type: 'post',
			dataType: 'json',
			data: postData,
			success: function(result){
				if(result.status == 1){
					$parent.html('已取消')
				}else if(result.status == 0){
					alert(result.info)
				}else{
					alert('取消失败')
				}
			},
			complete: function(){
				toast.loading(false)
			}
		});
		return false;
	});
});

var _address = {
		show: function(addr){
			var html = this._getTpl(addr);
			var $html = $(html);
			$html.appendTo('body');
			this._render($html, addr);
		},
		_getTpl: function(addr){
			var html = '';
			html += '<div style="height: 100%; position: fixed; top: 0px; left: 0px; right: 0px; z-index: 1000; transition: none 0.2s ease; opacity: .7; background-color: rgba(0, 0, 0, 0.901961);"></div>';
			html += '<div style="position:fixed;left:0;right:0px;bottom:0px;z-index:1000;background:white">';
			html += '	<div class="sku-layout">';
			html += '		<div class="thumb"><img src="'+addr.image_url+'"></div>';
			html += '		<div class="detail goods-base-info clearfix">';
			html += '			<p class="title c-black ellipsis">'+addr.title+'</p>';
			html += '			<div class="goods-price clearfix">';
			html += '				<div class="current-price pull-left c-black" style="color: #f60;text-decoration: line-through;">';
			html += '					<span class="price-name pull-left font-size-14 c-orange">￥</span>';
			html += '					<i class="js-goods-price price font-size-18 vertical-middle c-orange">'+addr.price+'</i>';
			html += '				</div>';
			html += '			</div>';
			html += '		</div>';
			html += '		<div class="js-cancel sku-cancel"><div class="cancel-img"></div></div>';
			html += '	</div>';
			html += '	<form class="js-address-fm address-ui address-fm" method="post" action="/h5/wheel/addOrder">';
			html += '    	<input type="hidden" name="mobile" value="'+addr.mobile+'">';
			html += '    	<div class="block form" style="margin:0;">';
			html += '    		<div class="block-item pay-notice">';
			html += '    		人工服务费10元，到付给快递小哥即可';
			html += '    		</div>';
			html += '	        <div class="block-item">';
			html += '	            <label>收货人</label>';
			html += '	            <input type="text" name="receiver_name" value="" placeholder="名字" maxlength="16" required="required" data-msg-required="请输入收货人">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>联系电话</label>';
			html += '	            <input type="tel" name="receiver_mobile" value="'+addr.mobile+'" placeholder="手机号码" maxlength="11" required="required" data-rule-mobile="mobile" data-msg-required="请输入联系电话" data-msg-mobile="请输入正确的手机号" maxlength="11">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>选择地区</label>';
			html += '	            <div class="js-area-select area-layout">';
			html += '	            	<span>';
			html += '						<select id="province" name="receiver_province" data-city="#city" data-selected="" data-name="true" class="address-province" required="required" data-msg-required="请选择省份">';
			html += '							<option value="">选择省份</option>';
			html += '						</select>';
			html += '					</span>';
			html += '					<span>';
			html += '						<select id="city" name="receiver_city" data-county="#county" data-selected="" class="address-city" required="required" data-msg-required="请选择城市">';
			html += '							<option value="">选择城市</option>';
			html += '						</select>';
			html += '					</span>';
			html += '					<span>';
			html += '						<select id="county" name="receiver_county" data-selected="" class="address-county" required="required" data-msg-required="请选择区县">';
			html += '							<option value="">选择区县</option>';
			html += '						</select>';
			html += '					</span>';
			html += '				</div>';
			html += '        	</div>';
			html += '	        <div class="block-item">';
			html += '	            <label>详细地址</label>';
			html += '	            <input type="text" name="receiver_detail" value="" maxlength="128" placeholder="街道门牌信息" required="required" data-msg-required="请输入详细地址">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>邮政编码</label>';
			html += '	            <input type="tel" maxlength="6" name="receiver_zip" value="" placeholder="邮政编码(选填)">';
			html += '	        </div>';
			html += '    	</div>';
			html += '	    <div class="action-container clearfix">';
			html += '	        <div class="half-button"><button type="button" class="btn btn-block btn-red js-cancel">放弃领取</button></div>';
			html += '	        <div class="half-button"><button type="submit" class="btn btn-block btn-green">领取奖品</button></div>';
			html += '	    </div>';
			html += '	</form>';
			html += '</div>';
			return html;
		},
		$html: null,
		close: function(conf){
			if(conf && confirm('确定放弃领取奖品吗？')){
				if(navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == "micromessenger"){
					WeixinJSBridge.invoke('closeWindow', {}, function (res) {})
				}else{
					appUtils.reload();
				}
			}
		},
		_render: function($html, addr){
			$('html,body').css({'height': document.documentElement.clientHeight + 'px', 'overflow': 'hidden'});
			var t = this;
			t.$html = $html;
			$html.eq(0).on('click', function(){return t.close(true), false});
			$html.find('.js-cancel').on('click', function(){return t.close(true), false});
			
			// 监听表单提交
			var $form = $html.find('form');
			$form.on('submit', function(){
				var data = {active_id: addr.active_id, record_id: addr.record_id};
            	var array = $form.serializeArray();
            	for(var i=0; i<array.length; i++){
            		if(typeof data[array[i].name] == 'undefined'){
            			data[array[i].name] = array[i].value;
            		}else{
            			data[array[i].name] += ',' + array[i].value;
            		}
            	}
            	
            	var tel = /^1[3|4|5|7|8]\d{9}$/,
            		msg = '';
            	if(data.receiver_name == ''){
            		msg = '请填写收货人'
            	}else if(!tel.test(data.receiver_mobile)){
            		msg = '请填写手机号码'
            	}else if(data.receiver_province == ''){
                	msg = '请选择省份'
            	}else if(data.receiver_city == ''){
                	msg = '请选择城市'
            	}else if(data.receiver_county == ''){
                	msg = '请选择区/县'
            	}else if(data.receiver_detail == ''){
                	msg = '请输入详细地址'
            	}
            	
            	if(msg != ''){
            		return alert(msg), false
            	}
			
				_address.save(data);
				return false;
			});
			
			$.getScript('/js/address.js', function(){
				new City('#province');
			});
		},
		save: function(data){
			toast.loading();
			$.ajax({
				url: _apps_global.post_url+'/addOrder?id='+_apps_global.id,
				type: 'post',
				dataType: 'json',
				data: data,
				success: function(result){
					if(result.status == 1){
						alert('领取成功');
						if(navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == "micromessenger"){
							WeixinJSBridge.invoke('closeWindow', {}, function (res) {})
						}else{
							appUtils.reload();
						}
					}else{
						toast.loading(false),
						alert('领取失败')
					}
				},
				error: function(){
					toast.loading(false),
					alert('领取失败，请重试')
				}
			});
		}
	}