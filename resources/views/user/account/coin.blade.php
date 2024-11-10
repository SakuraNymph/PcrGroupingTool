@extends('main')
@section('content')
    <style type="text/css">
        .image-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start; /* 确保所有图片靠左对齐 */
        }
        .image-item {
            width: 23%; /* 每行四个图片，留有间距 */
            margin-bottom: 20px; /* 行间距 */
            margin-right: 2%; /* 每个图片右边的间距 */
        }
        .image-item:nth-child(4n) {
            margin-right: 0; /* 每行最后一个图片取消右侧的间距 */
        }
        .image-item img {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body>

<div class="layui-container">
    <div class="image-container">
      @foreach($roles as $role)
        <div class="image-item">
          @if($role['select'])
            <img src="{{ asset('images/' . $role['roleId'] . '.webp') }}" val="1" id="{{ $role['roleId'] }}" alt="" style="opacity: 1;">
          @else
            <img src="{{ asset('images/' . $role['roleId'] . '.webp') }}" val="0" id="{{ $role['roleId'] }}" alt="" style="opacity: 0.5;">
          @endif
        </div>
      @endforeach
    </div>
    <button type="button" class="layui-btn layui-bg-blue" id="layuiadmin-app-form-submit" lay-submit lay-filter="layuiadmin-app-form-submit" style="visibility: hidden;">点击保存</button>
</div>
@endsection
@section('js')


<script>
  var id = "{{ $id }}";
  var ids = <?php echo json_encode($ids); ?>;
  ids = JSON.parse(ids);

  // console.log(aaa);

  $('#layuiadmin-app-form-submit').click(() => {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post("{{ url('user/account/coin') }}", {roles:ids,id:id}, function(res) {
      let obj = JSON.parse(res);
      if (obj.status) {
        // layer.msg('删除成功');
        var index = parent.layer.getFrameIndex(window.name); // 先得到当前 iframe 层的索引
        parent.layer.close(index); // 再执行关闭
        // window.parent.location.reload();
      } else {
        console.log(obj);
        // layer.msg(obj.result.message);
      }
    });

  });

  $('img').click(function (data) {
    var select = $(this).attr('val');
    var roleId = $(this).attr('id');

    if (select == 0) {
      ids.push(roleId);
      $(this).css('opacity', 1);
      $(this).attr('val', 1);
    } else {
      const key = $.inArray(roleId, ids);
      ids.splice(key, 1);
      $(this).css('opacity', 0.5);
      $(this).attr('val', 0);
    }
  });

</script>




@endsection