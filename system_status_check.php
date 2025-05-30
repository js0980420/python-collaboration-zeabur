<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python 協作教學平台 - 系統狀態檢查</title>
    <style>
        body { font-family: 'Microsoft YaHei', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-section { margin: 20px 0; padding: 15px; border-radius: 8px; }
        .status-ok { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .status-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .status-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .test-item { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #2980b9; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .table-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 15px 0; }
        .table-card { background: #ecf0f1; padding: 15px; border-radius: 8px; border-left: 4px solid #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Python 協作教學平台 - 系統狀態檢查</h1>
        
        <?php
        // 數據庫連接測試
        $db_status = "ok";
        $db_message = "";
        $tables_info = [];
        
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=python_collaboration", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ]);
            
            $db_message = "✅ 數據庫連接成功！使用 MariaDB/MySQL";
            
            // 檢查所有表
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 檢查每個表的結構和數據量
            foreach ($tables as $table) {
                $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table`");
                $count_stmt->execute();
                $count = $count_stmt->fetchColumn();
                
                $struct_stmt = $pdo->prepare("DESCRIBE `$table`");
                $struct_stmt->execute();
                $columns = $struct_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $tables_info[$table] = [
                    'count' => $count,
                    'columns' => $columns
                ];
            }
            
        } catch (PDOException $e) {
            $db_status = "error";
            $db_message = "❌ 數據庫連接失敗：" . $e->getMessage();
        }
        ?>

        <!-- 數據庫狀態 -->
        <div class="status-section status-<?php echo $db_status; ?>">
            <h2>📊 數據庫連接狀態</h2>
            <p><?php echo $db_message; ?></p>
            
            <?php if ($db_status == "ok"): ?>
                <div class="test-item">
                    <strong>發現 <?php echo count($tables); ?> 個數據表：</strong>
                    <?php echo implode(', ', $tables); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 表結構詳情 -->
        <?php if ($db_status == "ok" && !empty($tables_info)): ?>
        <div class="status-section status-ok">
            <h2>🗃️ 數據表結構詳情</h2>
            <div class="table-info">
                <?php foreach ($tables_info as $table => $info): ?>
                    <div class="table-card">
                        <h3><?php echo $table; ?></h3>
                        <p><strong>數據量：</strong><?php echo $info['count']; ?> 條記錄</p>
                        <p><strong>字段數：</strong><?php echo count($info['columns']); ?> 個</p>
                        <details>
                            <summary>查看字段結構</summary>
                            <pre><?php 
                                foreach ($info['columns'] as $col) {
                                    echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Key'] . "\n";
                                }
                            ?></pre>
                        </details>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- PHP 環境檢查 -->
        <div class="status-section status-ok">
            <h2>🔧 PHP 環境狀態</h2>
            <div class="test-item">
                <strong>PHP 版本：</strong><?php echo phpversion(); ?>
            </div>
            <div class="test-item">
                <strong>PDO MySQL：</strong><?php echo extension_loaded('pdo_mysql') ? '✅ 已安裝' : '❌ 未安裝'; ?>
            </div>
            <div class="test-item">
                <strong>JSON 支援：</strong><?php echo extension_loaded('json') ? '✅ 已安裝' : '❌ 未安裝'; ?>
            </div>
            <div class="test-item">
                <strong>時區設定：</strong><?php echo date_default_timezone_get(); ?>
            </div>
        </div>

        <!-- 協作功能測試 -->
        <?php if ($db_status == "ok"): ?>
        <div class="status-section status-ok">
            <h2>🤝 協作功能測試</h2>
            <div class="test-item">
                <strong>代碼同步 API：</strong>
                <a href="code_sync_handler.php?action=get_latest_code&room_id=test" target="_blank" class="btn">測試獲取最新代碼</a>
            </div>
            <div class="test-item">
                <strong>房間管理：</strong>
                <a href="?test_room=1" class="btn">測試創建房間</a>
                <?php if (isset($_GET['test_room'])): ?>
                    <?php
                    try {
                        $stmt = $pdo->prepare("INSERT INTO rooms (name, description, created_by, max_participants) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=name");
                        $stmt->execute(['測試房間_' . date('His'), '系統狀態檢查測試房間', 1, 5]);
                        echo "<span style='color: green;'>✅ 房間創建測試成功</span>";
                    } catch (Exception $e) {
                        echo "<span style='color: red;'>❌ 房間創建測試失敗：" . $e->getMessage() . "</span>";
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 快速導航 -->
        <div class="status-section status-warning">
            <h2>🚀 快速導航</h2>
            <a href="index.html" class="btn">進入協作平台</a>
            <a href="跨設備協作修復版_最終版.html" class="btn">進入最終版協作平台</a>
            <a href="code_sync_handler.php" class="btn">查看 API 處理器</a>
            <a href="../phpmyadmin/" class="btn">打開 phpMyAdmin</a>
        </div>

        <!-- 系統建議 -->
        <div class="status-section status-warning">
            <h2>💡 系統建議</h2>
            <div class="test-item">
                <p><strong>🔍 測試步驟：</strong></p>
                <ol>
                    <li>確認上述所有狀態為綠色 ✅</li>
                    <li>點擊「進入最終版協作平台」開始測試</li>
                    <li>在兩個不同瀏覽器窗口或設備中打開相同房間</li>
                    <li>測試代碼編輯同步功能（2-5秒延遲）</li>
                    <li>測試聊天功能和用戶列表</li>
                </ol>
            </div>
            <div class="test-item">
                <p><strong>⚠️ 注意事項：</strong></p>
                <ul>
                    <li>同步延遲：2-5秒（HTTP輪詢機制）</li>
                    <li>建議同一區域網路環境下測試</li>
                    <li>支援多人同時協作（建議3-5人）</li>
                    <li>如遇問題，檢查 MySQL 和 Apache 服務狀態</li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #34495e; color: white; border-radius: 10px;">
            <h3>🎓 Python 多人協作教學平台</h3>
            <p>版本：v2.0 跨設備協作版 | 生成時間：<?php echo date('Y-m-d H:i:s'); ?></p>
            <p>狀態檢查完成 - 準備開始教學協作！🚀</p>
        </div>
    </div>
</body>
</html> 