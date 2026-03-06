@extends('main')

@section('content')
<style>
    :root {
        --primary-color: #4f46e5;
        --primary-hover: #4338ca;
        --danger-color: #ef4444;
        --text-main: #1f2937;
        --text-secondary: #6b7280;
        --bg-body: #f3f4f6;
        --bg-card: #ffffff;
        --border-color: #e5e7eb;
        --radius: 8px;
    }

    body {
        font-family: 'Inter', "PingFang SC", "Microsoft YaHei", sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        margin: 0;
        padding: 20px;
        box-sizing: border-box;
    }

    * { box-sizing: border-box; }

    .page-wrapper {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        position: relative; /* 为绝对定位做参考 */
    }

    /* ==================== 默认样式 (PC端：每行 8 个) ==================== */
    .role-grid {
        display: grid !important;
        grid-template-columns: repeat(8, 1fr) !important; /* 强制 8 列 */
        gap: 12px;
        padding: 15px;
        width: 100%;
    }
    
    .role-item {
        width: 100%;
        aspect-ratio: 1 / 1;
        cursor: pointer;
        border: 2px solid transparent;
        background: #f3f4f6;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
    }

    .role-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.5;
        transition: all 0.2s;
    }

    .role-item.is-selected {
        border-color: var(--primary-color);
        background-color: #eef2ff;
        transform: translateY(-2px);
    }
    .role-item.is-selected img { opacity: 1; }

    /* ==================== 移动端适配 (宽度 < 1024px：每行 6 个) ==================== */
    @media (max-width: 1024px) {
        body { padding: 10px; }
        
        .form-card { padding: 12px; margin-bottom: 10px; }

        .role-grid {
            grid-template-columns: repeat(6, 1fr) !important; /* 强制 6 列 */
            gap: 5px !important; /* 紧凑间距 */
            padding: 5px 0 !important;
        }

        .role-item {
            border-width: 1px;
            border-radius: 4px;
            transform: none !important; /* 移动端取消位移防止抖动 */
        }
        
        .role-item.is-selected {
            box-shadow: 0 0 0 1px var(--primary-color);
        }

        .action-container { 
            flex-direction: row !important; 
            gap: 8px;
        }
        .btn { flex: 1; padding: 10px 5px; font-size: 13px; }
    }

    /* 基础组件样式 */
    .form-card {
        background: var(--bg-card);
        border-radius: var(--radius);
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .section-title { font-size: 18px; font-weight: 700; margin: 0 0 20px 0; display: flex; align-items: center; }
    .section-title span { display: inline-block; width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; margin-right: 10px; }
    .input-control { width: 100%; padding: 14px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 16px; }
    .accordion-card { background: #fff; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 10px; overflow: hidden; }
    .accordion-header { padding: 14px 16px; background: #fafafa; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .accordion-header h4 { margin: 0; font-size: 15px; }
    .accordion-icon { width: 20px; height: 20px; transition: transform 0.3s; }
    .accordion-card.active .accordion-icon { transform: rotate(180deg); }
    .accordion-body { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
    .accordion-card.active .accordion-body { border-top: 1px solid var(--border-color); }
    .action-container { display: flex; gap: 12px; margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--border-color); justify-content: flex-end; }
    .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; }
    .btn-secondary { background: white; border: 1px solid var(--primary-color); color: var(--primary-color); }
    .btn-primary { background: var(--primary-color); color: white; }
    .loading-wrapper { text-align: center; padding: 30px; color: var(--text-secondary); }

    /* ==================== 聚光灯引导样式 ==================== */
    .guide-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
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
        z-index: 9999;
    }

    .guide-tooltip {
        position: absolute;
        z-index: 10000;
        background: #fff;
        /* PC端固定宽度，移动端自适应 */
        width: 280px;
        max-width: 90%; 
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        padding: 15px;
        color: #333;
        pointer-events: auto;
        word-wrap: break-word; 
        
        /* 【修改点】强制最小高度，防止卡片忽大忽小 */
        min-height: 140px; 
        display: flex;
        flex-direction: column;
    }

    .guide-tooltip h3 {
        margin: 0 0 10px 0;
        font-size: 18px;
        color: var(--primary-color);
        line-height: 1.2;
    }

    .guide-tooltip p {
        margin: 0 0 15px 0;
        font-size: 14px;
        line-height: 1.5;
        flex-grow: 1; /* 撑开中间内容区，把按钮推到底部 */
    }

    .guide-actions {
        text-align: right;
        border-top: 1px solid #eee;
        padding-top: 10px;
        margin-top: auto; /* 按钮吸附底部 */
    }

    .guide-btn {
        padding: 5px 12px;
        font-size: 12px;
        border-radius: 3px;
        cursor: pointer;
        border: none;
    }

    .guide-btn-next {
        background-color: var(--primary-color);
        color: #fff;
    }

    .guide-btn-skip {
        background-color: transparent;
        color: #999;
        margin-right: 10px;
    }
    .guide-btn-skip:hover { color: #666; }
    /* ==================== 聚光灯样式结束 ==================== */
</style>

<div class="page-wrapper">
    <div class="form-card">
        <div class="section-title"><span></span>账号昵称</div>
        <div class="form-group">
            <input type="text" name="nickname" id="nickname" class="input-control" placeholder="请输入账号昵称" required 
            @if(isset($data['nickname']) && $data['nickname']) value="{{$data['nickname']}}" @endif>
            <input type="hidden" id="account-id" value="{{$id ?? ''}}">
        </div>
    </div>

    <div class="form-card">
        <div class="section-title"><span></span>角色编队</div>
        <div id="roles-container">
            <div class="loading-wrapper">正在加载角色数据...</div>
        </div>
        <div class="action-container">
            <button type="button" class="btn btn-secondary" id="all-add">一键全选</button>
            <button type="button" class="btn btn-primary" id="btn-save">保存配置</button>
        </div>
    </div>
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
@endsection

@section('js')
<script>
    (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const state = { allIds: [], selectedIds: new Set(), accountId: document.getElementById('account-id').value };
        const roleTypes = { 1: '前卫', 2: '中卫', 3: '后卫' };
        const container = document.getElementById('roles-container');
        const btnAllAdd = document.getElementById('all-add');
        const btnSave = document.getElementById('btn-save');
        const inputNickname = document.getElementById('nickname');

        // 【修改点】新增变量控制引导开关
        // 建议后端传递 $post_guide_switch 变量
        // false = 未完成，开启引导
        // true  = 已完成/手动关闭，跳过引导
        const POST_GUIDE_SWITCH = @json($switch ?? false);

        // 1x1 透明像素占位符
        const LAZY_PLACEHOLDER = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

        // 辅助函数：加载懒加载图片
        function loadLazyImages(containerElement) {
            if (!containerElement) return;
            const lazyImages = containerElement.querySelectorAll('img[data-src]');
            lazyImages.forEach(img => {
                if (img.dataset.src) {
                    img.src = img.dataset.src; 
                    img.removeAttribute('data-src'); 
                }
            });
        }

        async function fetchRoles() {
            try {
                const id = state.accountId || 0;
                const response = await fetch(`{{ url('user/account/get_can_use_roles') }}?id=${id}`);
                const text = await response.text();
                const json = JSON.parse(text);
                if (json.status == 1) { renderRoles(json.result); } 
                else { container.innerHTML = '<div class="loading-wrapper" style="color:red">加载失败</div>'; }
            } catch (e) { console.error(e); }
        }

        /* 辅助函数：关闭所有手风琴 */
        function closeAllAccordions(exclude = null) {
            document.querySelectorAll('.accordion-card').forEach(el => {
                if (el !== exclude) {
                    el.classList.remove('active');
                    el.classList.add('collapsed');
                    el.querySelector('.accordion-body').style.maxHeight = null;
                }
            });
        }

        function renderRoles(data) {
            container.innerHTML = ''; 
            [1, 2, 3].forEach((type, index) => {
                if (!data[type] || !Array.isArray(data[type])) return;
                const accordion = document.createElement('div');
                accordion.className = 'accordion-card collapsed';
                const header = document.createElement('div');
                header.className = 'accordion-header';
                header.innerHTML = `<h4>${roleTypes[type]}</h4><svg class="accordion-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>`;
                const body = document.createElement('div');
                body.className = 'accordion-body';
                const grid = document.createElement('div');
                grid.className = 'role-grid';

                data[type].forEach(role => {
                    const isSelected = role.switch == 1;
                    state.allIds.push(role.role_id);
                    if (isSelected) state.selectedIds.add(role.role_id);
                    const item = document.createElement('div');
                    item.className = `role-item ${isSelected ? 'is-selected' : ''}`;
                    item.dataset.id = role.role_id;
                    
                    if (index === 0) {
                        item.innerHTML = `<img src="{{ asset('images') }}/${role.image_id}.webp" alt="${role.name}" loading="lazy">`;
                    } else {
                        item.innerHTML = `<img src="${LAZY_PLACEHOLDER}" data-src="{{ asset('images') }}/${role.image_id}.webp" alt="${role.name}">`;
                    }

                    item.addEventListener('click', () => toggleRole(role.role_id, item));
                    grid.appendChild(item);
                });

                body.appendChild(grid); 
                accordion.appendChild(header); 
                accordion.appendChild(body); 
                container.appendChild(accordion);

                header.addEventListener('click', () => {
                    const isOpen = accordion.classList.contains('active');
                    if (!isOpen) {
                        closeAllAccordions(accordion);
                        loadLazyImages(body);
                        openAccordion(accordion);
                    } else {
                        closeAccordion(accordion);
                    }
                });
            });

            const firstCard = container.querySelector('.accordion-card');
            if (firstCard) {
                loadLazyImages(firstCard.querySelector('.role-grid'));
                openAccordion(firstCard);
            }

            // 检查是否开启引导逻辑
            if (!POST_GUIDE_SWITCH) {
                setTimeout(startGuide, 300);
            }
        }

        function openAccordion(e) { 
            e.classList.remove('collapsed'); 
            e.classList.add('active'); 
            e.querySelector('.accordion-body').style.maxHeight = e.querySelector('.accordion-body').scrollHeight + "px"; 
        }
        function closeAccordion(e) { 
            e.classList.remove('active'); 
            e.classList.add('collapsed'); 
            e.querySelector('.accordion-body').style.maxHeight = null; 
        }
        function toggleRole(id, dom) { (state.selectedIds.has(id) ? (state.selectedIds.delete(id), dom.classList.remove('is-selected')) : (state.selectedIds.add(id), dom.classList.add('is-selected'))); }

        btnAllAdd.addEventListener('click', () => {
            state.allIds.forEach(id => {
                state.selectedIds.add(id);
                const i = container.querySelector(`.role-item[data-id="${id}"]`);
                if (i) i.classList.add('is-selected');
            });
        });

        btnSave.addEventListener('click', async () => {
            const nickname = inputNickname.value.trim();
            if (!nickname) { inputNickname.style.borderColor = 'var(--danger-color)'; setTimeout(() => inputNickname.style.borderColor = '', 2000); return; }
            try {
                btnSave.textContent = '保存中...'; btnSave.disabled = true;
                const res = await fetch("{{ url('user/account/add') }}", { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Content-Type':'application/json'}, body:JSON.stringify({ id:state.accountId, nickname, role_ids:Array.from(state.selectedIds) }) });
                const result = await res.json();
                if (result.status) { window.parent.refreshList ? window.parent.refreshList() : window.parent.location.reload(); } else { alert(result.result?.message || '保存失败'); }
            } catch (e) { console.error(e); }
            finally { btnSave.textContent = '保存配置'; btnSave.disabled = false; }
        });
        fetchRoles();

        // ==================== 新手引导逻辑 (绝对坐标锁定版) ====================
        
        const GUIDE_API_URL = "{{ url('user/guide/update') }}";

        const guideLayer = document.getElementById('guideLayer');
        const guideHighlight = document.getElementById('guideHighlight');
        const guideTooltip = document.getElementById('guideTooltip');
        const guideTitle = document.getElementById('guideTitle');
        const guideText = document.getElementById('guideText');
        const guideNextBtn = document.getElementById('guideNextBtn');
        const guideSkipBtn = document.getElementById('guideSkipBtn');

        let currentGuideIndex = 0;
        
        // 【修改点】用于强制锁定 Y 轴坐标，防止卡片跳动
        let lockedTooltipTop = null;

        const guideSteps = [
            { selector: '#nickname', title: '账号昵称', text: '请输入您的账号昵称，方便识别。' },
            { selector: '.accordion-card:first-child .role-item', title: '选择角色', text: '点击即可选择该角色参与作业，蓝色高亮为已选中。' },
            { selector: '#all-add', title: '一键全选', text: '如果你拥有一键全选的权限，可以点击此处快速配置所有角色。' },
            { selector: '#btn-save', title: '保存配置', text: '配置完成后，点击保存。' }
        ];

        // AJAX 请求记录引导状态
        async function recordGuideStatus() {
            try {
                await fetch(GUIDE_API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ location: 'post' })
                });
                console.log('Guide success recorded');
            } catch (err) {
                console.error('Guide record error', err);
            }
        }

        function endGuide() {
            if(guideLayer) guideLayer.style.display = 'none';
        }

        function waitForElementStable(target, timeout = 1500, interval = 50) {
            return new Promise((resolve) => {
                if (!target) { resolve(false); return; }
                let attempts = 0;
                const maxAttempts = timeout / interval;
                let lastY = target.getBoundingClientRect().top;
                let lastHeight = target.getBoundingClientRect().height;

                const check = () => {
                    attempts++;
                    const rect = target.getBoundingClientRect();
                    const currentY = rect.top;
                    const currentHeight = rect.height;

                    if (
                        Math.abs(currentY - lastY) <= 1 && 
                        Math.abs(currentHeight - lastHeight) <= 1 || 
                        attempts >= maxAttempts
                    ) {
                        resolve(true);
                    } else {
                        lastY = currentY;
                        lastHeight = currentHeight;
                        setTimeout(check, interval);
                    }
                };
                setTimeout(check, interval);
            });
        }

        async function showGuideStep(index) {
            if (index >= guideSteps.length) {
                endGuide();
                return;
            }

            const step = guideSteps[index];
            const target = document.querySelector(step.selector);

            if (!target) {
                console.warn(`Guide not found: ${step.selector}`);
                currentGuideIndex++; 
                if(currentGuideIndex < guideSteps.length) showGuideStep(currentGuideIndex);
                else endGuide();
                return;
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
            
            let tooltipTop;
            const spacing = 35; 

            // ================= 锁定垂直坐标逻辑 =================
            // 索引 2 (一键全选) 和 索引 3 (保存配置) 通常在同一行
            // 我们强制它们使用相同的 Tooltip Y 坐标，防止卡片上下跳动
            if (index === 2 || index === 3) {
                if (lockedTooltipTop === null) {
                    // 第一次到达这一组步骤时，计算并锁定 Y 坐标
                    await waitForElementStable(target, 2000, 50);
                    const rect = target.getBoundingClientRect();
                    
                    let calculatedPos = Math.round(rect.bottom) + spacing;
                    const estimatedHeight = 160; // 预估高度，避免移出屏幕
                    const winHeight = window.innerHeight;
                    
                    if (calculatedPos + estimatedHeight > winHeight) {
                        calculatedPos = Math.round(rect.top) - spacing - estimatedHeight;
                        if (calculatedPos < 10) calculatedPos = 10;
                    }
                    
                    lockedTooltipTop = calculatedPos;
                }
                // 强制使用锁定的值
                tooltipTop = lockedTooltipTop;
                
            } else {
                // 其他步骤：重置锁定，正常计算
                lockedTooltipTop = null; // 回到非锁定状态
                await waitForElementStable(target, 2000, 50);
                const rect = target.getBoundingClientRect();
                const estimatedHeight = 160;
                
                tooltipTop = Math.round(rect.bottom) + spacing;
                if (tooltipTop + estimatedHeight > window.innerHeight - 20) {
                    tooltipTop = Math.round(rect.top) - spacing - estimatedHeight;
                }
                if (tooltipTop < 10) tooltipTop = 10;
            }

            // 渲染高亮边框
            const rect = target.getBoundingClientRect();
            const highlightPadding = 4; 
            
            guideHighlight.style.top = (Math.round(rect.top) - highlightPadding) + 'px'; 
            guideHighlight.style.left = (Math.round(rect.left) - highlightPadding) + 'px';
            guideHighlight.style.width = (Math.round(rect.width) + highlightPadding * 2) + 'px';
            guideHighlight.style.height = (Math.round(rect.height) + highlightPadding * 2) + 'px';
            guideHighlight.style.opacity = 1;

            // 更新文字
            guideTitle.innerText = step.title;
            guideText.innerText = step.text;
            
            if (index === guideSteps.length - 1) {
                guideNextBtn.innerText = '完成';
            } else {
                guideNextBtn.innerText = '下一步';
            }

            // 横向位置计算
            const tooltipWidth = 280;
            const mobileMaxWidth = window.innerWidth * 0.9;
            const finalTooltipWidth = (window.innerWidth < 768 && mobileMaxWidth < 280) ? mobileMaxWidth : 280;

            let tooltipLeft = Math.round(rect.left + (rect.width / 2) - (finalTooltipWidth / 2));
            
            if (tooltipLeft < 10) tooltipLeft = 10;
            if (tooltipLeft + finalTooltipWidth > window.innerWidth - 10) tooltipLeft = window.innerWidth - finalTooltipWidth - 10;
            tooltipLeft = Math.round(tooltipLeft);

            // 应用样式
            guideTooltip.style.width = finalTooltipWidth + 'px';
            guideTooltip.style.top = tooltipTop + 'px'; 
            guideTooltip.style.left = tooltipLeft + 'px';
            
            guideLayer.style.display = 'block';
        }

        function startGuide() {
            // 重置锁定状态
            lockedTooltipTop = null;
            currentGuideIndex = 0;
            showGuideStep(currentGuideIndex);
        }

        // 点击下一步
        guideNextBtn.addEventListener('click', function() {
            if (currentGuideIndex === guideSteps.length - 1) {
                // 最后一步：发送请求，结束引导
                recordGuideStatus();
                endGuide();
            } else {
                currentGuideIndex++;
                showGuideStep(currentGuideIndex);
            }
        });

        // 点击跳过全部：发送请求，结束引导
        guideSkipBtn.addEventListener('click', function() {
            recordGuideStatus();
            endGuide();
        });

    })();
</script>
@endsection
