-- 🎓 Python教學平台資料庫結構
-- 包含修改追蹤、教學記錄和視覺化功能
-- 
-- 創建時間: 2024-01-01
-- 版本: 1.0.0

-- 創建資料庫
CREATE DATABASE IF NOT EXISTS python_teaching 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE python_teaching;

-- ================================
-- 📝 修改追蹤相關表
-- ================================

-- 修改日誌表
CREATE TABLE change_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    change_type ENUM('新增', '修改', '刪除', '修復', '重構', '文檔') NOT NULL,
    affected_files TEXT NOT NULL,
    change_reason TEXT NOT NULL,
    change_content TEXT NOT NULL,
    test_result TEXT,
    teaching_value TEXT,
    author VARCHAR(100) DEFAULT 'Developer',
    commit_hash VARCHAR(40),
    file_size_before INT DEFAULT 0,
    file_size_after INT DEFAULT 0,
    lines_added INT DEFAULT 0,
    lines_removed INT DEFAULT 0,
    complexity_score DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_timestamp (timestamp),
    INDEX idx_change_type (change_type),
    INDEX idx_author (author),
    INDEX idx_commit_hash (commit_hash)
) ENGINE=InnoDB COMMENT='代碼修改日誌記錄';

-- Bug修復追蹤表
CREATE TABLE bug_fixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bug_title VARCHAR(255) NOT NULL,
    bug_description TEXT NOT NULL,
    occurrence_count INT DEFAULT 1,
    first_occurrence DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_occurrence DATETIME DEFAULT CURRENT_TIMESTAMP,
    solutions JSON,
    root_cause TEXT,
    prevention_measures TEXT,
    severity ENUM('低', '中', '高', '緊急') DEFAULT '中',
    status ENUM('開放', '進行中', '已解決', '已驗證', '已關閉') DEFAULT '開放',
    affected_files TEXT,
    related_changes JSON,
    fix_time_minutes INT DEFAULT 0,
    tester VARCHAR(100),
    test_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_occurrence_count (occurrence_count),
    FULLTEXT idx_title_description (bug_title, bug_description)
) ENGINE=InnoDB COMMENT='Bug修復追蹤記錄';

-- 教學文檔管理表
CREATE TABLE teaching_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_title VARCHAR(255) NOT NULL,
    doc_type ENUM('教學指南', '故障排除', '部署指南', '用戶手冊', 'API文檔', '最佳實踐') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    content_summary TEXT,
    related_changes JSON,
    view_count INT DEFAULT 0,
    download_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    tags JSON,
    difficulty_level ENUM('初級', '中級', '高級', '專家') DEFAULT '中級',
    estimated_read_time INT DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100) DEFAULT 'System',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_doc_type (doc_type),
    INDEX idx_difficulty (difficulty_level),
    INDEX idx_rating (rating),
    FULLTEXT idx_title_summary (doc_title, content_summary)
) ENGINE=InnoDB COMMENT='教學文檔管理';

-- 視覺化數據表
CREATE TABLE visualization_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chart_type VARCHAR(100) NOT NULL,
    chart_data JSON NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500),
    description TEXT,
    data_source VARCHAR(100),
    refresh_interval INT DEFAULT 3600,
    is_active BOOLEAN DEFAULT TRUE,
    access_count INT DEFAULT 0,
    
    INDEX idx_chart_type (chart_type),
    INDEX idx_generated_at (generated_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB COMMENT='視覺化圖表數據';

-- ================================
-- 👥 用戶和協作相關表
-- ================================

-- 用戶表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('學生', '教師', '管理員', '開發者') DEFAULT '學生',
    avatar_url VARCHAR(500),
    bio TEXT,
    skill_level ENUM('初學者', '初級', '中級', '高級', '專家') DEFAULT '初學者',
    preferred_language VARCHAR(10) DEFAULT 'zh-TW',
    timezone VARCHAR(50) DEFAULT 'Asia/Taipei',
    last_login DATETIME,
    login_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_skill_level (skill_level)
) ENGINE=InnoDB COMMENT='用戶基本信息';

