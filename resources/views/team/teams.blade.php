@extends('main')
@section('css')
<link rel="stylesheet" href="{{ asset('css/team.css') }}" media="all">
@endsection
@section('content')
<style type="text/css">
</style>


<div id="content">
  <div class="layui-form" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list">
      <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
          <div class="layui-card">
            <div class="layui-card-header">
              <h5>
                <span class="">分刀结果仅供参考，请以实际box练度为准</span>
              </h5>
            </div>
              <div class="layui-form" id="demoForm"></div>
              <div id="show"></div>
          </div>
        </div>
      </div>
  </div>
</div>
@endsection
@section('js')
<!-- <script src="./layui/dom-to-image.min.js"></script> -->


<script>
  layui.config({
    base: '/layuiadmin/' //静态资源所在路径
  }).extend({
    index: 'lib/index' //主入口模块
  }).use(['index', 'form'], function(){
    var o = layui.$
    ,form = layui.form;

    // radio 事件
    form.on('radio(demo-radio-filter)', function(data){
      var elem = data.elem; // 获得 radio 原始 DOM 对象
      var checked = elem.checked; // 获得 radio 选中状态
      var value = elem.value; // 获得 radio 值
      var othis = data.othis; // 获得 radio 元素被替换后的 jQuery 对象
      
      // layer.msg(['value: '+ value, 'checked: '+ checked].join('<br>'));
      // getTeamGroups(value);
    });

    getTeamGroups();
    getBossList();

    function getTeamGroups(bossMap = {}) {
      $.get("{{ url('get_team_groups') }}", { bossMap: bossMap }, function (res) {
        var obj = JSON.parse(res);
        if (obj.status == 1) {
          $('#show').html(''); // 清空容器
          const data = obj.result;
          if (Object.keys(data).length === 0) {
            let html = '<h5>无法分刀，请丰富您的box</h5>';
            $('#show').html(html); // 添加生成的 HTML
          } else {
            for (let key in data) {
              let html = '';
              html += '<fieldset class="layui-elem-field"><div class="layui-field-box"><div class="container">';
              for (let k in data[key]) {
                html += '<div class="group"><div class="title-row">';
                html += '<div class="group-title">E' + data[key][k].boss + '</div>';
                html += '<div class="group-subtitle">预估伤害：' + data[key][k].score + '</div></div>';
                html += '<div class="image-row">';

                for (let kk in data[key][k].team_roles) {
                  if (data[key][k].team_roles[kk].status == 1) {
                    html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].team_roles[kk].role_id + '.webp" alt="图片">';
                  } else {
                    html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].team_roles[kk].role_id + '.webp" alt="图片" style="opacity:0.6;">';
                  }
                }
                
                html += '<span class="text">借</span>';
                if (data[key][k].borrow) {
                  html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].borrow + '.webp" alt="图片" class="image-after-text">';
                } else {
                  html += '<img src="' + '{{ asset('images') }}' + '/renyi.webp" alt="图片" class="image-after-text">';
                }

                html += '</div>'; // 关闭 image-row
                html += '<div class="text">' + data[key][k].remark + '</div>'; // 备注

                html += '<div class="buttonContainer">';
                for (let kk in data[key][k].link) {
                  html += '<button class="layui-btn layui-btn-xs layui-btn-primary layui-border-blue" data-url="' + data[key][k].link[kk].url + '" data-image=' + data[key][k].link[kk].image + ' data-note="' + data[key][k].link[kk].note + '">' + data[key][k].link[kk].text + '</button>';
                }
                html += '</div>';
                html += '<div class="contentArea" style="margin-top: 20px; display: none;"></div>';

                html += '</div>'; // 关闭 group
              }
              html += '</div></div></fieldset>'; // 关闭 container 和 fieldset
              $('#show').append(html); // 添加生成的 HTML
            }
          }
          
        }
      });
    }

    function getBossList() {
      $.get("{{ url('get_this_month_boss_list') }}", function (res) {
        var obj = JSON.parse(res);
        if (obj.status == 1) {
          const data = obj.data;
          let html = '';
          for (let key in data) {
            html += '<div class="image-checkbox" data-status="0" data-value="' + data[key].sort + '"><img src="' + '{{ asset('boss') }}' + '/' + data[key].file_path + '" alt="选项1"></div>';
          }
          $('#demoForm').html(html);
        }
      });
    }

    var bossMap = [];

    // 事件委托
    $('#demoForm').on('click', '.image-checkbox', function() {
        var value = $(this).data('value');
        var status = $(this).data('status');

        if (status == 0) {
            // 添加到选中列表
            if (bossMap.length < 3) {
                bossMap.push(value);
                $(this).data('status', 1);
                $(this).find('img').css('opacity', 1);
            } else {
                // 超过三张，移除第一个
                var firstValue = bossMap.shift();
                $('.image-checkbox[data-value="' + firstValue + '"]').data('status', 0).find('img').css('opacity', 0.5);
                
                // 添加当前选择
                bossMap.push(value);
                $(this).data('status', 1);
                $(this).find('img').css('opacity', 1);
            }
        } else {
            // 已选中，移除
            $(this).data('status', 0);
            $(this).find('img').css('opacity', 0.5);
            bossMap = bossMap.filter(function(item) {
                return item !== value;
            });
        }

        getTeamGroups(bossMap);

        // console.log("Selected values: ", bossMap);
    });



    $('#show').on('click', 'button', function () {
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

  })
</script>
@endsection