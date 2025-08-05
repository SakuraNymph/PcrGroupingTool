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
  justify-content: center; /* 让图片在 .group 内水平居中 */
  gap: 5px;
  flex-wrap: wrap;  /* 小屏幕时换行 */
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

.image-checkbox {
    display: inline-block;
/*    margin: 5px;*/
    cursor: pointer;
    transition: filter 0.3s;
    width: 15%; /* 每行四个图片，留有间距 */
    margin-bottom: 20px; /* 行间距 */
    margin-right: 2%; /* 每个图片右边的间距 */
}

.image-checkbox.checked {
    filter: none;
}

.image-checkbox img {
  display: inline-block;
  margin: 0 5px; /* 图片间距，可选 */
  width: 100%;
  height: auto;
  opacity:0.5;
}

#bossList {
  text-align: center; /* 图片居中 */
}
#isAuto {
  text-align: center; /* 图片居中 */
}

h5 {
  text-align: center; /* 水平居中对齐 */
}

.title-row {
    display: flex;
    align-items: center; /* 副标题与标题垂直居中对齐 */
}
.group-title {
    font-size: 24px;
    font-weight: bold;
}
.group-subtitle {
    font-size: 18px;
    color: #666;
    margin-left: 20px; /* 副标题与标题的距离 */
}





.rainbow-border {
  position: relative;
  display: flex;     /* 让图片横向排列 */
  gap: 5px;          /* 图片间距 */
  border-radius: 12px;
  overflow: hidden;
  align-items: center; /* 图片垂直居中 */
}

.rainbow-border::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 3px; /* 边框粗细 */
  background: linear-gradient(
    270deg,
    red,
    orange,
    yellow,
    green,
    cyan,
    blue,
    purple,
    red
  );
  background-size: 300% 300%;
  border-radius: 12px;
  -webkit-mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: borderAnimation 5s linear infinite;
}

