@extends('main')
@section('content')
<style type="text/css">

/*img {
  width: 50px;
  height: 50px;
}*/

body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center; /* 水平居中 */
        }
        .header {
            display: flex;
            align-items: center;
            gap: 20px; /* 调整标题和副标题之间的距离 */
        }
        .header h1 {
            margin: 0;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            color: gray;
        }
        .images {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .images img {
            max-width: 12%;
            height: auto;
            margin-right: 10px;
            border-radius: 4px;
            object-fit: cover;
        }
        .description {
            margin-top: 1px;
        }
        .layui-btn-container {
          display: flex;
          justify-content: center; /* 水平居中 */
        }
        .layui-tab .layui-tab-title li {
          min-width: 40px;
        }



</style>




  <div class="layui-form" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list" >


    <div class="layui-row layui-col-space15">


      <div class="layui-tab layui-tab-brief" lay-filter="test-hash">
        <ul class="layui-tab-title">
          <li class="layui-this" lay-id="1">E1</li>
          <li lay-id="2">E2</li>
          <li lay-id="3">E3</li>
          <li lay-id="4">E4</li>
          <li lay-id="5">E5</li>
        </ul>
        <div class="layui-tab-content">
          <div class="layui-tab-item layui-show">
          </div>
          <div class="layui-tab-item"></div>
          <div class="layui-tab-item"></div>
          <div class="layui-tab-item"></div>
          <div class="layui-tab-item"></div>
        </div>
      </div>


          


          <div class="layui-btn-container">
            <!-- <button type="button" id="del" class="layui-btn layui-bg-red" data-action="del">一键删除</button> -->
            <!-- <button type="button" id="res" class="layui-btn layui-bg-blue" data-action="res">一键分刀</button> -->
            <button type="button" id="add" class="layui-btn layui-bg-blue" data-action="add">添加作业</button>
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


  $('button[data-action=res]').click(function() {
    location.href ='{{ url("res_team") }}';
  });
  $('button[data-action=del]').click(function() {
      layer.confirm('确定要清空所有作业吗?', {icon: 3, title: '删除'}, function(index) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post("{{ url('delete_all') }}", function(res) {
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



  <script>
layui.use(function(){
  var element = layui.element;
  
  // hash 地址定位
  var hashName = 'boss'; // hash 名称
  var bossId = location.hash.replace(new RegExp('^#'+ hashName + '='), ''); // 获取 lay-id 值

  function getTeams(bossId) {
    $.get("{{ url('admin/team/get_public_teams') }}" + '?boss=' + bossId, function(res) {
      var obj = JSON.parse(res);
      if (obj.status == 1) {
        const data = obj.result;
        let html = '<div class="layui-col-md12"><div class="layui-card"><div class="layui-card-body">';
        for (let key in data) {
          html += '<fieldset class="layui-elem-field"><div class="container"><div class="header"><h1>E' + data[key].boss + '</h1>';
          if (data[key].open) {
            html += '<h5>公开</h5>';
          } else {
            html += '<h5>保密</h5>';
          }
          if (data[key].ordinary) {
            html += '<h5>通用</h5>';
          } else {
            html += '<h5>特殊</h5>';
          }
          html += '<h2>预估伤害：' + data[key].score + '</h2></div><div class="images">';
          for (let k in data[key].team_roles) {
            if (data[key].team_roles[k].status == 1) {
              html += '<img src="' + '{{ asset('images') }}' + '/' + data[key].team_roles[k].role_id + '.webp" alt="图片">';
            } else {
              html += '<img src="' + '{{ asset('images') }}' + '/' + data[key].team_roles[k].role_id + '.webp" alt="图片" style="opacity:0.6;">';
            }
          }
          html += '<div class="layui-btn-group">';
          if (data[key].open == 1) {
            html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" data-action="open" val="' + data[key].id + '">待审核</button></div>';
          }  
          if (data[key].open == 2) {
            html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-blue" data-action="open" val="' + data[key].id + '">已公开</button></div>';
          }          
          html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-red" data-action="delete" val="' + data[key].id + '">驳回</button></div>';
          html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-orange" data-action="edit" val="' + data[key].id + '">修改</button></div>';
          html += '</div>';
          
          html += '</div><div class="description"><p>' + data[key].remark + '</p></div></div></fieldset>';
        }
        html += '</div></div></div>';
        $('.layui-show').html(html);
      } else {
        layer.msg('系统错误，请刷新！');
      }
    });
  }

  $('.layui-tab-item').on('click', 'button[data-action=delete]', function() {
    const id = $(this).attr('val');
    // console.log(id);return false;
    layer.confirm('确定要驳回作业吗?', {icon: 3, title: '删除'}, function(index) {
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post("{{ url('admin/team/delete') }}", {id: id}, function(res) {
        var obj = JSON.parse(res);
        if (obj.status) {
          layer.msg('成功', {icon:1,time:1000}, function () {
            getTeams(bossId);
          });
        } else {
          layer.msg(obj.result.message);
        }
      });
    });
  });
  $('.layui-tab-item').on('click', 'button[data-action=open]', function() {
    const id = $(this).attr('val');
    // console.log(id);return false;
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post("{{ url('admin/team/open') }}", {id: id}, function(res) {
      var obj = JSON.parse(res);
      if (obj.status) {
        layer.msg('成功', {icon:1,time:1000}, function () {
          getTeams(bossId);
        });
      } else {
        layer.msg(obj.result.message);
      }
    });
  });
  $('.layui-tab-item').on('click', 'button[data-action=edit]', function() {
    const id = $(this).attr('val');
    // console.log(id);return false;
    layer.open({
      type: 2
      ,title: '修改作业'
      ,content: "{{ url('admin/team/edit') }}" + '?id=' + id
      ,maxmin: true
      ,area: ['100%', '100%']
      // ,btn: ['确定', '取消']
      ,yes: function(index, layero){
        //点击确认触发 iframe 内容中的按钮提交
        var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
        submit.click();
      }
    });
  });

  getTeams(bossId);

  $('button[data-action=add]').click(function() {
    if (!bossId) {
      bossId = 1;
    }
    layer.open({
      type: 2
      ,title: '添加作业'
      ,content: "{{ url('admin/team/add') }}" + '?boss=' + bossId
      ,maxmin: true
      ,area: ['100%', '100%']
      // ,btn: ['确定', '取消']
      ,yes: function(index, layero){
        //点击确认触发 iframe 内容中的按钮提交
        var submit = layero.find('iframe').contents().find("#layuiadmin-app-form-submit");
        submit.click();
      }
    });
  });

    
  // 初始切换
  element.tabChange('test-hash', bossId);
  // 切换事件
  element.on('tab(test-hash)', function(obj){
    bossId = this.getAttribute('lay-id');
    location.hash = hashName +'='+ bossId;
    getTeams(bossId);
  });
});
</script>
@endsection