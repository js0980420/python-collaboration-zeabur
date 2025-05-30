-- Python協作教學平台 - Zeabur雲端數據庫初始化
-- 自動執行數據庫結構創建和示例數據插入

-- 設置字符集
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 使用指定的數據庫
USE python_collaboration;

-- 房間表
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_name` varchar(255) NOT NULL,
  `room_code` varchar(50) NOT NULL UNIQUE,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_code` (`room_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 房間參與者表
CREATE TABLE IF NOT EXISTS `room_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cursor_data` json DEFAULT NULL,
  `cursor_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_user` (`room_id`, `user_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_last_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 房間代碼快照表
CREATE TABLE IF NOT EXISTS `room_code_snapshots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `code_content` longtext NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `created_by_user_id` varchar(100) DEFAULT NULL,
  `created_by_user_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_version` (`room_id`, `version`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入示例數據
INSERT IGNORE INTO `rooms` (`room_name`, `room_code`, `description`) VALUES
('示例協作房間', 'room001', 'Python協作教學示例房間'),
('WebSocket測試房間', 'test001', 'WebSocket功能測試房間'),
('Zeabur雲端房間', 'cloud001', 'Zeabur雲端部署測試房間');

-- 插入初始代碼快照
INSERT IGNORE INTO `room_code_snapshots` (`room_id`, `code_content`, `version`, `created_by_user_name`) VALUES
(1, '# 🐍 Python協作教學平台 - Zeabur雲端版\n# 歡迎使用雲端部署的實時協作編程環境！\n\ndef welcome_message():\n    \"\"\"\n    歡迎使用Python協作教學平台\n    \"\"\"\n    print(\"🚀 歡迎使用Zeabur雲端部署版本！\")\n    print(\"✨ 支持多人實時協作編程\")\n    print(\"⚡ WebSocket延遲 < 0.5秒\")\n    print(\"🌐 全球可訪問的雲端服務\")\n    \n    return \"系統初始化完成\"\n\nif __name__ == \"__main__\":\n    result = welcome_message()\n    print(f\"狀態: {result}\")', 1, 'system'),
(2, '# WebSocket功能測試\ndef test_websocket():\n    print(\"WebSocket連接測試\")\n    return True\n\ntest_websocket()', 1, 'system'),
(3, '# Zeabur雲端測試\ndef cloud_test():\n    print(\"雲端部署測試成功！\")\n    return \"cloud_ready\"\n\ncloud_test()', 1, 'system');

-- 恢復外鍵檢查
SET FOREIGN_KEY_CHECKS = 1;

-- 顯示初始化完成信息
SELECT 'Python協作教學平台數據庫初始化完成！' as message; 