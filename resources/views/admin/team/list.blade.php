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
        .fixed-btn {
            position: fixed;  /* 固定定位 */
            bottom: 30%;      /* 距离底部10% */
            right: 10%;       /* 距离右侧10% */
            padding: 10px 20px;
            background-color: #007bff;  /* 按钮背景色 */
            color: white;             /* 按钮文本颜色 */
            border: none;             /* 去除边框 */
            border-radius: 5px;       /* 圆角 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* 阴影效果 */
            cursor: pointer;          /* 鼠标悬停时显示指针 */
            font-size: 16px;           /* 字体大小 */
            z-index: 1000;             /* 确保按钮在其他元素上方 */
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
          <li lay-id="6">B1</li>
          <li lay-id="7">B2</li>
          <li lay-id="8">B3</li>
          <li lay-id="9">B4</li>
          <li lay-id="10">B5</li>
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
            <button type="button" id="res" class="layui-btn layui-bg-blue" data-action="res">一键分刀</button>
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
    var url = "{{ url('/res_team') }}";
      layer.open({
        type: 2
        ,title: '分刀'
        ,content: url
        ,maxmin: true
        ,area: ['100%', '100%'] 
      });
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
          html += '<fieldset class="layui-elem-field"><div class="container"><div class="header">';
          if (data[key].stage == 2) {
            html += '<h1>B' + data[key].boss + '</h1>';
          }
          if (data[key].stage == 5) {
            html += '<h1>E' + data[key].boss + '</h1>';
          }

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
          html += '<h2>预估伤害：' + data[key].score + '</h2>';
          // 根据自动/手动设置显示相应的标签
          if (data[key].auto == 1) {
              html += '<h2>(半)auto</h2>'; // 半自动
          }
          if (data[key].auto == 2) {
              html += '<h2>手动</h2>'; // 手动
          }

          html += '</div><div class="images">';
          for (let k in data[key].team_roles) {
            if (data[key].team_roles[k].status == 1) {
              html += '<img src="' + '{{ asset('images') }}' + '/' + data[key].team_roles[k].image_id + '.webp" alt="图片">';
            } else {
              html += '<img src="' + '{{ asset('images') }}' + '/' + data[key].team_roles[k].image_id + '.webp" alt="图片" style="opacity:0.6;">';
            }
          }
          html += '<div class="layui-btn-group">';
          if (data[key].open == 0) {
            html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" data-action="open" val="' + data[key].id + '">不公开</button></div>';
          } 
          if (data[key].open == 1) {
            html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" data-action="open" val="' + data[key].id + '">待审核</button></div>';
          }  
          if (data[key].open == 2) {
            html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-blue" data-action="open" val="' + data[key].id + '">已公开</button></div>';
          }          
          html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-red" data-action="delete" val="' + data[key].id + '">驳回</button></div>';
          html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-orange" data-action="edit" val="' + data[key].id + '">修改</button></div>';
          html += '</div>';
          
          html += '</div><div class="description"><p>' + data[key].remark + '</p></div></div>';

          html += '<div class="buttonContainer">';
          for (let k in data[key].link) {
              html += '<button class="layui-btn layui-btn-xs layui-btn-primary layui-border-blue" data-url="' + data[key].link[k].url + '" data-image=' + data[key].link[k].image + ' data-note="' + data[key].link[k].note + '">' + data[key].link[k].text + '</button>';
          }
          html += '</div>'; // 结束按钮容器

          // 添加可展开内容区域
          html += '<div class="contentArea" style="margin-top: 20px; display: none;"></div>';
          html += '</fieldset>'; // 结束 fieldset
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
  $('.layui-tab-item').on('click', 'button', function () {
    var url   = $(this).data('url'); // 获取按钮关联的内容
    var image = $(this).data('image'); // 获取按钮关联的内容
    var note  = $(this).data('note'); // 获取按钮关联的内容

    // console.log(image[0].source);
    // return false;
    var $contentArea = $(this).closest('.buttonContainer').next('.contentArea');

    let html = '';
    html += '<h2>链接：</h2>';
    if (url) {
      html += '<a href="' + url + '" target="_blank">点击查看</a>';
    } else {
      html += '无';
    }
    html += '</br>';
    html += '<h2>图片：</h2>';
    if (image) {
      for (let k in image) {
        // console.log(image[k].source);
        html += '<img src="' + image[k].url + '" alt="" style="width:100%">';
      }
    } else {
      html += '无';
    }
    html += '</br>';
    html += '<h2>备注：</h2>';
    if (note) {
      html += '<span>' + note + '</span>';
    } else {
      html += '无';
    }

    // 如果内容区域是可见的，并且当前内容与按钮关联的内容相同，则隐藏
    if ($contentArea.is(':visible')) {
        $contentArea.hide();
    } else {
        // 更新内容并显示
        $contentArea.html(html).show();
    }
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