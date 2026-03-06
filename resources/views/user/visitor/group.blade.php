@extends('main')

@section('css')
<style>
    /* ==================== 基础样式 ==================== */
    :root {
        --primary: #4f46e5;
        --primary-hover: #4338ca;
        --danger: #ef4444;
        --text-main: #1f2937;
        --text-sub: #6b7280;
        --bg-body: #f3f4f6;
        --bg-card: #ffffff;
        --border: #e5e7eb;
        --radius: 12px;
        --avatar-size: 50px;
    }

    body {
        font-family: 'Inter', "PingFang SC", "Microsoft YaHei", sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        margin: 0;
        padding: 15px;
        font-size: 14px;
    }

    .app-container { max-width: 1000px; margin: 0 auto; }

    /* 顶部提示 */
    .alert-box {
        background: #fff7ed; border: 1px solid #ffedd5; color: #c2410c;
        padding: 12px; border-radius: var(--radius);
        text-align: center; font-weight: 500; margin-bottom: 20px;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }

    /* 卡片通用样式 */
    .card {
        background: var(--bg-card); border-radius: var(--radius);
        box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid var(--border);
        padding: 20px; margin-bottom: 20px;
    }

    .card-header {
        font-size: 16px; font-weight: 700; margin-bottom: 15px;
        padding-bottom: 10px; border-bottom: 1px solid #f3f4f6;
        color: var(--text-main); display: flex; align-items: center; gap: 10px;
    }
    
    .card-header-icon { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); }

    /* ==================== 筛选控制栏 ==================== */
    .filter-bar {
        display: flex;
        flex-direction: column;
        align-items: flex-start; 
        gap: 15px; 
        background: #f9fafb;
        padding: 15px;
        border-radius: 8px;
        width: 100%;
    }
    
    .filter-group {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 10px;
        width: 100%;
    }

    .filter-label {
        font-weight: 600; font-size: 13px; color: var(--text-sub);
        white-space: nowrap;
        min-width: 40px;
        text-align: right;
    }

    .formation-options-wrap {
        display: flex;
        flex-direction: row;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* ==================== 美化单选按钮 ==================== */
    .custom-radio { display: none; }
    .radio-label {
        padding: 3px 10px;
        font-size: 12px;
        cursor: pointer;
        border-radius: 6px;
        border: 1px solid transparent;
        color: var(--text-sub);
        background: rgba(0,0,0,0.03);
        transition: all 0.2s;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .radio-label:hover { color: var(--primary); background: rgba(79, 70, 229, 0.05); }
    
    /* 选中状态 */
    .custom-radio:checked + .radio-label {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
    }

    /* ==================== Boss 列表区域 ==================== */
    .boss-section-title {
        font-size: 14px; color: var(--primary); font-weight: 600;
        margin-bottom: 10px; display: block;
    }
    .boss-container { display: flex; flex-direction: column; gap: 10px; }
    .boss-row {
        display: flex; justify-content: center; align-items: center;
        background: #fff; padding: 6px; border-radius: 8px;
        border: 1px dashed #d1d5db;
    }
    .boss-list {
        display: flex; gap: 8px; padding: 4px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .boss-item {
        cursor: pointer; border-radius: 4px; border: 2px solid transparent;
        transition: all 0.2s; flex-shrink: 0; position: relative;
    }
    .boss-item img {
        display: block; width: 50px; height: 50px; border-radius: 4px;
        opacity: 0.5; background: #f3f4f6; object-fit: contain;
    }
    .boss-item.selected img { opacity: 1; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.5); transform: scale(1.05); }

    /* ==================== 结果区域 ==================== */
    .result-container { display: flex; flex-direction: column; gap: 20px; }

    .batch-group {
        display: flex; flex-direction: column; gap: 12px;
        padding: 15px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.05);
    }
    .batch-group.red { background-color: #fef2f2; border-color: #fecaca; }
    .batch-group.blue { background-color: #eff6ff; border-color: #bfdbfe; }

    .team-group {
        background: #fff; border: 1px solid rgba(0,0,0,0.1);
        border-radius: 8px; margin-bottom: 0;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    
    .team-header {
        background: #f9fafb; padding: 8px 12px; border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex; align-items: center; justify-content: space-between;
    }
    
    /* 标题组样式 - 紧挨着 */
    .title-group { 
        display: inline-flex; 
        align-items: baseline; 
        gap: 0; 
        margin: 0;
        padding: 0;
    }
    
    .boss-id-wrap { position: relative; } /* 相对定位用于引导 */
    
    .stage-tag { 
        font-weight: 800; font-size: 16px; 
        margin: 0; padding: 0;
        display: inline-block;
    }
    .boss-tag { font-size: 16px; font-weight: 700; color: var(--text-main); margin: 0; padding: 0; display: inline-block; }
    .dmg-tag { font-size: 13px; color: var(--text-sub); margin-left: 5px; }
    .team-content { padding: 10px; }
    
    .flex-row {
        display: flex; align-items: center; flex-wrap: nowrap;
        margin-bottom: 8px; justify-content: center;
        gap: 4px;
    }
    .borrow-tag {
        font-weight: 800; color: var(--primary); margin: 0 4px;
        font-size: 16px; align-self: center; flex-shrink: 0;
    }
    .unit-img {
        width: var(--avatar-size); height: var(--avatar-size);
        object-fit: contain; border-radius: 4px;
        border: 1px solid #f3f4f6; flex-shrink: 1;
    }
    .unit-img.disabled { opacity: 0.4; }

    /* 彩虹边框 */
    .rainbow-border {
        position: relative; display: inline-flex; align-items: center;
        padding: 4px; gap: 3px; border-radius: 8px;
        background: #fff; cursor: pointer; transition: transform 0.2s;
        z-index: 0; flex-shrink: 0;
    }
    .rainbow-border:hover { transform: scale(1.02); }
    .rainbow-border.locked { background: #fff; }
    .rainbow-border.locked::before {
        content: ''; position: absolute;
        top: -5px; left: -5px; right: -5px; bottom: -5px;
        background: linear-gradient(135deg, #ef4444, #f97316, #eab308, #22c55e, #06b6d4, #3b82f6, #a855f7, #ef4444);
        background-size: 300% 300%; border-radius: 12px;
        z-index: -1; animation: gradientMove 3s linear infinite;
    }
    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .team-remark { margin-top: 6px; font-size: 12px; color: #9ca3af; line-height: 1.4; text-align: center; }
    .btn-group { display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; justify-content: flex-start; }
    .mini-btn {
        padding: 4px 8px; font-size: 12px; line-height: 1.2;
        border-radius: 6px; border: 1px solid var(--primary);
        background: rgba(79, 70, 229, 0.05); color: var(--primary);
        cursor: pointer; margin: 0; transition: all 0.2s;
    }
    .mini-btn:hover { background: var(--primary); color: #fff; }
    .mini-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
    .expand-area {
        margin-top: 10px; padding: 10px; background: #f9fafb;
        border-radius: 8px; border: 1px solid #eee; display: none;
    }
    .expand-area h2 { margin: 0 0 5px 0; font-size: 13px; color: var(--text-sub); }
    .expand-area img { max-width: 100%; border-radius: 4px; margin-top: 5px; }
    .link-jump-btn {
        display: inline-block; padding: 8px 20px; margin-top: 10px;
        background-color: var(--primary); color: #fff; text-decoration: none;
        border-radius: 4px; font-size: 13px; font-weight: 500;
        transition: background 0.2s; text-align: center; width: 100%; box-sizing: border-box;
    }
    .link-jump-btn:hover { background-color: var(--primary-hover); }

    /* ==================== 模态框样式 ==================== */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(2px);
        z-index: 10000; display: none; justify-content: center; align-items: center;
    }
    .modal-box {
        background: #fff; padding: 20px; border-radius: 12px;
        width: 85%; max-width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        text-align: center; animation: popIn 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes popIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
    .modal-title { font-weight: bold; font-size: 16px; margin-bottom: 10px; color: var(--text-main); }
    .modal-desc { font-size: 13px; color: var(--text-sub); margin-bottom: 20px; line-height: 1.5; }
    .modal-actions { display: flex; flex-direction: column; gap: 10px; }
    .modal-btn { width: 100%; padding: 10px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
    .btn-lock { background: var(--primary); color: white; }
    .btn-unlock { background: #f3f4f6; color: var(--text-main); }
    .btn-hide { background: #fef2f2; color: var(--danger); }

    #toast {
        position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
        background: #333; color: #fff; padding: 8px 16px; border-radius: 20px;
        display: none; z-index: 11000; font-size: 13px;
    }

    /* ==================== 独立层高亮引导样式 (修复版) ==================== */
    /* 独立的高亮层，不依赖目标元素的样式 */
    #guide-spotlight {
        position: fixed;
        z-index: 10002; /* 在 modal (10000) 之上 */
        /* 巨大的阴影模拟全屏变暗 */
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75);
        pointer-events: none; /* 让点击穿透到底层 */
        border-radius: 4px;
        opacity: 0;
        transition: opacity 0.3s, top 0.3s, left 0.3s, width 0.3s, height 0.3s;
        border: 2px solid rgba(255, 255, 255, 0.5); /* 增加高亮边框 */
    }

    #guide-spotlight.active {
        opacity: 1;
    }

    /* 提示框样样式 */
    .guide-tooltip {
        position: fixed;
        z-index: 10003; /* 比高亮层更高 */
        width: 280px;
        background: #fff;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        color: #333;
        font-size: 14px;
        display: none;
        top: 0; left: 0;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .guide-tooltip.active { opacity: 1; }

    .guide-tooltip h4 {
        margin: 0 0 8px 0;
        font-size: 16px;
        color: var(--primary);
    }
    
    .guide-tooltip p {
        margin: 0 0 15px 0;
        line-height: 1.5;
        color: #666;
    }

    .guide-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .guide-btn {
        padding: 6px 14px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }

    .guide-btn-skip {
        background: #f3f4f6;
        color: #666;
    }
    .guide-btn-skip:hover { background: #e5e7eb; }

    .guide-btn-next {
        background: var(--primary);
        color: #fff;
    }
    .guide-btn-next:hover { background: var(--primary-hover); }

    .guide-step-count {
        font-size: 12px;
        color: #999;
    }

    /* 移动端适配 */
    @media (max-width: 768px) {
        :root { --avatar-size: 38px; }
        .card { padding: 10px; }
        .filter-bar { gap: 12px; padding: 10px; }
        .filter-group { gap: 6px; padding-bottom: 2px; }
        .filter-label { min-width: auto; font-size: 12px; margin-right: 4px; }
        .formation-options-wrap { gap: 4px; }
        
        .boss-row { padding: 2px 0; }
        .boss-list { justify-content: center; padding: 0; }
        .boss-item { width: 14%; margin: 0.5%; }
        .boss-item img { width: 100%; height: auto; aspect-ratio: 1/1; }
        .flex-row { gap: 2px; justify-content: center; }
        .borrow-tag { margin: 0 2px; font-size: 14px; }
        .rainbow-border { gap: 1px; padding: 2px; }
        .batch-group { padding: 10px; gap: 10px; }
        .guide-tooltip { width: 80% !important; left: 10% !important; }
    }
</style>
@endsection

@section('content')

<div class="app-container">
    <!-- 顶部提示 -->
    <div class="alert-box">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77-1.333.192 3 1.732 3z"></path></svg>
        <span>分刀结果仅供参考，请以实际box练度为准</span>
    </div>

    <!-- Boss 选择区域 -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"></div> Boss 选择
        </div>
        
        <!-- 低阶段 (B&C) -->
        <span class="boss-section-title">低阶段 (B & C)</span>
        <div id="bossListB" class="boss-container">
            <div style="text-align: center; color: #999; padding:10px;">加载中...</div>
        </div>

        <!-- 高阶段 (D) -->
        <span class="boss-section-title" style="margin-top: 15px;">高阶段 (D)</span>
        <div id="bossListD" class="boss-container">
            <div style="text-align: center; color: #999; padding:10px;">加载中...</div>
        </div>

        <!-- 筛选条件 -->
        <div class="filter-bar" style="margin-top: 20px;">
            @if($select_is_show)
            <div class="filter-group">
                <span class="filter-label">模式:</span>
                <input type="radio" name="type" id="type_auto" value="1" class="custom-radio" checked>
                <label for="type_auto" class="radio-label">自动/半自动</label>
                <input type="radio" name="type" id="type_manual" value="2" class="custom-radio">
                <label for="type_manual" class="radio-label">手动</label>
            </div>

            <div class="filter-group">
                <span class="filter-label">阶段:</span>
                <input type="radio" name="stage" id="stage_all" value="1" class="custom-radio" checked>
                <label for="stage_all" class="radio-label">不限</label>
                <input type="radio" name="stage" id="stage_b" value="2" class="custom-radio">
                <label for="stage_b" class="radio-label">B阶段</label>
                <input type="radio" name="stage" id="stage_c" value="3" class="custom-radio">
                <label for="stage_c" class="radio-label">C阶段</label>
            </div>
            @endif

            <div class="filter-group">
                <span class="filter-label">阵型:</span>
                <div class="formation-options-wrap">
                    <input type="radio" name="atk_type" id="atk_0" value="0" class="custom-radio" checked>
                    <label for="atk_0" class="radio-label">不限</label>
                    <input type="radio" name="atk_type" id="atk_1" value="1" class="custom-radio">
                    <label for="atk_1" class="radio-label">3物</label>
                    <input type="radio" name="atk_type" id="atk_2" value="2" class="custom-radio">
                    <label for="atk_2" class="radio-label">2物</label>
                    <input type="radio" name="atk_type" id="atk_3" value="3" class="custom-radio">
                    <label for="atk_3" class="radio-label">2法</label>
                    <input type="radio" name="atk_type" id="atk_4" value="4" class="custom-radio">
                    <label for="atk_4" class="radio-label">3法</label>
                </div>
            </div>
        </div>
    </div>

    <!-- 结果展示 -->
    <div id="show" class="result-container"></div>

    <!-- 模态框 -->
    <div class="modal-overlay" id="actionModal">
        <div class="modal-box">
            <div class="modal-title">作业操作</div>
            <div class="modal-desc">【锁定】套餐将固定此作业<br>【解锁】套餐解除锁定状态<br>【隐藏】套餐将排除此作业</div>
            <div class="modal-actions">
                <button class="modal-btn btn-lock" id="btn-lock">锁定作业</button>
                <button class="modal-btn btn-unlock" id="btn-unlock">解锁作业</button>
                <button class="modal-btn btn-hide" id="btn-hide">隐藏作业</button>
            </div>
        </div>
    </div>

    <!-- 引导层 DOM -->
    <div id="guide-spotlight"></div>
    
    <!-- 引导 Tip -->
    <div id="guideTooltip" class="guide-tooltip">
        <h4 id="guideTitle"></h4>
        <p id="guideText"></p>
        <div class="guide-footer">
            <span id="guideCount" class="guide-step-count"></span>
            <div style="display:flex; gap:8px;">
                <button id="guideBtnPrev" class="guide-btn guide-btn-skip">跳过全部</button>
                <button id="guideBtnNext" class="guide-btn guide-btn-next">下一步</button>
            </div>
        </div>
    </div>

    <div id="toast"></div>
</div>

@endsection

@section('js')
<script>
$(document).ready(function() {
    // ================== 全局配置 ==================
    // var GUIDE_DISABLED = @json($switch);
    var GUIDE_DISABLED = false;

    // ==================== 业务逻辑区域 ====================
    var id            = "{{ $id }}";
    var type          = $('input[name="type"]:checked').val();
    var stage         = $('input[name="stage"]:checked').val();
    var atk           = $('input[name="atk_type"]:checked').val();
    var bossMapB      = {row1:0, row2:0, row3:0};
    var bossMapD      = {row1:0, row2:0, row3:0};
    var lockedIdsB    = []; 
    var lockedIdsD    = []; 
    var hiddenIds     = []; 
    
    var tempCurrentId = '';
    var tempStage     = '';
    
    window.jumpToStepAfterRender = null;

    $('input[name="type"]').on('change', function() { type = $(this).val(); getTeamGroups(); });
    $('input[name="stage"]').on('change', function() { stage = $(this).val(); getTeamGroups(); });
    $('input[name="atk_type"]').on('change', function() { atk = $(this).val(); getTeamGroups(); });

    function renderBossUI(listId, map) {
        $.get("{{ url('get_this_month_boss_list') }}", function (res) {
            var obj = typeof res === 'string' ? JSON.parse(res) : res;
            if (obj.status == 1) {
                var data = obj.data;
                var html = "";
                for (var row = 1; row <= 3; row++) {
                    html += "<div class='boss-row'><div class='boss-list'>";
                    for (var key in data) {
                        var isSelected = (map['row' + row] == data[key].sort) ? 'selected' : '';
                        html += "<div class='boss-item " + isSelected + "' data-list='" + listId + "' data-row='" + row + "' data-value='" + data[key].sort + "'><img src='{{ asset('boss') }}/" + data[key].file_path + "'></div>";
                    }
                    html += "</div></div>";
                }
                $('#' + listId).html(html);
            }
        });
    }

    function bindBossEvent(listId, map) {
        $('#' + listId).on('click', '.boss-item', function() {
            var $row = $(this).closest('.boss-list');
            var row = $(this).data('row');
            var value = $(this).data('value');
            var isSelected = $(this).hasClass('selected');
            if (isSelected) {
                $(this).removeClass('selected');
                map['row' + row] = 0;
            } else {
                $row.find('.boss-item').removeClass('selected');
                $(this).addClass('selected');
                map['row' + row] = value;
            }
            getTeamGroups();
        });
    }

    renderBossUI('bossListB', bossMapB);
    renderBossUI('bossListD', bossMapD);
    bindBossEvent('bossListB', bossMapB);
    bindBossEvent('bossListD', bossMapD);
    getTeamGroups();

    function getTeamGroups() {
      var url = "{{ url('user/visitor/first_day') }}";

      var conditions = {
        row1: bossMapB.row1, row2: bossMapB.row2, row3: bossMapB.row3,
        row4: bossMapD.row1, row5: bossMapD.row2, row6: bossMapD.row3,
        id: id, type: type, stage: stage, atk: atk,
        lockedIdsB: lockedIdsB, lockedIdsD: lockedIdsD, hiddenIds: hiddenIds
      };

      $.get(url, conditions, function (res) {
        var obj = typeof res === 'string' ? JSON.parse(res) : res;
        $('#show').html('');
        if (obj.status == 1) {
            var data = obj.result;
            if (Object.keys(data).length === 0) {
                $('#show').html("<div style='text-align:center; padding:30px; color:#999;'>无法分刀，请丰富您的box</div>");
            } else {
                for (var key in data) {
                    var colorType = (key % 2 == 0) ? 'red' : 'blue';
                    var batchContainer = $("<div class='batch-group " + colorType + "'></div>");
                    for (var k in data[key]) {
                        var colorBoss = '';
                        if (k % 3 == 0) colorBoss = '#00bcd4';
                        if (k % 3 == 1) colorBoss = '#f1c40f';
                        if (k % 3 == 2) colorBoss = '#8e44ad';
                        for (var kk in data[key][k]) {
                            var item = data[key][k][kk];
                            var stageName = (item.stage == '2') ? 'B' : (item.stage == '3') ? 'C' : 'D';
                            var rolesHtml = '';
                            for (var kkk in item.roles) {
                                var r = item.roles[kkk];
                                var style = r.status == 1 ? '' : 'opacity: 0.4;';
                                rolesHtml += "<img src='{{ asset('images') }}/" + r.image_id + ".webp' class='unit-img' style='" + style + "'>";
                            }
                            var borrowHtml = '';
                            if (item.borrow) {
                                for (var kkk in item.roles) {
                                    if (item.roles[kkk].role_id == item.borrow) { borrowHtml = "<img src='{{ asset('images') }}/" + item.roles[kkk].image_id + ".webp' class='unit-img'>"; break; }
                                }
                            } else { borrowHtml = "<img src='{{ asset('images') }}/renyi.webp' class='unit-img'>"; }

                            var btnsHtml = '';
                            for (var kkk in item.link) {
                                btnsHtml += "<button class='mini-btn action-btn' data-url='" + item.link[kkk].url + "' data-note='" + item.link[kkk].note + "'>" + item.link[kkk].text + "</button>";
                            }
                            var lockedClass = (stageName == 'D') ? (lockedIdsD.indexOf(item.id) != -1 ? 'locked' : '') : (lockedIdsB.indexOf(item.id) != -1 ? 'locked' : '');
                            var titleColor = (colorType == 'red') ? '#ef4444' : '#3b82f6';

                            batchContainer.append(
                                "<div class='team-group'>" +
                                "<div class='team-header'><div class='title-group'>" +
                                "<div class='boss-id-wrap'>" +
                                "<span class='stage-tag' style='color:" + titleColor + "'>" + stageName + "</span>" +
                                "<span class='boss-tag' style='color:" + colorBoss + "'>" + item.boss + "</span>" +
                                "</div><span class='dmg-tag'>预估伤害: " + item.damage + "</span>" +
                                "</div></div>" +
                                "<div class='team-content'>" +
                                "<div class='flex-row'>" +
                                "<div class='rainbow-border " + lockedClass + "' data-id='" + item.id + "' data-stage='" + stageName + "'>" + rolesHtml + "</div>" +
                                "<span class='borrow-tag'>借</span>" + borrowHtml +
                                "</div>" +
                                "<div class='team-remark'>" + item.remark + "</div>" +
                                "<div class='btn-group'>" + btnsHtml + "</div>" +
                                "<div class='expand-area'></div>" +
                                "</div>" +
                                "</div>"
                            ); 
                        } 
                    } 
                    $('#show').append(batchContainer);
                }
            } 
        } 
        
        // 【关键逻辑】监听是否需要在渲染后跳转
        if (window.jumpToStepAfterRender !== null && window.isGuideActive) {
            var targetStep = window.jumpToStepAfterRender;
            // 清空标志位
            window.jumpToStepAfterRender = null;
            // 解锁遮罩并跳转
            window.guideOverlayLocked = false;
            requestAnimationFrame(function() {
                showGuideStep(targetStep);
            });
        }
      }); 
    } 

    // 【修正】点击阵容 -> 打开弹窗
    $('.app-container').on('click', '.rainbow-border', function(e) {
        tempCurrentId = $(this).data('id');
        tempStage     = $(this).data('stage');
        $('#actionModal').css('display', 'flex');

        if (window.isGuideActive && guideCurrentStep === 6) {
            // 场景二/三：从第6步（阵容）点击后，进入第7步（弹窗）
            showGuideStep(7);
        }
    });

    $('#actionModal').on('click', function(e) {
        if (e.target === this) {
            $('#actionModal').css('display', 'none');
            if (window.isGuideActive) {
                // 如果引导中关闭弹窗，视作放弃当前步骤操作，但不结束引导
                // 可以根据需要回退一步，这里保持在当前步骤或重置高亮
            }
        }
    });

    // 标准业务逻辑
    function handleAction(action) {
        if (window.isGuideActive && guideCurrentStep === 7) return; 
        standardHandleLogic(action);
    }

    function standardHandleLogic(action) {
        if (action === 'lock') {
            var targetArray = (tempStage === 'D') ? lockedIdsD : lockedIdsB;
            if (targetArray.indexOf(tempCurrentId) === -1) { 
                if (targetArray.length >= 2) targetArray.shift(); 
                targetArray.push(tempCurrentId); 
            }
            var hIdx = hiddenIds.indexOf(tempCurrentId);
            if (hIdx !== -1) hiddenIds.splice(hIdx, 1);
        } else if (action === 'unlock') {
            var targetArray = (tempStage === 'D') ? lockedIdsD : lockedIdsB;
            var idx = targetArray.indexOf(tempCurrentId);
            if (idx !== -1) targetArray.splice(idx, 1);
        } else if (action === 'hide') {
            if (hiddenIds.indexOf(tempCurrentId) === -1) hiddenIds.push(tempCurrentId);
            if (lockedIdsB.indexOf(tempCurrentId) !== -1) lockedIdsB.splice(lockedIdsB.indexOf(tempCurrentId), 1);
            if (lockedIdsD.indexOf(tempCurrentId) !== -1) lockedIdsD.splice(lockedIdsD.indexOf(tempCurrentId), 1);
        }
        refreshChange([], [], []);
        $('#actionModal').css('display', 'none');
    }
    
    $('#btn-lock').on('click', function() { handleAction('lock'); });
    $('#btn-unlock').on('click', function() { handleAction('unlock'); });
    $('#btn-hide').on('click', function() { handleAction('hide'); });

    function refreshChange(pB, pD, pH) { getTeamGroups(); }

    $('#show').on('click', 'button', function () {
        var btn = $(this);
        if(!btn.hasClass('action-btn')) return; 
        if (window.isGuideActive && guideCurrentStep === 8) return; // 引导最后一步阻止点击，避免跳转

        var url   = btn.data('url');
        var note  = btn.data('note');
        var expandArea = btn.closest('.btn-group').next('.expand-area');
        var activeBtn = document.querySelector('.mini-btn.active');
        var isAlreadyActive = (activeBtn === btn[0]);
        document.querySelectorAll('.expand-area').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.mini-btn').forEach(el => el.classList.remove('active'));
        if (!isAlreadyActive) {
            btn.addClass('active');
            var contentHtml = '';
            if (note) { contentHtml += '<h2>备注: ' + note + '</h2>'; }
            if (url && url.trim() !== '') { contentHtml += '<a href="' + url + '" target="_blank" class="link-jump-btn">点击查看</a>'; }
            expandArea.html(contentHtml);
            expandArea.show();
        }
    });

    // ==========================================
    // 新手引导逻辑
    // ==========================================
    
    window.isGuideActive = false;
    var guideCurrentStep = 0;
    var spotlight = document.getElementById('guide-spotlight');
    var tooltip = document.getElementById('guideTooltip');

    window.lockGuideOverlay = function() {
        if (!spotlight) return;
        spotlight.style.transition = 'none';
        spotlight.style.top    = '-100vw';
        spotlight.style.left   = '-100vw';
        spotlight.style.width  = '300vw';
        spotlight.style.height = '300vh';
        spotlight.style.boxShadow = '0 0 0 0 rgba(0, 0, 0, 0.9)'; 
        spotlight.style.zIndex = '999990';
        spotlight.style.display = 'block';
        window.guideOverlayLocked = true;
    }

    // 步骤配置：
    // 0-5: 普通步骤
    // 6: 第七步用户视角（作业阵容 - Rainbow Border）
    // 7: 中间态（作业操作弹窗 - Modal Content）
    // 8: 最后一步（详情查看 - Mini Button）
    var GUIDE_STEPS = [
        { selector: '#bossListB .boss-item:first-child', title: '选择Boss', text: '这里是低阶段Boss列表，每行（每刀）最多只能选择一个boss。' },
        { selector: 'label[for="type_auto"]', title: '选择模式', text: '选择作业模式，包括“自动”或“手动”。' },
        { selector: 'label[for="stage_all"]', title: '选择Boss阶段', text: '点击此处可以筛选 B、C 不同阶段的作业。' },
        { selector: 'label[for="atk_0"]', title: '选择阵型', text: '这里可以筛选队伍的物理/法术构成。默认“不限”即可。' },
        { selector: '.batch-group:first-child .boss-id-wrap', title: 'Boss标识', text: '这里显示的是当前Boss的编号，对应不同的阶段的Boss。' },
        { selector: '.batch-group:first-child .dmg-tag', title: '预估伤害', text: '此数值展示了该作业在满足练度的情况下的预计输出伤害。' },
        { selector: '.batch-group:first-child .rainbow-border', title: '作业阵容', text: '👉 请点击此处（队伍图片区域）打开作业操作菜单。' },
        { selector: '#actionModal .modal-content', title: '操作菜单', text: '现在请点击弹窗中的按钮进行操作（或点击“下一步”跳过）。' }, 
        { selector: '.batch-group:first-child .btn-group .mini-btn:first-child', title: '详情查看', text: '最后，点击此处查看作业的详细说明，完成引导。' }
    ];

    window.moveToStep = function(index) {
        if (!window.isGuideActive) return;
        var step = GUIDE_STEPS[index];
        var element = document.querySelector(step.selector);

        if (!element) {
            // 允容错：如果元素在加载中，不报错，等待
            console.warn('Guide step element not found yet', index, step.selector);
            return;
        }

        spotlight.style.transition = 'top 0.3s ease, left 0.3s ease, width 0.3s ease, height 0.3s ease, box-shadow 0.3s ease';
        
        if (index !== 7) { // Modal step 不需要滚动
            element.scrollIntoView({ behavior: 'auto', block: 'center' });
        }

        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                var rect = element.getBoundingClientRect();
                var cssTop = rect.top - 2; 
                var cssLeft = rect.left - 2;
                var cssWidth = rect.width + 4;
                var cssHeight = rect.height + 4;

                spotlight.style.top = cssTop + 'px';
                spotlight.style.left = cssLeft + 'px';
                spotlight.style.width = cssWidth + 'px';
                spotlight.style.height = cssHeight + 'px';
                spotlight.style.zIndex = '9999';

                var isModalStep = (index === 7);
                if (isModalStep) {
                    spotlight.style.boxShadow = '0 0 0 9999px rgba(0, 0, 0, 0.45)';
                } else {
                    spotlight.style.boxShadow = '0 0 0 9999px rgba(0, 0, 0, 0.75)';
                }
                
                spotlight.classList.add('active');
                window.guideOverlayLocked = false;
                positionTooltip(rect, cssWidth, index);
            });
        });
    }

    function positionTooltip(targetRect, targetWidth, index) {
        var tooltipHeight = tooltip.offsetHeight;
        var tooltipWidth = tooltip.offsetWidth;
        
        var top, left;
        var gap = 15;

        if (index === 7) {
            left = window.innerWidth * 0.2; 
            top = (window.innerHeight / 2) - (tooltipHeight / 2);
            if (left + tooltipWidth > window.innerWidth) {
                left = 10; 
            }
        } else {
            top = targetRect.bottom + gap;
            left = targetRect.left + (targetWidth / 2) - (tooltipWidth / 2);

            if (top + tooltipHeight > window.innerHeight) {
                top = targetRect.top - tooltipHeight - gap;
            }
            if (top < 10) top = 10;
            if (left < 10) left = 10;
            if (left + tooltipWidth > window.innerWidth) {
                left = window.innerWidth - tooltipWidth - 10;
            }
        }

        tooltip.style.top = top + 'px';
        tooltip.style.left = left + 'px';
        tooltip.classList.add('active');
    }

    function notifyGuideEnd() {
        var apiUrl = "{{ url('user/guide/update') }}"; 
        if (apiUrl === "" || apiUrl.indexOf("user/guide/update") === -1) {
             console.warn('Guide: API URL not configured');
            endGuide();
            return;
        }

        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                location: 'group'
            },
            success: function(res) {
                console.log("Guide status updated", res);
                endGuide();
            },
            error: function(err) {
                console.error("Guide update failed", err);
                endGuide(); 
            }
        });
    }

    // 【核心逻辑调整】引导控制器
    window.showGuideStep = function(index) {
        if (!window.isGuideActive) return;
        guideCurrentStep = index;
        
        var step = GUIDE_STEPS[index] || GUIDE_STEPS[0]; // Fallback
        if(step) {
            document.getElementById('guideTitle').innerText = step.title;
            document.getElementById('guideText').innerText = step.text;
        }
        
        // 更新计数显示（用户视角：1-8）
        var displayStep = index + 1;
        if(index >= 8) displayStep = 8; 
        // document.getElementById('guideCount').innerText = displayStep + '/8';

        var btnSkip = document.getElementById('guideBtnPrev');
        var btnAction = document.getElementById('guideBtnNext');

        if (!btnSkip || !btnAction) return;

        // 按钮逻辑控制
        if (index === 8) {
            // 最后一步
            btnSkip.style.display = 'none';
            btnAction.style.display = 'block';
            btnAction.innerText = '完成引导';
            btnAction.onclick = function() {
                notifyGuideEnd();
            };
        } else {
            // 普通步骤
            btnSkip.style.display = 'block';
            btnSkip.innerText = '全部跳过';
            // 【修复】直接AJAX，无弹窗
            btnSkip.onclick = function() {
                notifyGuideEnd();
            };
            
            btnAction.style.display = 'block';
            btnAction.innerText = '下一步';
            
            btnAction.onclick = function() {
                // 场景一：在第6步（阵容）点下一步，直接跳到最后一步
                if (guideCurrentStep === 6) {
                    showGuideStep(8);
                } 
                // 场景二：在第7步（弹窗）点下一步，进最后一步
                else if (guideCurrentStep === 7) {
                    $('#actionModal').css('display', 'none');
                    showGuideStep(8);
                }
                // 其他普通步骤
                else {
                    showGuideStep(guideCurrentStep + 1);
                }
            };
        }

        tooltip.style.display = 'block';
        
        if (!window.guideOverlayLocked) {
            moveToStep(index);
        }
    }

    function initGuide() {
        if (GUIDE_DISABLED) return;
        var checker = setInterval(function() {
            var bossExists = document.querySelector('#bossListB .boss-item');
            var resultExists = document.querySelector('#show .batch-group');
            if (bossExists && resultExists) {
                clearInterval(checker);
                window.isGuideActive = true;
                setTimeout(function() { showGuideStep(0); }, 500);
            }
        }, 300);
    }

    function endGuide(reason) {
        window.isGuideActive = false;
        window.jumpToStepAfterRender = null;
        spotlight.classList.remove('active');
        setTimeout(function() { spotlight.style.display = 'none'; }, 300);
        tooltip.style.display = 'none';
    }

    $(window).on('resize', function() {
        if (window.isGuideActive) {
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(function() { moveToStep(guideCurrentStep); }, 200);
        }
    });

    initGuide();

    // 【核心逻辑调整】场景三：弹窗按钮点击拦截
    $('#btn-lock, #btn-unlock, #btn-hide').on('click.guide', function(e) {
        if (window.isGuideActive && guideCurrentStep === 7) {
            
            e.preventDefault();
            e.stopPropagation();

            var actionName = $(this).attr('id').replace('btn-', '');
            
            // 1. 锁定遮罩，防止刷新时闪烁
            window.lockGuideOverlay();
            $('#guideTooltip').hide(); // 临时隐藏提示框

            // 2. 执行业务逻辑（刷新列表）
            standardHandleLogic(actionName);

            // 3. 设置标志位，刷新完成后自动跳转
            window.jumpToStepAfterRender = 8;
            
            // 4. 关闭弹窗
            $('#actionModal').css('display', 'none');
            
            // 核心点：不在这里直接调 showGuideStep(8)
            // 而是 standardHandleLogic 内部会触发 getTeamGroups()
            // getTeamGroups 成功回调会检测 jumpToStepAfterRender 并跳转
            return false;
        }
    });

}); 
</script>
@endsection














