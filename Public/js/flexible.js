;(function(win) {
    var doc = win.document;
    var docEl = doc.documentElement;
    var metaEl = doc.querySelector('meta[name="viewport"]');
    var dpr = 0;
    var scale = 0;
    var tid;
//在html中不写<meta>让其自动生成，下面的if块是取出dpr和scale。方便一会写入<meta>
    if (!dpr && !scale) {
        var isAndroid = win.navigator.appVersion.match(/android/gi);
        var isIPhone = win.navigator.appVersion.match(/iphone/gi);
        var devicePixelRatio = win.devicePixelRatio;
        if (isIPhone) {
            // iOS下，对于2和3的屏，用2倍的方案，其余的用1倍方案
            if (devicePixelRatio >= 3 && (!dpr || dpr >= 3)) {
                dpr = 3;
            } else if (devicePixelRatio >= 2 && (!dpr || dpr >= 2)){
                dpr = 2;
            } else {
                dpr = 1;
            }
        } else {
            dpr=1;
        }
        scale = 1 / dpr;
    }
//docEl代表的是html根元素，先把data-dpr写入根元素，这样通过这个属性
//就可以在设置background的时候加载不同大小的图片了
//举个栗子：
//.ad_2{
//      width:100%;
//      height:100%;
//      background-image: url("../images/1x_ad_2.jpg");
//      background-size: 100% 100%;
//      [data-dpr = "2"] & {
//          background-image: url("../images/@2x/2x_ad_2.jpg");
//      }
//      [data-dpr = "3"] & {
//          background-image: url("../images/@3x/3x_ad_2.jpg");
// }
    docEl.setAttribute('data-dpr', dpr);

//下面是设置<meta>
    //$("meta[name$='viewport']").remove();//删除原有meta节点，这样写必须先引入Jquery,再引入flexible
    //metaEl = null;//把metaEl置空，让程序走下面的if语句，自动生成新的<meta>标签

    if (!metaEl) {
        metaEl = doc.createElement('meta');
        metaEl.setAttribute('name', 'viewport');
        metaEl.setAttribute('content', 'initial-scale=' + scale + ', maximum-scale=' + scale + ', minimum-scale=' + scale + ', user-scalable=no');
        if (docEl.firstElementChild) {
            docEl.firstElementChild.appendChild(metaEl);
        } else {
            var wrap = doc.createElement('div');
            wrap.appendChild(metaEl);
            doc.write(wrap.innerHTML);
        }
    }
//定义设置<html>根元素的font-size值的函数，Iphone6 75px  iphone6s是124.3px
    function refreshRem(){
        var width = docEl.getBoundingClientRect().width;
        var rem = width / 10;
        docEl.style.fontSize = rem + 'px';
        win.rem = rem;
    }
//只要屏幕一变化就重新调<html> font-size大小
    win.addEventListener('resize', function() {
        clearTimeout(tid);
        tid = setTimeout(refreshRem, 300);
    }, false);
    win.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            clearTimeout(tid);
            tid = setTimeout(refreshRem, 300);
        }
    }, false);
//当文档加载完毕以后设置body中的font-size。在不同屏幕上看上去和12号字体一样大
    if (doc.readyState === 'complete') {
        doc.body.style.fontSize = 12 * dpr + 'px';
    } else {
        doc.addEventListener('DOMContentLoaded', function(e) {
            doc.body.style.fontSize = 12 * dpr + 'px';
        }, false);
    }
//调用上面定义的函数
    refreshRem();
    win.dpr = dpr;
})(window);