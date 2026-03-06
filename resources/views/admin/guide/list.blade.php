@extends('main')
@section('content')
<style type="text/css">
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

                <div class="layui-col-md2">
                    <div class="layui-input-inline">
                      <button class="layui-btn layui-btn-primary" data-action="add">添加攻略</button>
                    </div>
                </div>

                <form class="layui-form layui-form-pane" action="">
                          <div class="layui-col-md1">
                            <select name="status" lay-search>
                              <option value="">状态</option>
                              <option value="0">隐藏</option>
                              <option value="1">展示</option>
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
                                  <input type="text" name="title" autocomplete="off" class="layui-input" placeholder="请输入名称">
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

  function isMobileDevice() {
      return /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  }

  // 渲染表格
  table.render({
      elem: '#tree-table',
      url: "{{ url('/admin/guide/list') }}",
      method: 'post',
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      cols: [[
          {title:'序号', type:'numbers',  width: 80}
         ,{field:'title', title:'标题', align: 'center'}
         ,{field:'url', title:'链接地址', align: 'center', width: 120, templet: function(data) {
          return '<a href="' + data.url + '" target="_blank">点击查看</a>';
         }}
         ,{field:'sort', title:'排序', align: 'center', width:50}
          ,{field:'status', title:'状态', align: 'center', width:100, templet: function(data) {
          if (data.status) {
            return '<input type="checkbox" lay-event="status" switchId='+data.id+' checked name="status" lay-skin="switch" lay-filter="status" lay-text="显示|隐藏">';
          } else {
            return '<input type="checkbox" lay-event="status" switchId='+data.id+' name="status" lay-skin="switch" lay-filter="status" lay-text="显示|隐藏">';
          }
        }}
          ,{field:'type', title:'分类', align: 'center', minWidth: 120, templet: function(data) {
          let arr = ['', '推荐页', '主页'];
          let html = '';
          for (var i = 1; i < arr.length; i++) {
            if (data.type == i) {
              html += '<input type="radio" data-id="'+data.id+'" name="type'+data.id+'" value="'+i+'" title="'+arr[i]+'" checked>';
            } else {
              html += '<input type="radio" data-id="'+data.id+'" name="type'+data.id+'" value="'+i+'" title="'+arr[i]+'">';
            }
          }
          return html;
        }}
        ,{field:'created_at', title:'创建时间', align: 'center', minWidth:120}
        ,{field:'updated_at', title:'修改时间', align: 'center', minWidth:120}
        ,{title:'操作', align: 'center', minWidth: 120, templet: function(data) {
          let html = '';
          html += '<button type="button" lay-event="edit" class="layui-btn layui-btn-primary layui-bg-orange layui-btn-sm">修改</button>';
          html += '<button type="button" lay-event="delete" class="layui-btn layui-btn-primary layui-bg-red layui-btn-sm">删除</button>';
          return html;
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
              title: data.field.title,
              status: data.field.status,
              is_6: data.field.is_6,
              is_ghz: data.field.is_ghz
          }
      }, 'data');
      return false;
  });

  form.on('switch(status)', function(obj){

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.post("{{ url('/admin/guide/status') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
      var obj = JSON.parse(res);
      if (obj.status) {
        layer.msg('Success');
      } else {
        layer.msg(obj.msg);
      }
    });
  });

  form.on('radio()', function(data){
    var elem = data.elem; // 获得 radio 原始 DOM 对象
    var checked = elem.checked; // 获得 radio 选中状态
    var value = elem.value; // 获得 radio 值
    var othis = data.othis; // 获得 radio 元素被替换后的 jQuery 对象
    var id = elem.dataset.id;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post("{{ url('/admin/guide/type') }}", {id:id, type:value}, function(res) {
      console.log(res);
    });
    // layer.msg(['value: '+ value, 'checked: '+ checked].join('<br>'));
  });



  let width_add = '60%';
  let hight_add = '40%';

  if (isMobileDevice()) { width_add = '100%'; hight_add = '40%'; }

  $('button[data-action=add]').click(function() {
    layer.open({
      type: 2
      ,title: '添加攻略'
      ,content: "{{ url('/guide/add') }}?"
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

  table.on('tool(tree-table)', function(obj) {
    var data = obj.data;  // 获得当前行数据
    var event = obj.event; // 获得lay-event对应的值

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    if (event === 'edit') {
      var url = "{{ url('/admin/guide/edit') }}" + "?id=" + data.id;
      layer.open({
        type: 2
        ,title: '修改'
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
    }

    if (event === 'delete') {
      var url = "{{ url('/admin/guide/delete') }}" + "?id=" + data.id;
      layer.confirm('确定要删除该吗?', {icon: 3, title: '删除'}, function(index) {
        $.post("{{ url('admin/guide/delete') }}", {id: data.id}, function(res) {
          var obj = JSON.parse(res);
          if (obj.status) {
            layer.msg('删除成功');
            table.reload('tree-table');
            // window.parent.location.reload();
          } else {
            layer.msg(obj.result.message);
          }
          // console.log(obj);
        });
        layer.close(index);
      });
    }
  })

});
</script>
@endsection


