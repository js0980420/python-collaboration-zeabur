-- 📊 學習分析與錯誤記錄系統

-- 1. 詳細學習軌跡表
CREATE TABLE learning_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_end TIMESTAMP NULL,
    total_duration_minutes INT DEFAULT 0,
    lessons_viewed INT DEFAULT 0,
    exercises_attempted INT DEFAULT 0,
    exercises_completed INT DEFAULT 0,
    code_executions INT DEFAULT 0,
    errors_encountered INT DEFAULT 0,
    help_requests INT DEFAULT 0,
    device_type ENUM('desktop', 'tablet', 'mobile') DEFAULT 'desktop',
    browser_info VARCHAR(200),
    ip_address VARCHAR(45),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_session (user_id, session_start),
    INDEX idx_session_date (session_start)
) ENGINE=InnoDB;

-- 2. 程式碼錯誤詳細記錄表
CREATE TABLE code_error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT,
    lesson_id INT,
    challenge_id VARCHAR(50),
    error_type ENUM('syntax', 'runtime', 'logic', 'timeout') NOT NULL,
    error_code VARCHAR(50), -- 如：SyntaxError, NameError等
    error_message TEXT NOT NULL,
    error_line_number INT,
    problematic_code LONGTEXT,
    user_code_before_error LONGTEXT,
    suggested_fix TEXT,
    time_to_fix_seconds INT DEFAULT 0,
    was_fixed BOOLEAN DEFAULT FALSE,
    fix_attempts INT DEFAULT 1,
    occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fixed_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES learning_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE SET NULL,
    INDEX idx_user_errors (user_id, occurred_at),
    INDEX idx_error_type (error_type),
    INDEX idx_lesson_errors (lesson_id, occurred_at)
) ENGINE=InnoDB;

-- 3. 學習行為追蹤表
CREATE TABLE learning_behaviors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT,
    behavior_type ENUM('page_view', 'code_edit', 'code_run', 'hint_request', 'solution_view', 'pause', 'resume') NOT NULL,
    target_type ENUM('lesson', 'challenge', 'example', 'documentation') NOT NULL,
    target_id VARCHAR(50) NOT NULL,
    behavior_data JSON, -- 額外的行為數據
    duration_seconds INT DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES learning_sessions(id) ON DELETE SET NULL,
    INDEX idx_user_behavior (user_id, timestamp),
    INDEX idx_behavior_type (behavior_type),
    INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB;

-- 4. 知識點掌握度表
CREATE TABLE knowledge_mastery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    knowledge_point VARCHAR(100) NOT NULL, -- 如：variables, loops, functions
    mastery_level ENUM('not_learned', 'learning', 'practiced', 'mastered') DEFAULT 'not_learned',
    confidence_score DECIMAL(3,2) DEFAULT 0.00, -- 0.00-1.00
    practice_count INT DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT 0.00, -- 成功率百分比
    last_practiced TIMESTAMP NULL,
    first_learned TIMESTAMP NULL,
    mastery_achieved_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_knowledge (user_id, knowledge_point),
    INDEX idx_user_mastery (user_id, mastery_level),
    INDEX idx_knowledge_point (knowledge_point)
) ENGINE=InnoDB;

-- 5. 學習困難點分析表
CREATE TABLE learning_difficulties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    difficulty_type ENUM('concept_understanding', 'syntax_errors', 'logic_errors', 'problem_solving') NOT NULL,
    specific_topic VARCHAR(100) NOT NULL,
    difficulty_level ENUM('minor', 'moderate', 'major', 'critical') DEFAULT 'minor',
    occurrence_count INT DEFAULT 1,
    total_time_stuck_minutes INT DEFAULT 0,
    help_effectiveness_score DECIMAL(3,2) DEFAULT 0.00,
    first_encountered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_encountered TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolution_status ENUM('unresolved', 'partially_resolved', 'resolved') DEFAULT 'unresolved',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_difficulty (user_id, specific_topic),
    INDEX idx_user_difficulties (user_id, difficulty_level),
    INDEX idx_topic_difficulties (specific_topic)
) ENGINE=InnoDB;

-- 6. 學習路徑追蹤表
CREATE TABLE learning_paths (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    path_name VARCHAR(100) NOT NULL,
    current_step INT DEFAULT 1,
    total_steps INT NOT NULL,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    estimated_completion_time INT, -- 預估剩餘時間（分鐘）
    actual_time_spent INT DEFAULT 0, -- 實際花費時間（分鐘）
    difficulty_adjustments JSON, -- 難度調整記錄
    personalized_recommendations JSON, -- 個人化建議
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_path (user_id, started_at),
    INDEX idx_path_completion (completion_percentage)
) ENGINE=InnoDB;

