<?php
/**
 * 為現有數據表添加進階功能字段
 * 支援游標同步、用戶統計等進階功能
 */

// 數據庫配置
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'python_collaboration';

echo "🔗 正在連接到數據庫...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ 數據庫連接成功\n\n";
    
    // 檢查並添加 room_participants 表的進階字段
    echo "📝 檢查並更新 room_participants 表...\n";
    
    // 檢查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'room_participants'");
    if ($stmt->rowCount() == 0) {
        // 如果表不存在，創建完整的表
        echo "⚠️ room_participants 表不存在，創建新表...\n";
        $pdo->exec("
            CREATE TABLE room_participants (
                id INT PRIMARY KEY AUTO_INCREMENT,
                room_id INT NOT NULL,
                user_id VARCHAR(100) NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                user_color VARCHAR(7) DEFAULT '#3498db',
                cursor_data JSON COMMENT '游標位置數據',
                cursor_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                selection_data JSON COMMENT '選取範圍數據',
                last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                total_edits INT DEFAULT 0,
                lines_added INT DEFAULT 0,
                lines_deleted INT DEFAULT 0,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_room_user (room_id, user_id),
                INDEX idx_room_id (room_id),
                INDEX idx_user_id (user_id),
                INDEX idx_last_active (last_active),
                INDEX idx_cursor_updated (cursor_updated_at)
            )
        ");
        echo "✅ room_participants 表創建成功\n";
    } else {
        // 如果表存在，添加缺少的字段
        $columns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM room_participants");
        while ($row = $stmt->fetch()) {
            $columns[] = $row['Field'];
        }
        
        // 需要添加的字段
        $fieldsToAdd = [
            'user_color' => "ADD COLUMN user_color VARCHAR(7) DEFAULT '#3498db'",
            'cursor_data' => "ADD COLUMN cursor_data JSON COMMENT '游標位置數據'",
            'cursor_updated_at' => "ADD COLUMN cursor_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            'selection_data' => "ADD COLUMN selection_data JSON COMMENT '選取範圍數據'",
            'total_edits' => "ADD COLUMN total_edits INT DEFAULT 0",
            'lines_added' => "ADD COLUMN lines_added INT DEFAULT 0",
            'lines_deleted' => "ADD COLUMN lines_deleted INT DEFAULT 0"
        ];
        
        foreach ($fieldsToAdd as $fieldName => $alterSQL) {
            if (!in_array($fieldName, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE room_participants $alterSQL");
                    echo "✅ 添加字段: $fieldName\n";
                } catch (Exception $e) {
                    echo "⚠️ 添加字段 $fieldName 失敗: " . $e->getMessage() . "\n";
                }
            } else {
                echo "ℹ️ 字段 $fieldName 已存在\n";
            }
        }
        
        // 添加索引
        try {
            $pdo->exec("ALTER TABLE room_participants ADD INDEX idx_cursor_updated (cursor_updated_at)");
            echo "✅ 添加游標更新索引\n";
        } catch (Exception $e) {
            echo "ℹ️ 游標更新索引可能已存在\n";
        }
    }
    
    // 更新 room_code_snapshots 表的進階字段
    echo "\n📝 檢查並更新 room_code_snapshots 表...\n";
    
    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM room_code_snapshots");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }
    
    $fieldsToAdd = [
        'code_language' => "ADD COLUMN code_language VARCHAR(20) DEFAULT 'python'",
        'change_type' => "ADD COLUMN change_type ENUM('create', 'edit', 'delete', 'restore') DEFAULT 'edit'",
        'diff_data' => "ADD COLUMN diff_data JSON COMMENT '變更差異數據'",
        'execution_result' => "ADD COLUMN execution_result TEXT COMMENT '代碼執行結果'"
    ];
    
    foreach ($fieldsToAdd as $fieldName => $alterSQL) {
        if (!in_array($fieldName, $columns)) {
            try {
                $pdo->exec("ALTER TABLE room_code_snapshots $alterSQL");
                echo "✅ 添加字段: $fieldName\n";
            } catch (Exception $e) {
                echo "⚠️ 添加字段 $fieldName 失敗: " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️ 字段 $fieldName 已存在\n";
        }
    }
    
    // 創建進階功能所需的新表
    echo "\n🚀 創建進階功能表...\n";
    
    // 代碼執行記錄表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS code_executions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            code_content TEXT NOT NULL,
            execution_output TEXT,
            execution_error TEXT,
            execution_time_ms INT COMMENT '執行時間(毫秒)',
            execution_status ENUM('success', 'error', 'timeout') DEFAULT 'success',
            python_version VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_id (room_id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (execution_status),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✅ 代碼執行記錄表創建成功\n";
    
    // 學習統計表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS learning_statistics (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id VARCHAR(100) NOT NULL,
            room_id INT NOT NULL,
            date DATE NOT NULL,
            coding_time_seconds INT DEFAULT 0,
            lines_written INT DEFAULT 0,
            code_executions INT DEFAULT 0,
            collaboration_time_seconds INT DEFAULT 0,
            messages_sent INT DEFAULT 0,
            concepts_learned JSON COMMENT '學習的概念',
            progress_score FLOAT DEFAULT 0.0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_room_date (user_id, room_id, date),
            INDEX idx_user_id (user_id),
            INDEX idx_room_id (room_id),
            INDEX idx_date (date)
        )
    ");
    echo "✅ 學習統計表創建成功\n";
    
    // AI助教記錄表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ai_assistance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            request_type ENUM('explain', 'debug', 'improve', 'question') NOT NULL,
            user_question TEXT NOT NULL,
            code_context TEXT,
            ai_response TEXT NOT NULL,
            response_quality ENUM('excellent', 'good', 'fair', 'poor') COMMENT '用戶評價',
            processing_time_ms INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_id (room_id),
            INDEX idx_user_id (user_id),
            INDEX idx_request_type (request_type),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✅ AI助教記錄表創建成功\n";
    
    // 插入一些測試數據
    echo "\n🌱 插入測試數據...\n";
    
    // 為現有房間參與者設置顏色
    $colors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e'];
    $stmt = $pdo->query("SELECT id FROM room_participants WHERE user_color = '#3498db' OR user_color IS NULL");
    $participants = $stmt->fetchAll();
    
    foreach ($participants as $index => $participant) {
        $color = $colors[$index % count($colors)];
        $updateStmt = $pdo->prepare("UPDATE room_participants SET user_color = ? WHERE id = ?");
        $updateStmt->execute([$color, $participant['id']]);
    }
    echo "✅ 為現有參與者分配顏色\n";
    
    // 統計信息
    $tables = ['users', 'rooms', 'room_participants', 'room_code_snapshots', 'chat_messages', 'code_executions', 'learning_statistics', 'ai_assistance'];
    echo "\n📊 數據表統計:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "   📋 $table: $count 條記錄\n";
    }
    
    echo "\n✨ 進階功能已成功添加！\n";
    echo "🎯 新增功能:\n";
    echo "   ✓ 游標位置同步\n";
    echo "   ✓ 用戶顏色識別\n";
    echo "   ✓ 代碼執行記錄\n";
    echo "   ✓ 學習統計分析\n";
    echo "   ✓ AI助教功能\n";
    echo "   ✓ 編輯統計追蹤\n\n";
    
} catch (PDOException $e) {
    echo "❌ 數據庫錯誤: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}
?> 