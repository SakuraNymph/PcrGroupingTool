<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="/layuiadmin/layui/css/layui.css" media="all">
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




</style>
</head>
<body>

<div class="button-container">
  
  <!-- <button onclick="location.href='{{ url("team") }}'">分刀工具</button> -->
  
  <h3>您的花凛星级是?</h3>
  <br>
  <br>
  <br>
  <div class="layui-form">
    <div class="layui-form-item">
      <input type="radio" name="star" value="1" lay-filter="toupiao" title="1星" checked>
      <input type="radio" name="star" value="3" lay-filter="toupiao" title="3星" > 
      <!-- <input type="radio" name="AAA" value="3" title="禁用" disabled>  -->
    </div>
    <div class="layui-form-item">
      <!-- <div class="layui-input-block"> -->
        <button type="button" class="layui-btn layui-bg-blue" id="submit" lay-submit lay-filter="toupiao">提交</button>
        <!-- <input type="button" lay-submit lay-filter="layuiadmin-app-form-submit" id="layuiadmin-app-form-submit" value="确认添加"> -->
        <!-- <input type="button" lay-submit lay-filter="layuiadmin-app-form-edit" id="layuiadmin-app-form-edit" value="确认编辑"> -->
      <!-- </div> -->
    </div>
  </div>
  <br>
  <br>
  <br>
  <br>
</div>



</body>
</html>
<script src="/layuiadmin/layui/layui.js"></script>
<script src="/layuiadmin/jquery-3.4.1.min.js"></script>

<script type="text/javascript">
  layui.use(['form'], function () {
    var form = layui.form;
    $('#rank').click(function () {
      var url = "{{ url('rank_image') }}";
        // console.log(data);
        layer.open({
          type: 2
          ,title: '推荐rank 仅供参考'
          ,content: url
          ,maxmin: true
          ,area: ['100%', '100%']
        });
    });


    form.on('submit(toupiao)', function(data) {
      var field = data.field; //获取提交的字段
      // console.log(field);
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post("{{ url('toupiao') }}", {star:field.star}, function(res) {
        var obj = JSON.parse(res);
        if (obj.status) {
          layer.msg('成功', {icon:1,time:1000}, function () {
            // getTeams(bossId);
          });
        } else {
          layer.msg(obj.result.message);
        }
      });
    });
  });
</script>