@keyframes borderAnimation {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.disabled-border::before {
  display: none;
}

@media (max-width: 768px) {
    .rainbow-border img,
    .image-row > img {
        width: 50px; /* 移动端更小 */
    }

    .borrow-text {
        font-size: 12px;
    }
}






</style>
<!-- <link rel="stylesheet" href="{{ asset('css/team.css') }}" media="all"> -->
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
                <input type="radio" name="type" lay-filter="is_auto" value="1" title="自动/半自动" checked>
                <input type="radio" name="type" lay-filter="is_auto" value="2" title="手动"> 
              </div>
              @endif
              <div class="layui-form" id="isAuto">
                <input type="radio" name="atk_type" lay-filter="atk_type" value="0" title="不限" checked>
                <input type="radio" name="atk_type" lay-filter="atk_type" value="1" title="3物"> 
                <input type="radio" name="atk_type" lay-filter="atk_type" value="2" title="2物"> 
                <input type="radio" name="atk_type" lay-filter="atk_type" value="3" title="2法"> 
                <input type="radio" name="atk_type" lay-filter="atk_type" value="4" title="3法">
              </div>
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
    let o = layui.$
    ,form = layui.form;

    let id             = "{{ $id }}";
    let type           = $('input[name="type"]:checked').val();
    let atk            = $('input[name="atk_type"]:checked').val();
    let bossMap        = {row1:0, row2:0, row3:0};
    let lockedIds      = []; // 锁定ID
    let hiddenIds      = []; // 隐藏ID
    let selectedImages = {}; // 存储每行选中的图片

    // radio 事件
    form.on('radio(is_auto)', function(data){
      let elem = data.elem; // 获得 radio 原始 DOM 对象
      let checked = elem.checked; // 获得 radio 选中状态
      let value = elem.value; // 获得 radio 值
      let othis = data.othis; // 获得 radio 元素被替换后的 jQuery 对象
      type = value;
      // layer.msg(['value: '+ value, 'checked: '+ checked].join('<br>'));
      getTeamGroups(bossMap, type, atk, lockedIds, hiddenIds);
    });

    form.on('radio(atk_type)', function(data){
      let elem = data.elem; // 获得 radio 原始 DOM 对象
      let checked = elem.checked; // 获得 radio 选中状态
      let value = elem.value; // 获得 radio 值
      let othis = data.othis; // 获得 radio 元素被替换后的 jQuery 对象
      atk = value;
      // layer.msg(['value: '+ value, 'checked: '+ checked].join('<br>'));
      getTeamGroups(bossMap, type, atk, lockedIds, hiddenIds);
    });

    getTeamGroups(bossMap, type, atk, lockedIds, hiddenIds);
    getBossList();

    function getTeamGroups(bossMap = {}, type = 0, atk = 0, lockedIds = [], hiddenIds = []) {
      if (id == '0') {
        url = "{{ url('get_team_groups') }}";
        type = 0;
      } else {
        url = "{{ url('user/account/get_team_groups') }}";
      }
      $.get(url, { row1: bossMap.row1, row2: bossMap.row2, row3: bossMap.row3, id: id, type:type, atk:atk, lockedIds:lockedIds, hiddenIds:hiddenIds }, function (res) {
        let obj = JSON.parse(res);
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

                const currentId = data[key][k].id;
                const borderClass = lockedIds.includes(currentId) ? '' : 'disabled-border';
                html += '<div class="rainbow-border ' + borderClass + '" data-id="' + currentId + '">';

                for (let kk in data[key][k].team_roles) {
                  if (data[key][k].team_roles[kk].status == 1) {
                    html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].team_roles[kk].image_id + '.webp" alt="图片">';
                  } else {
                    html += '<img src="' + '{{ asset('images') }}' + '/' + data[key][k].team_roles[kk].image_id + '.webp" alt="图片" style="opacity:0.6;">';
                  }
                }
                html += '</div>';
                
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



    $(document).on('click', '.rainbow-border', function() {
        const currentId = $(this).data('id');

        // 克隆原始数组，便于后续比较
        const prevLockedIds = [...lockedIds];
        const prevHiddenIds = [...hiddenIds];

        layer.open({
            title: '操作选项',
            content: '请选择操作',
            btn: ['锁定', '解锁', '隐藏'],
            yes: function(index) { // 锁定
                if (!lockedIds.includes(currentId)) {
                    if (lockedIds.length >= 2) {
                        lockedIds.shift();
                    }
                    lockedIds.push(currentId);
                }
                checkAndRefresh(prevLockedIds, prevHiddenIds);
                layer.close(index);
            },
            btn2: function(index) { // 解锁
                const idx = lockedIds.indexOf(currentId);
                if (idx !== -1) {
                    lockedIds.splice(idx, 1);
                }
                checkAndRefresh(prevLockedIds, prevHiddenIds);
                layer.close(index);
                return false;
            },
            btn3: function(index) { // 隐藏
                if (!hiddenIds.includes(currentId)) {
                    hiddenIds.push(currentId);
                }
                // 同时从lockedIds移除
                const idx = lockedIds.indexOf(currentId);
                if (idx !== -1) {
                    lockedIds.splice(idx, 1);
                }
                checkAndRefresh(prevLockedIds, prevHiddenIds);
                layer.close(index);
            }
        });
    });

    // 判断两个数组是否变化
    function arraysEqual(arr1, arr2) {
        if (arr1.length !== arr2.length) return false;
        for (let i = 0; i < arr1.length; i++) {
            if (arr1[i] !== arr2[i]) return false;
        }
        return true;
    }

    // 只在lockedIds 或 hiddenIds 变化时刷新
    function checkAndRefresh(prevLockedIds, prevHiddenIds) {
        if (!arraysEqual(prevLockedIds, lockedIds) || !arraysEqual(prevHiddenIds, hiddenIds)) {
            getTeamGroups(bossMap, type, atk, lockedIds, hiddenIds);
        }
    }







    

    // 动态获取并渲染图片
    function getBossList() {
      $.get("{{ url('get_this_month_boss_list') }}", function (res) {
        let obj = JSON.parse(res);
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
      getTeamGroups(bossMap, type, atk, lockedIds, hiddenIds);

    });




    $('#show').on('click', 'button', function () {
      let url   = $(this).data('url'); // 获取按钮关联的内容
      let image = $(this).data('image'); // 获取按钮关联的内容
      let note  = $(this).data('note'); // 获取按钮关联的内容

      // console.log(image[0].source);
      // return false;
      let $contentArea = $(this).closest('.buttonContainer').next('.contentArea');

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