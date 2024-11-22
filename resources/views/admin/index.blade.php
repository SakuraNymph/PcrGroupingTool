<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</style>
</head>
<body>

<div class="button-container">
  
  <button onclick="location.href='{{ url("admin/team/get_boss_list") }}'">boss列表</button>
  <button onclick="location.href='{{ url("admin/team/list") }}'">作业列表</button>
  <button onclick="location.href='{{ url("admin/team/get_boss_images") }}'">获取boss数据</button>
  @if(!Auth::guard('admin')->check())
  <!-- <button onclick="location.href='{{ url("register") }}'">注册</button> -->
  <!-- <button onclick="location.href='{{ url("admin/login") }}'">登录</button> -->
  @endif
</div>

</body>
</html>
