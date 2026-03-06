<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>B阶段排班表</title>
    <!-- 引入 jQuery 和 html2canvas -->
    <script src="/layuiadmin/html2canvas.min.js"></script>
    <script src="/layuiadmin/jquery-3.4.1.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto 20px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            height: 50px;
            vertical-align: middle;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            color: #333;
            font-size: 16px;
            user-select: none; /* 固定表头不可选 */
        }
        td {
            background-color: white;
            transition: background-color 0.3s ease;
        }
        td input {
            width: 100%;
            border: none;
            text-align: center;
            font-size: 14px;
            outline: none;
            background: transparent;
            max-width: 100%; /* 防止溢出 */
        }
        .buttons {
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .save-img {
            background-color: #4CAF50;
            color: white;
        }
        .save-img:hover {
            background-color: #45a049;
        }
        .clear {
            background-color: #f44336;
            color: white;
        }
        .clear:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>B阶段排班表</h1>
        <table id="scheduleTable">
            <!-- 表头行 -->
            <thead>
                <tr>
                    <th>一王</th>
                    <th>二王</th>
                    <th>三王</th>
                    <th>四王</th>
                    <th>五王</th>
                </tr>
            </thead>
            <tbody>
                <!-- 6行数据行，初始为空，添加 maxlength="255" -->
                <tr><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td></tr>
                <tr><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td></tr>
                <tr><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td></tr>
                <tr><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td></tr>
                <tr><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td></tr>
                <tr><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td><td><input type="text" maxlength="255" value=""></td></tr>
            </tbody>
        </table>
        <div class="buttons">
            <button class="save-img" onclick="saveAsImage()">保存图片</button>
            <button class="clear" onclick="clearTable()">一键清空</button>
        </div>
    </div>

    <script>
        // 预定义颜色数组（用于相同内容的背景）
        const colors = [
            '#FFE4E1', // MistyRose
            '#E6F3FF', // PowderBlue
            '#FFF2CC', // LightYellow
            '#E1FFE1', // LightGreen
            '#F0E68C', // Khaki
            '#DDA0DD', // Plum
            '#FFB6C1', // LightPink
            '#F5F5DC', // Beige
            '#E0FFFF', // LightCyan
            '#FFFACD'  // LemonChiffon
        ];
        let colorMap = new Map(); // 内容 -> 颜色
        let colorIndex = 0;

        // 获取表格数据：6x5 二维数组（忽略空值，用null）
        function getTableData() {
            const data = [];
            $('#scheduleTable tbody tr').each(function() {
                const row = [];
                $(this).find('td input').each(function() {
                    const val = $(this).val().trim();
                    row.push(val === '' ? null : val);
                });
                data.push(row);
            });
            return data;
        }

        // 更新颜色：基于当前内容
        function updateColors() {
            colorMap.clear();
            colorIndex = 0;
            const contentSet = new Set();
            // 先收集所有非空内容
            $('#scheduleTable tbody td input').each(function() {
                const val = $(this).val().trim();
                if (val !== '') {
                    contentSet.add(val);
                }
            });
            // 为每个唯一内容分配颜色
            contentSet.forEach(content => {
                if (!colorMap.has(content)) {
                    colorMap.set(content, colors[colorIndex % colors.length]);
                    colorIndex++;
                }
            });
            // 应用颜色
            $('#scheduleTable tbody td').each(function() {
                const input = $(this).find('input');
                const val = input.val().trim();
                if (val === '') {
                    $(this).css('background-color', 'white');
                } else {
                    $(this).css('background-color', colorMap.get(val) || 'white');
                }
            });
        }

        // 发送AJAX请求到后端（统一URL，GET加载，POST保存）
        function sendToBackend(data, method = 'POST') {
            const ajaxConfig = {
                url: '/b_stage_table_data', // Laravel 路由 URL
                type: method,
                data: JSON.stringify({ content: data }),
                success: function(response) {
                    console.log('操作成功', response);
                    // 可添加提示，如 alert('保存成功！');
                },
                error: function(xhr, status, error) {
                    console.error('操作失败', error);
                    // 可添加错误提示
                }
            };

            if (method === 'POST') {
                ajaxConfig.contentType = 'application/json';
                ajaxConfig.headers = {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                };
            } else {
                ajaxConfig.dataType = 'json';  // GET 期望 JSON
                ajaxConfig.data = {};  // GET 无 body
            }

            $.ajax(ajaxConfig);
        }

        // 加载初始数据（页面加载时调用）
        function loadTableData() {
            sendToBackend(null, 'GET');  // GET 请求，data 忽略
        }

        // 页面加载时，从后端获取初始数据
        $(document).ready(function() {
            loadTableData();  // 首次加载发送 GET
        });

        // GET 成功回调：加载数据到表格
        $(document).ajaxSuccess(function(event, xhr, settings) {
            if (settings.url === '/b_stage_table_data' && settings.type === 'GET') {
                const response = xhr.responseJSON;
                if (response && response.content) {
                    const tableData = response.content || [];
                    let rowIndex = 0;
                    $('#scheduleTable tbody tr').each(function() {
                        $(this).find('td input').each(function(colIndex) {
                            const val = tableData[rowIndex] ? tableData[rowIndex][colIndex] : null;
                            $(this).val(val || '');
                        });
                        rowIndex++;
                    });
                    updateColors();  // 加载后更新颜色
                }
            }
        });

        // 监听所有输入变化
        $(document).on('input', '#scheduleTable tbody input', function() {
            // 可选：额外限制长度（HTML maxlength 已处理，但这里可防 JS 绕过）
            if ($(this).val().length > 255) {
                $(this).val($(this).val().substring(0, 255));
            }
            updateColors(); // 更新颜色
            const data = getTableData();
            sendToBackend(data, 'POST'); // POST 保存
        });

        // 保存为图片
        function saveAsImage() {
            html2canvas(document.getElementById('scheduleTable')).then(function(canvas) {
                const link = document.createElement('a');
                link.download = 'B阶段排班表.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }

        // 一键清空
        function clearTable() {
            if (confirm('确认清空表格吗？')) {
                $('#scheduleTable tbody td input').val('');
                updateColors();
                sendToBackend(getTableData(), 'POST'); // POST 空数据
            }
        }
    </script>
</body>
</html>