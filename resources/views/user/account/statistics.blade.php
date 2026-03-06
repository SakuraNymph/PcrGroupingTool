<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>兰德索尔小助手 - 角色抽卡统计</title>
    <script src="/layuiadmin/html2canvas.min.js"></script>
    <style>
        :root {
            --primary-bg: #1a1a2e;
            --card-bg: #252545;
            --text-color: #ffffff;
            --green-bar: linear-gradient(45deg, #2e7d32 25%, #4caf50 25%, #4caf50 50%, #2e7d32 50%, #2e7d32 75%, #4caf50 75%, #4caf50 100%);
            --red-bar: linear-gradient(45deg, #c62828 25%, #f44336 25%, #f44336 50%, #c62828 50%, #c62828 75%, #f44336 75%, #f44336 100%);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #0f0f1e;
            color: var(--text-color);
            font-family: "Microsoft YaHei", sans-serif;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container { width: 100%; max-width: 800px; }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 25px;
            border-left: 5px solid #4f46e5;
            padding-left: 15px;
        }

        select {
            background: #2a2a4a; color: #fff; padding: 8px 12px; border-radius: 6px; 
            border: 1px solid #4f46e5; cursor: pointer;
        }

        /* 头像网格 */
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
            gap: 15px;
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .char-item { display: flex; flex-direction: column; align-items: center; }
        .img-box {
            width: 100%; aspect-ratio: 1/1; background: #3a3a5a; 
            border-radius: 8px; overflow: hidden; border: 2px solid #555; 
            margin-bottom: 8px; cursor: help;
        }
        .img-box img { width: 100%; height: 100%; object-fit: cover; }

        input[type="number"] { 
            width: 100%; padding: 6px; text-align: center; border-radius: 4px; 
            border: 1px solid #444; background: #1a1a2e; color: white;
        }

        .btns { display: flex; gap: 15px; margin-bottom: 30px; }
        button {
            flex: 1; padding: 14px; border-radius: 8px; border: none; cursor: pointer;
            font-weight: bold; font-size: 1rem; transition: 0.2s;
        }
        .btn-run { background: #4f46e5; color: white; }
        .btn-save { background: #475569; color: white; }

        /* 导出报告区域 */
        #exportArea {
            background: var(--primary-bg);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid #334155;
            display: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .report-title { text-align: center; margin-bottom: 25px; color: #818cf8; font-size: 1.5rem; }

        /* 统计数据 */
        .stats-summary {
            display: flex;
            justify-content: space-around;
            background: rgba(0,0,0,0.4);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #333;
        }
        .stats-item { text-align: center; }
        .stats-val { display: block; font-size: 1.5rem; color: #fbbf24; font-weight: bold; }
        .stats-label { font-size: 0.8rem; color: #94a3b8; margin-top: 4px; }

        .lucky { color: #4ade80 !important; }
        .unlucky { color: #f87171 !important; }

        /* 柱状图部分 */
        .chart-row { display: flex; align-items: center; margin-bottom: 15px; }
        .res-avatar { width: 50px; height: 50px; border-radius: 6px; margin-right: 15px; border: 1px solid #444; }
        .bar-wrap { flex-grow: 1; height: 38px; background: transparent; overflow: hidden; }
        .bar-inner {
            height: 100%; display: flex; align-items: center; padding-left: 15px;
            font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
            transition: width 1s ease-out; background-size: 40px 40px; border-radius: 8px;
        }
        .bar-inner.green { background-image: var(--green-bar); }
        .bar-inner.red { background-image: var(--red-bar); }

        footer.watermark {
            margin-top: 30px; text-align: center; color: #475569; font-size: 0.9rem;
            border-top: 1px solid #334155; padding-top: 20px;
        }

        @media (max-width: 500px) {
            .avatar-grid { grid-template-columns: repeat(4, 1fr); padding: 10px; }
            .stats-val { font-size: 1.1rem; }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h2>兰德索尔小助手</h2>
        <select id="yearPicker" onchange="handleYearChange()">
            <option value="2025">2025年</option>
            <option value="2026" selected>2026年</option>
        </select>
    </header>

    <div class="avatar-grid" id="inputGrid"></div>

    <div class="btns">
        <button class="btn-run" onclick="generateReport()">生成统计报告</button>
        <button class="btn-save" onclick="downloadImg()">保存为图片</button>
    </div>

    <div id="exportArea">
        <h2 class="report-title">角色抽卡鉴定报告</h2>
        
        <div class="stats-summary">
            <div class="stats-item">
                <span class="stats-label">总抽数</span>
                <span class="stats-val" id="totalPulls">0</span>
            </div>
            <div class="stats-item">
                <span class="stats-label">平均抽数</span>
                <span class="stats-val" style="color: #60a5fa;" id="avgPulls">0</span>
            </div>
            <div class="stats-item">
                <span class="stats-label">欧气鉴定</span>
                <span class="stats-val" id="rating">--</span>
            </div>
        </div>

        <div id="chartList"></div>
        <footer class="watermark">pcr.saololi.fun 提供技术支持</footer>
    </div>
</div>

<script src="/layuiadmin/jquery-3.4.1.min.js"></script>
<script>

    const accountId = @json($accountId);
    const mockDB    = @json($cardData);

    function handleYearChange() {
        document.getElementById('exportArea').style.display = 'none';
        document.getElementById('chartList').innerHTML = "";
        loadChars();
    }

    async function loadChars() {
        const year = document.getElementById('yearPicker').value;
        const grid = document.getElementById('inputGrid');
        grid.innerHTML = "<p style='grid-column: 1/-1; text-align:center;'>数据调取中...</p>";

        setTimeout(() => {
            const data = mockDB[year] || [];
            grid.innerHTML = "";
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'char-item';
                div.innerHTML = `
                    <div class="img-box" title="角色：${item.name}">
                        <img src="${item.img}">
                    </div>
                    <input type="number" class="pull-box" 
                           data-id="${item.id}" 
                           data-url="${item.img}" 
                           data-prob="${item.probability}"
                           min="0" max="200"
                           oninput="if(value>200)value=200;if(value<0)value=0"
                           value="${item.count !== undefined ? item.count : ''}" 
                           placeholder="输入抽数">
                `;
                grid.appendChild(div);
            });
        }, 300);
    }

    function generateReport() {
        const inputs        = document.querySelectorAll('.pull-box');
        const listContainer = document.getElementById('chartList');
        const exportArea    = document.getElementById('exportArea');
        const currentYear   = document.getElementById('yearPicker').value;
        
        let actualTotal     = 0;
        let theoryTotal     = 0;
        let validCharCount  = 0;

        // 分离变量：一个用于渲染，一个用于提交
        let chartData       = [];  // 存放：img, val, isRed (UI用)
        let submitData      = []; // 存放：id, count (后端用)

        listContainer.innerHTML = "";
        
        inputs.forEach(input => {
            const val = parseInt(input.value);
            const prob = parseInt(input.dataset.prob);
            const id = input.dataset.id;

            if (!isNaN(val) && val >= 0) {
                actualTotal += val;
                validCharCount++;
                
                // 1. 计算 UI 逻辑：红绿判定
                let isRed = false;
                let threshold = (prob === 1) ? 143 : 72;
                isRed = val >= threshold;
                
                theoryTotal += threshold; // 依然计算理论总和用于后台鉴定

                // 2. 存入渲染变量
                chartData.push({
                    img: input.dataset.url,
                    val: val,
                    isRed: isRed
                });

                // 3. 存入后端变量 (干净、纯粹的数据)
                submitData.push({
                    id: id,
                    count: val
                });
            }
        });

        if (validCharCount === 0) return alert("请输入至少一个抽数数据");

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.post("{{ url('user/account/statistics') }}", {id: accountId, year:currentYear, data:submitData});

        // 渲染列表
        chartData.forEach(data => {
            const row = document.createElement('div');
            row.className = 'chart-row';
            row.innerHTML = `
                <img src="${data.img}" class="res-avatar">
                <div class="bar-wrap">
                    <div class="bar-inner ${data.isRed?'red':'green'}" style="width:0%">
                        ${data.val} 抽
                    </div>
                </div>
            `;
            listContainer.appendChild(row);
            setTimeout(() => {
                row.querySelector('.bar-inner').style.width = Math.min((data.val/200)*100, 100) + '%';
            }, 50);
        });

        // 更新报告面板
        const actualAvg = (actualTotal / validCharCount).toFixed(1);
        document.getElementById('totalPulls').innerText = actualTotal;
        document.getElementById('avgPulls').innerText = actualAvg;

        const ratingEl = document.getElementById('rating');
        // 核心鉴定逻辑：实际 > 理论 = 非酋
        if (actualTotal >= theoryTotal) {
            ratingEl.innerText = "非酋";
            ratingEl.className = "stats-val unlucky";
        } else {
            ratingEl.innerText = "欧皇";
            ratingEl.className = "stats-val lucky";
        }

        exportArea.style.display = 'block';
    }

    function downloadImg() {
        const target = document.getElementById('exportArea');
        if (target.style.display === 'none') return alert("请先生成报告");
        html2canvas(target, { backgroundColor: "#1a1a2e", useCORS: true, scale: 2 }).then(canvas => {
            const link = document.createElement('a');
            link.download = `兰德索尔抽卡统计_${Date.now()}.png`;
            link.href = canvas.toURL ? canvas.toDataURL() : canvas.toDataURL("image/png");
            link.click();
        });
    }

    window.onload = loadChars;
</script>

</body>
</html>