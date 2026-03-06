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
    height: 1500px;
    flex-direction: column;
  }

  .button-container {
    text-align: center;
  }

  .button-container button {
    display: block;
    width: 100%;
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

</div>



</body>
</html>

<script src="/layuiadmin/layui/layui.js"></script>
<script src="/layuiadmin/jquery-3.4.1.min.js"></script>

<script type="text/javascript">

  function isMobileDevice() {
      return /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  }


  let width_add = '60%';
  let hight_add = '50%';

  if (isMobileDevice()) { width_add = '100%'; hight_add = '50%'; }

  $.get("{{ url('/guide/get_data') }}", {type:1}, function(res) {
    var obj = JSON.parse(res);
    if (obj.status) {
      let data = obj.data;
      let html = '';
      for(const key in data) {
        html += '<a href="' + data[key].url + '" target="_blank"><button>' + data[key].title + '</button></a>';
      }
      html += '<button id="add">欢迎补充</button>';
      $('.button-container').html(html);
    }
  });


  // 使用事件委托
  $('.button-container').on('click', '#add', function() {
    var url = "{{ url('/guide/add') }}";
    layer.open({
      type: 2
      ,title: '添加'
      ,content: url
      ,maxmin: true
      ,area: [width_add, hight_add]
      ,btn: ['确定', '取消']
      ,yes: function(index, layero){
        //点击确认触发 iframe 内容中的按钮提交
        var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
        submit.click();
      }
    });
  });

</script>
