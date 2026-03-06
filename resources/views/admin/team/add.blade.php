@extends('main')
@section('content')
    <style>
        :root {
            --primary: #4a90e2;   /* 蓝色-添加 */
            --success: #10b981;   /* 绿色-提交 */
            --danger: #ef4444;    /* 红色-删除 */
            --bg: #f8fafc;
            --card-border: #e2e8f0;
            --error: #ef4444;
        }

        body { 
            font-family: 'PingFang SC', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            padding: 20px; 
            color: #334155; 
            padding-bottom: 100px; /* 为底部固定按钮留出空间 */
        }
        .container { max-width: 1000px; margin: 0 auto; }

        /* --- 顶部标题区 (仅输入框) --- */
        .header {
            display: flex; gap: 15px; background: white; padding: 20px; 
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .header-author { width: 180px; display: flex; flex-direction: column; gap: 5px; flex-shrink: 0; }
        .header-link { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .header label { font-size: 12px; font-weight: bold; color: #64748b; }
        
        input {
            padding: 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; width: 100%;
            box-sizing: border-box; transition: all 0.2s;
        }
        input:focus { border-color: var(--primary); }
        .input-error { border-color: var(--error) !important; background: #fff1f0; }

        /* --- 卡片 (左右结构) --- */
        .card {
            background: white; border: 1px solid var(--card-border); border-radius: 12px;
            margin-bottom: 20px; display: flex; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            position: relative;
        }
        .card-left {
            flex: 1.2; padding: 20px; border-right: 1px solid #f1f5f9;
            display: flex; flex-direction: column; gap: 18px; background: #fcfcfd;
        }
        .card-right { flex: 1; padding: 20px; display: flex; flex-direction: column; gap: 18px; }
        .section-box { display: flex; flex-direction: column; gap: 8px; }
        .section-title { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

        /* 选项组 */
        .options-row { display: flex; flex-wrap: nowrap; background: #edf2f7; padding: 3px; border-radius: 8px; gap: 2px; }
        .options-row label { flex: 1; text-align: center; cursor: pointer; }
        .options-row input { display: none; }
        .opt-label { display: block; padding: 8px 2px; font-size: 13px; border-radius: 6px; transition: 0.2s; color: #475569; }
        input:checked + .opt-label { background: var(--primary); color: white; font-weight: bold; }

        /* 头像 */
        .avatar-row { display: flex; gap: 8px; justify-content: flex-end; margin-top: auto; padding-top: 10px; }
        .ava-slot { width: 58px; height: 58px; background: #f1f5f9; border-radius: 6px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .ava-slot img { width: 100%; height: 100%; object-fit: cover; }

        /* --- 底部固定交互栏 --- */
        .footer-actions {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
            padding: 15px 20px; display: flex; justify-content: center; align-items: center;
            gap: 15px; box-shadow: 0 -4px 15px rgba(0,0,0,0.08); z-index: 1000;
        }
        .btn-base {
            border: none; border-radius: 8px; font-size: 15px; font-weight: bold;
            cursor: pointer; height: 48px; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-delete { background: white; color: var(--danger); border: 2.5px solid var(--danger); width: 130px; }
        .btn-delete:hover { background: #fff1f0; }
        .btn-submit { background: var(--success); color: white; flex: 0 1 350px; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .btn-submit:active { transform: scale(0.98); }
        .btn-add { background: var(--primary); color: white; width: 130px; }

        @media (max-width: 768px) {
            .header { flex-direction: column; }
            .header-author { width: 100%; }
            .card { flex-direction: column; }
            .footer-actions { gap: 8px; padding: 10px; }
            .btn-delete, .btn-add { width: 85px; font-size: 13px; }
        }
    </style>

<div class="container">
    <div class="header">
        <div class="header-author">
            <label>作者 *</label>
            <input type="text" id="global-author" placeholder="必填" required>
        </div>
        <div class="header-link">
            <label>链接 *</label>
            <input type="url" id="global-link" placeholder="请输入完整URL" required>
        </div>
    </div>

    <div id="card-list"></div>

    <div class="footer-actions">
        <button type="button" class="btn-base btn-delete" onclick="deleteLastCard()">✖ 删除最后</button>
        <button type="button" class="btn-base btn-submit" onclick="collectData()">确认并提交数据</button>
        <button type="button" class="btn-base btn-add" onclick="createCard()">＋ 添加作业</button>
    </div>
</div>

@endsection

@section('js')
<script>
    const roleAvatars = @json($roles);
    let cardIdx = 0;

    function createCard() {
        cardIdx++;
        const id = `card_${cardIdx}`;
        
        const stageOpts = [{l:'B阶段', v:2}, {l:'C阶段', v:3}, {l:'D阶段', v:5}];
        const bossOpts = [{l:'一王', v:1}, {l:'二王', v:2}, {l:'三王', v:3}, {l:'四王', v:4}, {l:'五王', v:5}];
        const diffOpts = [{l:'纯SET', v:1}, {l:'简单SET', v:2}, {l:'开关SET', v:3}, {l:'简单目押', v:4}, {l:'目押', v:5}];

        const template = `
            <div class="card" id="${id}">
                <div class="card-left">
                    <div class="section-box">
                        <span class="section-title">阶段</span>
                        <div class="options-row">
                            ${stageOpts.map(o => `
                                <label><input type="radio" name="stg_${id}" value="${o.v}" ${o.v === 5 ? 'checked' : ''}>
                                <span class="opt-label">${o.l}</span></label>
                            `).join('')}
                        </div>
                    </div>
                    <div class="section-box">
                        <span class="section-title">Boss (必选)</span>
                        <div class="options-row">
                            ${bossOpts.map(o => `
                                <label><input type="checkbox" name="bss_${id}" value="${o.v}">
                                <span class="opt-label">${o.l}</span></label>
                            `).join('')}
                        </div>
                    </div>
                    <div class="section-box">
                        <span class="section-title">难度</span>
                        <div class="options-row">
                            ${diffOpts.map(o => `
                                <label><input type="radio" name="dif_${id}" value="${o.v}" ${o.v === 2 ? 'checked' : ''}>
                                <span class="opt-label">${o.l}</span></label>
                            `).join('')}
                        </div>
                    </div>
                </div>

                <div class="card-right">
                    <div class="section-box">
                        <span class="section-title">伤害 (整数)</span>
                        <input type="number" class="in-damage" min="0" max="999999" placeholder="必填" required>
                    </div>
                    <div class="section-box">
                        <span class="section-title">角色 (空格区分)</span>
                        <input type="text" class="in-roles" placeholder="必填" required oninput="updateAvatars(this)">
                    </div>
                    <div class="avatar-row">
                        <div class="ava-slot"></div><div class="ava-slot"></div><div class="ava-slot"></div><div class="ava-slot"></div><div class="ava-slot"></div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('card-list').insertAdjacentHTML('beforeend', template);
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }

    // 删除最后一张卡片的函数
    function deleteLastCard() {
        const list = document.getElementById('card-list');
        if (list.children.length > 1) {
            list.lastElementChild.remove();
        } else {
            layui.layer.msg('至少需要保留一份作业内容', {icon: 0});
        }
    }

    function updateAvatars(input) {
        const card = input.closest('.card');
        const roles = input.value.trim().split(/\s+/).slice(0, 5);
        const slots = card.querySelectorAll('.ava-slot');
        slots.forEach(s => s.innerHTML = '');
        roles.reverse().forEach((name, i) => {
            const slot = slots[4 - i];
            if (roleAvatars[name]) {
                slot.innerHTML = `<img src="${roleAvatars[name].icon}">`;
            } else if (name) {
                slot.innerHTML = `<span style="font-size:12px; color:#94a3b8;">${name.slice(0,2)}</span>`;
            }
        });
    }

    function collectData() {
        document.querySelectorAll('input').forEach(i => i.classList.remove('input-error'));
        const author = document.getElementById('global-author');
        const link = document.getElementById('global-link');
        
        let isValid = true;
        if(!author.value) { author.classList.add('input-error'); isValid = false; }
        if(!link.value) { link.classList.add('input-error'); isValid = false; }

        const records = [];
        document.querySelectorAll('.card').forEach(card => {
            const id = card.id;
            const dmgInput = card.querySelector('.in-damage');
            const roleInput = card.querySelector('.in-roles');
            const checkedBosses = Array.from(card.querySelectorAll(`input[name="bss_${id}"]:checked`));

            // 校验 Boss 是否选择
            if(checkedBosses.length === 0) {
                card.querySelector('.options-row').style.border = "1.5px solid var(--error)";
                isValid = false;
            } else {
                card.querySelector('.options-row').style.border = "none";
            }

            if(!dmgInput.value) { dmgInput.classList.add('input-error'); isValid = false; }
            if(!roleInput.value) { roleInput.classList.add('input-error'); isValid = false; }

            const names = roleInput.value.trim().split(/\s+/);
            const ids = names.map(n => roleAvatars[n]?.id).filter(v => v !== undefined);
            
            if(ids.length !== names.length && names[0] !== "") {
                roleInput.classList.add('input-error');
                isValid = false;
            }

            records.push({
                stage: parseInt(card.querySelector(`input[name="stg_${id}"]:checked`).value),
                boss: checkedBosses.map(el => parseInt(el.value)),
                difficulty: parseInt(card.querySelector(`input[name="dif_${id}"]:checked`).value),
                damage: parseInt(dmgInput.value || 0),
                roles: ids
            });
        });

        if(!isValid) {
            layui.layer.msg("请检查红框处，确保必填项（包括Boss选择）正确", {icon: 2});
            return;
        }

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $.ajax({
            url: "{{ url('admin/team/add') }}",
            type: "POST",
            data: { author: author.value, link: link.value, records: records },
            success: function(res) {
                layui.layer.msg('全部保存成功', { icon: 1, time: 1000 }, function() {
                    if (parent && typeof parent.getTeams === 'function') parent.getTeams();
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                });
            },
            error: function(xhr) {
                const msg = xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : '保存失败';
                layui.layer.msg(msg, { icon: 2 });
            }
        });
    }

    createCard();
</script>
@endsection