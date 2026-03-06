<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>公主连结分刀助手</title>
<link rel="stylesheet" href="/layuiadmin/layui/css/layui.css" media="all">
<link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="icon" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
<link rel="icon" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

<style>
  /* 基础样式 */
  body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    flex-direction: column;
  }

  .button-container {
    text-align: center;
    position: relative;
    z-index: 1;
  }

  .button-container button {
    display: block;
    width: 200px;
    height: 40px;
    margin: 0 auto 10px auto;
    font-size: 16px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .button-container button:hover {
    background-color: #0056b3;
  }
  
  .button-container a {
    text-decoration: none;
    display: block;
    width: 200px;
    margin: 0 auto 10px auto;
  }

  .hint-text {
    font-size: 14px;
    color: #888;
    margin-top: 20px;
    text-align: center;
    width: 100%;
  }

  /* ==================== 新手引导样式 ==================== */
  .guide-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2147483647 !important; 
    background-color: transparent;
    pointer-events: none;
    display: none;
  }

  .guide-highlight-box {
    position: absolute;
    border: 2px solid #f1c40f;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75);
    border-radius: 4px;
    transition: all 0.5s ease;
    pointer-events: none;
    z-index: 2147483647 !important;
  }

  .guide-tooltip {
    position: absolute;
    z-index: 2147483648 !important; 
    background: #fff;
    width: 280px;
    max-width: 90%;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    padding: 15px;
    color: #333;
    pointer-events: auto;
    
    /* 优化样式：防止高度跳动 */
    min-height: 140px; 
    display: flex;
    flex-direction: column;
  }

  .guide-tooltip h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #007bff;
    line-height: 1.2;
  }

  .guide-tooltip p {
    margin: 0 0 15px 0;
    font-size: 14px;
    line-height: 1.5;
    flex-grow: 1; /* 撑开内容区，将底部按钮推至下方 */
  }

  .guide-actions {
    text-align: right;
    border-top: 1px solid #eee;
    padding-top: 10px;
    margin-top: auto; /* 确保按钮吸附在底部 */
  }

  .guide-btn {
    padding: 5px 12px;
    font-size: 12px;
    border-radius: 3px;
    cursor: pointer;
    border: none;
  }

  .guide-btn-next {
    background-color: #007bff;
    color: #fff;
  }

  .guide-btn-skip {
    background-color: transparent;
    color: #999;
    margin-right: 10px;
  }
  
  .guide-btn-skip:hover {
    color: #666;
  }

</style>
</head>
<body>

<div class="button-container">
  
  @if(Auth::guard('user')->check())
  <button onclick="location.href='{{ url("user/account/list") }}'">我的账号</button>
  @endif
  
  <button id="my_team_btn" onclick="location.href='{{ url("team") }}'">我的作业</button>
  <button id="team_list_btn" onclick="location.href='{{ url("team_list") }}'">作业列表</button>
  <button id="subscribe">作业订阅</button>
  
  <!-- 位置调整：移动到作业订阅下方 -->
  <button id="firstDayHomeWork">满补分刀(体验账号)</button>
  
  <!-- D面分刀保持原位 -->
  <button id="visitor">D面分刀(体验账号)</button>
  
  <button id="b_stage_table">B面排班表</button>
  <button id="teach">使用教程</button>
  <button id="support">支持作者</button>
  <button id="bug">bug反馈</button>
  
  <a href="https://github.com/SakuraNymph/PcrGroupingTool"><button>github</button></a>
  
  @if(!Auth::guard('user')->check())
  <button onclick="location.href='{{ url("register") }}'">注册</button>
  <button onclick="location.href='{{ url("login") }}'">登录</button>
  @endif
</div>

<!-- 页面底部的提示文字 -->
<div class="hint-text">
  <p>每月季前赛前一天开始更新作业数据</p>
  <p>稳定400/稳定800公会招人,群【585880616】</p>
</div>

