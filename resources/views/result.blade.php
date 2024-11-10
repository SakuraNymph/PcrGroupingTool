@extends('main')
@section('content')


<style type="text/css">

.container {
    display: flex;
    flex-wrap: wrap; /* 允许换行 */
}
.item {
    width: 90px;
    height: 50px;
    display: flex;
    align-items: center;
    margin-right: 10px;
    margin-bottom: 5px; /* 调整上下两个div的间距 */
    padding: 5px;
}
.item img {
    max-width: 50%;
    height: auto;
    margin-right: 5px;
}
.details {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
}
.details div {
    font-size: 12px;
    line-height: 1; /* 调整同一个div内的三行文字间距 */
}
.red {
    color: red;
}
.green {
    color: green;
}
.blue {
    color: blue;
}
.purple {
    color: purple;
}

h1 {
  text-align: center; /* 水平居中对齐 */
}




</style>



<div id="content">
  <div class="layui-form" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list">
    <div class="layui-bg-gray" style="padding: 16px;">
      <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
          <div class="layui-card">
            <div class="layui-card-header">
              <h1>
                @foreach($colors as $key => $val)
                  @if(isset($val->color))
                   <span class="{{ $val->color }}">{{ $val->name }}</span>
                  @endif
                @endforeach
              </h1>
            </div>
            <div class="layui-card-body">
              @foreach($data as $key => $val)
                <fieldset class="layui-elem-field">
                  <div class="layui-field-box">
                    <div class="container">
                      @foreach($val as $role_id => $rank_info)
                        <div class="item">
                          <img src="./images/{{$role_id}}.webp" alt="图片">
                          <div class="details">
                              @foreach($rank_info as $k => $v)
                                <div class="{{ $v['color'] }}">{{ $v['rank_name'] }}</div>
                              @endforeach
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </fieldset>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
@section('js')
<script src="./layuiadmin/dom-to-image.min.js"></script>

<script>
    var node = document.getElementById('content');

    // 设置缩放因子以提高图像分辨率
    var scale = 3; // 2 倍分辨率
    var options = {
      quality: 1, // 对于 JPEG，设置为 0 到 1 之间的值
      width: node.offsetWidth * scale,
      height: node.offsetHeight * scale,
      style: {
        transform: `scale(${scale})`,
        transformOrigin: 'top left'
      }
    };

    domtoimage.toPng(node, options)
        .then(function (dataUrl) {
            // 创建一个Date对象，它将包含当前日期和时间
            var currentDate = new Date();

            // 使用Date对象的方法获取各个时间组件
            var year = currentDate.getFullYear();
            var month = currentDate.getMonth() + 1; // 月份是从0开始的，所以需要加1
            var day = currentDate.getDate();
            var hours = currentDate.getHours();
            var minutes = currentDate.getMinutes();
            var seconds = currentDate.getSeconds();

            // 创建一个链接元素并设置其属性
            var link = document.createElement('a');
            link.href = dataUrl;
            link.download = year+'年'+month+'月公会战角色.png';

            // 模拟点击链接以下载图片
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        })
        .catch(function (error) {
            console.error('Error generating image: ', error);
        });
</script>


<script>
  layui.config({
    base: '/layuiadmin/' //静态资源所在路径
  }).extend({
    index: 'lib/index' //主入口模块
  }).use(['index', 'form', 'colorpicker'], function(){
    var o = layui.$
    ,colorpicker = layui.colorpicker
    ,form = layui.form;
  })
</script>
@endsection