-- 協作房間表
CREATE TABLE collaboration_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) UNIQUE NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    description TEXT,
    owner_id INT NOT NULL,
    max_participants INT DEFAULT 10,
    current_participants INT DEFAULT 0,
    room_type ENUM('公開', '私人', '教學', '考試') DEFAULT '公開',
    programming_language VARCHAR(20) DEFAULT 'python',
    initial_code TEXT,
    current_code TEXT,
    code_version INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_owner_id (owner_id),
    INDEX idx_room_type (room_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB COMMENT='協作房間管理';

-- 協作會話記錄表
CREATE TABLE collaboration_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    session_start DATETIME DEFAULT CURRENT_TIMESTAMP,
    session_end DATETIME,
    duration_minutes INT DEFAULT 0,
    actions_count INT DEFAULT 0,
    code_changes_count INT DEFAULT 0,
    chat_messages_count INT DEFAULT 0,
    session_data JSON,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_user_id (user_id),
    INDEX idx_session_start (session_start)
) ENGINE=InnoDB COMMENT='協作會話記錄';

-- ================================
-- 📊 學習分析相關表
-- ================================

-- 學習進度表
CREATE TABLE learning_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    topic VARCHAR(100) NOT NULL,
    subtopic VARCHAR(100),
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    time_spent_minutes INT DEFAULT 0,
    exercises_completed INT DEFAULT 0,
    exercises_total INT DEFAULT 0,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    mastery_level ENUM('未開始', '學習中', '基本掌握', '熟練', '精通') DEFAULT '未開始',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_topic (user_id, topic, subtopic),
    INDEX idx_user_id (user_id),
    INDEX idx_topic (topic),
    INDEX idx_mastery_level (mastery_level)
) ENGINE=InnoDB COMMENT='學習進度追蹤';

-- 代碼執行記錄表
CREATE TABLE code_executions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id VARCHAR(50),
    code_content TEXT NOT NULL,
    execution_result TEXT,
    execution_time_ms INT DEFAULT 0,
    memory_usage_mb DECIMAL(8,2) DEFAULT 0.00,
    has_error BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    error_type VARCHAR(100),
    line_number INT,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_room_id (room_id),
    INDEX idx_has_error (has_error),
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB COMMENT='代碼執行記錄';

-- 錯誤分析表
CREATE TABLE error_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    error_message TEXT NOT NULL,
    code_snippet TEXT,
    occurrence_count INT DEFAULT 1,
    first_occurrence DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_occurrence DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolution_suggestions JSON,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolution_time_minutes INT DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_error_type (error_type),
    INDEX idx_occurrence_count (occurrence_count)
) ENGINE=InnoDB COMMENT='錯誤分析和建議';

-- ================================
-- 📈 統計和報告相關表
-- ================================

-- 每日統計表
CREATE TABLE daily_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_rooms INT DEFAULT 0,
    active_rooms INT DEFAULT 0,
    code_executions INT DEFAULT 0,
    successful_executions INT DEFAULT 0,
    error_count INT DEFAULT 0,
    total_changes INT DEFAULT 0,
    bugs_reported INT DEFAULT 0,
    bugs_fixed INT DEFAULT 0,
    docs_created INT DEFAULT 0,
    docs_updated INT DEFAULT 0,
    
    UNIQUE KEY unique_date (stat_date),
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB COMMENT='每日統計數據';

-- 性能監控表
CREATE TABLE performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    metric_unit VARCHAR(20),
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    server_info JSON,
    
    INDEX idx_metric_name (metric_name),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB COMMENT='系統性能監控';

-- ================================
-- 🔧 系統配置和日誌表
-- ================================

-- 系統配置表
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    config_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_config_key (config_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB COMMENT='系統配置參數';

-- 操作日誌表
CREATE TABLE operation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    operation_type VARCHAR(50) NOT NULL,
    operation_description TEXT,
    target_type VARCHAR(50),
    target_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data JSON,
    response_data JSON,
    execution_time_ms INT DEFAULT 0,
    status ENUM('成功', '失敗', '警告') DEFAULT '成功',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='系統操作日誌';

-- ================================
-- 📚 教學內容相關表
-- ================================

-- 課程表
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    description TEXT,
    instructor_id INT NOT NULL,
    difficulty_level ENUM('初級', '中級', '高級', '專家') DEFAULT '初級',
    estimated_hours INT DEFAULT 0,
    prerequisites JSON,
    learning_objectives JSON,
    is_active BOOLEAN DEFAULT TRUE,
    enrollment_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_course_code (course_code),
    INDEX idx_instructor_id (instructor_id),
    INDEX idx_difficulty_level (difficulty_level)
) ENGINE=InnoDB COMMENT='課程信息';

