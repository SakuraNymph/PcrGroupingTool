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
  
  

  <button id="rank">推荐rank</button>
  <!-- <button id="teach">使用教程</button> -->
  <!-- <button id="support">支持作者</button> -->


</div>

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
      // console.log("这是移动设备");
  } else {
    var width_rank = '60%';
    var width_teach = '40%';
      // console.log("这是PC设备");
  }

  layui.use(function () {
    $('#rank').click(function () {
      var url = "{{ url('aaaa') }}";
      $.get(url, function (res) {
        console.log(res);
      });
    });

    $('#teach').click(function () {
      var url = "{{ url('teach') }}";
        // console.log(data);
        layer.open({
          type: 2
          ,title: '使用教程 交流群943379496'
          ,content: url
          ,maxmin: true
          ,area: [width_teach, '100%']
        });
    });

    $('#support').click(function () {
      var url = "{{ url('support') }}";
        // console.log(data);
        layer.open({
          type: 2
          ,title: '支持作者 交流群943379496'
          ,content: url
          ,maxmin: true
          ,area: [width_teach, '100%']
        });
    });
  });
</script>