<!-- 引导层结构 -->
<div id="guideLayer" class="guide-overlay">
  <div id="guideHighlight" class="guide-highlight-box"></div>
  <div id="guideTooltip" class="guide-tooltip">
    <h3 id="guideTitle">标题</h3>
    <p id="guideText">内容介绍</p>
    <div class="guide-actions">
      <button class="guide-btn guide-btn-skip" id="guideSkipBtn">跳过全部</button>
      <button class="guide-btn guide-btn-next" id="guideNextBtn">下一步</button>
    </div>
  </div>
</div>

<script src="/layuiadmin/layui/layui.js"></script>
<script src="/layuiadmin/jquery-3.4.1.min.js"></script>

<script type="text/javascript">
  layui.use(['layer', 'jquery'], function () {
    var layer = layui.layer;
    var $ = layui.jquery;

    // 【修改点1】新增变量控制引导开关
    // 建议后端传递 $switch 变量
    // false = 未完成，开启引导
    // true  = 已完成/手动关闭，跳过引导
    const WELCOME_GUIDE_STATUS = @json($switch ?? false);

    function isMobileDevice() {
        return /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    // ==================== 按钮事件绑定 ====================

    // 1. 首日满补分刀按钮 (手动点击时弹出空层)
    $('#firstDayHomeWork').click(function () {
      layer.open({
        type: 2,
        title: '首日满补分刀',
        content: "{{ url('user/visitor/group') }}", 
        maxmin: true,
        area: ['100%', '100%']
      });
    });

    // 2. D面分刀按钮 (手动点击时弹出空层)
    $('#visitor').click(function () {
      layer.open({
        type: 2,
        title: 'D面分刀',
        content: "{{ url('user/visitor/team') }}", 
        maxmin: true,
        area: ['100%', '100%']
      });
    });

    $('#subscribe').click(function () {
      const uid = "{{ $uid }}";
      if (uid) {
        layer.open({
          type: 2,
          title: '作业订阅',
          content: "{{ url('subscribe') }}",
          maxmin: true,
          area: ['80%', '60%']
        });
      } else {
        layer.msg('请注册后使用');
      }
    });

    $('#b_stage_table').click(function () {
      layer.open({
        type: 2,
        title: 'B阶段排班表',
        content: "{{ url('b_stage_table') }}",
        maxmin: true,
        area: ['60%', '90%']
      });
    });

    $('#bug').click(function () {
      layer.open({
        type: 2,
        title: 'bug反馈',
        content: "{{ url('bug') }}",
        maxmin: true,
        area: ['60%', '100%']
      });
    });

    $('#teach').click(function () {
      $.ajax({
          url: "{{ url('user/guide/update') }}",
          type: 'POST',
          data: {
              location: 'reset'
          },
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
          },
          success: function(res) {
            startGuide();
              console.log('引导状态记录成功');
          },
          error: function(xhr, status, error) {
              console.error('引导状态记录失败', error);
          }
      });
    });

    $('#support').click(function () {
      layer.open({
        type: 2,
        title: '支持作者',
        content: "{{ url('support') }}",
        maxmin: true,
        area: ['40%', '100%']
      });
    });

    // ==================== 新手引导逻辑 ====================
    
    // 配置后端记录接口
    const RECORD_URL = "{{ url('user/guide/update') }}"; 

    function recordGuideStatus() {
        // 【修改点2】执行 AJAX 请求，无论是完成还是跳过都会触发
        $.ajax({
            url: RECORD_URL,
            type: 'POST',
            data: {
                location: 'welcome'
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
            },
            success: function(res) {
                console.log('引导状态记录成功');
            },
            error: function(xhr, status, error) {
                console.error('引导状态记录失败', error);
            }
        });
    }
    
    // 引导步骤定义
    const guideSteps = [
      {
        selector: '#visitor', 
        title: 'D面分刀',
        text: '体验公会战D面作业一键分刀'
      },
      {
        selector: '#firstDayHomeWork', 
        title: '首日满补分刀',
        text: '体验公会战首日B/C+D满补分刀功能(上月数据)'
      },
      {
        selector: '#team_list_btn',
        title: '作业列表',
        text: '查看当月公会战作业。'
      },
      {
        selector: '#my_team_btn',
        title: '我的作业',
        text: '查看我的公会战作业及公会战分刀情况。'
      }
    ];

    let currentGuideIndex = 0;
    const $guideLayer = $('#guideLayer');
    const $guideHighlight = $('#guideHighlight');
    const $guideTooltip = $('#guideTooltip');
    const $guideTitle = $('#guideTitle');
    const $guideText = $('#guideText');
    const $guideNextBtn = $('#guideNextBtn');
    const $guideSkipBtn = $('#guideSkipBtn');

    function endGuide() {
      $guideLayer.hide();
      currentGuideIndex = 0;
      layer.closeAll(); 
    }

    function showGuideStep(index) {
      if (index >= guideSteps.length) {
        endGuide();
        return;
      }

      const step = guideSteps[index];
      const $target = $(step.selector);

      if ($target.length === 0) {
         if (!window.guideRetryCount) window.guideRetryCount = 0;
         if (window.guideRetryCount < 30) {
             window.guideRetryCount++;
             setTimeout(function(){
                 if(index === currentGuideIndex) showGuideStep(index);
             }, 100);
         } else {
             window.guideRetryCount = 0;
             endGuide();
         }
         return;
      }
      
      window.guideRetryCount = 0;

      const offset = $target.offset();
      const width = $target.outerWidth();
      const height = $target.outerHeight();

      $guideHighlight.css({
        top: offset.top - 4,
        left: offset.left - 4,
        width: width + 8,
        height: height + 8
      });

      $guideTitle.text(step.title);
      $guideText.text(step.text);
      
      // 逻辑：如果是最后一步（我的作业），将按钮文字改成"完成"
      if (index === guideSteps.length - 1) {
          $guideNextBtn.text('完成');
      } else {
          $guideNextBtn.text('下一步');
      }

      const tooltipWidth = 280;
      const windowWidth = $(window).width();
      const tooltipTop = offset.top + (height / 2) - 60;
      
      let tooltipLeft = offset.left + width + 20; 
      if (tooltipLeft + tooltipWidth > windowWidth) {
        tooltipLeft = offset.left - tooltipWidth - 20;
      }

      if (isMobileDevice()) {
          tooltipLeft = (windowWidth - tooltipWidth) / 2;
          $guideTooltip.css('top', (offset.top + height + 15) + 'px');
      } else {
          $guideTooltip.css('top', tooltipTop + 'px');
      }
      
      $guideTooltip.css('left', tooltipLeft + 'px');
      $guideLayer.show();
    }

    function startGuide() {
      // 【修改点3】检查开关变量
      // 如果 WELCOME_GUIDE_STATUS 为 true (已完成)，则直接返回，不显示引导
      if (WELCOME_GUIDE_STATUS === true) return;

      currentGuideIndex = 0;
      setTimeout(function() {
        showGuideStep(currentGuideIndex);
      }, 500);
    }

    // 下一步/完成 按钮逻辑
    $guideNextBtn.click(function() {
      // 判断是否是最后一步
      if (currentGuideIndex === guideSteps.length - 1) {
          // 点击"完成"，触发 AJAX 并关闭
          recordGuideStatus();
          endGuide();
      } else {
          // 正常下一步
          currentGuideIndex++;
          showGuideStep(currentGuideIndex);
      }
    });

    // 跳过全部 按钮逻辑
    $guideSkipBtn.click(function() {
      // 触发 AJAX 并关闭
      recordGuideStatus();
      endGuide();
    });

    $(document).ready(function() {
        // 页面加载完成后尝试启动引导
        startGuide();
    });

  });
</script>

</body>
</html>
