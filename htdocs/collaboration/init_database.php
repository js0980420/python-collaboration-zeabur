<?php
/**
 * Python協作教學平台 - 數據庫初始化腳本
 * 創建所有必要的數據庫表
 */

// XAMPP MySQL 連接設定
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'username' => 'root',
    'password' => '',  // XAMPP 預設無密碼
    'charset' => 'utf8mb4'
];

try {
    // 首先連接到MySQL（不指定數據庫）
    $pdo = new PDO(
        "mysql:host={$db_config['host']};port={$db_config['port']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ 成功連接到MySQL服務器\n";
    
    // 創建數據庫
    $pdo->exec("CREATE DATABASE IF NOT EXISTS python_collaboration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ 數據庫 'python_collaboration' 已創建或已存在\n";
    
    // 選擇數據庫
    $pdo->exec("USE python_collaboration");
    echo "✅ 已選擇數據庫 'python_collaboration'\n";
    
    // 先刪除可能存在的舊表，以確保結構更新
    $pdo->exec("DROP TABLE IF EXISTS room_participants");
    $pdo->exec("DROP TABLE IF EXISTS room_code_snapshots");
    // rooms 表通常不需要頻繁重建結構，但如果需要也可以解除下面這行的註解
    // $pdo->exec("DROP TABLE IF EXISTS rooms"); 
    echo "ℹ️  已嘗試刪除舊表 (如果存在)，以便重建最新結構。\n";
    
    // 創建 rooms 表
    $createRoomsTable = "
    CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_name VARCHAR(100) NOT NULL,
        room_code VARCHAR(50) UNIQUE NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_room_code (room_code)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $pdo->exec($createRoomsTable);
    echo "✅ 表 'rooms' 已創建或已存在\n";
    
    // 創建 room_code_snapshots 表
    $createSnapshotsTable = "
    CREATE TABLE IF NOT EXISTS room_code_snapshots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        code_content LONGTEXT NOT NULL,
        version INT NOT NULL,
        created_by_user_id VARCHAR(50),
        created_by_user_name VARCHAR(100),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        INDEX idx_room_version (room_id, version),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $pdo->exec($createSnapshotsTable);
    echo "✅ 表 'room_code_snapshots' 已創建或已存在\n";
    
    // 創建 room_participants 表
    $createParticipantsTable = "
    CREATE TABLE IF NOT EXISTS room_participants (
        room_id INT NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        user_name VARCHAR(100),
        last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
        cursor_data JSON,
        cursor_updated_at DATETIME,
        PRIMARY KEY (room_id, user_id),
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        INDEX idx_last_active (last_active),
        INDEX idx_cursor_updated (cursor_updated_at)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $pdo->exec($createParticipantsTable);
    echo "✅ 表 'room_participants' 已創建或已存在\n";
    
    // 創建一個示例房間（如果不存在）
    $checkRoom = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_code = 'demo-room'");
    $checkRoom->execute();
    
    if ($checkRoom->fetchColumn() == 0) {
        $insertRoom = $pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
        $insertRoom->execute([
            'Demo Room',
            'demo-room',
            '示例協作房間 - 用於測試和演示'
        ]);
        echo "✅ 已創建示例房間 'demo-room'\n";
    } else {
        echo "✅ 示例房間 'demo-room' 已存在\n";
    }
    
    // 顯示數據庫狀態
    echo "\n📊 數據庫狀態檢查:\n";
    
    $tables = ['rooms', 'room_code_snapshots', 'room_participants'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "   📋 表 '$table': $count 條記錄\n";
    }
    
    echo "\n🎉 數據庫初始化完成！\n";
    echo "現在您可以測試 code_sync_handler.php 了。\n";
    
} catch (PDOException $e) {
    echo "❌ 數據庫錯誤: " . $e->getMessage() . "\n";
    echo "請檢查:\n";
    echo "1. XAMPP MySQL 服務是否正在運行\n";
    echo "2. MySQL 端口 3306 是否可用\n";
    echo "3. 用戶名和密碼是否正確\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ 一般錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
?> 