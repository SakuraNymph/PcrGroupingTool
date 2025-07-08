@extends('main')
@section('content')
<style type="text/css">

img {
  width: 50px;
  height: 50px;
}


</style>


<div class="layui-card-body layui-row layui-col-space10 layui-form-item">
    <div class="layui-btn-group">
      <button type="button" class="layui-btn layui-bg-blue" data-action="add_author">添加作者</button>
      <button type="button" class="layui-btn layui-bg-blue" data-action="add">添加角色</button>
      <button type="button" class="layui-btn layui-bg-blue" data-action="res">查看结果</button>
    </div>
    <div class="layui-btn-group">
      @foreach($authors as $author)
        <button type="button" class="layui-btn layui-bg-red" data-author="{{ $author['id'] }}" data-action="delete_author">{{ $author['name'] }}<i class="layui-icon layui-icon-delete"></i></button>
      @endforeach
    </div>
</div>

  <div class="layui-form" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list" >



    <div class="layui-bg-gray" style="padding: 16px;">
      <div class="layui-row layui-col-space15">
        @foreach($map as $key => $val)
          <div class="layui-col-md{{ $num }}">
            <div class="layui-card">
              <div class="layui-card-header">
                @foreach($authors as $author)
                  @if($author['id'] == $key) <h1>{{ $author['name'] }}</h1> @endif
                @endforeach
              </div>
              <div class="layui-card-body">
                @foreach($val as $v)
                  <fieldset class="layui-elem-field">
                    <legend>
                      <button type="button" class="layui-btn layui-bg-blue" data-action="edit" id="{{$v['id']}}">修改</button>
                      <button type="button" class="layui-btn layui-bg-red" data-action="delete" id="{{$v['id']}}">删除</button>
                      {{$v['rank_name']}}
                    </legend>
                    <div class="layui-field-box">
                      @foreach($v['role_ids'] as $role_id)
                        <img src="{{ asset('images') }}/{{$role_id}}.webp">
                      @endforeach
                    </div>
                  </fieldset>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>


    <div class="layui-form-item">
      <div class="layui-input-block">
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


  $('button[data-action=add_author]').click(function() {
    layer.open({
      type: 2
      ,title: '添加作者'
      ,content: "{{ url('add_author') }}?"
      ,maxmin: true
      ,area: ['40%', '30%']
      // ,btn: ['确定', '取消']
      ,yes: function(index, layero){
        //点击确认触发 iframe 内容中的按钮提交
        var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
        submit.click();
      }
    });
  });
  $('button[data-action=add]').click(function() {
    $.get("{{ url('get_author') }}", function(data) {
      let obj = JSON.parse(data);
      if (obj.status) {
        layer.open({
          type: 2
          ,title: '添加rank'
          ,content: "{{ url('add') }}"
          ,maxmin: true
          ,area: ['60%', '100%']
          // ,btn: ['确定', '取消']
          ,yes: function(index, layero){
            //点击确认触发 iframe 内容中的按钮提交
            var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
            submit.click();
          }
        });
      } else {
        layer.msg('请先添加作者！');
      }
    });
  });
  $('button[data-action=edit]').click(function() {
      const id = $(this).attr('id');
      // console.log(id);return false;
    layer.open({
      type: 2
      ,title: '修改rank'
      ,content: "{{ url('edit') }}?id="+id
      ,maxmin: true
      ,area: ['60%', '100%']
      // ,btn: ['确定', '取消']
      ,yes: function(index, layero){
        //点击确认触发 iframe 内容中的按钮提交
        var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
        submit.click();
      }
    });
  });
  $('button[data-action=delete]').click(function() {
      const id = $(this).attr('id');
      // console.log(id);return false;
      layer.confirm('真的要删除吗?', {icon: 3, title: '删除'}, function(index) {
        // console.log($id);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.post("{{ url('delete') }}", {id: id}, function(res) {
          var obj = JSON.parse(res);
          if (obj.status) {
            layer.msg('删除成功');
            // table.reload('tree-table');
            window.parent.location.reload();
          } else {
            layer.msg(obj.result.message);
          }
          // console.log(obj);
        });
        layer.close(index);
      });
  });
  $('button[data-action=res]').click(function() {
    layer.open({
      type: 2
      ,title: '查看结果'
      ,content: "{{ url('result') }}"
      ,maxmin: true
      ,area: ['52%', '100%']
      // ,btn: ['确定', '取消']
      ,yes: function(index, layero){
        //点击确认触发 iframe 内容中的按钮提交
        var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
        submit.click();
      }
    });
  });
  $('button[data-action=delete_author]').click(function() {
      const author_id = $(this).data('author');
      layer.confirm('确定要删除作者和rank吗?', {icon: 3, title: '删除'}, function(index) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post("{{ url('delete_author') }}", {id: author_id}, function(res) {
          let obj = JSON.parse(res);
          if (obj.status) {
            layer.msg('删除成功');
            window.parent.location.reload();
          } else {
            layer.msg(obj.result.message);
          }
        });
        layer.close(index);
      });
  });

  })
  </script>
@endsection