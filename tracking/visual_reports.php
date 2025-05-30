<?php
/**
 * 視覺化報告系統
 * 文件: visual_reports.php
 * 創建時間: 2024-01-01 10:00:00
 * 功能: 生成修改追蹤的視覺化圖表和報告
 */

require_once 'change_tracker.php';

class VisualReports {
    private $tracker;
    
    public function __construct() {
        $this->tracker = new ChangeTracker();
    }
    
    /**
     * 生成完整的視覺化報告頁面
     */
    public function generateReportPage() {
        $stats = $this->tracker->getChangeStats('7 DAY');
        $repeatedIssues = $this->tracker->getRepeatedIssues(20);
        $sessionReport = $this->tracker->getSessionReport();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="zh-TW">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>修改追蹤視覺化報告</title>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 20px;
                }
                
                .container {
                    max-width: 1400px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                
                .header h1 {
                    font-size: 2.5em;
                    margin-bottom: 10px;
                    font-weight: 300;
                }
                
                .header p {
                    font-size: 1.2em;
                    opacity: 0.9;
                }
                
                .dashboard {
                    padding: 30px;
                }
                
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin-bottom: 40px;
                }
                
                .stat-card {
                    background: #f8f9fa;
                    border-radius: 15px;
                    padding: 25px;
                    text-align: center;
                    border-left: 5px solid #667eea;
                    transition: transform 0.3s ease;
                }
                
