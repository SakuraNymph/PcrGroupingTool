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
                  <button class="layui-btn layui-btn-primary" data-action="getVideoImage">getVideoImage</button>
                  </div>
              </div>
              <div class="layui-col-md1">
                <div class="layui-input-inline">
                  <button class="layui-btn layui-btn-primary" data-action="getData">getData</button>
                  </div>
              </div>
              <div class="layui-col-md1">
                  <div class="layui-input-inline">
                    <a href="{{ url('download_excel') }}?is_Chinese=0">
                      <button class="layui-btn layui-btn-primary" data-action="add">导出excel</button>
                    </a>
                  </div>
              </div>

              <div class="layui-col-md2">
                  <div class="layui-input-inline">
                    <a href="{{ url('download_excel') }}?is_Chinese=1">
                      <button class="layui-btn layui-btn-primary" data-action="add3">导出excel中文</button>
                    </a>
                  </div>
              </div>
                
              <form class="layui-form layui-form-pane" action="">

                        <div class="layui-col-md1">
                          <div class="layui-input-inline">
                            <input type="text" name="month" class="layui-input" id="ID-laydate-type-month" placeholder="年-月">
                          </div>
                        </div>
                        <!-- <div class="layui-col-md1">
                          <select name="type" lay-search>
                            <option value="">分区</option>
                          </select>
                        </div> -->
                        <div class="layui-col-md2">
                            <div class="layui-input-inline">
                                <input type="text" name="name" autocomplete="off" class="layui-input" placeholder="请输入名称">
                            </div>
                        </div>
                          <button type="submit" class="layui-btn layui-btn-primary" lay-submit  lay-filter="data-search-btn"><i class="layui-icon"></i> 搜 索</button>
              </form>
            </div>
        <div class="layui-card-body">
          <table class="layui-table" id="tree-table" lay-filter="tree-table"></table>
          <script type="text/html" id="test-table-toolbar-toolbarDemo">
            <div class="layui-btn-container">
              <button class="layui-btn layui-btn-primary layui-btn-sm layui-btn-disabled" lay-event="on" chuang-data-url="{{ url('user/user/status') }}"><i class="layui-icon layui-icon-ok"></i>开启</button>
              <button class="layui-btn layui-btn-primary layui-btn-sm layui-btn-disabled" lay-event="off" chuang-data-url="{{ url('user/user/status') }}"><i class="layui-icon layui-icon-close"></i>关闭</button>
              <button class="layui-btn layui-btn-primary layui-btn-sm layui-btn-disabled" lay-event="delete" chuang-data-url="{{ url('user/user/delete') }}"><i class="layui-icon layui-icon-delete"></i>删除</button>
            </div>
          </script>
          <script type="text/html" id="test-table-toolbar-barDemo">
            <a class="layui-btn layui-btn-primary layui-btn-xs" lay-event="edit"><i class="layui-icon">&#xe642;</i></a>
            <a class="layui-btn layui-btn-primary layui-btn-xs" lay-event="del"><i class="layui-icon">&#xe640;</i></a>
            <button class="layui-btn layui-btn-primary" data-action="add">添加菜单</button>
          </script>
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
}).use(['index', 'table', 'form', 'table', 'laydate'], function(){
  var admin = layui.admin
  ,table = layui.table
  ,form = layui.form
  ,laydate = layui.laydate
  ,table = layui.table;


  // 年月选择器
  laydate.render({
    elem: '#ID-laydate-type-month',
    type: 'month'
  });



  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });

  // 渲染表格
  table.render({
      elem: '#tree-table',
      url: "{{ url('youtube_list') }}",
      method: 'post',
      toolbar: '#toolbarDemo',
      defaultToolbar: ['filter', 'exports', 'print', {
        title: '提示',
        layEvent: 'LAYTABLE_TIPS',
        icon: 'layui-icon-tips'
      }],

      cols: [[
          {title:'序号', type:'numbers',  width: 80}
         ,{field:'title', title:'名称', align: 'center', minWidth:180}
         ,{field:'country', title:'注册地', align: 'center'}
         ,{field:'view_count', title:'播放总量', align: 'center', minWidth:120, sort:true}
         ,{field:'view_count_increase', title:'上月播放增量', align: 'center', minWidth:120, sort:true}
         ,{field:'subscriber_count', title:'订阅总量', align: 'center', minWidth:120, sort:true}
         ,{field:'subscriber_count_increase', title:'上月订阅增量', align: 'center', minWidth:120, sort:true}
         ,{field:'video_count', title:'视频总量', align: 'center', minWidth:120, sort:true}
         ,{field:'video_count_increase', title:'上月视频增量', align: 'center', minWidth:120, sort:true}
         ,{field:'avg_count', title:'上月平均观看量', align: 'center', minWidth:120, sort:true}
          // ,{field:'ip', title:'IP', width:150}
          // ,{field:'createtime', title:'时间', align: 'center', sort: true}
        ]],
      page: true
  });

  // 监听搜索操作
  form.on('submit(data-search-btn)', function (data) {
    // console.log(data);
    // return false;
      //执行搜索重载
      table.reload('tree-table', {
          page: {
              curr: 1
          }
          , where: {
              month: data.field.month,
              name: data.field.name
          }
      }, 'data');
      return false;
  });



  $('button[data-action=getData]').click(function() {
    let url = "{{ url('dddd') }}";
    makeAjaxRequest(url);
  });

  $('button[data-action=getVideoImage]').click(function() {
    let url = "{{ url('get_video_image') }}";
    makeAjaxRequest(url);
  });



  function makeAjaxRequest($url) {
    $.ajax({
        url: $url,
        method: 'GET',
        // async: false,
        success: function(data) {
            // 处理返回的数据
            let obj = JSON.parse(data);
            if (obj.status) {
              console.log(obj.result.message);
              makeAjaxRequest($url);
            } else {
              console.log('End');
              return false;
            }
            // 在成功获取数据后再次发起下一次ajax请求
            // setTimeout(makeAjaxRequest, 1000); // 1000毫秒后再次执行makeAjaxRequest函数
        },
        error: function(xhr, status, error) {
            // 处理错误情况
            // console.error(status, error);
        }
    });
}

  form.on('switch(switchTest)', function(obj){

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.post("{{ url('nav/name/status') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
      var obj = JSON.parse(res);
      if (obj.status) {
        // table.reload('tree-table');
        // if (obj.result.message == 'on') {
        //   a.removeClass('layui-btn-danger');
        //   a.addClass('layui-btn-normal');
        //   i.removeClass('layui-icon-close');
        //   i.addClass('layui-icon-ok');
        // } else {
        //   a.removeClass('layui-btn-normal');
        //   a.addClass('layui-btn-danger');
        //   i.removeClass('layui-icon-ok');
        //   i.addClass('layui-icon-close');
        // }
      } else {
        layer.msg(obj.result.message);
      }
    });
    
    // layer.msg('checked 状态: '+ elem.checked);
  });

  

  table.on('tool(tree-table)', function(obj){
      var data = obj.data;  // 获得当前行数据
      var event = obj.event; // 获得lay-event对应的值



      if (event === 'up') {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.post("{{ url('nav/photo/up') }}", {id: data.id}, function(res) {
        var obj = JSON.parse(res);
        if (obj.status) {
          table.reload('tree-table');
          // if (obj.result.message == 'on') {
          //   a.removeClass('layui-btn-danger');
          //   a.addClass('layui-btn-normal');
          //   i.removeClass('layui-icon-close');
          //   i.addClass('layui-icon-ok');
          // } else {
          //   a.removeClass('layui-btn-normal');
          //   a.addClass('layui-btn-danger');
          //   i.removeClass('layui-icon-ok');
          //   i.addClass('layui-icon-close');
          // }
        } else {
          layer.msg(obj.result.message);
        }
      });
      }

      if (event === 'down') {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        $.post("{{ url('nav/photo/down') }}", {id: data.id}, function(res) {
        var obj = JSON.parse(res);
        if (obj.status) {
          table.reload('tree-table');
          // if (obj.result.message == 'on') {
          //   a.removeClass('layui-btn-danger');
          //   a.addClass('layui-btn-normal');
          //   i.removeClass('layui-icon-close');
          //   i.addClass('layui-icon-ok');
          // } else {
          //   a.removeClass('layui-btn-normal');
          //   a.addClass('layui-btn-danger');
          //   i.removeClass('layui-icon-ok');
          //   i.addClass('layui-icon-close');
          // }
        } else {
          layer.msg(obj.result.message);
        }
      });
      }

      if (event === 'edit') {
        var url = "{{ url('nav/photo/edit') }}" + "?id=" + data.id;
        // console.log(data);
        layer.open({
          type: 2
          ,title: '修改'
          ,content: url
          ,maxmin: true
          ,area: ['100%', '100%']
          ,btn: ['确定', '取消']
          ,yes: function(index, layero){
            //点击确认触发 iframe 内容中的按钮提交
            var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
            submit.click();
          }
        });
      }

      if (event === 'status') {

        var a = $(this);
        var i = $(this.children);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.post("{{ url('nav/name/status') }}", {id: data.id}, function(res) {
          var obj = JSON.parse(res);
          if (obj.status) {
            table.reload('tree-table');
            // if (obj.result.message == 'on') {
            //   a.removeClass('layui-btn-danger');
            //   a.addClass('layui-btn-normal');
            //   i.removeClass('layui-icon-close');
            //   i.addClass('layui-icon-ok');
            // } else {
            //   a.removeClass('layui-btn-normal');
            //   a.addClass('layui-btn-danger');
            //   i.removeClass('layui-icon-ok');
            //   i.addClass('layui-icon-close');
            // }
          } else {
            layer.msg(obj.result.message);
          }
        });
      }

      if (event === 'del') {
        layer.confirm('真的要删除吗?', {icon: 3, title: '删除'}, function(index) {
          // console.log($id);
          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              }
          });

          $.post("{{ url('nav/photo/delete') }}", {id: data.id}, function(res) {
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

      if (event === 'del2') {
        layer.confirm('真的要删除该分类吗?', {icon: 3, title: '删除分类'}, function(index) {
          // console.log($id);
          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              }
          });

          $.post("{{ url('book/type/delete') }}", {id: data.id}, function(res) {
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
  });
});
</script>
@endsection


