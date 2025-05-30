<?php
/**
 * XAMPP MySQL 數據庫初始化腳本
 * 為 Python 協作教學平台創建必要的數據庫和表
 */

// XAMPP MySQL 預設連接設定
$host = 'localhost';
$username = 'root';
$password = '';  // XAMPP 預設無密碼
$database = 'python_collaboration';

echo "<h1>Python 協作教學平台 - 數據庫初始化</h1>\n";
echo "<pre>\n";

try {
    // 1. 連接到 MySQL（不指定數據庫）
    echo "🔗 正在連接到 XAMPP MySQL...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 成功連接到 MySQL\n\n";

    // 2. 創建數據庫（如果不存在）
    echo "📊 創建數據庫...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ 數據庫 '$database' 創建成功\n\n";

    // 3. 選擇數據庫
    $pdo->exec("USE `$database`");

    // 4. 創建用戶表
    echo "👥 創建用戶表...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `display_name` VARCHAR(100) NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM('student', 'teacher', 'admin') DEFAULT 'student',
            `avatar_color` VARCHAR(7) DEFAULT '#007bff',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `last_active` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_username` (`username`),
            INDEX `idx_role` (`role`)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    echo "✅ 用戶表創建成功\n";

    // 5. 創建房間表
    echo "🏠 創建房間表...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `rooms` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_name` VARCHAR(100) NOT NULL UNIQUE,
            `room_code` VARCHAR(10) NOT NULL UNIQUE,
            `description` TEXT,
            `max_participants` INT DEFAULT 3,
            `current_participants` INT DEFAULT 0,
            `teacher_id` INT,
            `is_active` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_room_code` (`room_code`),
            INDEX `idx_teacher` (`teacher_id`)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    echo "✅ 房間表創建成功\n";

    // 6. 創建聊天消息表
    echo "💬 創建聊天消息表...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chat_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `message_type` ENUM('user', 'ai', 'system') DEFAULT 'user',
            `content` TEXT NOT NULL,
            `is_ai_response` BOOLEAN DEFAULT FALSE,
            `ai_action` VARCHAR(20) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_room_time` (`room_id`, `created_at`),
            INDEX `idx_user` (`user_id`)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    echo "✅ 聊天消息表創建成功\n";

    // 7. 創建代碼變更表
    echo "📝 創建代碼變更表...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `code_changes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `change_type` ENUM('insert', 'delete', 'replace') NOT NULL,
            `position` INT NOT NULL,
            `content` TEXT,
            `version` INT NOT NULL DEFAULT 1,
            `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_room_version` (`room_id`, `version`),
            INDEX `idx_timestamp` (`timestamp`)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    echo "✅ 代碼變更表創建成功\n";

    // 8. 創建房間代碼快照表
    echo "📷 創建代碼快照表...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `room_code_snapshots` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `code_content` LONGTEXT,
            `version` INT NOT NULL DEFAULT 1,
            `created_by` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_room_version` (`room_id`, `version`),
            INDEX `idx_room_latest` (`room_id`, `version` DESC)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    echo "✅ 代碼快照表創建成功\n\n";

    // 9. 插入示例數據
    echo "🌱 插入示例數據...\n";
    
    // 示例用戶
    $pdo->exec("
        INSERT IGNORE INTO `users` (`username`, `display_name`, `password_hash`, `role`, `avatar_color`) VALUES
        ('teacher1', '王老師', '" . password_hash('teacher123', PASSWORD_DEFAULT) . "', 'teacher', '#28a745'),
        ('student1', '小明', '" . password_hash('student123', PASSWORD_DEFAULT) . "', 'student', '#007bff'),
        ('student2', '小華', '" . password_hash('student123', PASSWORD_DEFAULT) . "', 'student', '#dc3545'),
        ('student3', '小美', '" . password_hash('student123', PASSWORD_DEFAULT) . "', 'student', '#ffc107')
    ");
    echo "✅ 示例用戶創建成功\n";

    // 示例房間
    $pdo->exec("
        INSERT IGNORE INTO `rooms` (`room_name`, `room_code`, `description`, `teacher_id`) VALUES
        ('Python基礎學習', 'PY001', 'Python基礎語法協作學習房間', 1),
        ('演算法練習', 'PY002', '數據結構與演算法協作練習', 1),
        ('專案開發', 'PY003', '小組專案開發協作空間', 1)
    ");
    echo "✅ 示例房間創建成功\n";

    // 初始代碼快照
    $initial_code = "# Python 協作編程練習\n# 歡迎來到協作學習！\n\nprint('Hello, Python!')\n\n# 請在下方編寫您的程式碼\n";
    $pdo->exec("
        INSERT IGNORE INTO `room_code_snapshots` (`room_id`, `code_content`, `version`, `created_by`) VALUES
        (1, '" . addslashes($initial_code) . "', 1, 1),
        (2, '" . addslashes($initial_code) . "', 1, 1),
        (3, '" . addslashes($initial_code) . "', 1, 1)
    ");
    echo "✅ 初始代碼快照創建成功\n\n";

    echo "🎉 數據庫初始化完成！\n\n";
    echo "📋 系統信息：\n";
    echo "   - 數據庫名稱: $database\n";
    echo "   - 字符集: utf8mb4\n";
    echo "   - 引擎: InnoDB\n\n";
    
    echo "👥 測試帳號：\n";
    echo "   教師: teacher1 / teacher123\n";
    echo "   學生: student1 / student123\n";
    echo "   學生: student2 / student123\n";
    echo "   學生: student3 / student123\n\n";
    
    echo "🏠 測試房間：\n";
    echo "   PY001 - Python基礎學習\n";
    echo "   PY002 - 演算法練習\n";
    echo "   PY003 - 專案開發\n\n";
    
    echo "✅ 可以開始使用協作平台了！\n";

} catch (PDOException $e) {
    echo "❌ 數據庫錯誤: " . $e->getMessage() . "\n";
    echo "💡 請確認：\n";
    echo "   1. XAMPP MySQL 服務是否正在運行\n";
    echo "   2. MySQL 端口 3306 是否可用\n";
    echo "   3. root 用戶是否有建立數據庫的權限\n";
} catch (Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='index.html'>返回協作平台</a></p>\n";
?> 