@extends('main')
@section('content')
    <style type="text/css">
        body {
            background-color: #f8f9fa;
            padding: 15px;
        }

        /* 图片容器布局 */
        .image-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        /* 单个图片项 */
        .image-item {
            width: 23%;
            margin-bottom: 15px;
            margin-right: 2%;
            position: relative;
            cursor: pointer;
            transition: transform 0.1s;
        }

        .image-item:nth-child(4n) {
            margin-right: 0;
        }

        .image-item:active {
            transform: scale(0.95); /* 点击时的按压效果 */
        }

        .image-item img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: opacity 0.2s;
        }

        /* 底部大关闭按钮样式 */
        .bottom-close-btn {
            display: block;
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background-color: #ef4444; /* 红色 */
            color: white;
            text-align: center;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .bottom-close-btn:hover {
            background-color: #dc2626;
        }
        .bottom-close-btn:active {
            transform: scale(0.98);
        }
    </style>

<div class="layui-container">
    <div class="image-container">
      @foreach($roles as $role)
        <div class="image-item">
          @if($role['select'])
            <img src="{{ asset('images/' . $role['roleId'] . '.webp') }}" val="1" id="{{ $role['roleId'] }}" alt="" style="opacity: 1; border: 2px solid #4f46e5;">
          @else
            <img src="{{ asset('images/' . $role['roleId'] . '.webp') }}" val="0" id="{{ $role['roleId'] }}" alt="" style="opacity: 0.4;">
          @endif
        </div>
      @endforeach
    </div>

    <!-- 底部增加一个显眼的关闭按钮 -->
    <!-- <button type="button" class="bottom-close-btn" id="manual-close">完成并关闭页面</button> -->
</div>
@endsection

@section('js')
<script>
  var id = "{{ $id }}";
  // 初始化选中的ids数组
  var ids = <?php echo json_encode($ids); ?>;
  
  // 确保格式正确
  if (typeof ids === 'string') {
      ids = JSON.parse(ids);
  }

  // 设置 CSRF 头
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });

  // 页面加载完成后，强制修改右上角X按钮的样式
  $(document).ready(function() {
      // 寻找父级窗口中的关闭按钮类名（通常是 layui-layer-close）
      var closeBtn = $('.layui-layer-close', parent.document);
      
      if (closeBtn.length > 0) {
          closeBtn.css({
              'background-color': '#ef4444',     // 红色背景
              'color': '#fff',                  // 白色图标
              'opacity': '1',                   // 不透明
              'text-align': 'center',
              'border-radius': '50%',           // 圆形
              'font-size': '20px',              // 加大字体
              'line-height': '30px',            // 居中
              'width': '40px',                  // 拉宽
              'height': '40px'                  // 拉高
          });
      }
  });

  // 点击图片事件
  $('img').click(function () {
      var $img = $(this);
      var select = $img.attr('val');
      var roleId = $img.attr('id');
      var isAdding = false;

      // 1. 本地UI和状态更新
      if (select == 0) {
          // 选中
          ids.push(roleId);
          $img.css('opacity', 1);
          // 添加一个明显的边框表示选中
          $img.css('border', '2px solid #4f46e5'); 
          $img.attr('val', 1);
          isAdding = true;
      } else {
          // 取消选中
          const key = $.inArray(roleId, ids);
          if (key > -1) {
              ids.splice(key, 1);
          }
          $img.css('opacity', 0.4);
          // 移除边框
          $img.css('border', 'none'); 
          $img.attr('val', 0);
          isAdding = false;
      }

      // 2. 立即发送 AJAX 请求
      saveSelection();
  });

  // 保存状态函数
  function saveSelection() {
      // 发送请求
      $.post("{{ url('user/account/coin') }}", {roles: ids, id: id}, function(res) {
          // 处理结果
          try {
              // Laravel 有时返回的 res 已经是对象，有时是 JSON 字符串
              var obj = typeof res === 'object' ? res : JSON.parse(res);
              
              if (obj.status) {
                  layer.msg('设置成功', {icon: 1, time: 1000});
              } else {
                  console.log(obj);
                  var msg = obj.result ? obj.result.message : '操作失败';
                  layer.msg(msg, {icon: 2, time: 2000});
              }
          } catch (e) {
              console.error(e);
              layer.msg('服务器返回数据格式错误', {icon: 2});
          }
      }).fail(function() {
          layer.msg('网络请求失败', {icon: 2});
      });
  }

  // 底部手动关闭按钮事件
  $('#manual-close').click(function() {
      var index = parent.layer.getFrameIndex(window.name);
      parent.layer.close(index);
  });

</script>
@endsection