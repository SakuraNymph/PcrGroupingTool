@extends('main')
@section('content')
<style type="text/css">
.layui-table-cell {
  height: auto;
  line-height: 30px;
}
</style>
<div class="layui-fluid">
  <div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
      <div class="layui-card">
            <div class="layui-card-body layui-row layui-col-space10 layui-form-item">
                <div class="layui-col-md1">
                    <div class="layui-input-inline">
                    </div>
                </div>

                <div class="layui-col-md3">
                    <div class="layui-input-inline">
                    </div>
                </div>

                <form class="layui-form layui-form-pane" action="">
                          <div class="layui-col-md1">
                            <select name="position" lay-search>
                              <option value="">位置</option>
                              <option value="1">前卫</option>
                              <option value="2">中卫</option>
                              <option value="3">后卫</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="is_6" lay-search>
                              <option value="">6★开花</option>
                              <option value="1">是</option>
                              <option value="0">否</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="is_ghz" lay-search>
                              <option value="">会战角色</option>
                              <option value="1">是</option>
                              <option value="0">否</option>
                            </select>
                          </div>
                          <div class="layui-col-md2">
                              <div class="layui-input-inline">
                                  <input type="text" name="nickname" autocomplete="off" class="layui-input" placeholder="请输入名称">
                              </div>
                          </div>
                            <button type="submit" class="layui-btn layui-btn-primary" lay-submit  lay-filter="data-search-btn"><i class="layui-icon"></i> 搜 索</button>
                </form>
            </div>
        <div class="layui-card-body">
          <table class="layui-table" id="tree-table" lay-filter="tree-table"></table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')

<script>
layui.config({
  base: '/layuiadmin/' //静态资源所在路径
}).extend({
  index: 'lib/index' //主入口模块
}).use(['index', 'table', 'form'], function(){
  var index = layui.index
  ,form = layui.form
  ,table = layui.table;

  // 渲染表格
  table.render({
      elem: '#tree-table',
      url: "{{ url('list') }}",
      method: 'post',
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      cols: [[
          {title:'序号', type:'numbers',  width: 80}
         ,{field:'name', title:'昵称', align: 'center', minWidth:120}
         ,{field:'role_id', title:'缩略图', align: 'center', minWidth: 120, templet: function(data) {
            return '<img style="height:50px" src="/images/'+ data.role_id +'.webp" title='+ data.role_id +'  class="layui-upload-img">';
          }}
          ,{field:'magic_atk', title:'输出角色', align: 'center', templet: function(data) {
          if (data.magic_atk) {
            return '<input type="checkbox" lay-event="magic_atk" switchId='+data.id+' checked name="magic_atk" lay-skin="switch" lay-filter="magic_atk" lay-text="是|否">';
          } else {
            return '<input type="checkbox" lay-event="magic_atk" switchId='+data.id+' name="magic_atk" lay-skin="switch" lay-filter="magic_atk" lay-text="是|否">';
          }
        }}
          ,{field:'is_6', title:'是否6星开花', align: 'center', templet: function(data) {
          if (data.is_6) {
            return '<input type="checkbox" lay-event="is_6" switchId='+data.id+' checked name="is_6" lay-skin="switch" lay-filter="is_6" lay-text="是|否">';
          } else {
            return '<input type="checkbox" lay-event="is_6" switchId='+data.id+' name="is_6" lay-skin="switch" lay-filter="is_6" lay-text="是|否">';
          }
        }}
        ,{field:'is_ghz', title:'会战角色', align: 'center', templet: function(data) {
          if (data.is_ghz) {
            return '<input type="checkbox" lay-event="is_ghz" switchId='+data.id+' checked name="is_ghz" lay-skin="switch" lay-filter="is_ghz" lay-text="是|否">';
          } else {
            return '<input type="checkbox" lay-event="is_ghz" switchId='+data.id+' name="is_ghz" lay-skin="switch" lay-filter="is_ghz" lay-text="是|否">';
          }
        }}
        ]],
      page: true
  });

  // 监听搜索操作
  form.on('submit(data-search-btn)', function (data) {
      //执行搜索重载
      table.reload('tree-table', {
          page: {
              curr: 1
          }
          , where: {
              nickname: data.field.nickname,
              position: data.field.position,
              is_6: data.field.is_6,
              is_ghz: data.field.is_ghz
          }
      }, 'data');
      return false;
  });

  form.on('switch(is_6)', function(obj){

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.post("{{ url('is_6') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
      var obj = JSON.parse(res);
      if (obj.status) {
      } else {
        layer.msg(obj.result.message);
      }
    });
  });

  form.on('switch(magic_atk)', function(obj){

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.post("{{ url('magic_atk') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
      var obj = JSON.parse(res);
      if (obj.status) {
      } else {
        layer.msg(obj.result.message);
      }
    });
  });

  form.on('switch(is_ghz)', function(obj){

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.post("{{ url('is_ghz') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
      var obj = JSON.parse(res);
      if (obj.status) {
      } else {
        layer.msg(obj.result.message);
      }
    });
  });

});
</script>
@endsection


