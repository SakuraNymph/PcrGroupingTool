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




<div class="layui-fluid">
  <div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
      <div class="layui-card">
            <div class="layui-card-body layui-row layui-col-space10 layui-form-item">
                <div class="layui-col-md1">
                    <div class="layui-input-inline">
                    </div>
                </div>

                <div class="layui-col-md1">
                    <div class="layui-input-inline">
                      <button type="button" id="addHomework" class="layui-btn">
                        <i class="layui-icon layui-icon-add-1"></i>
                        添加作业
                      </button>
                    </div>
                </div>

                <div class="layui-col-md1">
                    <div class="layui-input-inline">
                      <button type="button" class="layui-btn demo-class-accept" lay-options="{accept: 'file'}">
                        <i class="layui-icon layui-icon-upload"></i> 
                        上传文件
                      </button>
                    </div>
                </div>

                <form class="layui-form layui-form-pane" lay-filter="teams" action="">
                          <div class="layui-col-md1">
                            <select name="stage" lay-search>
                              <option value="">阶段</option>
                              <option value="2">B阶段</option>
                              <option value="3">C阶段</option>
                              <option value="5">D阶段</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="boss" lay-search>
                              <option value="">boss</option>
                              <option value="1">1王</option>
                              <option value="2">2王</option>
                              <option value="3">3王</option>
                              <option value="4">4王</option>
                              <option value="5">5王</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="auto" lay-search>
                              <option value="">操作类型</option>
                              <option value="1">自动</option>
                              <option value="2">手动</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="atk" lay-search>
                              <option value="">攻击类型</option>
                              <option value="1">物理</option>
                              <option value="-1">魔法</option>
                            </select>
                          </div>
                          <div class="layui-col-md1">
                            <select name="status" lay-search>
                              <option value="">状态</option>
                              <option value="1">展示</option>
                              <option value="0">隐藏</option>
                            </select>
                          </div>
                          <div class="layui-col-md0">
                              <div class="layui-input-inline">
                                  <!-- <input type="text" name="nickname" autocomplete="off" class="layui-input" placeholder="请输入名称"> -->
                              </div>
                          </div>
                            <button type="submit" class="layui-btn layui-btn-primary" lay-submit  lay-filter="data-search-btn"><i class="layui-icon"></i> 搜 索</button>
                </form>
            </div>
          <div class="layui-tab-content">
            <div class="layui-tab-item layui-show">
            </div>
            <div class="layui-tab-item"></div>
            <div class="layui-tab-item"></div>
            <div class="layui-tab-item"></div>
            <div class="layui-tab-item"></div>
          </div>
      </div>
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


    
    // let stage  = 0;
    // let boss   = 0;
    // let auto   = 0;
    // let atk    = 0;
    // let status = '';


    function getTeams() {

      // 关键点：从名为 'teams' 的表单中实时提取所有字段的值
      var formData = layui.form.val("teams");

      let conditions = {
          stage:  formData.stage,  // 对应 select 的 name 属性
          boss:   formData.boss,
          auto:   formData.auto,
          atk:    formData.atk,
          status: formData.status,
      };

      $.get("{{ url('admin/team/get_public_teams') }}", conditions, function(res) {
        var obj = JSON.parse(res);
        if (obj.status == 1) {
          const data = obj.result;
          let html = '<div class="layui-col-md12"><div class="layui-card"><div class="layui-card-body">';
          for (let key in data) {
            html += '<fieldset class="layui-elem-field"><div class="container"><div class="header">';
            if (data[key].stage == 2) {
              html += '<h1>B' + data[key].boss + '</h1>';
            }
            if (data[key].stage == 3) {
              html += '<h1>C' + data[key].boss + '</h1>';
            }
            if (data[key].stage == 5) {
              html += '<h1>E' + data[key].boss + '</h1>';
            }

            // if (data[key].open) {
            //   html += '<h5>公开</h5>';
            // } else {
            //   html += '<h5>保密</h5>';
            // }
            // if (data[key].ordinary) {
            //   html += '<h5>通用</h5>';
            // } else {
            //   html += '<h5>特殊</h5>';
            // }
            html += '<h2>预估伤害：' + data[key].damage + '</h2>';
            // 根据自动/手动设置显示相应的标签
            if (data[key].auto == 1) {
                html += '<h2>(半)auto</h2>'; // 半自动
            }
            if (data[key].auto == 2) {
                html += '<h2>手动</h2>'; // 手动
            }

            html += '</div><div class="images">';
            for (let k in data[key].team) {
              if (data[key].team[k].status == 1) {
                html += '<img src="' + '{{ asset('images') }}' + '/' + data[key].team[k].image_id + '.webp" alt="图片">';
              } else {
                html += '<img src="' + '{{ asset('images') }}' + '/' + data[key].team[k].image_id + '.webp" alt="图片" style="opacity:0.6;">';
              }
            }
            html += '<div class="layui-btn-group">';
            // if (data[key].open == 0) {
            //   html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" data-action="open" val="' + data[key].id + '">不公开</button></div>';
            // } 
            // if (data[key].open == 1) {
            //   html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" data-action="open" val="' + data[key].id + '">待审核</button></div>';
            // }  
            // if (data[key].open == 2) {
            //   html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-blue" data-action="open" val="' + data[key].id + '">已公开</button></div>';
            // }
            
            if (data[key].status) {
              html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-blue" data-action="status" val="' + data[key].id + '">展示</button></div>';
            } else {
              html += '<div class="buttons"><button type="button" class="layui-btn layui-btn-fluid layui-btn-sm layui-bg-red" data-action="status" val="' + data[key].id + '">隐藏</button></div>';
            }         
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

    window.getTeams = getTeams;

    getTeams();

    // 监听搜索操作
    form.on('submit(data-search-btn)', function (data) {
        getTeams();
        return false;
    });

    $('#addHomework').click(function () {
      let url = "{{ url('/admin/team/add') }}";
      layer.open({
        type: 2
        ,title: '添加作业'
        ,content: url
        ,maxmin: true
        ,area: ['100%', '100%']
      });
    });

    $('.layui-tab-item').on('click', 'button[data-action=status]', function() {
      const id = $(this).attr('val');
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post("{{ url('admin/team/status') }}", {id: id}, function(res) {
        var obj = JSON.parse(res);
        if (obj.status) {
          layer.msg('成功', {icon:1,time:1000}, function () {
            getTeams();
          });
        } else {
          layer.msg(obj.result.message);
        }
      });
      // layer.confirm('确定要驳回作业吗?', {icon: 3, title: '删除'}, function(index) {
      // });
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
            getTeams();
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
  });
</script>

<script>
layui.use(function(){
  var upload = layui.upload;
  var layer = layui.layer;
  // 渲染
  upload.render({
    elem: '.demo-class-accept', // 绑定多个元素
    url: '/admin/team/upload', // 此处配置你自己的上传接口即可
    accept: 'file', // 普通文件
    exts: 'txt',     
    headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
    done: function(res){
      if (res.code === 1) {
        // ✅ 弹出数据预览窗口
        layer.open({
          type: 1,
          title: '确认导入数据',
          area: ['420px', '750px'],
          btn: ['确认导入', '取消'],
          content: '<div id="previewBox" style="padding:15px;"></div>',
          success: function(layero, index){
            layero.find('.layui-layer-content').css('overflow', 'auto');
            let html = '';
            res.data.forEach((item, idx) => {
              // 计算颜色：每5条数据切换颜色
              let color = Math.floor(idx / 5) % 2 === 0 ? 'blue' : 'red';
              let stageMap = ['', 'A', 'B', 'C', 'D', 'E'];
              html += `
                <div class="data-item" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;border-radius:8px;">
                  <b style="color:${color}">${stageMap[item.stage]}${item.boss}</b> | 伤害：${item.damage} | <a href="${item.url}" target="_blank">${item.title}</a>
                  <div style="margin-top:8px;display:flex;gap:5px;flex-wrap:wrap; justify-content:flex-end;">
                    ${item.team_images.map(imageId => {
                      // 显示图片
                      return `<img src="{{ asset('images') }}/${imageId}.webp" style="width:60px;height:60px;border:1px solid #ccc;border-radius:6px;padding:2px;">`;
                    }).join('')}
                  </div>
                </div>
              `;
            });
            $('#previewBox').html(html);
          },
          yes: function(index){
            layer.confirm('确定要导入这些数据吗？', function(){
              $.post('/confirm-import', {timestamp: res.timestamp}, function(r){
                if (r.code === 1) {
                  layer.msg('导入成功');
                  layer.closeAll();
                } else {
                  layer.msg(r.msg || '导入失败');
                }
              });
            });
          }
        });
      } else {
        layer.msg(res.msg || '部分数据格式有误');
        if (res.errors && res.errors.length) {
          console.warn('错误行信息：', res.errors);
        }
      }
    },
    error: function(){
      layer.msg('上传出错');
    }
  });
});
</script>
@endsection