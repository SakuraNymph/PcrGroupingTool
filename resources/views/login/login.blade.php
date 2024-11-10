@extends('main')
@section('content')
<style>
.demo-login-container{width: 320px; margin: 21px auto 0;}
.demo-login-other .layui-icon{position: relative; display: inline-block; margin: 0 2px; top: 2px; font-size: 26px;}
.layui-form{margin-top:200px}
</style>
<form action="{{ url('user/login') }}" method="post" class="layui-form">
  {{ csrf_field() }}
  <div class="demo-login-container">

    <div class="layui-form-item">
      @if ($error)
        <center><span style="color:#f54c4c">
          <strong>您输入的帐号信息或者密码不正确</strong>
        </span></center>
      @endif
    </div>
    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix">
          <i class="layui-icon layui-icon-email"></i>
        </div>
        <input type="text" name="email" value="" lay-verify="required" placeholder="邮   箱" lay-reqtext="请填写邮箱" autocomplete="on" class="layui-input" lay-affix="clear" autofocus>
      </div>
    </div>
    <div class="layui-form-item">
      <div class="layui-input-wrap">
        <div class="layui-input-prefix">
          <i class="layui-icon layui-icon-password"></i>
        </div>
        <input type="password" name="password" value="" lay-verify="required" placeholder="密   码" lay-reqtext="请填写密码" autocomplete="on" class="layui-input" lay-affix="eye">
      </div>
    </div>
    <div class="layui-form-item">
      <input type="checkbox" name="remember" lay-skin="primary" title="记住密码">
      <!-- <a href="#forget" style="float: right; margin-top: 7px;">忘记密码？</a> -->
    </div>
    <div class="layui-form-item">
      <button class="layui-btn layui-btn-fluid" lay-submit lay-filter="demo-login">登录</button>
    </div>
  </div>
</form>
@endsection
@section('js')
<script>
layui.use(function(){
  var form = layui.form;
  var layer = layui.layer;
  // 提交事件
  // form.on('submit(demo-login)', function(data){
  //   var field = data.field; // 获取表单字段值
  //   // console.log(field);
  //   $.ajaxSetup({
  //       headers: {
  //           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  //       }
  //   });
  //   $.post("{{ url('api/do_login') }}", {email:field.email, password:field.password}, function (res) {
  //     console.log(res);
  //   });
  //   return false; // 阻止默认 form 跳转
  // });
});
</script>
@endsection