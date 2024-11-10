@extends('main')
@section('content')
<style type="text/css">



img {
  width: 50px;
  height: 50px;
}

.role{
  opacity: 0.6;
}




</style>

  <div class="layui-form" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list" style="padding: 20px 30px 0 0;">







    <div class="layui-form-item">
      <label class="layui-form-label">预览</label>
      <div class="layui-input-block">
        <div class="layui-col-md6" id="show">
        </div>
      </div>
    </div>

    <div class="layui-form-item">
      <label class="layui-form-label">方案</label>
      <div class="layui-input-block">
        <div class="layui-col-md6">
          <select name="project" lay-filter="author">
            @foreach($authors as $author)
              <option value="{{ $author->id }}" @if($author_id == $author->id) selected @endif>{{ $author->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <div class="layui-form-item">
      <label class="layui-form-label">rank</label>
      <div class="layui-input-block">
        <div class="layui-col-md6">
          <input type="text" name="rank" placeholder="rank" lay-verify="required" class="layui-input" @if(isset($data->rank_name) && $data->rank_name) value="{{$data->rank_name}} @endif">
        </div>
      </div>
    </div>

    <div class="layui-form-item">
      <label class="layui-form-label">角色</label>
      <div class="layui-input-block">
        <div class="layui-col-md6">
          <div class="layui-collapse" lay-accordion>
            <div class="layui-colla-item">
              <div class="layui-colla-title">前卫</div>
              <div class="layui-colla-content layui-show front-roles">
              </div>
            </div>
            <div class="layui-colla-item">
              <div class="layui-colla-title">中卫</div>
              <div class="layui-colla-content middle-roles">
              </div>
            </div>
            <div class="layui-colla-item">
              <div class="layui-colla-title">后卫</div>
              <div class="layui-colla-content back-roles">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>



    <div class="layui-form-item">
      <div class="layui-input-block">
        <button type="button" class="layui-btn layui-bg-blue" id="layuiadmin-app-form-submit" lay-submit lay-filter="layuiadmin-app-form-submit">点击保存</button>
        <!-- <input type="button" lay-submit lay-filter="layuiadmin-app-form-submit" id="layuiadmin-app-form-submit" value="确认添加"> -->
        <!-- <input type="button" lay-submit lay-filter="layuiadmin-app-form-edit" id="layuiadmin-app-form-edit" value="确认编辑"> -->
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
  }).use(['index', 'form', 'colorpicker'], function(){
    var o = layui.$
    ,colorpicker = layui.colorpicker
    ,form = layui.form;



    var ids             = [];
    var ids_json        = '{!! $ids_json !!}';
    if (ids_json) { ids = JSON.parse(ids_json); }
    var id              = "{{$id}}";
    var author_id       = "{{$author_id}}";

    getRoles(author_id);

    $('.role').click(function() {

      // console.log($(this).attr('src'));
      // return false;
      const id     = Number($(this).attr('id'));
      const select = $(this).attr('switch');
      const src    = $(this).attr('src');
      if (select == 0) {
        $(this).attr('switch', 1);
        $(this).css('opacity', 1);
        $('#show').append("<img id='img"+id+"' src='"+src+"'>");
        ids.push(id);
      }
      if (select == 1) {
        $(this).attr('switch', 0);
        $(this).css('opacity', 0.6);
        $('#img'+id).remove();
        const key = $.inArray(id, ids);
        ids.splice(key, 1);
      }
    });

    // 使用事件委托
    $('.layui-colla-content').on('click', '.role', function() {
      const id     = $(this).attr('id');
      const select = $(this).attr('switch');
      const src    = $(this).attr('src');
      if (select == 0) {
        $(this).attr('switch', 1);
        $(this).css('opacity', 1);
        $('#show').append("<img id='img"+id+"' src='"+src+"'>");
        ids.push(id);
      }
      if (select == 1) {
        $(this).attr('switch', 0);
        $(this).css('opacity', 0.6);
        $('#img'+id).remove();
        const key = $.inArray(id, ids);
        ids.splice(key, 1);
      }
    });


    form.on('select(author)', function(data){
      var elem = data.elem; // 获得 select 原始 DOM 对象
      let author_id = data.value; // 获得被选中的值
      var othis = data.othis; // 获得 select 元素被替换后的 jQuery 对象
      // layer.msg(this.innerHTML + ' 的 value: '+ value); // this 为当前选中 <option> 元素对象
      getRoles(author_id);
    });



    function getRoles(author_id) {
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post("{{ url('get_can_use_roles') }}", {author_id:author_id, id:id}, function(data) {
        const obj = JSON.parse(data);
        if (obj.status == 1) {
          // 替换所有具有 your-class 的元素的 HTML 内容
          $('.front-roles').html(makeHtml(obj.result[1]));
          $('.middle-roles').html(makeHtml(obj.result[2]));
          $('.back-roles').html(makeHtml(obj.result[3]));
          $('#show').html(makeShow(obj.result));
        }
      });
    }

    function makeShow(data) {
      let html = '';
      for(const key in data) {
        if (Array.isArray(data[key])) {
          for(const k in data[key]) {
            if (data[key][k].switch == 1) {
              let src ="./images/"+ data[key][k].role_id +".webp";
              html += "<img id='img"+data[key][k].role_id+"' src='"+src+"'>";
            }
          }
        }
      }
      return html;
    }

    function makeHtml(data) {
      let html = '';
      for(const value of data) {
        if (value.switch == 1) {
          html += "<img class='role' id="+ value.role_id +" title="+ value.nickname +" alt="+ value.nickname +" style='opacity: 1;' switch='1' src='./images/"+ value.role_id +".webp'>";
        } else {
          html += "<img class='role' id="+ value.role_id +" title="+ value.nickname +" alt="+ value.nickname +" switch='0' src='./images/"+ value.role_id +".webp'>";
        }
      }
      return html;
    }




    //监听提交
    form.on('submit(layuiadmin-app-form-submit)', function(data){
      var field = data.field; //获取提交的字段

      
      // console.log(field.rank);
      // console.log(field.project);
      // console.log(ids);

      // return false;


      var post_url = "{{ url('add') }}";
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post(post_url,
        {
          id: id,
          rank_name: field.rank,
          author_id: field.project,
          role_ids: ids
        }, function(data) {
        var obj = JSON.parse(data);
        if (obj.status) {
          layer.msg('修改成功');

          var index = parent.layer.getFrameIndex(window.name); // 先得到当前 iframe 层的索引
          parent.layer.close(index); // 再执行关闭


          // var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
            // parent.layui.table.reload('LAY-app-content-list'); //重载表格
            window.parent.location.reload();

            // parent.layer.close(index); //再执行关闭
            // parent.layer.msg('添加成功');
            // console.log(data);
          // renderTable(obj.result);
        } else {
          layer.msg(obj.result.message);
          return false;
        }
      });
    });
  })
  </script>
@endsection