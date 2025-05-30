<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 教學開發儀表板 - Python教學平台</title>
    
    <!-- CSS框架 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 10px;
            padding: 20px;
            min-height: calc(100vh - 20px);
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            height: 400px;
        }

        .timeline-item {
            border-left: 3px solid var(--secondary-color);
            padding-left: 20px;
            margin-bottom: 20px;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--secondary-color);
        }

        .bug-item {
            background: #f8f9fa;
            border-left: 4px solid var(--danger-color);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .bug-item.resolved {
            border-left-color: var(--success-color);
        }

        .teaching-doc {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }

        .progress-ring circle {
            fill: transparent;
            stroke-width: 8;
            stroke-linecap: round;
        }

        .progress-ring .background {
            stroke: #e0e0e0;
        }

        .progress-ring .progress {
            stroke: var(--success-color);
            stroke-dasharray: 0 100;
            transition: stroke-dasharray 0.3s ease;
        }

        .filter-controls {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .export-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .real-time-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            z-index: 1000;
        }

        .real-time-indicator.updating {
            background: var(--warning-color);
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="dashboard-container">
            <!-- 標題欄 -->
            <div class="header">
                <h2><i class="fas fa-chart-line"></i> 教學開發儀表板</h2>
                <p>Python教學平台 - 修改追蹤與視覺化分析</p>
                <small>最後更新: <span id="lastUpdate">載入中...</span></small>
            </div>

            <!-- 即時狀態指示器 -->
            <div class="real-time-indicator" id="realTimeIndicator">
                <i class="fas fa-circle pulse"></i> 即時監控中
            </div>

            <!-- 篩選控制 -->
            <div class="filter-controls">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label for="dateRange" class="form-label">時間範圍</label>
                        <select class="form-select" id="dateRange" onchange="updateDashboard()">
                            <option value="7">最近7天</option>
                            <option value="30" selected>最近30天</option>
                            <option value="90">最近90天</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="changeType" class="form-label">修改類型</label>
                        <select class="form-select" id="changeType" onchange="updateDashboard()">
                            <option value="">全部類型</option>
                            <option value="新增">新增</option>
                            <option value="修改">修改</option>
                            <option value="修復">修復</option>
                            <option value="刪除">刪除</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="author" class="form-label">開發者</label>
                        <select class="form-select" id="author" onchange="updateDashboard()">
                            <option value="">全部開發者</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button class="btn export-btn" onclick="exportReport()">
                                <i class="fas fa-download"></i> 匯出報告
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 統計卡片 -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-primary" id="totalChanges">0</div>
                        <h6>總修改次數</h6>
                        <small class="text-muted">本月累計</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-success" id="bugFixed">0</div>
                        <h6>Bug修復</h6>
                        <small class="text-muted">已解決問題</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-warning" id="docsUpdated">0</div>
                        <h6>文檔更新</h6>
                        <small class="text-muted">教學文檔</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-info" id="codeQuality">85%</div>
                        <h6>代碼品質</h6>
                        <small class="text-muted">綜合評分</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- 修改類型分布圖 -->
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5><i class="fas fa-pie-chart"></i> 修改類型分布</h5>
                        <canvas id="changeTypeChart"></canvas>
                    </div>
                </div>

                <!-- 開發時間線 -->
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5><i class="fas fa-chart-line"></i> 開發時間線</h5>
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- 文件修改熱力圖 -->
                <div class="col-md-8">
                    <div class="chart-container">
                        <h5><i class="fas fa-fire"></i> 文件修改熱力圖</h5>
                        <canvas id="heatmapChart"></canvas>
                    </div>
                </div>

                <!-- 項目進度 -->
                <div class="col-md-4">
                    <div class="chart-container text-center">
                        <h5><i class="fas fa-tasks"></i> 項目進度</h5>
                        <div class="progress-ring" id="progressRing">
                            <svg width="120" height="120">
                                <circle class="background" cx="60" cy="60" r="54"></circle>
                                <circle class="progress" cx="60" cy="60" r="54" id="progressCircle"></circle>
                            </svg>
                        </div>
                        <div class="mt-3">
                            <h4 id="progressPercent">0%</h4>
                            <small class="text-muted">整體完成度</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- 最近修改記錄 -->
                <div class="col-md-6">
                    <div class="stat-card">
                        <h5><i class="fas fa-history"></i> 最近修改記錄</h5>
                        <div id="recentChanges">
                            <!-- 動態載入 -->
                        </div>
                    </div>
                </div>

                <!-- Bug追蹤 -->
                <div class="col-md-6">
                    <div class="stat-card">
                        <h5><i class="fas fa-bug"></i> Bug追蹤</h5>
                        <div id="bugTracker">
                            <!-- 動態載入 -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- 教學文檔狀態 -->
            <div class="row">
                <div class="col-12">
                    <div class="stat-card">
                        <h5><i class="fas fa-book"></i> 教學文檔狀態</h5>
                        <div id="teachingDocs">
                            <!-- 動態載入 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript庫 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>

    <script>
        // 全局變數
        let charts = {};
        let dashboardData = {};
        let updateInterval;

        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            startRealTimeUpdates();
        });

        // 初始化儀表板
        function initializeDashboard() {
            updateDashboard();
            initializeCharts();
        }

        // 更新儀表板數據
        async function updateDashboard() {
            try {
                updateRealTimeIndicator('updating');
                
                const dateRange = document.getElementById('dateRange').value;
                const changeType = document.getElementById('changeType').value;
                const author = document.getElementById('author').value;
                
                // 獲取統計數據
                const response = await fetch('change_tracker.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_stats&days=${dateRange}&change_type=${changeType}&author=${author}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    dashboardData = result.data;
                    updateStatCards();
                    updateCharts();
                    updateRecentChanges();
                    updateBugTracker();
                    updateTeachingDocs();
                    updateLastUpdateTime();
                }
                
                updateRealTimeIndicator('connected');
                
            } catch (error) {
                console.error('更新儀表板失敗:', error);
                updateRealTimeIndicator('error');
            }
        }

        // 更新統計卡片
        function updateStatCards() {
            // 模擬數據更新
            document.getElementById('totalChanges').textContent = Math.floor(Math.random() * 100) + 50;
            document.getElementById('bugFixed').textContent = Math.floor(Math.random() * 20) + 10;
            document.getElementById('docsUpdated').textContent = Math.floor(Math.random() * 15) + 5;
            
            // 更新進度環
            const progress = Math.floor(Math.random() * 30) + 70;
            updateProgressRing(progress);
        }

        // 初始化圖表
        function initializeCharts() {
            // 修改類型分布圖
            const changeTypeCtx = document.getElementById('changeTypeChart').getContext('2d');
            charts.changeType = new Chart(changeTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['新增', '修改', '修復', '刪除', '重構'],
                    datasets: [{
                        data: [30, 25, 20, 15, 10],
                        backgroundColor: [
                            '#27ae60',
                            '#3498db',
                            '#e74c3c',
                            '#f39c12',
                            '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // 開發時間線
            const timelineCtx = document.getElementById('timelineChart').getContext('2d');
            charts.timeline = new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: '修改次數',
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // 文件修改熱力圖
            const heatmapCtx = document.getElementById('heatmapChart').getContext('2d');
            charts.heatmap = new Chart(heatmapCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: '修改次數',
                        data: [],
                        backgroundColor: 'rgba(231, 76, 60, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // 更新圖表
        function updateCharts() {
            // 生成模擬數據
            const last7Days = [];
            const timelineData = [];
            
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                last7Days.push(date.toLocaleDateString('zh-TW', { month: 'short', day: 'numeric' }));
                timelineData.push(Math.floor(Math.random() * 10) + 1);
            }
            
            // 更新時間線圖表
            charts.timeline.data.labels = last7Days;
            charts.timeline.data.datasets[0].data = timelineData;
            charts.timeline.update();
            
            // 更新熱力圖
            const files = ['server.js', 'dashboard.php', 'change_tracker.php', 'collaboration.html', 'package.json'];
            const heatmapData = files.map(() => Math.floor(Math.random() * 15) + 1);
            
            charts.heatmap.data.labels = files;
            charts.heatmap.data.datasets[0].data = heatmapData;
            charts.heatmap.update();
        }

        // 更新最近修改記錄
        function updateRecentChanges() {
            const container = document.getElementById('recentChanges');
            const changes = [
                {
                    type: '修復',
                    file: 'server.js',
                    time: '2分鐘前',
                    author: 'Developer',
                    description: '修復WebSocket連接問題'
                },
                {
                    type: '新增',
                    file: 'dashboard.php',
                    time: '15分鐘前',
                    author: 'Developer',
                    description: '新增視覺化圖表功能'
                },
                {
                    type: '修改',
                    file: 'change_tracker.php',
                    time: '1小時前',
                    author: 'Developer',
                    description: '優化資料庫查詢性能'
                }
            ];
            
            container.innerHTML = changes.map(change => `
                <div class="timeline-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-primary">${change.type}</span>
                            <strong class="ms-2">${change.file}</strong>
                        </div>
                        <small class="text-muted">${change.time}</small>
                    </div>
                    <p class="mb-1 mt-2">${change.description}</p>
                    <small class="text-muted">by ${change.author}</small>
                </div>
            `).join('');
        }

        // 更新Bug追蹤
        function updateBugTracker() {
            const container = document.getElementById('bugTracker');
            const bugs = [
                {
                    title: 'WebSocket連接不穩定',
                    severity: '高',
                    status: '已解決',
                    occurrence: 3
                },
                {
                    title: '程式碼同步延遲',
                    severity: '中',
                    status: '進行中',
                    occurrence: 1
                },
                {
                    title: 'UI響應式問題',
                    severity: '低',
                    status: '已解決',
                    occurrence: 2
                }
            ];
            
            container.innerHTML = bugs.map(bug => `
                <div class="bug-item ${bug.status === '已解決' ? 'resolved' : ''}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${bug.title}</strong>
                            <span class="badge bg-${bug.severity === '高' ? 'danger' : bug.severity === '中' ? 'warning' : 'info'} ms-2">${bug.severity}</span>
                        </div>
                        <span class="badge bg-${bug.status === '已解決' ? 'success' : 'warning'}">${bug.status}</span>
                    </div>
                    <small class="text-muted">出現次數: ${bug.occurrence}</small>
                </div>
            `).join('');
        }

        // 更新教學文檔
        function updateTeachingDocs() {
            const container = document.getElementById('teachingDocs');
            const docs = [
                {
                    title: 'WebSocket協作系統教學指南',
                    type: '教學指南',
                    updated: '今天',
                    views: 25
                },
                {
                    title: 'Bug修復故障排除手冊',
                    type: '故障排除',
                    updated: '昨天',
                    views: 18
                },
                {
                    title: 'XAMPP部署指南',
                    type: '部署指南',
                    updated: '2天前',
                    views: 32
                }
            ];
            
            container.innerHTML = docs.map(doc => `
                <div class="teaching-doc">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${doc.title}</strong>
                            <span class="badge bg-info ms-2">${doc.type}</span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">更新: ${doc.updated}</small>
                            <small class="text-muted">瀏覽: ${doc.views}</small>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // 更新進度環
        function updateProgressRing(percent) {
            const circle = document.getElementById('progressCircle');
            const percentText = document.getElementById('progressPercent');
            
            const circumference = 2 * Math.PI * 54;
            const offset = circumference - (percent / 100) * circumference;
            
            circle.style.strokeDasharray = `${circumference} ${circumference}`;
            circle.style.strokeDashoffset = offset;
            
            percentText.textContent = `${percent}%`;
        }

        // 更新即時狀態指示器
        function updateRealTimeIndicator(status) {
            const indicator = document.getElementById('realTimeIndicator');
            
            switch (status) {
                case 'connected':
                    indicator.className = 'real-time-indicator';
                    indicator.innerHTML = '<i class="fas fa-circle pulse"></i> 即時監控中';
                    break;
                case 'updating':
                    indicator.className = 'real-time-indicator updating';
                    indicator.innerHTML = '<i class="fas fa-sync fa-spin"></i> 更新中...';
                    break;
                case 'error':
                    indicator.className = 'real-time-indicator';
                    indicator.style.background = '#e74c3c';
                    indicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> 連接錯誤';
                    break;
            }
        }

        // 更新最後更新時間
        function updateLastUpdateTime() {
            document.getElementById('lastUpdate').textContent = new Date().toLocaleString('zh-TW');
        }

        // 開始即時更新
        function startRealTimeUpdates() {
            updateInterval = setInterval(updateDashboard, 30000); // 每30秒更新一次
        }

        // 匯出報告
        async function exportReport() {
            try {
                const response = await fetch('change_tracker.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=generate_report'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 創建下載連結
                    const link = document.createElement('a');
                    link.href = result.report_path;
                    link.download = `teaching_report_${new Date().toISOString().split('T')[0]}.md`;
                    link.click();
                    
                    alert('報告已生成並開始下載！');
                } else {
                    alert('報告生成失敗: ' + result.error);
                }
                
            } catch (error) {
                console.error('匯出報告失敗:', error);
                alert('匯出報告時發生錯誤');
            }
        }

        // 頁面關閉時清理
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
</body>
</html> 