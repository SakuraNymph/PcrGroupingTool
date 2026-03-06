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
                <div class="layui-col-md0">
                    <div class="layui-input-inline">
                    </div>
                </div>

                <div class="layui-col-md1">
                    <div class="layui-input-inline">
                    </div>
                </div>

                <form class="layui-form layui-form-pane" action="">
                          <div class="layui-col-md1">
                            <select name="status" lay-search>
                              <option value="">状态</option>
                              <option value="0">未实装</option>
                              <option value="1">已实装</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="element" lay-search>
                              <option value="">属性</option>
                              <option value="1">火属性</option>
                              <option value="2">水属性</option>
                              <option value="3">风属性</option>
                              <option value="4">光属性</option>
                              <option value="5">暗属性</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="atk_type" lay-search>
                              <option value="">攻击类型</option>
                              <option value="-1">魔法</option>
                              <option value="0">辅助</option>
                              <option value="1">物理</option>
                            </select>
                          </div>
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
}).use(['index', 'table', 'form', 'layer'], function(){
  var index = layui.index;
  var form  = layui.form;
  var table = layui.table;
  var layer = layui.layer;
  var $     = layui.$;

  // 全局保存当前表格状态
  var tableConfig = {
      where: {},  // 筛选条件，如 {title: '关键词'}
      page: {curr: 1, limit: 10}  // 当前页码和每页数
  };

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
         ,{field:'obtain', title:'获取方式', align: 'center', templet: function(data) {
            var btnText = '';
            var btnColor = '';
            switch(data.obtain){
                case 1: btnText = '抽卡'; btnColor = 'layui-bg-blue'; break;
                case 2: btnText = '活动'; btnColor = 'layui-bg-orange'; break;
                case 3: btnText = '兑换'; btnColor = 'layui-bg-red'; break;
                // case 4: btnText = '光属性'; btnColor = 'layui-bg-orange'; break;
                // case 5: btnText = '暗属性'; btnColor = 'layui-bg-purple'; break;
                default: btnText = '常驻'; btnColor = 'layui-bg-default'; break;
            }
            return '<button class="layui-btn ' + btnColor + ' layui-btn-radius" lay-event="changeObtain">' + btnText + '</button>';
        }}
         ,{field:'role_id', title:'缩略图', align: 'center', minWidth: 120, templet: function(data) {
            return '<img style="height:50px" src="/images/'+ data.role_id +'.webp" title='+ data.role_id +'  class="layui-upload-img">';
          }}
         ,{field:'element', title:'属性', align: 'center', templet: function(data) {
            var btnText = '';
            var btnColor = '';
            switch(data.element){
                case 1: btnText = '火属性'; btnColor = 'layui-bg-red'; break;
                case 2: btnText = '水属性'; btnColor = 'layui-bg-blue'; break;
                case 3: btnText = '风属性'; btnColor = ''; break;
                case 4: btnText = '光属性'; btnColor = 'layui-bg-orange'; break;
                case 5: btnText = '暗属性'; btnColor = 'layui-bg-purple'; break;
                default: btnText = '未知'; btnColor = 'layui-bg-default'; break;
            }
            return '<button class="layui-btn ' + btnColor + ' layui-btn-radius" lay-event="changeElement">' + btnText + '</button>';
        }}
        //   ,{field:'atk_type', title:'输出类型', align: 'center', templet: function(data) {
        //   if (data.atk_type) {
        //     return '<input type="checkbox" lay-event="atk_type" switchId='+data.id+' checked name="atk_type" lay-skin="switch" lay-filter="atk_type" lay-text="物理|魔法">';
        //   } else {
        //     return '<input type="checkbox" lay-event="atk_type" switchId='+data.id+' name="atk_type" lay-skin="switch" lay-filter="atk_type" lay-text="物理|魔法">';
        //   }
        // }}
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
      page: true,
      done: function(res, curr, count){  // 渲染完成回调，更新分页状态
          tableConfig.page.curr = curr;
          tableConfig.page.limit = this.limit;  // 当前 limit
      }
  });

  // 表格渲染完成后，绑定按钮点击事件
  table.on('tool(tree-table)', function(obj){  // tree-table 是表格的 id（elem: '#tree-table'）
      if(obj.event === 'changeElement'){
          let data = obj.data;  // 获取行数据
          let rowId = data.id;  // 获取 ID
          let currentElement = data.element;  // 获取当前 element

          // 输出到控制台（或用于其他逻辑，如 AJAX 发送到后端）
          // console.log('行 ID: ' + rowId);
          // console.log('当前 element: ' + currentElement);

          // 计算新 element（循环变化）
          let newElement = currentElement + 1;
          if(newElement > 5) newElement = 1;

          // 显示加载
          let index = layer.load(1);

          // AJAX 更新后端（假设接口为 /changeElement，根据你的后端调整）
          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              }
          });

          $.post("{{ url('change_element') }}", {id: rowId, element: newElement}, function(res) {
              layer.close(index);  // 关闭加载
              let result = JSON.parse(res);
              if (result.status) {
                  // 刷新表格，带当前条件
                  reloadTable();
                  // layer.msg('ID ' + rowId + ' 的 element 已更新为 ' + newElement);
              } else {
                  layer.msg(result.result.message || '更新失败');
              }
          }).fail(function(){
              layer.close(index);
              layer.msg('网络错误');
          });
      }

      if(obj.event === 'changeObtain'){
          let data = obj.data;  // 获取行数据
          let rowId = data.id;  // 获取 ID
          let currentObtain = data.obtain;  // 获取当前 obtain

          // 输出到控制台（或用于其他逻辑，如 AJAX 发送到后端）
          // console.log('行 ID: ' + rowId);
          // console.log('当前 obtain: ' + currentObtain);

          // 计算新 obtain（循环变化）
          let newObtain = currentObtain + 1;
          if(newObtain > 3) newObtain = 1;

          // 显示加载
          let index = layer.load(1);

          // AJAX 更新后端（假设接口为 /changeElement，根据你的后端调整）
          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              }
          });

          $.post("{{ url('change_obtain') }}", {id: rowId, obtain: newObtain}, function(res) {
              layer.close(index);  // 关闭加载
              let result = JSON.parse(res);
              if (result.status) {
                  // 刷新表格，带当前条件
                  reloadTable();
                  // layer.msg('ID ' + rowId + ' 的 obtain 已更新为 ' + newObtain);
              } else {
                  layer.msg(result.result.message || '更新失败');
              }
          }).fail(function(){
              layer.close(index);
              layer.msg('网络错误');
          });
      }
  });

  // 监听搜索操作
  form.on('submit(data-search-btn)', function (data) {
      // 更新 where 和重置页码
      tableConfig.where = {
          nickname: data.field.nickname,
          status: data.field.status,
          element: data.field.element,
          atk_type: data.field.atk_type,
          position: data.field.position,
          is_6: data.field.is_6,
          is_ghz: data.field.is_ghz
      };
      tableConfig.page.curr = 1;  // 搜索时从第一页开始

      // 执行搜索重载
      reloadTable();
      return false;
  });

  // 分页事件：更新当前页码
  table.on('page(tree-table)', function(obj){
      tableConfig.page.curr = obj.curr;
      tableConfig.page.limit = obj.limit;
  });

  // 刷新函数：封装 reload，带当前 where 和 page
  function reloadTable(){
      var reloadOpts = {
          where: tableConfig.where,  // 带筛选条件
          page: {
              curr: tableConfig.page.curr,  // 当前页码
              limit: tableConfig.page.limit  // 当前每页数
          }
      };
      table.reload('tree-table', reloadOpts);
  }

  // 其他 switch 事件保持不变（已添加 reload 以保持一致性）
  form.on('switch(is_6)', function(obj){
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });

      var index = layer.load(1);
      $.post("{{ url('is_6') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
          layer.close(index);
          var result = JSON.parse(res);
          if (result.status) {
              reloadTable();  // 添加刷新
          } else {
              layer.msg(result.result.message);
              obj.elem.checked = !obj.elem.checked;  // 回滚
              form.render('checkbox');
          }
      }).fail(function(){
          layer.close(index);
          layer.msg('网络错误');
          obj.elem.checked = !obj.elem.checked;
          form.render('checkbox');
      });
  });

  form.on('switch(atk_type)', function(obj){
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });

      var index = layer.load(1);
      $.post("{{ url('change_atk_type') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
          layer.close(index);
          var result = JSON.parse(res);
          if (result.status) {
              reloadTable();  // 添加刷新
          } else {
              layer.msg(result.result.message);
              obj.elem.checked = !obj.elem.checked;
              form.render('checkbox');
          }
      }).fail(function(){
          layer.close(index);
          layer.msg('网络错误');
          obj.elem.checked = !obj.elem.checked;
          form.render('checkbox');
      });
  });

  form.on('switch(magic_atk)', function(obj){
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });

      var index = layer.load(1);
      $.post("{{ url('magic_atk') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
          layer.close(index);
          var result = JSON.parse(res);
          if (result.status) {
              reloadTable();  // 添加刷新
          } else {
              layer.msg(result.result.message);
              obj.elem.checked = !obj.elem.checked;
              form.render('checkbox');
          }
      }).fail(function(){
          layer.close(index);
          layer.msg('网络错误');
          obj.elem.checked = !obj.elem.checked;
          form.render('checkbox');
      });
  });

  form.on('switch(is_ghz)', function(obj){
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });

      var index = layer.load(1);
      $.post("{{ url('is_ghz') }}", {id: obj.elem.getAttribute('switchId')}, function(res) {
          layer.close(index);
          var result = JSON.parse(res);
          if (result.status) {
              reloadTable();  // 添加刷新
          } else {
              layer.msg(result.result.message);
              obj.elem.checked = !obj.elem.checked;
              form.render('checkbox');
          }
      }).fail(function(){
          layer.close(index);
          layer.msg('网络错误');
          obj.elem.checked = !obj.elem.checked;
          form.render('checkbox');
      });
  });

});
</script>
@endsection