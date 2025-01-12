@extends('main')
@section('css')
<style>


#bossList {
  display: flex;
  flex-direction: column;
}

.image-row {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.row-label {
  writing-mode: vertical-rl;
  text-align: center;
  margin-right: 10px;
  font-size: 16px;
  font-weight: bold;
}

.image-checkbox {
  position: relative;
  margin-right: 10px;
  cursor: pointer;
}

.image-checkbox img {
  opacity: 0.5;
  transition: opacity 0.3s ease;
}

.image-checkbox.selected img {
  opacity: 1;
}

.image-checkbox.selected::after {
  content: '✓';
  position: absolute;
  top: 0;
  right: 0;
  font-size: 20px;
  color: green;
}

</style>
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
              <div class="layui-form" id="bossList"></div>
              @if($select_is_show)
              <div class="layui-form" id="isAuto">
                <input type="radio" name="type" value="1" title="自动/半自动" checked>
                <input type="radio" name="type" value="2" title="手动"> 
              </div>
              @endif
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

    var id = "{{ $id }}";
    var type = $('input[name="type"]:checked').val();
    var bossMap = {row1:0, row2:0, row3:0};

    // radio 事件
    form.on('radio', function(data){
      var elem = data.elem; // 获得 radio 原始 DOM 对象
      var checked = elem.checked; // 获得 radio 选中状态
      var value = elem.value; // 获得 radio 值
      var othis = data.othis; // 获得 radio 元素被替换后的 jQuery 对象
      type = value;
      // layer.msg(['value: '+ value, 'checked: '+ checked].join('<br>'));
      getTeamGroups(bossMap, type);
    });

    getTeamGroups(bossMap, type);
    getBossList();

    function getTeamGroups(bossMap = {}, type = 0) {
      if (id == '0') {
        url = "{{ url('get_team_groups') }}";
        type = 0;
      } else {
        url = "{{ url('user/account/get_team_groups') }}";
      }
      $.get(url, { row1: bossMap.row1, row2: bossMap.row2, row3: bossMap.row3, id: id, type:type }, function (res) {
        var obj = JSON.parse(res);
        if (obj.status == 1) {
          $('#show').html(''); // 清空容器
          const data = obj.result;
          if (Object.keys(data).length === 0) {
            let html = '<h5>无法分刀，请丰富您的box</h5>';
            $('#show').html(html); // 添加生成的 HTML
          } else {
            for (let key in data) {
              let color = '';
              if (key % 2 == 0) { color = 'red'; }
              if (key % 2 == 1) { color = 'blue'; }
              let html = '';
              html += '<fieldset class="layui-elem-field"><div class="layui-field-box"><div class="container">';
              for (let k in data[key]) {
                html += '<div class="group"><div class="title-row">';
                html += '<div class="group-title" style="color:' + color + '">E' + data[key][k].boss + '</div>';
                html += '<div class="group-subtitle">预估伤害：' + data[key][k].score + '</div></div>';
                html += '<div class="image-row">';

                for (let kk in data[key][k].team_roles) {
                  if (data[key][k].team_roles[kk].status == 1) {
                    html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].team_roles[kk].image_id + '.webp" alt="图片">';
                  } else {
                    html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].team_roles[kk].image_id + '.webp" alt="图片" style="opacity:0.6;">';
                  }
                }
                
                html += '<span class="text">借</span>';
                if (data[key][k].borrow) {
                  for (let kk in data[key][k].team_roles) {
                    if (data[key][k].team_roles[kk].role_id == data[key][k].borrow) {
                      html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].team_roles[kk].image_id + '.webp" alt="图片" class="image-after-text">';
                      break;
                    }
                  }
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

    let selectedImages = {}; // 存储每行选中的图片

    // 动态获取并渲染图片
    function getBossList() {
      $.get("{{ url('get_this_month_boss_list') }}", function (res) {
        var obj = JSON.parse(res);
        if (obj.status == 1) {
          const data = obj.data;
          let html = '';
          let rows = 3; // 假设有三行
          let imagesPerRow = 5;

          let num = ['', '①', '②', '③'];

          for (let row = 1; row <= rows; row++) {
            html += '<div class="image-row-new" data-row="' + row + '">';
            for (let key in data) {
              html += '<div class="image-checkbox" data-row="' + row + '" data-status="0" data-value="' + data[key].sort + '"><img src="' + '{{ asset('boss') }}' + '/' + data[key].file_path + '" alt="选项1"></div>';
            }
            html += '</div>';
          }



          $('#bossList').html(html);
        }
      });
    }

    // 事件绑定
    $('#bossList').on('click', '.image-checkbox', function() {
      const row = $(this).closest('.image-row-new').data('row');
      const value = $(this).data('value');
      const isSelected = $(this).hasClass('selected');

      if (isSelected) {
        // 取消选中当前图片
        $(this).removeClass('selected');
        bossMap['row' + row] = 0;
        // delete bossMap['row' + row];
      } else {
        // 取消当前行的其他选中状态
        $('.image-row-new[data-row="' + row + '"] .image-checkbox').removeClass('selected');
        // 设置当前图片为选中
        $(this).addClass('selected');
        bossMap['row' + row] = value;
      }
      getTeamGroups(bossMap, type);

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