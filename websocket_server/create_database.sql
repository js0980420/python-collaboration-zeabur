-- Python協作教學平台 - 資料庫初始化腳本
-- 執行前請確保MySQL服務已啟動

-- 創建資料庫（如果不存在）
CREATE DATABASE IF NOT EXISTS python_collaborate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 切換到新資料庫
USE python_collaborate;

-- 用戶表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    room_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_room_id (room_id)
);

-- 房間表
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) UNIQUE NOT NULL,
    code TEXT,
    version INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_id (room_id)
);

-- 聊天消息表
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_id (room_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- 代碼變更歷史表
CREATE TABLE IF NOT EXISTS code_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    code TEXT NOT NULL,
    version INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_id (room_id),
    INDEX idx_version (version),
    INDEX idx_created_at (created_at)
);

-- 插入示例資料
INSERT INTO rooms (room_id, code, version) VALUES 
('demo-room', '# 歡迎來到Python協作教學平台！\n# 這是一個示例房間\n\ndef hello_world():\n    print(\"Hello, World!\")\n    return \"協作愉快！\"\n\n# 開始您的Python學習之旅吧！', 0)
ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP;

-- 顯示創建結果
SELECT 'Python協作平台資料庫初始化完成！' AS status;
SHOW TABLES; 