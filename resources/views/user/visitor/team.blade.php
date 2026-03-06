@extends('main')

@section('css')
<style>
    /* ==================== 基础样式 (保持原有) ==================== */
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
        
        /* PC 端头像尺寸 */
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

    .app-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .alert-box {
        background: #fff7ed;
        border: 1px solid #ffedd5;
        color: #c2410c;
        padding: 12px;
        border-radius: var(--radius);
        text-align: center;
        font-weight: 500;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .card {
        background: var(--bg-card);
        border-radius: var(--radius);
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        border: 1px solid var(--border);
        padding: 20px;
        margin-bottom: 20px;
    }

    .card-header {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f3f4f6;
        color: var(--text-main);
    }

    .filter-bar {
        display: flex;
        flex-direction: column; 
        gap: 15px;
        margin-bottom: 15px;
        background: #f9fafb;
        padding: 12px;
        border-radius: 8px;
    }
    
    .filter-group { 
        display: flex; 
        align-items: center; 
        gap: 6px;
        flex-wrap: wrap; 
    }
    
    .filter-label { 
        font-weight: 600; 
        font-size: 12px;
        color: var(--text-sub); 
        white-space: nowrap; 
    }

    /* 单选按钮样式 */
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
    .custom-radio:checked + .radio-label {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
    }

    /* Boss 区域 */
    .boss-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .boss-row {
        display: flex;
        justify-content: center;
        align-items: center;
        background: #fff;
        padding: 8px;
        border-radius: 8px;
        border: 1px dashed #d1d5db;
    }

    .boss-list {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 5px;
        justify-content: flex-start;
    }

    .boss-item {
        cursor: pointer;
        border-radius: 4px;
        border: 2px solid transparent;
        transition: all 0.2s;
        flex-shrink: 0;
        position: relative;
    }

    .boss-item img {
        display: block;
        width: 50px;
        height: 50px;
        border-radius: 4px;
        opacity: 0.5;
        background: #f3f4f6;
        object-fit: contain;
    }

    .boss-item.selected img {
        opacity: 1;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.4);
        transform: scale(1.05);
    }

    /* 结果区域 (PC) */
    .result-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .team-group {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 15px;
        overflow: hidden;
        position: relative; 
    }

    .team-header {
        background: #f9fafb;
        padding: 10px 16px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .boss-title { font-size: 16px; font-weight: 800; margin-right: 10px; }
    .team-damage { font-size: 14px; color: var(--text-sub); }
    .team-content { padding: 12px; }

    .flex-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 8px;
        justify-content: center;
        gap: 4px;
    }

    .borrow-tag {
        font-weight: 800;
        color: var(--primary);
        margin: 0 6px;
        font-size: 16px;
        align-self: center;
    }

    .unit-img {
        width: var(--avatar-size);
        height: var(--avatar-size);
        object-fit: contain;
        border-radius: 4px;
        border: 1px solid #f3f4f6;
        flex-shrink: 0;
    }
    .unit-img.disabled { opacity: 0.5; }

    /* 彩虹边框 */
    .rainbow-border {
        position: relative;
        display: inline-flex;
        align-items: center;
        padding: 4px;
        gap: 3px;
        border-radius: 8px;
        background: #fff; 
        cursor: pointer;
        transition: transform 0.2s;
        z-index: 0; 
    }

    .rainbow-border:hover { transform: scale(1.02); }
    .rainbow-border.locked { background: #fff; }
    
    .rainbow-border.locked::before {
        content: ''; 
        position: absolute; 
        top: -5px; left: -5px; right: -5px; bottom: -5px;
        background: linear-gradient(135deg, #ef4444, #f97316, #eab308, #22c55e, #06b6d4, #3b82f6, #a855f7, #ef4444);
        background-size: 300% 300%; 
        border-radius: 12px; 
        z-index: -1; 
        animation: gradientMove 3s linear infinite; 
    }

    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .team-remark { margin-top: 6px; font-size: 12px; color: #9ca3af; line-height: 1.4; text-align: center; }
    
    .btn-group { 
        display: flex; 
        gap: 6px; 
        margin-top: 6px; 
        flex-wrap: wrap; 
        justify-content: flex-start; 
    }
    
    .mini-btn {
        padding: 4px 8px; 
        font-size: 12px; 
        line-height: 1.2;
        border-radius: 6px; 
        border: 1px solid var(--primary);
        background: rgba(79, 70, 229, 0.05); 
        color: var(--primary); 
        cursor: pointer;
        margin: 0; 
    }
    .mini-btn:hover { background: var(--primary); color: #fff; }
    .mini-btn.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    .expand-area {
        margin-top: 10px; padding: 10px; background: #f9fafb;
        border-radius: 8px; border: 1px solid #eee; display: none;
    }
    .expand-area h2 { margin: 0 0 5px 0; font-size: 13px; color: var(--text-sub); }
    .expand-area img { max-width: 100%; border-radius: 4px; margin-top: 5px; }

    .link-jump-btn {
        display: inline-block;
        padding: 8px 20px;
        margin-top: 10px;
        background-color: var(--primary);
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        transition: background 0.2s;
        text-align: center;
        width: 100%; 
        box-sizing: border-box;
    }
    .link-jump-btn:hover { background-color: var(--primary-hover); }

    /* 模态框 */
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
    .btn-close { background: #e5e7eb; color: #374151; }

    #toast {
        position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
        background: #333; color: #fff; padding: 8px 16px; border-radius: 20px;
        display: none; z-index: 11000; font-size: 13px;
    }

    /* ==================== 移动端适配 ==================== */
    @media (max-width: 768px) {
        :root { --avatar-size: 44px; }
        .card { padding: 10px; }
        .team-content { padding: 8px; }
        .boss-row { padding: 2px 0; }
        .boss-list { flex-wrap: wrap; justify-content: center; overflow-x: visible; padding: 0; gap: 6px; }
        .boss-item { width: 14%; margin: 0.2%; }
        .boss-item img { width: 100%; height: auto; aspect-ratio: 1/1; }
        .flex-row { justify-content: center; gap: 2px; }
        .unit-img { flex-shrink: 1; min-width: 38px; }
        .rainbow-border { gap: 2px; padding: 2px; }
        .borrow-tag { margin: 0 4px; font-size: 14px; }
        .guide-tooltip { width: 80% !important; left: 10% !important; }
    }

    /* ==================== 独立层高亮引导样式 ==================== */
    #guide-spotlight {
        position: fixed;
        z-index: 10002; /* 在 modal (10000) 之上 */
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75);
        pointer-events: none; 
        border-radius: 4px;
        opacity: 0;
        transition: opacity 0.3s, top 0.3s, left 0.3s, width 0.3s, height 0.3s;
        border: 2px solid rgba(255, 255, 255, 0.5);
    }
    #guide-spotlight.active { opacity: 1; }

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
    .guide-tooltip h4 { margin: 0 0 8px 0; font-size: 16px; color: var(--primary); }
    .guide-tooltip p { margin: 0 0 15px 0; line-height: 1.5; color: #666; }
    .guide-footer { display: flex; justify-content: space-between; align-items: center; }
    .guide-btn { padding: 6px 14px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; }
    .guide-btn-skip { background: #f3f4f6; color: #666; }
    .guide-btn-skip:hover { background: #e5e7eb; }
    .guide-btn-next { background: var(--primary); color: #fff; }
    .guide-btn-next:hover { background: var(--primary-hover); }
    .guide-step-count { font-size: 12px; color: #999; }
</style>
@endsection

@section('content')

<div class="app-container">
    <!-- 顶部警告 -->
    <div class="alert-box">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77-1.333.192 3 1.732 3z"></path></svg>
        <span>分刀结果仅供参考，请以实际box练度为准</span>
    </div>

    <!-- 筛选控制栏 -->
    <div class="card">
        <div class="card-header">作业设置</div>

        <!-- Boss 列表 -->
        <div id="bossList" class="boss-container">
            <div style="text-align: center; color: #999;">加载Boss列表中...</div>
        </div>

        <!-- 单选按钮区域 -->
        <div class="filter-bar">
            <!-- 自动模式 -->
            <div class="filter-group">
                <span class="filter-label">模式:</span>
                <input type="radio" name="type" id="type_auto" value="1" class="custom-radio" checked>
                <label for="type_auto" class="radio-label">自动</label>
                
                <input type="radio" name="type" id="type_manual" value="2" class="custom-radio">
                <label for="type_manual" class="radio-label">手动</label>
            </div>

            <!-- 攻击类型 -->
            <div class="filter-group">
                <span class="filter-label">阵型:</span>
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

    <!-- 结果展示区域 -->
    <div id="show" class="result-container"></div>
</div>

<!-- 模态框 -->
<div class="modal-overlay" id="actionModal">
    <div class="modal-box">
        <div class="modal-title">作业操作</div>
        <div class="modal-desc">【锁定】套餐将固定此作业<br>【解锁】套餐解除锁定状态<br>【隐藏】套餐将排除此作业</div>
        <div class="modal-actions">
            <button class="modal-btn btn-lock" id="btn-lock">锁定作业</button>
            <button class="modal-btn btn-unlock" id="btn-unlock">解锁作业</button>
            <button class="modal-btn btn-hide" id="btn-hide">隐藏作业</button>
            <button class="modal-btn btn-close" id="btn-close">关闭</button>
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

@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ================== 后端变量接收与控制 ==================
    // 假设后端传递了 $switch 变量 (false 表示未完成，true 表示已完成)
    // 如果后端没有传该变量，默认为 false (显示引导)
    const guideStatusFromBackend = "{!! isset($switch) && $switch ? 'true' : 'false' !!}";

    // 业务逻辑区域
    const id = "{{ $id }}";
    const baseUrl = (id == '0') ? "{{ url('user/visitor/get_team_groups') }}" : "{{ url('user/visitor/get_team_groups') }}";
    const bossListUrl = "{{ url('get_this_month_boss_list') }}";

    let bossMap = { row1: 0, row2: 0, row3: 0 };
    let type = '1';
    let atk = '0';
    let lockedIds = [];
    let hiddenIds = [];
    let currentModalId = null;

    function buildUrlParams(params) {
        const parts = [];
        for (let key in params) {
            if (params.hasOwnProperty(key)) {
                const value = params[key];
                if (Array.isArray(value)) {
                    value.forEach(val => parts.push(`${encodeURIComponent(key)}[]=${encodeURIComponent(val)}`));
                } else {
                    parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`);
                }
            }
        }
        return parts.join('&');
    }

    function fetchBossList() {
        fetch(bossListUrl)
            .then(r => r.json())
            .then(res => {
                if (res.status == 1) renderBossList(res.data);
            })
            .catch(e => console.error(e));
    }

    function renderBossList(data) {
        const container = document.getElementById('bossList');
        container.innerHTML = '';

        for (let row = 1; row <= 3; row++) {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'boss-row';

            const listDiv = document.createElement('div');
            listDiv.className = 'boss-list';

            data.forEach(boss => {
                const item = document.createElement('div');
                item.className = 'boss-item';
                if (bossMap['row' + row] == boss.sort) item.classList.add('selected');

                const img = document.createElement('img');
                img.src = "{{ asset('boss') }}/" + boss.file_path;
                item.appendChild(img);

                item.addEventListener('click', function() {
                    if (window.isGuideActive && window.guideCurrentStep === 0) {
                        // 允许
                    }

                    if (this.classList.contains('selected')) {
                        this.classList.remove('selected');
                        bossMap['row' + row] = 0;
                    } else {
                        listDiv.querySelectorAll('.boss-item').forEach(el => el.classList.remove('selected'));
                        this.classList.add('selected');
                        bossMap['row' + row] = boss.sort;
                    }
                    fetchTeamGroups();
                });

                listDiv.appendChild(item);
            });

            rowDiv.appendChild(listDiv);
            container.appendChild(rowDiv);
        }
    }

    function fetchTeamGroups() {
        const params = {
            row1: bossMap.row1, row2: bossMap.row2, row3: bossMap.row3,
            id: id, type: type, atk: atk, lockedIds: lockedIds, hiddenIds: hiddenIds
        };
        const url = `${baseUrl}?${buildUrlParams(params)}`;
        fetch(url)
            .then(r => r.json()) 
            .then(obj => renderTeams(obj))
            .catch(e => console.error('Error:', e));
    }

    function renderTeams(obj) {
        const container = document.getElementById('show');
        container.innerHTML = '';

        if (obj.status == 1) {
            const data = obj.result || {};
            if (Object.keys(data).length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:30px; color:#999;">无法分刀，请丰富您的box</div>';
                return;
            }

            let keyNum = 0;
            for (let key in data) {
                const color = (keyNum % 2 === 0) ? '#ef4444' : '#3b82f6';
                keyNum++;
                const groupDiv = document.createElement('div');
                groupDiv.className = 'team-group';

                for (let k in data[key]) {
                    const item = data[key][k];
                    
                    let rolesHtml = '';
                    for (let kk in item.roles) {
                        const r = item.roles[kk];
                        const style = (r.status == 1) ? '' : 'opacity: 0.5;';
                        rolesHtml += `<img src="{{ asset('images') }}/${r.image_id}.webp" class="unit-img ${r.status==0?'disabled':''}" style="${style}">`;
                    }

                    let borrowHtml = '';
                    if (item.borrow) {
                        for (let kk in item.roles) {
                            if (item.roles[kk].role_id == item.borrow) {
                                borrowHtml = `<img src="{{ asset('images') }}/${item.roles[kk].image_id}.webp" class="unit-img">`;
                                break;
                            }
                        }
                    } else {
                        borrowHtml = `<img src="{{ asset('images') }}/renyi.webp" class="unit-img">`;
                    }

                    let btnsHtml = '';
                    for (let kk in item.link) {
                        btnsHtml += `<button class="mini-btn action-btn" data-url="${item.link[kk].url}" data-image='${JSON.stringify(item.link[kk].image)}' data-note="${item.link[kk].note}">${item.link[kk].text}</button>`;
                    }

                    const lockedClass = lockedIds.includes(item.id) ? 'locked' : '';

                    const html = `
                        <div style="padding: 10px; border-bottom: 1px solid #f3f4f6;">
                            <div class="team-header" style="padding-left:0; padding-top:0; padding-right:0; background:none; margin-bottom: 5px;">
                                <div>
                                    <span class="boss-title" style="color:${color}">D${item.boss}</span>
                                    <span class="team-damage">预估伤害: ${item.damage}</span>
                                </div>
                            </div>
                            
                            <div class="flex-row">
                                <div class="rainbow-border ${lockedClass}" data-id="${item.id}">
                                    ${rolesHtml}
                                </div>
                                <span class="borrow-tag">借</span>
                                ${borrowHtml}
                            </div>
                            
                            <div class="team-remark">${item.remark}</div>
                            <div class="btn-group">${btnsHtml}</div>
                            <div class="expand-area"></div>
                        </div>
                    `;
                    groupDiv.insertAdjacentHTML('beforeend', html);
                }
                container.appendChild(groupDiv);
            }

            if (window.ENABLE_GUIDE && !window.hasGuideRun) {
                window.hasGuideRun = true;
                initGuide();
            }

            if (window.jumpToStepAfterRender !== null) {
                const targetStep = window.jumpToStepAfterRender;
                window.jumpToStepAfterRender = null;
                
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        showGuideStep(targetStep);
                    }, 50);
                });
            }
        }
    }

    const radios = document.querySelectorAll('input[type="radio"]');
    radios.forEach(r => {
        r.addEventListener('change', (e) => {
            if (e.target.name === 'type') type = e.target.value;
            if (e.target.name === 'atk_type') atk = e.target.value;
            fetchTeamGroups();
        });
    });

    const modal = document.getElementById('actionModal');
    document.getElementById('btn-lock').onclick = () => handleModalAction('lock');
    document.getElementById('btn-unlock').onclick = () => handleModalAction('unlock');
    document.getElementById('btn-hide').onclick = () => handleModalAction('hide');
    document.getElementById('btn-close').onclick = () => closeModal();

    function handleModalAction(action) {
        if (!currentModalId) return;

        if (window.isGuideActive && window.guideCurrentStep === 6) {
            window.lockGuideOverlay();
            
            var tooltip = document.getElementById('guideTooltip');
            if(tooltip) tooltip.style.display = 'none';
            
            if (action === 'lock') {
                if (!lockedIds.includes(currentModalId)) {
                    if (lockedIds.length >= 2) lockedIds.shift();
                    lockedIds.push(currentModalId);
                }
            } else if (action === 'unlock') {
                const idx = lockedIds.indexOf(currentModalId);
                if (idx > -1) lockedIds.splice(idx, 1);
            } else if (action === 'hide') {
                if (!hiddenIds.includes(currentModalId)) hiddenIds.push(currentModalId);
                const idx = lockedIds.indexOf(currentModalId);
                if (idx > -1) lockedIds.splice(idx, 1);
            }

            if(modal) modal.style.display = 'none';
            currentModalId = null;
            
            window.jumpToStepAfterRender = 7;
            
            fetchTeamGroups();
            return;
        }

        if (action === 'lock') {
            if (!lockedIds.includes(currentModalId)) {
                if (lockedIds.length >= 2) lockedIds.shift();
                lockedIds.push(currentModalId);
            }
        } else if (action === 'unlock') {
            const idx = lockedIds.indexOf(currentModalId);
            if (idx > -1) lockedIds.splice(idx, 1);
        } else if (action === 'hide') {
            if (!hiddenIds.includes(currentModalId)) hiddenIds.push(currentModalId);
            const idx = lockedIds.indexOf(currentModalId);
            if (idx > -1) lockedIds.splice(idx, 1);
        }
        closeModal();
        fetchTeamGroups();
    }

    function openModal(id) { currentModalId = id; modal.style.display = 'flex'; }
    function closeModal() { modal.style.display = 'none'; currentModalId = null; }
    
    modal.addEventListener('click', (e) => { 
        if (e.target === modal) closeModal(); 
    });

    document.getElementById('show').addEventListener('click', function(e) {
        const btn = e.target.closest('.action-btn');
        if (btn) {
            if(e.target.closest('.link-jump-btn')) return;

            const area = btn.closest('.btn-group').nextElementSibling;
            
            if (window.isGuideActive && window.guideCurrentStep === 7) return;

            const activeBtn = document.querySelector('.mini-btn.active');
            const isAlreadyActive = (activeBtn === btn);
            
            document.querySelectorAll('.expand-area').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.mini-btn').forEach(el => el.classList.remove('active'));

            if (!isAlreadyActive) {
                btn.classList.add('active');
                
                const note = btn.dataset.note;
                const url = btn.dataset.url;
                
                let contentHtml = '';

                if (note) {
                    contentHtml += `<h2>备注: ${note}</h2>`;
                }

                try {
                    const images = JSON.parse(btn.dataset.image);
                    if (Array.isArray(images) && images.length > 0) {
                        contentHtml += `<h2>图片:</h2><div style="display:flex;gap:5px;flex-wrap:wrap;">`;
                        images.forEach(img => {
                            contentHtml += `<img src="${img.url}" style="max-height:150px;">`;
                        });
                        contentHtml += `</div>`;
                    }
                } catch(err) {}

                if (url && url.trim() !== '') {
                    contentHtml += `<a href="${url}" target="_blank" class="link-jump-btn">点击查看</a>`;
                }

                area.innerHTML = contentHtml;
                area.style.display = 'block';
            }
            
            return;
        }

        const border = e.target.closest('.rainbow-border');
        if (border) { 
            openModal(parseInt(border.dataset.id)); 
            
            if (window.isGuideActive && window.guideCurrentStep === 5) {
                setTimeout(() => {
                    showGuideStep(6);
                }, 100);
            }
        }
    });

    fetchBossList();
    fetchTeamGroups();

    // ==========================================
    // 新手引导逻辑 (优化后的开关与AJAX)
    // ==========================================
    
    // 根据后端变量决定是否开启引导
    // 当 guideStatusFromBackend 为 false 时，ENABLE_GUIDE 为 true
    window.ENABLE_GUIDE = (guideStatusFromBackend === 'false' || guideStatusFromBackend === '0');
    
    window.hasGuideRun = false; 
    window.jumpToStepAfterRender = null; 

    window.isGuideActive = false;
    window.guideCurrentStep = 0;
    var spotlight = document.getElementById('guide-spotlight');
    var tooltip = document.getElementById('guideTooltip');

    window.lockGuideOverlay = function() {
        if (!spotlight) return;
        spotlight.style.transition = 'none';
        spotlight.style.top    = '50%';
        spotlight.style.left   = '50%';
        spotlight.style.width  = '0px';
        spotlight.style.height = '0px';
        spotlight.style.zIndex = 10003; 
        spotlight.style.boxShadow = '0 0 0 9999px rgba(0, 0, 0, 0.85)'; 
        spotlight.style.display = 'block';
        window.guideOverlayLocked = true;
    }

    var GUIDE_STEPS = [
        { selector: '.boss-item', title: '选择Boss', text: '这里是Boss列表，每行（每刀）最多只能选择一个boss。' },
        { selector: 'label[for="type_auto"]', title: '选择模式', text: '选择作业模式，包括“自动”或“手动”。推荐新人使用自动模式。' },
        { selector: 'label[for="atk_0"]', title: '选择阵型', text: '这里可以筛选队伍的物理/法术构成。默认“不限”即可。' },
        { selector: '.team-group .boss-title', title: '查看Boss标识', text: '这里显示的是Boss编号，对应不同的阶段的Boss。' },
        { selector: '.team-group .team-damage', title: '预估伤害', text: '此数值展示了该作业在满足练度的情况下的预计输出伤害。' },
        { selector: '.team-group .rainbow-border', title: '操作队伍', text: '👉 请点击此处（队伍图片区域）打开作业操作菜单。' }, 
        { selector: '#actionModal .btn-lock', title: '操作菜单', text: '现在请点击弹窗中的按钮进行操作（或点击“下一步”跳过）。' }, 
        { selector: '.team-group .btn-group .mini-btn:first-child', title: '查看详情', text: '最后，点击此处查看作业的详细说明，完成引导。' }
    ];

    window.moveToStep = function(index) {
        if (!window.isGuideActive) return;
        var step = GUIDE_STEPS[index];
        var element = document.querySelector(step.selector);

        if (!element) {
            console.warn('Guide step element not found yet', index, step.selector);
            return;
        }

        spotlight.style.transition = 'top 0.3s ease, left 0.3s ease, width 0.3s ease, height 0.3s ease';
        
        if (index !== 6) { 
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
                spotlight.style.zIndex = 10002; 

                var isModalStep = (index === 6);
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

        if (index === 6) {
            left = targetRect.left + (targetWidth / 2) - (tooltipWidth / 2);
            top = targetRect.bottom + gap;

            if (top + tooltipHeight > window.innerHeight) {
                top = targetRect.top - tooltipHeight - gap;
            }
            
            if (left < 10) left = 10;
            if (left + tooltipWidth > window.innerWidth - 10) {
                left = window.innerWidth - tooltipWidth - 10;
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
        window.isGuideActive = false;
        jumpToStepAfterRender = null;
        spotlight.classList.remove('active');
        setTimeout(function() { spotlight.style.display = 'none'; }, 300);
        tooltip.style.display = 'none';
        
        // ================== AJAX 传递后端 ==================
        // 发送 POST 请求保存引导完成状态
        const saveUrl = "{{ url('user/guide/update') }}";
        
        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}', // Laravel 需要 CSRF Token
                'Accept': 'application/json'
            },
            body: JSON.stringify({ location: 'team' })
        })
        .then(response => {
            if(response.ok) {
                console.log('[Guide] 引导状态已保存到后端');
            } else {
                console.error('[Guide] 保存引导状态失败', response.status);
            }
        })
        .catch(error => {
            console.error('[Guide] 请求发生错误', error);
        });
    }

    window.showGuideStep = function(index) {
        if (!window.isGuideActive) return;
        window.guideCurrentStep = index;
        
        var step = GUIDE_STEPS[index];
        if(step) {
            document.getElementById('guideTitle').innerText = step.title;
            document.getElementById('guideText').innerText = step.text;
        }
        
        var displayStep = index + 1;
        if(index >= 7) displayStep = 8; 
        // document.getElementById('guideCount').innerText = displayStep + '/188';

        var btnSkip = document.getElementById('guideBtnPrev');
        var btnAction = document.getElementById('guideBtnNext');

        if (index === 7) {
            btnSkip.style.display = 'none';
            btnAction.style.display = 'block';
            btnAction.innerText = '完成引导';
            btnAction.onclick = function() {
                notifyGuideEnd(); // 触发 AJAX
            };
        } else {
            btnSkip.style.display = 'block';
            btnSkip.innerText = '跳过全部';
            btnSkip.onclick = function() {
                notifyGuideEnd(); // 触发 AJAX
            };
            
            btnAction.style.display = 'block';
            btnAction.innerText = '下一步';
            
            btnAction.onclick = function() {
                if (window.guideCurrentStep === 5) {
                    showGuideStep(7);
                } 
                else if (window.guideCurrentStep === 6) {
                    closeModal();
                    setTimeout(() => showGuideStep(7), 200);
                }
                else {
                    showGuideStep(window.guideCurrentStep + 1);
                }
            };
        }

        tooltip.style.display = 'block';
        moveToStep(index);
    }

    function initGuide() {
        if (!window.ENABLE_GUIDE) return;
        const checker = setInterval(function() {
            const bossExists = document.querySelector('#bossList .boss-item');
            const resultExists = document.querySelector('#show .team-group');
            if (bossExists && resultExists) {
                clearInterval(checker);
                window.isGuideActive = true;
                window.showGuideStep(0);
            }
        }, 300);
    }
});
</script>
@endsection