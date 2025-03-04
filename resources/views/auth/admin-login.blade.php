<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>PCR Tool Admin</title>
  <meta name="renderer" content="webkit">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
  <link rel="stylesheet" href="/layuiadmin/layui/css/layui.css" media="all">
  <link rel="stylesheet" href="/layuiadmin/style/admin.css" media="all">
  <link rel="stylesheet" href="/layuiadmin/style/login.css" media="all">
  <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
  <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
  <link rel="icon" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
  <link rel="icon" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
  <link rel="icon" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
</head>
<body>

  <div class="layadmin-user-login layadmin-user-display-show" id="LAY-user-login" style="display: none;">

    <div class="layadmin-user-login-main">
      <div class="layadmin-user-login-box layadmin-user-login-header">
        <h2>PCR Tool Admin</h2>
        <!-- <p>layui 官方出品的单页面后台管理模板系统</p> -->
      </div>
      <form action="{{ url('admin/login') }}" method="post">
        {{ csrf_field() }}
        <div class="layadmin-user-login-box layadmin-user-login-body layui-form">
        @if ($errors->has('username') || $errors->has('password'))
          <center><span style="color:#f54c4c">
            <strong>您输入的帐号信息或者密码不正确</strong>
          </span></center>
        @endif
        @if ($errors->has('is_banned'))
          <center><span style="color:#f54c4c">
            <strong>您的帐号还未通过审核或已冻结</strong>
          </span></center>
        @endif
        @if ($errors->has('is_ip'))
          <center><span style="color:#f54c4c">
            <strong>IP未授权</strong>
          </span></center>
        @endif
        <div class="layui-form-item">
          <label class="layadmin-user-login-icon layui-icon layui-icon-username" for="LAY-user-login-username"></label>
          <input type="text" name="username" id="LAY-user-login-username" lay-verify="required" placeholder="用户名" class="layui-input">
        </div>
        <div class="layui-form-item">
          <label class="layadmin-user-login-icon layui-icon layui-icon-password" for="LAY-user-login-password"></label>
          <input type="password" name="password" id="LAY-user-login-password" lay-verify="required" placeholder="密码" class="layui-input">
        </div>
        <!-- <div class="layui-form-item">
          <div class="layui-row">
            <div class="layui-col-xs7">
              <label class="layadmin-user-login-icon layui-icon layui-icon-vercode" for="LAY-user-login-vercode"></label>
              <input type="text" name="vercode" id="LAY-user-login-vercode" lay-verify="required" placeholder="图形验证码" class="layui-input">
            </div>
            <div class="layui-col-xs5">
              <div style="margin-left: 10px;">
                <img src="https://www.oschina.net/action/user/captcha" class="layadmin-user-login-codeimg" id="LAY-user-get-vercode">
              </div>
            </div>
          </div>
        </div> -->
        <!-- <div class="layui-form-item" style="margin-bottom: 20px;">
          <input type="checkbox" name="remember" lay-skin="primary" title="记住密码">
          <a href="forget.html" class="layadmin-user-jump-change layadmin-link" style="margin-top: 7px;">忘记密码？</a>
        </div> -->
        <div class="layui-form-item">
          <button class="layui-btn layui-btn-fluid" lay-submit lay-filter="LAY-user-login-submit">登 入</button>
        </div>
        <!-- <div class="layui-trans layui-form-item layadmin-user-login-other">
          <label>社交账号登入</label>
          <a href="javascript:;"><i class="layui-icon layui-icon-login-qq"></i></a>
          <a href="javascript:;"><i class="layui-icon layui-icon-login-wechat"></i></a>
          <a href="javascript:;"><i class="layui-icon layui-icon-login-weibo"></i></a>
          <a href="reg.html" class="layadmin-user-jump-change layadmin-link">注册帐号</a>
        </div> -->
      </div>
      </form>
    </div>
    
    <!-- <div class="layui-trans layadmin-user-login-footer">
      <p>© 2018 <a href="http://www.layui.com/" target="_blank">layui.com</a></p>
      <p>
        <span><a href="http://www.layui.com/admin/#get" target="_blank">获取授权</a></span>
        <span><a href="http://www.layui.com/admin/pro/" target="_blank">在线演示</a></span>
        <span><a href="http://www.layui.com/admin/" target="_blank">前往官网</a></span>
      </p>
    </div> -->
  </div>
  <script>
    if (window != top)
             top.location.href = location.href; 
  </script>
  <script src="/layuiadmin/layui/layui.js"></script>  
  <script>
  layui.config({
    base: '/layuiadmin/' //静态资源所在路径
  }).extend({
    index: 'lib/index' //主入口模块
  }).use(['index', 'user'], function(){
    var $ = layui.$
    ,setter = layui.setter
    ,admin = layui.admin
    ,form = layui.form
    ,router = layui.router()
    ,search = router.search;

    form.render();
  });
  </script>
</body>
</html>