-- 課程章節表
CREATE TABLE course_chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    chapter_number INT NOT NULL,
    chapter_title VARCHAR(200) NOT NULL,
    content TEXT,
    code_examples JSON,
    exercises JSON,
    estimated_minutes INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    order_index INT DEFAULT 0,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_chapter (course_id, chapter_number),
    INDEX idx_course_id (course_id),
    INDEX idx_order_index (order_index)
) ENGINE=InnoDB COMMENT='課程章節內容';

-- ================================
-- 🎯 視圖和觸發器
-- ================================

-- 創建統計視圖
CREATE VIEW v_change_statistics AS
SELECT 
    DATE(timestamp) as change_date,
    change_type,
    COUNT(*) as change_count,
    COUNT(DISTINCT author) as author_count,
    AVG(complexity_score) as avg_complexity
FROM change_logs 
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(timestamp), change_type
ORDER BY change_date DESC, change_type;

-- Bug統計視圖
CREATE VIEW v_bug_statistics AS
SELECT 
    severity,
    status,
    COUNT(*) as bug_count,
    AVG(occurrence_count) as avg_occurrence,
    AVG(fix_time_minutes) as avg_fix_time,
    SUM(CASE WHEN status = '已解決' THEN 1 ELSE 0 END) as resolved_count
FROM bug_fixes 
GROUP BY severity, status;

-- 用戶活躍度視圖
CREATE VIEW v_user_activity AS
SELECT 
    u.id,
    u.username,
    u.role,
    COUNT(DISTINCT cs.room_id) as rooms_participated,
    SUM(cs.duration_minutes) as total_time_minutes,
    COUNT(ce.id) as code_executions,
    AVG(lp.progress_percentage) as avg_progress
FROM users u
LEFT JOIN collaboration_sessions cs ON u.id = cs.user_id
LEFT JOIN code_executions ce ON u.id = ce.user_id
LEFT JOIN learning_progress lp ON u.id = lp.user_id
WHERE u.is_active = TRUE
GROUP BY u.id, u.username, u.role;

-- ================================
-- 🔄 觸發器設置
-- ================================

-- 更新房間參與者數量觸發器
DELIMITER //
CREATE TRIGGER tr_update_room_participants
AFTER INSERT ON collaboration_sessions
FOR EACH ROW
BEGIN
    UPDATE collaboration_rooms 
    SET current_participants = (
        SELECT COUNT(DISTINCT user_id) 
        FROM collaboration_sessions 
        WHERE room_id = NEW.room_id 
        AND session_end IS NULL
    )
    WHERE room_id = NEW.room_id;
END//

-- 自動更新每日統計觸發器
CREATE TRIGGER tr_update_daily_stats_changes
AFTER INSERT ON change_logs
FOR EACH ROW
BEGIN
    INSERT INTO daily_statistics (stat_date, total_changes)
    VALUES (DATE(NEW.timestamp), 1)
    ON DUPLICATE KEY UPDATE 
    total_changes = total_changes + 1;
END//

-- Bug修復統計觸發器
CREATE TRIGGER tr_update_daily_stats_bugs
AFTER UPDATE ON bug_fixes
FOR EACH ROW
BEGIN
    IF OLD.status != '已解決' AND NEW.status = '已解決' THEN
        INSERT INTO daily_statistics (stat_date, bugs_fixed)
        VALUES (CURDATE(), 1)
        ON DUPLICATE KEY UPDATE 
        bugs_fixed = bugs_fixed + 1;
    END IF;
END//

DELIMITER ;

-- ================================
-- 📊 初始化數據
-- ================================

