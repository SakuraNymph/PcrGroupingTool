@extends('main')
@section('content')
<style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        background-color: #f2f2f2;
    }
    .container {
        text-align: center;
    }
    .hint {
        margin-top: 80px; /* 调整这里的值增加间距 */
        font-size: 14px;
        color: #666;
    }
</style>
  <div class="layui-form layui-form-pane" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list" style="padding: 20px 30px 0 30px;">



    


    <div class="container">
      <label class="layui-form-label">提醒时段</label>
      <div class="layui-input-inline">
        <input type="text" class="layui-input" id="period-of-time" @if($start && $end) value="{{ $start }} - {{ $end }}"  @endif placeholder=" - ">
      </div>
      <div class="layui-input-inline">
        <input type="checkbox" name="subscribe" lay-skin="switch" lay-text="开启|关闭" id="switch" @if($status) checked @endif></br>
      </div>
        <div class="hint">开启后,当有作业更新时,会以邮件的方式提醒</div>
    </div>



    

    <div class="layui-form-item layui-hide">
      <input type="button" lay-submit lay-filter="layuiadmin-app-form-submit" id="layuiadmin-app-form-submit" value="确认添加">
      <input type="button" lay-submit lay-filter="layuiadmin-app-form-edit" id="layuiadmin-app-form-edit" value="确认编辑">
    </div>
  </div>
@endsection
@section('js')
  <script>
  layui.config({
    base: '/layuiadmin/' //静态资源所在路径
  }).extend({
    index: 'lib/index' //主入口模块
  }).use(['index', 'form'], function(){
    var $ = layui.$
    ,form = layui.form
    ,laydate = layui.laydate;



    // 时间范围
    laydate.render({
      elem: '#period-of-time',
      type: 'time',
      id: 'period-of-time',
      range: true,
      done: function(value, date, endDate) {
        subscribe(value);
      }
    });


    function subscribe(timeRange) {
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post("{{ url('subscribe') }}", {time:timeRange}, function(res) {
        var obj = JSON.parse(res);
        layer.msg(obj.msg);
      });
    }




    form.on('switch()', function(obj){
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post("{{ url('subscribe') }}", function(res) {
        var obj = JSON.parse(res);
        layer.msg(obj.msg);
      });

    });
  })
  </script>
@endsection