                .stat-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                }
                
                .stat-number {
                    font-size: 2.5em;
                    font-weight: bold;
                    color: #667eea;
                    margin-bottom: 10px;
                }
                
                .stat-label {
                    font-size: 1.1em;
                    color: #666;
                    font-weight: 500;
                }
                
                .charts-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-bottom: 40px;
                }
                
                .chart-container {
                    background: #f8f9fa;
                    border-radius: 15px;
                    padding: 25px;
                    height: 400px;
                }
                
                .chart-title {
                    font-size: 1.3em;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 20px;
                    text-align: center;
                }
                
                .issues-section {
                    background: #f8f9fa;
                    border-radius: 15px;
                    padding: 25px;
                    margin-bottom: 30px;
                }
                
                .section-title {
                    font-size: 1.5em;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 10px;
                }
                
                .issue-item {
                    background: white;
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 15px;
                    border-left: 4px solid #dc3545;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                }
                
                .issue-header {
                    display: flex;
                    justify-content: between;
                    align-items: center;
                    margin-bottom: 10px;
                }
                
                .issue-count {
                    background: #dc3545;
                    color: white;
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.9em;
                    font-weight: bold;
                }
                
                .issue-description {
                    color: #666;
                    line-height: 1.6;
                    margin-bottom: 10px;
                }
                
                .issue-timeline {
                    font-size: 0.9em;
                    color: #999;
                }
                
                .controls {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 30px;
                    flex-wrap: wrap;
                }
                
                .control-group {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .control-group label {
                    font-weight: 500;
                    color: #333;
                }
                
                .control-group select,
                .control-group button {
                    padding: 8px 15px;
                    border: 2px solid #ddd;
                    border-radius: 8px;
                    font-size: 0.9em;
                    transition: all 0.3s ease;
                }
                
                .control-group button {
                    background: #667eea;
                    color: white;
                    border-color: #667eea;
                    cursor: pointer;
                }
                
                .control-group button:hover {
                    background: #5a6fd8;
                    transform: translateY(-2px);
                }
                
                .session-info {
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white;
                    border-radius: 15px;
                    padding: 25px;
                    margin-bottom: 30px;
                }
                
                .session-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-top: 15px;
                }
                
                .session-stat {
                    text-align: center;
                }
                
                .session-stat .number {
                    font-size: 2em;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .session-stat .label {
                    opacity: 0.9;
                    font-size: 0.9em;
                }
                
                @media (max-width: 768px) {
                    .charts-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .stats-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .controls {
                        flex-direction: column;
                    }
                }
                
                .loading {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                }
                
                .export-buttons {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 20px;
                }
                
                .export-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.3s ease;
                }
                
                .export-btn.json {
                    background: #17a2b8;
                    color: white;
                }
                
                .export-btn.csv {
                    background: #28a745;
                    color: white;
                }
                
                .export-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔍 修改追蹤視覺化報告</h1>
                    <p>實時監控代碼修改，追蹤重複問題，優化開發流程</p>
                </div>
                
                <div class="dashboard">
                    <!-- 當前會話信息 -->
                    <?php if ($sessionReport): ?>
                    <div class="session-info">
                        <h2>📊 當前開發會話</h2>
                        <div class="session-grid">
                            <div class="session-stat">
                                <div class="number"><?= $sessionReport['total_changes'] ?></div>
                                <div class="label">總修改次數</div>
                            </div>
                            <div class="session-stat">
                                <div class="number"><?= $sessionReport['bug_fixes'] ?></div>
                                <div class="label">Bug修復</div>
                            </div>
                            <div class="session-stat">
                                <div class="number"><?= $sessionReport['repeated_bugs'] ?></div>
                                <div class="label">重複Bug</div>
                            </div>
                            <div class="session-stat">
                                <div class="number"><?= $sessionReport['total_logs'] ?></div>
                                <div class="label">日誌記錄</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 控制面板 -->
                    <div class="controls">
                        <div class="control-group">
                            <label>時間範圍:</label>
                            <select id="timeframe" onchange="updateReports()">
                                <option value="24 HOUR">過去24小時</option>
                                <option value="7 DAY" selected>過去7天</option>
                                <option value="30 DAY">過去30天</option>
                                <option value="90 DAY">過去90天</option>
                            </select>
                        </div>
                        
                        <div class="control-group">
                            <button onclick="refreshData()">🔄 刷新數據</button>
                            <button onclick="endSession()">⏹️ 結束會話</button>
                        </div>
                        
                        <div class="export-buttons">
                            <button class="export-btn json" onclick="exportReport('json')">📄 導出JSON</button>
                            <button class="export-btn csv" onclick="exportReport('csv')">📊 導出CSV</button>
                        </div>
                    </div>
                    
                    <!-- 統計卡片 -->
                    <div class="stats-grid" id="statsGrid">
                        <?php echo $this->generateStatsCards($stats); ?>
                    </div>
                    
                    <!-- 圖表區域 -->
                    <div class="charts-grid">
                        <div class="chart-container">
                            <div class="chart-title">📈 修改類型分布</div>
                            <canvas id="changeTypeChart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <div class="chart-title">🎯 修改原因分析</div>
                            <canvas id="changeReasonChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- 重複問題列表 -->
                    <div class="issues-section">
                        <div class="section-title">🚨 重複問題追蹤</div>
                        <div id="repeatedIssues">
                            <?php echo $this->generateIssuesList($repeatedIssues); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                // 圖表數據
                const chartData = <?php echo json_encode($this->prepareChartData($stats)); ?>;
                
                // 初始化圖表
                let changeTypeChart, changeReasonChart;
                
                function initCharts() {
                    // 修改類型圖表
                    const typeCtx = document.getElementById('changeTypeChart').getContext('2d');
                    changeTypeChart = new Chart(typeCtx, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.types.labels,
                            datasets: [{
                                data: chartData.types.data,
                                backgroundColor: [
                                    '#667eea',
                                    '#764ba2',
                                    '#f093fb',
                                    '#f5576c'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                    
                    // 修改原因圖表
                    const reasonCtx = document.getElementById('changeReasonChart').getContext('2d');
                    changeReasonChart = new Chart(reasonCtx, {
                        type: 'bar',
                        data: {
                            labels: chartData.reasons.labels,
                            datasets: [{
                                label: '修改次數',
                                data: chartData.reasons.data,
                                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                                borderColor: 'rgba(102, 126, 234, 1)',
                                borderWidth: 2,
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
                
                // 更新報告
                function updateReports() {
                    const timeframe = document.getElementById('timeframe').value;
                    
                    fetch('visual_reports.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_stats&timeframe=${timeframe}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateStatsCards(data.stats);
                            updateCharts(data.chartData);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
                
                // 刷新數據
                function refreshData() {
                    location.reload();
                }
                
                // 結束會話
                function endSession() {
                    if (confirm('確定要結束當前開發會話嗎？')) {
                        const notes = prompt('請輸入會話總結（可選）:');
                        
                        fetch('change_tracker.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=end_session&notes=${encodeURIComponent(notes || '')}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('會話已結束！');
                                location.reload();
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                }
                
                // 導出報告
                function exportReport(format) {
                    const timeframe = document.getElementById('timeframe').value;
                    
                    fetch('change_tracker.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=export_report&format=${format}&timeframe=${timeframe}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const blob = new Blob([data.report], {
                                type: format === 'json' ? 'application/json' : 'text/csv'
                            });
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `change_report_${new Date().toISOString().split('T')[0]}.${format}`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
                
                // 頁面加載完成後初始化
                document.addEventListener('DOMContentLoaded', function() {
                    initCharts();
                    
                    // 每30秒自動刷新數據
                    setInterval(updateReports, 30000);
                });
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * 生成統計卡片HTML
     */
    private function generateStatsCards($stats) {
        $totalChanges = array_sum(array_column($stats, 'count'));
        $bugFixes = 0;
        $repeatedBugs = 0;
        $avgRepeats = 0;
        
        foreach ($stats as $stat) {
            if ($stat['change_reason'] === 'BUG_FIX') {
                $bugFixes += $stat['count'];
            }
            if ($stat['change_reason'] === 'REPEATED_BUG') {
                $repeatedBugs += $stat['count'];
            }
            $avgRepeats += $stat['avg_repeats'] * $stat['count'];
        }
        
        $avgRepeats = $totalChanges > 0 ? round($avgRepeats / $totalChanges, 2) : 0;
        
        return "
        <div class='stat-card'>
            <div class='stat-number'>{$totalChanges}</div>
            <div class='stat-label'>總修改次數</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number'>{$bugFixes}</div>
            <div class='stat-label'>Bug修復</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number'>{$repeatedBugs}</div>
            <div class='stat-label'>重複Bug</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number'>{$avgRepeats}</div>
            <div class='stat-label'>平均重複次數</div>
        </div>
        ";
    }
    
    /**
     * 生成問題列表HTML
     */
    private function generateIssuesList($issues) {
        if (empty($issues)) {
            return "<div class='loading'>🎉 太棒了！目前沒有重複問題</div>";
        }
        
        $html = '';
        foreach ($issues as $issue) {
            $attempts = json_decode($issue['resolution_attempts'], true) ?? [];
            $attemptsCount = count($attempts);
            
            $html .= "
            <div class='issue-item'>
                <div class='issue-header'>
                    <div class='issue-count'>重複 {$issue['occurrence_count']} 次</div>
                </div>
                <div class='issue-description'>{$issue['issue_description']}</div>
                <div class='issue-timeline'>
                    首次出現: {$issue['first_occurrence']} | 
                    最後出現: {$issue['last_occurrence']} | 
                    嘗試解決: {$attemptsCount} 次
                </div>
            </div>
            ";
        }
        
        return $html;
    }
    
    /**
     * 準備圖表數據
     */
    private function prepareChartData($stats) {
        $types = [];
        $reasons = [];
        
        foreach ($stats as $stat) {
            // 修改類型統計
            if (!isset($types[$stat['change_type']])) {
                $types[$stat['change_type']] = 0;
            }
            $types[$stat['change_type']] += $stat['count'];
            
            // 修改原因統計
            if (!isset($reasons[$stat['change_reason']])) {
                $reasons[$stat['change_reason']] = 0;
            }
            $reasons[$stat['change_reason']] += $stat['count'];
        }
        
        return [
            'types' => [
                'labels' => array_keys($types),
                'data' => array_values($types)
            ],
            'reasons' => [
                'labels' => array_keys($reasons),
                'data' => array_values($reasons)
            ]
        ];
    }
}

// API處理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    try {
        $reports = new VisualReports();
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_stats':
                $tracker = new ChangeTracker();
                $stats = $tracker->getChangeStats($_POST['timeframe'] ?? '7 DAY');
                $chartData = $reports->prepareChartData($stats);
                echo json_encode([
                    'success' => true, 
                    'stats' => $stats,
                    'chartData' => $chartData
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => '未知操作']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // 顯示報告頁面
    $reports = new VisualReports();
    echo $reports->generateReportPage();
}
?> 