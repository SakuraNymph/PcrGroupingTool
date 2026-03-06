@extends('main')
@section('content')
<style>
    /* ==================== 全局变量 ==================== */
    :root {
        --primary: #4f46e5;
        --primary-hover: #4338ca;
        --info: #3b82f6;
        --warning: #f59e0b;
        --danger: #ef4444;
        --text-main: #1f2937;
        --text-light: #6b7280;
        --bg-body: #f3f4f6;
        --bg-card: #ffffff;
        --border: #e5e7eb;
        --radius: 8px;
    }

    body {
        font-family: 'Inter', "PingFang SC", "Microsoft YaHei", sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        margin: 0;
        padding: 20px;
    }

    /* ==================== 桌面端表格 (默认) ==================== */
    .table-container {
        background: var(--bg-card);
        border-radius: var(--radius);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
        border: 1px solid var(--border);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    th, td {
        padding: 16px;
        border-bottom: 1px solid var(--border);
    }
    th {
        background-color: #f9fafb;
        font-weight: 600;
        color: var(--text-light);
        font-size: 14px;
    }
    th:last-child, td:last-child {
        text-align: right;
    }

    /* 按钮组 - 桌面端 */
    .btn-group-wrapper {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        color: white;
        transition: opacity 0.2s, transform 0.1s;
    }
    .btn:active { transform: scale(0.98); }
    .btn:hover { opacity: 0.9; }
    .btn-info { background-color: var(--info); }
    .btn-warning { background-color: var(--warning); color: #fff; }
    .btn-danger { background-color: var(--danger); }
    
    /* ==================== 移动端适配 ==================== */
    @media (max-width: 768px) {
        body { padding: 12px; }
        
        .table-container {
            background: transparent;
            box-shadow: none;
            border: none;
        }
        table, tbody { background: transparent; }
        table, thead, tbody, th, td, tr { display: block; }
        thead { display: none; }

        tbody tr {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            margin-bottom: 20px;
            overflow: hidden;
            padding: 0;
            position: relative;
        }

        /* 统一所有 td 的基础样式 */
        td {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            text-align: left;
            padding: 16px; 
            border-bottom: 0;
        }

        /* --- 1. 隐藏标签 --- */
        td[data-label="昵称"]::before,
        td[data-label="功能操作"]::before,
        td[data-label="账号管理"]::before {
            display: none;
        }
        td[data-label="昵称"] strong {
            font-size: 18px;
            color: var(--text-main);
            margin: 0;
        }

        /* --- 2. 按钮布局完全统一 --- */
        td[data-label="功能操作"] { padding: 16px 16px 12px 16px; }
        td[data-label="账号管理"] { padding: 0 16px 20px 16px; }
        
        .btn-group-wrapper {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .btn {
            width: 100%;
            text-align: center;
            padding: 14px 0;
            font-size: 14px;
            border-radius: 6px;
        }
    }

    /* ==================== 聚光灯效果样式 ==================== */
    .guide-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 99999; /* 确保高于 Modal (9999) */
        background-color: transparent;
        pointer-events: none; /* 允许点击穿透 */
        display: none;
    }

    .guide-highlight-box {
        position: absolute;
        border: 2px solid #f1c40f;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75); /* 巨大阴影模拟遮罩 */
        border-radius: 4px;
        transition: all 0.5s ease;
        pointer-events: none;
        z-index: 2;
    }

    .guide-tooltip {
        position: absolute;
        z-index: 3;
        background: #fff;
        width: 260px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        padding: 15px;
        color: #333;
        pointer-events: auto; /* 提示框本身可交互(如果有交互的话) */
    }

    .guide-tooltip h3 {
        margin: 0 0 8px 0;
        font-size: 16px;
        color: var(--info);
    }

    .guide-tooltip p {
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: var(--text-light);
    }
    /* ==================== 聚光灯样式结束 ==================== */
</style>

<div class="main-panel" style="max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap:10px;">
        <h2 style="margin:0; font-size: 20px; color: var(--text-main);">账号列表</h2>
        <button type="button" id="btn-add" class="btn btn-info" style="padding: 10px 20px;">
            + 添加账号
        </button>
    </div>

    <div class="table-container">
        <table id="data-table">
            <thead>
                <tr>
                    <th width="20%">昵称</th>
                    <th width="50%">功能操作</th>
                    <th width="30%">账号管理</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <!-- 数据加载区域 -->
            </tbody>
        </table>
        <div id="empty-message" style="display:none; padding: 40px; text-align: center; color: var(--text-light);">暂无数据</div>
    </div>
    
    <div id="loading" style="display:none; text-align: center; padding: 20px; color: var(--text-light);">
        <i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop">&#xe63d;</i> 加载中...
    </div>
</div>

<!-- 聚光灯引导层 -->
<div id="guideLayer" class="guide-overlay">
    <div id="guideHighlight" class="guide-highlight-box"></div>
    <div id="guideTooltip" class="guide-tooltip">
        <h3>欢迎开始</h3>
        <p>点击这里添加您的第一个公会战账号。</p>
    </div>
</div>

<!-- 全局 Toast 提示 -->
<div id="toast" class="toast">
    <div id="toast-icon"></div>
    <div id="toast-msg"></div>
</div>

<!-- 全局模态框 (弹窗) -->
<div id="global-modal">
    <div class="modal-container">
        <div class="modal-header">
            <span id="modal-title">标题</span>
            <span class="close-btn" onclick="Modal.close()">×</span>
        </div>
        <div class="modal-body">
            <iframe id="modal-iframe" frameborder="0"></iframe>
        </div>
    </div>
</div>

<style>
    /* 弹窗遮罩 */
    #global-modal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(3px);
    }
    #global-modal.active { display: flex; }
    
    .modal-container {
        background: #fff;
        width: 60%;
        max-width: 800px;
        height: 90%;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: modalIn 0.3s ease-out;
    }
    @keyframes modalIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }

    .modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
    }
    .modal-header span { font-weight: 600; font-size: 16px; }

    /* ==================== 修改区域：增强关闭按钮 X ==================== */
    .close-btn {
        font-size: 28px;              /* 加大字体 */
        cursor: pointer;
        color: white;                 /* 白色文字 */
        background-color: #ef4444;    /* 红色背景 */
        width: 36px;                  /* 固定宽度 */
        height: 36px;                 /* 固定高度 */
        line-height: 30px;            /* 垂直居中微调 */
        text-align: center;
        border-radius: 6px;           /* 圆角 */
        display: block;               /* 块级显示以应用宽高 */
        transition: all 0.2s ease;
        font-weight: 300;
    }
    .close-btn:hover {
        background-color: #dc2626;    /* 深红色悬停 */
        color: white;
        transform: scale(1.1);        /* 悬停放大效果 */
    }
    /* ==================== 修改结束 ==================== */

    .modal-body { flex: 1; overflow: hidden; background: #fff; }
    #modal-iframe { width: 100%; height: 100%; border: none; }

    /* Toast 样式 */
    .toast {
        position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
        background: rgba(0,0,0,0.8); color: white; padding: 10px 20px;
        border-radius: 30px; z-index: 10000; display: none; align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .toast.show { display: flex; animation: fadeInDown 0.3s; }
    .toast.success #toast-icon { color: #10b981; margin-right: 8px; font-size: 18px; }
    .toast.error #toast-icon { color: #ef4444; margin-right: 8px; font-size: 18px; }
    @keyframes fadeInDown { from { opacity:0; transform: translate(-50%, -20px); } to { opacity:1; transform: translate(-50%, 0); } }
</style>
@endsection

@section('js')
<script>
    (function() {
        const $ = (selector) => document.querySelector(selector);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // ==================== 聚光灯效果开关与逻辑 ====================
        const ADD_ACCOUNT_GUIDE_ENABLED = @json($switch); // 设置为 true 关闭聚光灯效果
        
        // 记录聚光灯点击的后端接口地址，请修改为实际路由
        const RECORD_GUIDE_URL = "{{ url('user/guide/update') }}"; 

        function initAddAccountGuide() {
            if (ADD_ACCOUNT_GUIDE_ENABLED) return;

            const targetBtn = $('#btn-add');
            if (!targetBtn) return;

            const guideLayer = $('#guideLayer');
            const guideHighlight = $('#guideHighlight');
            const guideTooltip = $('#guideTooltip');

            // 获取位置并设置高亮框
            const rect = targetBtn.getBoundingClientRect();
            
            // 设置高亮框位置
            guideHighlight.style.top = (rect.top - 4) + 'px';
            guideHighlight.style.left = (rect.left - 4) + 'px';
            guideHighlight.style.width = (rect.width + 8) + 'px';
            guideHighlight.style.height = (rect.height + 8) + 'px';

            // 设置提示框位置 (显示在按钮下方)
            const tooltipWidth = 260;
            let tooltipLeft = rect.left + (rect.width / 2) - (tooltipWidth / 2);
            
            // 移动端适配位置
            if (window.innerWidth < 768) {
                tooltipLeft = (window.innerWidth - tooltipWidth) / 2;
                guideTooltip.style.top = (rect.bottom + 20) + 'px';
            } else {
                guideTooltip.style.top = (rect.bottom + 15) + 'px';
            }
            
            // 边界检测，防止超出右侧屏幕
            if (tooltipLeft < 10) tooltipLeft = 10;
            if (tooltipLeft + tooltipWidth > window.innerWidth) tooltipLeft = window.innerWidth - tooltipWidth - 10;

            guideTooltip.style.left = tooltipLeft + 'px';

            // 显示遮罩层
            guideLayer.style.display = 'block';

            // 点击按钮后，隐藏遮罩（由下面的点击事件监听器处理）
        }

        // 触发记录 AJAX 的函数
        async function recordGuideAction() {
            try {
                await fetch(RECORD_GUIDE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ location: 'list' })
                });
                console.log('Guide action recorded');
            } catch (error) {
                console.error('Failed to record guide action', error);
            }
        }
        // ==================== 聚光灯逻辑结束 ====================

        const Toast = {
            elem: $('#toast'), msgElem: $('#toast-msg'), iconElem: $('#toast-icon'), timer: null,
            show(msg, type = 'info') {
                this.msgElem.textContent = msg;
                this.elem.className = `toast show ${type}`;
                this.iconElem.innerHTML = type === 'success' ? '&#10004;' : (type === 'error' ? '&#10006;' : '&#8505;');
                if (this.timer) clearTimeout(this.timer);
                this.timer = setTimeout(() => this.elem.className = 'toast', 3000);
            }
        };

        const Modal = {
            overlay: $('#global-modal'), iframe: $('#modal-iframe'), title: $('#modal-title'),
            open(options) {
                const { title, url, width = '60%', height = '90%' } = options;
                this.title.textContent = title;
                this.iframe.src = url;
                const container = document.querySelector('.modal-container');
                // 响应式全屏适配
                if (window.innerWidth < 768) { container.style.width = '100%'; container.style.height = '100%'; } 
                else { container.style.width = width.replace('%', '') + '%'; }
                this.overlay.classList.add('active');
            },
            close() { 
                this.overlay.classList.remove('active'); 
                setTimeout(() => { this.iframe.src = ''; }, 100); // 稍微延迟清空，防止白屏
            }
        };
        window.Modal = Modal;

        async function fetchList() {
            $('#loading').style.display = 'flex';
            try {
                const response = await fetch("{{ url('user/account/list') }}", { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({}) });
                const result = await response.json();
                renderTable(result.data || []);
            } catch (error) { console.error(error); Toast.show('数据加载失败', 'error'); } 
            finally { $('#loading').style.display = 'none'; }
        }

        function renderTable(data) {
            const tbody = $('#table-body'); tbody.innerHTML = '';
            if (!data || !data.length) { $('#empty-message').style.display = 'block'; return; } else { $('#empty-message').style.display = 'none'; }

            const isMobile = /Mobi|Android/i.test(navigator.userAgent);
            const width_ = isMobile ? '100%' : '60%';

            data.forEach(item => {
                const tr = document.createElement('tr');
                const actionHtml = `
                    <div class="btn-group-wrapper">
                        <button class="btn btn-info" onclick="handleAction('resultD', ${item.id})">D面分刀</button>
                        <button class="btn btn-info" onclick="handleAction('resultB', ${item.id})">首日满补</button>
                        <button class="btn btn-info" onclick="handleAction('coin', ${item.id})">大师币商店</button>
                        <button class="btn btn-info" onclick="handleAction('statistics', ${item.id})">抽卡统计</button>
                    </div>`;
                const manageHtml = `<div class="btn-group-wrapper"><button class="btn btn-warning" onclick="handleAction('edit', ${item.id})">修改</button><button class="btn btn-danger" onclick="handleAction('delete', ${item.id})">删除</button></div>`;
                tr.innerHTML = `<td data-label="昵称"><strong>${item.nickname || '-'}</strong></td><td data-label="功能操作">${actionHtml}</td><td data-label="账号管理">${manageHtml}</td>`;
                tbody.appendChild(tr);
            });
        }

        window.handleAction = async function(type, id) {
            const isMobile = /Mobi|Android/i.test(navigator.userAgent); const modalWidth = isMobile ? '100%' : '60%';
            const fetchAndHandle = async(typeStr, urlStr) => {
                try {
                    const res = await fetch(`{{ url('/get_team_num') }}?type=${typeStr}`);
                    const num = await res.text();
                    if (Number(num) >= 3) { Modal.open({ title: getTitles(type), url: urlStr + id, width: modalWidth, height: '100%' }); } 
                    else { Toast.show('本月作业暂未更新,敬请期待,Ciallo～(∠・ω< )⌒★', 'info'); }
                } catch(e){ console.error(e); }
            };
            const getTitles = (t) => ({resultD:'D阶段分刀', resultB:'首日满补分刀', coin:'大师币商店', statistics:'抽卡统计', edit:'修改账号'}[t]);

            switch (type) {
                case 'edit': Modal.open({ title: '修改账号', url: `{{ url('user/account/edit') }}?id=${id}`, width: modalWidth, height: '95%' }); break;
                case 'coin': Modal.open({ title: '大师币商店', url: `{{ url('user/account/coin') }}?id=${id}`, width: modalWidth, height: '100%' }); break;
                case 'statistics': Modal.open({ title: '抽卡统计', url: `{{ url('user/account/statistics') }}?id=${id}`, width: modalWidth, height: '100%' }); break;
                case 'resultD': fetchAndHandle(2, `{{ url('user/account/team') }}?id=`); break;
                case 'resultB': fetchAndHandle(3, `{{ url('user/account/group') }}?id=`); break;
                case 'delete': if (confirm('确定要删除该账号吗?')) {
                    try {
                        const res = await fetch("{{ url('user/account/delete') }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
                        const json = await res.json();
                        if (json.status) { Toast.show('删除成功', 'success'); fetchList(); } else { Toast.show(json.result?.message || '删除失败', 'error'); }
                    } catch(e){ Toast.show('请求错误', 'error'); }
                } break;
            }
        };
        
        // 添加账号按钮逻辑修改
        $('#btn-add').addEventListener('click', () => { 
            
            // 1. 触发记录 AJAX
            recordGuideAction();

            // 2. 如果有聚光灯遮罩，点击后关闭遮罩提升体验
            const guide = $('#guideLayer');
            if (guide && guide.style.display === 'block') {
                guide.style.display = 'none';
            }

            // 3. 原有打开模态框逻辑
            const isMobile = /Mobi|Android/i.test(navigator.userAgent); 
            Modal.open({ title: '添加账号', url: `{{ url('user/account/add') }}`, width: isMobile ? '100%' : '60%', height: '95%' }); 
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            fetchList();
            // 初始化聚光灯
            initAddAccountGuide();
        });

        window.refreshList = function() { fetchList(); Modal.close(); };
    })();
</script>
@endsection