-- 插入系統配置
INSERT INTO system_config (config_key, config_value, config_type, description, is_public) VALUES
('system_name', 'Python教學平台', 'string', '系統名稱', TRUE),
('version', '1.0.0', 'string', '系統版本', TRUE),
('max_room_participants', '10', 'number', '房間最大參與者數', FALSE),
('session_timeout_minutes', '30', 'number', '會話超時時間(分鐘)', FALSE),
('auto_save_interval', '60', 'number', '自動保存間隔(秒)', FALSE),
('enable_real_time_sync', 'true', 'boolean', '啟用即時同步', FALSE),
('default_programming_language', 'python', 'string', '預設程式語言', TRUE),
('max_code_length', '10000', 'number', '程式碼最大長度', FALSE);

-- 插入示例用戶
INSERT INTO users (username, email, password_hash, full_name, role, skill_level) VALUES
('admin', 'admin@example.com', '$2y$10$example_hash', '系統管理員', '管理員', '專家'),
('teacher1', 'teacher1@example.com', '$2y$10$example_hash', '張老師', '教師', '高級'),
('student1', 'student1@example.com', '$2y$10$example_hash', '李同學', '學生', '初級'),
('developer', 'dev@example.com', '$2y$10$example_hash', '開發者', '開發者', '專家');

-- 插入示例課程
INSERT INTO courses (course_code, course_name, description, instructor_id, difficulty_level, estimated_hours) VALUES
('PY101', 'Python基礎程式設計', 'Python程式語言基礎入門課程', 2, '初級', 40),
('PY201', 'Python進階應用', 'Python進階功能和實際應用', 2, '中級', 60),
('WEB301', 'Web開發實戰', '使用Python進行Web開發', 2, '高級', 80);

-- 插入示例教學文檔
INSERT INTO teaching_docs (doc_title, doc_type, file_path, content_summary, difficulty_level, estimated_read_time) VALUES
('Python安裝指南', '部署指南', '/docs/python_installation.md', '詳細的Python環境安裝步驟', '初級', 15),
('常見錯誤解決方案', '故障排除', '/docs/common_errors.md', 'Python學習中常見錯誤的解決方法', '中級', 25),
('協作編程最佳實踐', '最佳實踐', '/docs/collaboration_best_practices.md', '多人協作編程的技巧和建議', '高級', 30);

-- 插入性能監控初始數據
INSERT INTO performance_metrics (metric_name, metric_value, metric_unit) VALUES
('cpu_usage', 15.5, 'percent'),
('memory_usage', 512.0, 'MB'),
('disk_usage', 2048.0, 'MB'),
('response_time', 150.0, 'ms'),
('concurrent_users', 5, 'count');

-- ================================
-- 📝 索引優化建議
-- ================================

-- 為經常查詢的欄位添加複合索引
CREATE INDEX idx_change_logs_date_type ON change_logs(timestamp, change_type);
CREATE INDEX idx_bug_fixes_status_severity ON bug_fixes(status, severity);
CREATE INDEX idx_collaboration_sessions_room_user ON collaboration_sessions(room_id, user_id);
CREATE INDEX idx_code_executions_user_date ON code_executions(user_id, executed_at);

-- ================================
-- 🔒 權限設置
-- ================================

-- 創建應用程式用戶
CREATE USER IF NOT EXISTS 'teaching_app'@'localhost' IDENTIFIED BY 'secure_password_2024';

-- 授予必要權限
GRANT SELECT, INSERT, UPDATE, DELETE ON python_teaching.* TO 'teaching_app'@'localhost';
GRANT CREATE TEMPORARY TABLES ON python_teaching.* TO 'teaching_app'@'localhost';

-- 刷新權限
FLUSH PRIVILEGES;

-- ================================
-- 📋 維護腳本
-- ================================

-- 清理舊數據的事件調度器
SET GLOBAL event_scheduler = ON;

DELIMITER //
CREATE EVENT IF NOT EXISTS ev_cleanup_old_logs
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- 清理90天前的操作日誌
    DELETE FROM operation_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- 清理30天前的性能監控數據
    DELETE FROM performance_metrics WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- 清理已結束的協作會話(7天前)
    DELETE FROM collaboration_sessions 
    WHERE session_end IS NOT NULL 
    AND session_end < DATE_SUB(NOW(), INTERVAL 7 DAY);
END//
DELIMITER ;

-- 資料庫結構創建完成
SELECT 'Python教學平台資料庫結構創建完成！' as message;
SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'python_teaching'; 