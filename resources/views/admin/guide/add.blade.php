@extends('main')
@section('content')
  <div class="layui-form layui-form-pane" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list" style="padding: 20px 30px 0 30px;">



    

    <div class="layui-form-item">
      <label class="layui-form-label">标题</label>
      <div class="layui-input-block">
        <input type="text" name="title" lay-verify="required" placeholder="请输入标题" autocomplete="off" class="layui-input" @if(isset($data->title)) value="{{ $data->title }}" @endif>
      </div>
    </div>

    <div class="layui-form-item">
      <label class="layui-form-label">链接</label>
      <div class="layui-input-block">
        <input type="text" name="url" lay-verify="required" placeholder="请输入链接" autocomplete="off" class="layui-input" @if(isset($data->url)) value="{{ $data->url }}" @endif>
      </div>
    </div>


    <div style="display: flex; justify-content: center; align-items: center; height: 100px; border: 0px solid #000;">
        <span style="color: red">*</span><p style="margin: 0;">审核后展示</p>
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
    ,form = layui.form;


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });



    //监听提交
    form.on('submit(layuiadmin-app-form-submit)', function(data){
      var field = data.field; //获取提交的字段
      // console.log(field);
      // return false;


      var id = "{{ $id }}";

      if (id == '0') {

        var post_url = "{{ url('/guide/add') }}";

      } else {

        var post_url = "{{ url('/admin/guide/edit') }}";
      }

      $.post(post_url,
        {
          title: field.title,
          url: field.url,
          id: id
        }, function(data) {
        var obj = JSON.parse(data);
        if (obj.status) {
          var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
            if (id == '0') {
              parent.layer.msg('添加成功');
            } else {
              parent.layer.msg('修改成功');
            }
            parent.layui.table.reload('tree-table'); //重载表格
            parent.layer.close(index); //再执行关闭
        } else {
          layer.msg(obj.msg);
          return false;
        }
      });
    });
  })
  </script>
@endsection