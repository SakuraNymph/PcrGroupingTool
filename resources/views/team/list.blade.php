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

      <a href="{{ url('/') }}"><button class="fixed-btn">返回首页</button></a>
          


          <div class="layui-btn-container">
            
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
  $('button[data-action=res]').click(function() {
    location.href ='{{ url("res_team") }}';
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
    // 发送 GET 请求，获取与特定 bossId 相关的团队数据
    $.get("{{ url('get_public_teams') }}" + '?boss=' + bossId, function(res) {
        // 解析返回的 JSON 字符串
        var obj = JSON.parse(res);
        
        // 检查请求是否成功
        if (obj.status == 1) {
            const data = obj.result; // 获取结果数据
            // console.log(data); // 输出数据到控制台，用于调试
            
            // 初始化 HTML 内容
            let html = '<h5 style="color:red">练度参考作者<h5><div class="layui-col-md12"><div class="layui-card"><div class="layui-card-body">';
            
            // 遍历每个团队数据
            for (let key in data) {
                html += '<fieldset class="layui-elem-field"><div class="container"><div class="header"><h1>E' + data[key].boss + '</h1>';
                html += '<h2>预估伤害：' + data[key].score + '</h2>';
                
                // 根据自动/手动设置显示相应的标签
                if (data[key].auto == 1) {
                    html += '<h2>(半)auto</h2>'; // 半自动
                }
                if (data[key].auto == 2) {
                    html += '<h2>手动</h2>'; // 手动
                }
                
                html += '</div><div class="images">';

                // 遍历团队角色，生成图片
                for (let k in data[key].team_roles) {
                    html += '<img val=' + data[key].team_roles[k].role_id + ' src="./images/' + data[key].team_roles[k].image_id + '.webp" alt="图片" switch="1"';
                    
                    // 设置图片透明度，如果状态为 0
                    if (data[key].team_roles[k].status == 0) {
                        html += 'style="opacity:0.6;"';
                    }
                    html += '>';
                }

                // 检查是否可以添加或删除团队
                if (data[key].has_add) {
                    html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-sm layui-btn-normal layui-bg-red" data-action="del" id="' + data[key].id + '"><i class="layui-icon layui-icon-delete">'; // 删除按钮
                } else {
                    html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-sm layui-btn-normal" data-action="add" id="' + data[key].id + '"><i class="layui-icon layui-icon-add-1">'; // 添加按钮
                }
                html += '</i></button></div></div><div class="description"><p>' + data[key].remark + '</p></div></div>';
                
                // 生成链接按钮
                html += '<div class="buttonContainer">';
                for (let k in data[key].link) {
                    html += '<button class="layui-btn layui-btn-xs layui-btn-primary layui-border-blue" data-url="' + data[key].link[k].url + '" data-image=' + data[key].link[k].image + ' data-note="' + data[key].link[k].note + '">' + data[key].link[k].text + '</button>';
                }
                html += '</div>'; // 结束按钮容器

                // 添加可展开内容区域
                html += '<div class="contentArea" style="margin-top: 20px; display: none;"></div>';
                html += '</fieldset>'; // 结束 fieldset
            }
            html += '</div></div></div>'; // 结束卡片和内容

            // 将生成的 HTML 插入到页面中
            $('.layui-show').html(html);
        } else {
            // 请求失败，显示错误提示
            layer.msg('系统错误，请刷新！');
        }
    });
  }



  $('.layui-tab-item').on('click', 'img', function() {
      // 获取被点击的图片
      var clickedImg = $(this);

      // 找到最近的 fieldset 容器
      var container = clickedImg.closest('fieldset');
      
      // 获取同组的所有 img 元素
      var images = container.find('img');

      // 获取图片状态
      var select = clickedImg.attr('switch');
      if (select == 1) {
        clickedImg.css('opacity', 0.6);
        clickedImg.attr('switch', 0);
        
        // 收集其他四个图片的 ID（排除被点击的图片）
        images.each(function() {
            if ($(this).attr('val') !== clickedImg.attr('val')) {
              $(this).css('opacity', 1);
              $(this).attr('switch', 1);
            }
        });
      }
      if (select == 0) {
        clickedImg.css('opacity', 1);
        clickedImg.attr('switch', 1);
      }
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




  $('.layui-tab-item').on('click', 'button[data-action=del]', function() {
      const id = $(this).attr('id');
      // console.log(id);return false;
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post("{{ url('delete_team') }}", {id:id, type:1}, function(res) {
        const obj = JSON.parse(res);
        if (obj.status) {
          layer.msg('删除成功', {icon:1,time:1000}, function () {
            getTeams(bossId);
          });
        } else {
          layer.msg(obj.result.message);
        }
      });
  });

  $('.layui-tab-item').on('click', 'button[data-action=add]', function() {
    // 获取被点击的按钮
    var button = $(this);
    
    // 找到最近的 fieldset 容器
    var container = button.closest('fieldset');
    
    // 获取容器中所有的 img 元素
    var images = container.find('img');
    
    var status_role_id = 0;
    images.each(function() {
      if ($(this).attr('switch') == 0) {
        status_role_id = $(this).attr('val');
      }
    });

    const id = $(this).attr('id');
    // console.log(id);return false;
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post("{{ url('add_other_team') }}", {id:id,role_id:status_role_id}, function(res) {
      const obj = JSON.parse(res);
      // console.log(obj);return false;
      if (obj.status) {
        layer.msg('添加成功', {icon:1,time:1000}, function () {
          getTeams(bossId);
        });
      } else {
        layer.msg(obj.result.message);
      }
    });
  });


  getTeams(bossId);
    
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