<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>瑞德健康</title>
    <script src="/js/flexible.js"></script>
    <link rel="stylesheet" href="/css/ruide.css">
</head>
<body>
<div id="head">
    <img src="/img/ruide/head.png" alt="">
</div>
<div id="search" >
   <div id="check_form">
        <select name="type" id="type">
            <option value="">类型</option>
            <option value="wx">微信</option>
            <option value="mobile">手机号码</option>
        </select>
        <input type="text" name="content" id="content">
        <i></i>
        <button id="btn">授权查询</button>
   </div>
<!--    </form>-->
</div>
<div id="details">
    <div class="userinfo">
        <span>姓名 </span><input class="name" type="text" disabled="true">
        微信号 <input class="wechat" type="text" disabled="true">
        身份证号码 <input class="id" type="text" disabled="true">
    </div>
    <div class="content">
        <p>所获得以下权限</p>
        <table border="1" id="check_content">
            <tr>
                <th>产品</th>
                <th>职位</th>
                <th>授权书展示</th>
            </tr>

        </table>
    </div>
    <div><img id="img_show" src="" alt=""></div>
</div>
<div id="explain">
        <h4>【查询说明】</h4>
        <span>可以用微信号(开头字母6-20位),手机号码(11位)任意一个来查询</span>
        <p>电子授权书是经销商合法销售本公司产品的唯一凭证，经销商必须严格按照本公司规定的价格体系进行销售，凡事发现不按公司相关价格体系销售产品，销售假货，串货任何一种情况的经销商，一经查实，将会立刻取消授权资格，扣除保险金，并拉入黑名单</p>
    </div>
    <div id="foot"></div>
<script src="/js/jquery-1.12.0.min.js"></script>
<script>
    $(function () {
        $("#btn").click(function () {
            var sd = $('#type').val();
            var sd2 = $('#content').val();
            var data= {type:sd,content:sd2};
            var tel = /^1[3|4|5|7|8]\d{9}$/;
            var reg=/^[a-zA-Z\d_]{5,20}$/;
            var msg = '';
            if(data.type == ''){
                msg = '请选择查询类型'
            }else if(data.type == 'mobile'){
                if(!tel.test(data.content)){
                    msg = '请填写正确的电话格式'
                }
            }else if(data.type == 'wx'){
                if(!reg.test(data.content)){
                    msg = '请填写正确的微信号格式'
                }
            }
            if(msg != ''){
                $('#btn').removeAttr('disabled', false);
                alert(msg);
                return false;
            };
            $.ajax({
                url: '/h5/ruide/check',
                type: 'post',
                /*dataType: 'json',*/
                data: data,
                success: function(data){
                    if(data !=0){
                        $("#check_content").find('td').remove();
                        $('#details').addClass('show');
                        $('#details input').eq(0).val(data[0].username);
                        $('#details input').eq(1).val(data[0].wechat);
                        $('#details input').eq(2).val(data[0].card);
                        for(var i=0;i<data.length;i++){
                            var html =
                                '<tr>'
                                + '<td>'+data[i].product+'</td>'
                                + '<td>'+data[i].position+'</td>'
                                + '<input type="hidden" value="'+data[i].show+'"></input>'
                                + '<td><span class="check_btn">点击查看<i></i></span></td>'
                                + '</tr>';
                            $("#check_content").append(html);
                        }

                        $('.check_btn').on('click',function(){
                            var imgSrc = $(this).parent().prev().val();
                            $("#img_show").addClass('show').attr("src",imgSrc);
                        });
                        $("#img_show").on('click',function () {
                            $(this).removeClass('show');

                        })
                    }else{
                        alert('您所查询的信息不存在');
                    }
                },
                error: function(result){
                    alert('提交失败，请重试');
                    $('#btn').removeAttr('disabled', false);
                },
                complete: function(){
                    $('#btn').removeAttr('disabled', false);
                }
            });
        })


    });


</script>
</body>
</html>