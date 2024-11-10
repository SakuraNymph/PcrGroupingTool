@extends('main')
@section('content')
<style>
.demo-reg-container{width: 320px; margin: 21px auto 0;}
.demo-reg-other .layui-icon{position: relative; display: inline-block; margin: 0 2px; top: 2px; font-size: 26px;}
.layui-form{margin-top:200px}
</style>
<div class="layui-form">
  <div class="demo-reg-container">
    <div class="layui-form-item">
      <div class="layui-row">
        <div class="layui-col-xs7">
          <div class="layui-input-wrap">
            <div class="layui-input-prefix">
              <i class="layui-icon layui-icon-email"></i>
            </div>
            <input type="text" name="email" value="" lay-verify="required|email" placeholder="邮箱" lay-reqtext="请填写邮箱" autocomplete="off" class="layui-input" id="reg-email">
          </div>
        </div>
        <div class="layui-col-xs5">
          <div style="margin-left: 11px;">
            <button type="button" id="vercode" class="layui-btn layui-btn-fluid layui-btn-primary" lay-on="reg-get-vercode">获取验证码</button>
          </div>
        </div>
      </div>
    </div>
    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix">
          <i class="layui-icon layui-icon-vercode"></i>
        </div>
        <input type="text" name="vercode" value="" lay-verify="required" placeholder="验证码" lay-reqtext="请填写验证码" autocomplete="off" class="layui-input">
      </div>
    </div>
    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix">
          <i class="layui-icon layui-icon-password"></i>
        </div>
        <input type="password" name="password" value="" lay-verify="required" placeholder="密码" autocomplete="off" class="layui-input" id="reg-password" lay-affix="eye">
      </div>
    </div>
    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix">
          <i class="layui-icon layui-icon-password"></i>
        </div>
        <input type="password" name="confirmPassword" value="" lay-verify="required|confirmPassword" placeholder="确认密码" autocomplete="off" class="layui-input" lay-affix="eye">
      </div>
    </div>
    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix">
          <i class="layui-icon layui-icon-username"></i>
        </div>
        <input type="text" name="nickname" value="" lay-verify="required" placeholder="昵称" autocomplete="off" class="layui-input" lay-affix="clear">
      </div>
    </div>
    <div class="layui-form-item">
      <button type="button" class="layui-btn layui-btn-fluid" lay-submit lay-filter="demo-reg">注册</button>
    </div>
  </div>
</div>
@endsection
@section('js')
<!-- 请勿在项目正式环境中引用该 layui.js 地址 -->
<script>
layui.use(function(){
  var $ = layui.$;
  var form = layui.form;
  var layer = layui.layer;
  var util = layui.util;
  
  // 自定义验证规则
  form.verify({
    // 确认密码
    confirmPassword: function(value, item){
      var passwordValue = $('#reg-password').val();
      if(value !== passwordValue){
        return '两次密码输入不一致';
      }
    }
  });
  
  // 提交事件
  form.on('submit(demo-reg)', function(data){
    var field = data.field; // 获取表单字段值

    // 是否勾选同意
    if(!field.agreement){
      // layer.msg('您必须勾选同意用户协议才能注册');
      // return false;
    }
    
    // 显示填写结果，仅作演示用
    // layer.alert(JSON.stringify(field), {
    //   title: '当前填写的字段值'
    // });
    // return false;
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post("{{ url('api/do_register') }}", {email:field.email,vercode:field.vercode,password:field.password,confirmPassword:field.confirmPassword,nickname:field.nickname}, function (res) {
      const obj = JSON.parse(res);
      if (obj.status == 0) {
        layer.msg(obj.result.message);
      }
      if (obj.status == 1) {
        layer.msg('注册成功', {icon:0}, function () {
          window.location.href = "{{ url('login') }}";
        });
      }
    });
    return false; // 阻止默认 form 跳转
  });
  
  // 普通事件
  util.on('lay-on', {
    // 获取验证码
    'reg-get-vercode': function(othis){
      var isvalid = form.validate('#reg-email'); // 主动触发验证，v2.7.0 新增 
      const email = $('#reg-email').val();
      if ($('#vercode').hasClass('layui-btn-disabled')) {
        return false;
      }
      // 验证通过
      if(isvalid){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post("{{ url('api/send_email') }}", {email:email}, function (res) {
          const obj = JSON.parse(res);
          if (obj.status == 1) {

            layer.msg('验证码发送成功，请到邮箱内查看');
            $('#vercode').addClass('layui-btn-disabled');
            // 倒计时的初始时间（60秒）
            var countdownTime = 60;

            // 定义倒计时函数
            var countdown = setInterval(function() {
                // 显示剩余时间
                $('#vercode').text(countdownTime);

                // 每次减少一秒
                countdownTime--;

                // 当倒计时结束时
                if (countdownTime < 0) {
                    clearInterval(countdown); // 停止倒计时
                    $('#vercode').text('获取验证码'); // 显示结束信息
                    $('#vercode').removeClass('layui-btn-disabled');
                }
            }, 1000); // 每1000毫秒执行一次，即每秒执行
          } else {
            layer.msg(obj.result.message);
          }
        });
      }
    }
  });
});
</script>
@endsection