-- 7. 個人化學習建議表
CREATE TABLE personalized_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recommendation_type ENUM('next_lesson', 'practice_exercise', 'review_topic', 'difficulty_adjustment') NOT NULL,
    content_id VARCHAR(50) NOT NULL,
    content_type ENUM('lesson', 'challenge', 'example', 'documentation') NOT NULL,
    priority_score DECIMAL(3,2) DEFAULT 0.50,
    reasoning TEXT, -- 推薦理由
    is_shown BOOLEAN DEFAULT FALSE,
    is_accepted BOOLEAN DEFAULT FALSE,
    shown_at TIMESTAMP NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_recommendations (user_id, priority_score DESC),
    INDEX idx_recommendation_type (recommendation_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- 8. 學習效果評估表
CREATE TABLE learning_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assessment_type ENUM('pre_test', 'post_test', 'quiz', 'project_evaluation') NOT NULL,
    topic_area VARCHAR(100) NOT NULL,
    score DECIMAL(5,2) NOT NULL, -- 分數
    max_score DECIMAL(5,2) NOT NULL, -- 滿分
    percentage DECIMAL(5,2) NOT NULL, -- 百分比
    time_taken_minutes INT DEFAULT 0,
    questions_total INT DEFAULT 0,
    questions_correct INT DEFAULT 0,
    detailed_results JSON, -- 詳細結果分析
    improvement_suggestions TEXT,
    assessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_assessments (user_id, assessed_at),
    INDEX idx_topic_scores (topic_area, percentage),
    INDEX idx_assessment_type (assessment_type)
) ENGINE=InnoDB;

-- 創建學習分析視圖
CREATE VIEW user_learning_analytics AS
SELECT 
    u.id as user_id,
    u.username,
    u.display_name,
    
    -- 基本統計
    COUNT(DISTINCT ls.id) as total_sessions,
    COALESCE(SUM(ls.total_duration_minutes), 0) as total_learning_time,
    COALESCE(AVG(ls.total_duration_minutes), 0) as avg_session_duration,
    
    -- 錯誤統計
    COUNT(DISTINCT cel.id) as total_errors,
    COUNT(DISTINCT CASE WHEN cel.was_fixed = TRUE THEN cel.id END) as errors_fixed,
    COALESCE(AVG(cel.time_to_fix_seconds), 0) as avg_fix_time,
    
    -- 掌握度統計
    COUNT(DISTINCT km.knowledge_point) as topics_learned,
    COUNT(DISTINCT CASE WHEN km.mastery_level = 'mastered' THEN km.knowledge_point END) as topics_mastered,
    COALESCE(AVG(km.confidence_score), 0) as avg_confidence,
    
    -- 最近活動
    MAX(ls.session_start) as last_session,
    DATEDIFF(CURRENT_DATE, MAX(DATE(ls.session_start))) as days_since_last_session
    
FROM users u
LEFT JOIN learning_sessions ls ON u.id = ls.user_id
LEFT JOIN code_error_logs cel ON u.id = cel.user_id
LEFT JOIN knowledge_mastery km ON u.id = km.user_id
GROUP BY u.id, u.username, u.display_name;

-- 創建錯誤趨勢分析視圖
CREATE VIEW error_trend_analysis AS
SELECT 
    DATE(cel.occurred_at) as error_date,
    cel.error_type,
    cel.error_code,
    COUNT(*) as error_count,
    COUNT(DISTINCT cel.user_id) as affected_users,
    AVG(cel.time_to_fix_seconds) as avg_fix_time,
    SUM(CASE WHEN cel.was_fixed = TRUE THEN 1 ELSE 0 END) as fixed_count,
    ROUND(SUM(CASE WHEN cel.was_fixed = TRUE THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as fix_rate_percentage
FROM code_error_logs cel
WHERE cel.occurred_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
GROUP BY DATE(cel.occurred_at), cel.error_type, cel.error_code
ORDER BY error_date DESC, error_count DESC;

-- 插入示例知識點
INSERT INTO knowledge_mastery (user_id, knowledge_point, mastery_level, confidence_score, practice_count, success_rate) VALUES
(1, 'variables', 'mastered', 0.95, 15, 93.33),
(1, 'data_types', 'practiced', 0.80, 8, 87.50),
(1, 'conditionals', 'learning', 0.60, 5, 60.00),
(1, 'loops', 'not_learned', 0.00, 0, 0.00),
(1, 'functions', 'not_learned', 0.00, 0, 0.00); 