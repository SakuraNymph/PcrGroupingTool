@extends('main')
@section('content')
<style type="text/css">



img {
  width: 50px;
  height: 50px;
}

.role{
  opacity: 0.6;
}




</style>

  <div class="layui-form" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list" style="padding: 20px 30px 0 0;">




    <div class="layui-form-item">
      <label class="layui-form-label">作者名称</label>
      <div class="layui-input-block">
        <div class="layui-col-md6">
          <input type="text" name="author" placeholder="请填写作者名称" lay-verify="required" class="layui-input" value="">
        </div>
      </div>
    </div>



    <div class="layui-form-item">
      <div class="layui-input-block">
        <button type="button" class="layui-btn layui-bg-blue" id="layuiadmin-app-form-submit" lay-submit lay-filter="layuiadmin-app-form-submit">点击保存</button>
      </div>
    </div>
  </div>
@endsection
@section('js')

<script type="text/javascript">



        


</script>
  <script>
  layui.config({
    base: '/layuiadmin/' //静态资源所在路径
  }).extend({
    index: 'lib/index' //主入口模块
  }).use(['index', 'form'], function(){
    var index = layui.index
    ,form = layui.form;



    //监听提交
    form.on('submit(layuiadmin-app-form-submit)', function(data){
      var field = data.field; //获取提交的字段
      var post_url = "{{ url('add_author') }}";
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post(post_url,
        {
          author: field.author,
        }, function(data) {
        var obj = JSON.parse(data);
        if (obj.status) {
          layer.msg('添加成功');
          var index = parent.layer.getFrameIndex(window.name); // 先得到当前 iframe 层的索引
          parent.layer.close(index); // 再执行关闭


          // var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
            // parent.layui.table.reload('LAY-app-content-list'); //重载表格
            window.parent.location.reload();

            // parent.layer.close(index); //再执行关闭
            // parent.layer.msg('添加成功');
          // renderTable(obj.result);
        } else {
          layer.msg(obj.result.message);
          return false;
        }
      });
    });
  })
  </script>
@endsection