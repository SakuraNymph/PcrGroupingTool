@extends('main')
@section('content')
<style type="text/css">




.role{
  opacity: 0.6;
}
#show{
  height: auto;
}
#show>img{
  max-width: 12%;
  height: auto;
  margin-left: 1px;
}
.title {
  margin-left:20px;
}
.new {
  margin-left:20px;
}

.container {
    position: relative;
    display: inline-block;
    max-width: 12%; /* 固定图片的宽度 */
    height: auto;
    margin-left: 1px;
}

.container img {
    width: 100%;
    height: auto;
    display: block;
}

.text-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2em; /* 使用相对单位 em 来根据容器的大小调整字体 */
    color: red;
    font-weight: bold;
}







</style>

  <div class="layui-form" lay-filter="layuiadmin-app-form-list" id="layuiadmin-app-form-list" >



    <div class="layui-form-item">
      <label class="title">预览</label>
      <div class="layui-input-block">
        <div class="layui-col-md6" id="show">
        </div>
      </div>
    </div>

    <div class="layui-form-item">
      <label class="title">Boss</label>
      <div class="layui-col-md12 new">
          <input type="radio" name="boss" value="1" title="E1" @if($bossId == 1) checked @endif> 
          <input type="radio" name="boss" value="2" title="E2" @if($bossId == 2) checked @endif>
          <input type="radio" name="boss" value="3" title="E3" @if($bossId == 3) checked @endif>
          <input type="radio" name="boss" value="4" title="E4" @if($bossId == 4) checked @endif>
          <input type="radio" name="boss" value="5" title="E5" @if($bossId == 5) checked @endif>
        </div>
    </div>

    <div class="layui-form-item">
      <label class="title">伤害（1-99999）</label>
      <div class="layui-col-md12 new">
          <input type="number" name="score" placeholder="预估伤害" lay-verify="required|number" class="layui-input" @if(isset($data['score']) && $data['score']) value="{{$data['score']}}" @endif>
        </div>
    </div>

    <div class="layui-form-item">
      <label class="title">操作方式</label>
      <div class="layui-col-md12 new">
        <input type="checkbox" name="auto" lay-skin="switch" lay-filter="switchTest" lay-text="(半)AUTO|手动" value="1" @if(isset($data['auto']) && $data['auto']) checked @endif>
      </div>
    </div>

    <div class="layui-form-item layui-form-text">
      <label class="title">备注</label>
      <textarea name="remark" placeholder="请输入内容" class="layui-textarea new">@if(isset($data['remark']) && $data['remark']) {{$data['remark']}} @endif</textarea>
    </div>

    <div class="layui-form-item">
      <label class="title">角色</label>
      <div class="layui-col-md12 new">
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



    <div class="layui-form-item">
      <div class="layui-input-block">
        <button type="button" class="layui-btn layui-bg-blue" id="layuiadmin-app-form-submit" lay-submit lay-filter="layuiadmin-app-form-submit">点击保存</button>
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
    var id              = "{{$id}}";

    getRoles(id);

    // $('.role').click(function() {

    //   console.log($(this).attr('src'));
    //   return false;
    //   const id     = $(this).attr('id');
    //   const select = $(this).attr('switch');
    //   const src    = $(this).attr('src');
    //   if (select == 0) {
    //     $(this).attr('switch', 1);
    //     $(this).css('opacity', 1);
    //     $('#show').append("<img id='img"+id+"' src='"+src+"'>");
    //     ids.push(id);
    //   }
    //   if (select == 1) {
    //     $(this).attr('switch', 0);
    //     $(this).css('opacity', 0.6);
    //     $('#img'+id).remove();
    //     const key = $.inArray(id, ids);
    //     ids.splice(key, 1);
    //   }
    // });

    // 使用事件委托
    $('.layui-colla-content').on('click', '.container', function() {
      const id     = Number($(this).find('.role').attr('val'));
      const select = $(this).find('.role').attr('switch');
      const src    = $(this).find('.role').attr('src');

      // console.log(id);
      // return false;

      // 被选中 由灰变彩
      if (select == 0) {
        const length = ids.length;
        if (length >= 5) {
          layer.alert('最多只能添加5个角色！', {icon:0});
          return false;
        }
        $(this).find('.role').attr('switch', 1);
        $(this).find('.role').css('opacity', 1);
        $('#show').append("<div id='img"+id+"' class='container'><img src='"+src+"'><div class='text-overlay'></div></div>");
        ids.push({role_id:id,status:1});
      }
      // 被选中 由彩变缺
      if (select == 1) {
        $(this).find('.role').attr('switch', -1);
        $('.text-overlay').text('');
        $(this).find('.text-overlay').text('缺');
        $('#img'+id).find('.text-overlay').text('缺');
        for (var i = 0; i < ids.length; i++) {
          if (ids[i].role_id == id) {
            ids[i].status = 0;
          } else {
            ids[i].status = 1;
            $('#role' + ids[i].role_id).attr('switch', 1);
          }
        }
      }
      // 被取消 由缺变灰
      if (select == -1) {
        $(this).find('.role').attr('switch', 0);
        $(this).find('.role').css('opacity', 0.6);
        $('#img'+id).remove();
        $(this).find('.text-overlay').text('');
        let temp = ids.filter(item => !(item.role_id == id && item.status == 0));
        ids = temp;
      }
    });

    form.on('select(author)', function(data){
      var elem = data.elem; // 获得 select 原始 DOM 对象
      let author_id = data.value; // 获得被选中的值
      var othis = data.othis; // 获得 select 元素被替换后的 jQuery 对象
      // layer.msg(this.innerHTML + ' 的 value: '+ value); // this 为当前选中 <option> 元素对象
      // getRoles(0);
    });



    function getRoles(teamId) {
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.get("{{ url('get_all_roles') }}?id=" + teamId, function(data) {
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
              let src ="{{ asset('images') }}" + '/' + data[key][k].image_id +".webp";
              html += "<img id='img"+data[key][k].role_id+"' src='"+src+"'>";
              ids.push({role_id:data[key][k].role_id,status:1});
            }
          }
        }
      }
      return html;
    }

    function makeHtml(data) {
      let html = '';
      for(const value of data) {
        html += '<div class="container">';
        if (value.switch == 1) {
          html += "<img class='role' val="+ value.role_id +" id=" + 'role' + value.role_id +" title="+ value.name +" alt="+ value.name +" style='opacity: 1;' switch='1' src=" + "{{ asset('images') }}" + '/' + value.image_id +".webp>";
        } else {
          html += "<img class='role' val="+ value.role_id +" id=" + 'role'+ value.role_id +" title="+ value.name +" alt="+ value.name +" switch='0' src=" + "{{ asset('images') }}" + '/' + value.image_id +".webp>";
        }
        html += '<div class="text-overlay"></div></div>';
      }
      return html;
    }




    //监听提交
    form.on('submit(layuiadmin-app-form-submit)', function(data){
      var field = data.field; //获取提交的字段

      
      // console.log(field.rank);
      // console.log(field.project);
      // console.log(ids);
      // console.log(field);

      // return false;

      if (ids.length != 5) {
        layer.alert('请添加5名角色！',{icon:0});
        return false;
      }

      let status_num = 0;
      for (var i = 0; i < ids.length; i++) {
        if (ids[i].status == 0) {
          status_num += 1;
        }
      }

      if (status_num >= 2) {
        layer.alert('最多只能缺少一个角色！',{icon:0});
        return false;
      }


      var post_url = "{{ url('admin/team/add') }}";
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.post(post_url,
        {
          id:id,
          boss: field.boss,
          remark: field.remark,
          score: field.score,
          open: 2,
          auto:field.auto ?? 0,
          teams: ids
        }, function(data) {
        var obj = JSON.parse(data);
        if (obj.status) {
          layer.msg('成功');

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