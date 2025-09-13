<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/layuiadmin/layui/css/layui.css" media="all">
<link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="icon" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
<link rel="icon" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<title>公主连结分刀工具</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    flex-direction: column;
  }

  .button-container {
    text-align: center;
  }

  .button-container button {
    display: block;
    width: 200px;
    height: 40px;
    margin-bottom: 10px;
    font-size: 16px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .button-container button:hover {
    background-color: #0056b3;
  }

  .hint-text {
    font-size: 14px;
    color: #888;
    margin-top: 20px;
    text-align: center;
    width: 100%;
  }
</style>
</head>
<body>

<div class="button-container">
  
  <button id="firstDayHomeWork">首日满补套餐</button>
  @if(Auth::guard('user')->check())
  <button onclick="location.href='{{ url("user/account/list") }}'">我的账号</button>
  @endif
  <button onclick="location.href='{{ url("team") }}'">我的作业</button>
  <button onclick="location.href='{{ url("team_list") }}'">作业列表</button>
  <button id="subscribe">作业订阅</button>
  <div id="rank_button">
  </div>
  <!-- <button onclick="location.href='{{ url("guide") }}'">推荐攻略</button> -->
  <!-- <button id="rank">推荐rank</button> -->
  <button id="teach">使用教程</button>
  <button id="support">支持作者</button>
  <button id="bug">bug反馈</button>
  <a href="https://github.com/SakuraNymph/PcrGroupingTool"><button>github</button></a>
  
  @if(!Auth::guard('user')->check())
  <button onclick="location.href='{{ url("register") }}'">注册</button>
  <button onclick="location.href='{{ url("login") }}'">登录</button>
  @endif

</div>

<!-- 页面底部的提示文字 -->
<p class="hint-text">每月会战前一天更新作业数据</p>
<!-- <p class="hint-text">400/800/1800公会招人,群【585880616】</p> -->
<!-- <p class="hint-text">国服600会招人,群【585880616】</p> -->

</body>
</html>

<script src="/layuiadmin/layui/layui.js"></script>
<script src="/layuiadmin/jquery-3.4.1.min.js"></script>

<script type="text/javascript">
  function isMobileDevice() {
      return /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  }

  if (isMobileDevice()) {
    var width_rank = '100%';
    var width_teach = '100%';
  } else {
    var width_rank = '60%';
    var width_teach = '40%';
  }

  layui.use(function () {

    $('#firstDayHomeWork').click(function () {
      $.get("{{ url('/get_team_num') }}", {type:3}, function(num) {
        if (num == 1) {
          layer.msg('仅限注册用户使用!');
        } else {
          var url = "{{ url('/user/account/list') }}";
          window.location.href=url;
        }
      });
    });

    $('#subscribe').click(function () {
      const uid = "{{ $uid }}";
      if (uid) {
        var url = "{{ url('subscribe') }}";
        layer.open({
          type: 2
          ,title: '作业订阅'
          ,content: url
          ,maxmin: true
          ,area: ['80%', '60%']
        });
      } else {
        layer.msg('请注册后使用');
      }
    });

    $('#rank').click(function () {
      var url = "{{ url('rank_image') }}";
      layer.open({
        type: 2
        ,title: '推荐rank 仅供参考'
        ,content: url
        ,maxmin: true
        ,area: [width_rank, '100%']
      });
    });

    $('#bug').click(function () {
      var url = "{{ url('bug') }}";
      layer.open({
        type: 2
        ,title: 'bug反馈'
        ,content: url
        ,maxmin: true
        ,area: [width_rank, '100%']
      });
    });

    $('#teach').click(function () {
      var url = "{{ url('teach') }}";
      layer.open({
        type: 2
        ,title: '使用教程'
        ,content: url
        ,maxmin: true
        ,area: [width_teach, '100%']
      });
    });

    $('#support').click(function () {
      var url = "{{ url('support') }}";
      layer.open({
        type: 2
        ,title: '支持作者'
        ,content: url
        ,maxmin: true
        ,area: [width_teach, '100%']
      });
    });
  });

  $.get("{{ url('/guide/get_data') }}", {type:2}, function(res) {
    var obj = JSON.parse(res);
    if (obj.status) {
      let data = obj.data;
      let html = '';
      for(const key in data) {
        html += '<a href="' + data[key].url + '" target="_blank"><button>' + data[key].title + '</button></a>';
      }
      $('#rank_button').html(html);
    }
  });

</script>
