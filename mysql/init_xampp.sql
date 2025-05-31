-- 🏠 XAMPP本地版 - Python協作教學平台數據庫初始化
-- 適用於XAMPP內建MySQL環境

-- 創建數據庫
CREATE DATABASE IF NOT EXISTS python_collaboration 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE python_collaboration;

-- 房間表
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(255) NOT NULL,
    room_code VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_code (room_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 房間代碼快照表
CREATE TABLE IF NOT EXISTS room_code_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    code_content LONGTEXT NOT NULL,
    version INT NOT NULL DEFAULT 1,
    created_by_user_id VARCHAR(100),
    created_by_user_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room_version (room_id, version),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 房間參與者表
CREATE TABLE IF NOT EXISTS room_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cursor_data JSON,
    cursor_updated_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_user (room_id, user_id),
    INDEX idx_last_active (last_active),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 聊天消息表
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room_time (room_id, created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI助教請求記錄表
CREATE TABLE IF NOT EXISTS ai_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    user_id VARCHAR(100) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    request_type VARCHAR(50) NOT NULL,
    code_content LONGTEXT,
    question TEXT,
    ai_response LONGTEXT,
    processing_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    INDEX idx_room_time (room_id, created_at),
    INDEX idx_request_type (request_type),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入示例數據
INSERT IGNORE INTO rooms (room_name, room_code, description) VALUES 
('XAMPP測試房間', 'xampp_room', 'XAMPP本地環境測試房間'),
('Python入門', 'python_basic', 'Python基礎學習房間'),
('進階協作', 'advanced_collab', '進階協作編程房間');

-- 插入示例代碼快照
INSERT IGNORE INTO room_code_snapshots (room_id, code_content, version, created_by_user_id, created_by_user_name) 
SELECT 
    r.id,
    '# 🏠 XAMPP本地版 - Python協作教學平台
# 歡迎使用本地WebSocket實時協作環境！

def fibonacci_sequence(n):
    """
    生成斐波那契數列 - XAMPP本地版示例
    
    參數:
        n (int): 要生成的數列長度
    
    返回:
        list: 斐波那契數列
    """
    if n <= 0:
        return []
    elif n == 1:
        return [0]
    elif n == 2:
        return [0, 1]
    
    sequence = [0, 1]
    for i in range(2, n):
        next_num = sequence[i-1] + sequence[i-2]
        sequence.append(next_num)
    
    return sequence

def analyze_sequence(sequence):
    """分析數列的特性"""
    if not sequence:
        return "數列為空"
    
    total = sum(sequence)
    average = total / len(sequence)
    max_num = max(sequence)
    
    print(f"數列長度: {len(sequence)}")
    print(f"總和: {total}")
    print(f"平均值: {average:.2f}")
    print(f"最大值: {max_num}")
    
    return {
        "length": len(sequence),
        "sum": total,
        "average": average,
        "max": max_num
    }

# 主程式 - XAMPP本地測試
if __name__ == "__main__":
    print("🏠 XAMPP本地協作編程示例：斐波那契數列分析")
    
    # 生成前15個斐波那契數
    fib_sequence = fibonacci_sequence(15)
    print(f"前15個斐波那契數: {fib_sequence}")
    
    # 分析數列特性
    analysis = analyze_sequence(fib_sequence)
    print("\\n📊 數列分析完成！")
    
    # 💡 XAMPP本地版特色：
    # 1. 本地MySQL數據庫存儲
    # 2. WebSocket實時同步 (ws://127.0.0.1:8080)
    # 3. 離線AI助教功能
    # 4. 完全本地化，無需網路連接
    # 5. 適合教學演示和開發測試',
    1,
    'system',
    'XAMPP系統'
FROM rooms r 
WHERE r.room_code = 'xampp_room'
AND NOT EXISTS (
    SELECT 1 FROM room_code_snapshots s 
    WHERE s.room_id = r.id AND s.version = 1
);

-- 創建視圖：房間統計
CREATE OR REPLACE VIEW room_stats AS
SELECT 
    r.id,
    r.room_name,
    r.room_code,
    COUNT(DISTINCT p.user_id) as active_users,
    MAX(s.version) as latest_version,
    COUNT(s.id) as total_snapshots,
    COUNT(c.id) as total_messages,
    r.created_at,
    MAX(p.last_active) as last_activity
FROM rooms r
LEFT JOIN room_participants p ON r.id = p.room_id AND p.last_active >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
LEFT JOIN room_code_snapshots s ON r.id = s.room_id
LEFT JOIN chat_messages c ON r.id = c.room_id
GROUP BY r.id, r.room_name, r.room_code, r.created_at;

-- 創建存儲過程：清理舊數據
DELIMITER //
CREATE PROCEDURE CleanOldData()
BEGIN
    -- 清理7天前的聊天消息
    DELETE FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- 清理30天前的AI請求記錄
    DELETE FROM ai_requests WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- 清理非活躍用戶（24小時未活動）
    DELETE FROM room_participants WHERE last_active < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    -- 返回清理統計
    SELECT 
        'Data cleanup completed' as status,
        NOW() as cleanup_time;
END //
DELIMITER ;

-- 顯示初始化完成信息
SELECT 
    '🎉 XAMPP本地版數據庫初始化完成！' as message,
    COUNT(*) as total_tables
FROM information_schema.tables 
WHERE table_schema = 'python_collaboration';

-- 顯示表結構統計
SELECT 
    table_name as '表名',
    table_rows as '記錄數',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as '大小(MB)'
FROM information_schema.tables 
WHERE table_schema = 'python_collaboration'
ORDER BY table_name; 