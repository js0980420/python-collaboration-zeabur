-- 🤝 多人協作系統資料庫結構

-- 1. 協作房間表
CREATE TABLE collaboration_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL,
    room_code VARCHAR(10) UNIQUE NOT NULL, -- 房間邀請碼
    creator_id INT NOT NULL,
    max_members INT DEFAULT 4,
    current_members INT DEFAULT 1,
    task_id VARCHAR(50),
    status ENUM('waiting', 'active', 'completed', 'closed') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES challenges(id) ON DELETE SET NULL,
    INDEX idx_room_code (room_code),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- 2. 房間成員表
CREATE TABLE room_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('creator', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    contribution_score INT DEFAULT 0, -- 貢獻度分數
    
    FOREIGN KEY (room_id) REFERENCES collaboration_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_user (room_id, user_id),
    INDEX idx_room_active (room_id, last_active)
) ENGINE=InnoDB;

-- 3. 協作程式碼版本表
CREATE TABLE collaboration_code_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    version_number INT NOT NULL,
    code_content LONGTEXT NOT NULL,
    change_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES collaboration_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_version (room_id, version_number),
    INDEX idx_room_time (room_id, created_at)
) ENGINE=InnoDB;

-- 4. 即時聊天記錄表
CREATE TABLE room_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message_type ENUM('text', 'code_snippet', 'system') DEFAULT 'text',
    message_content TEXT NOT NULL,
    code_snippet LONGTEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES collaboration_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_time (room_id, sent_at)
) ENGINE=InnoDB;

-- 5. 團隊任務表
CREATE TABLE team_challenges (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    min_team_size INT DEFAULT 2,
    max_team_size INT DEFAULT 4,
    estimated_duration INT DEFAULT 60, -- 預估完成時間（分鐘）
    starter_code LONGTEXT,
    solution_code LONGTEXT,
    test_cases JSON,
    team_experience_reward INT DEFAULT 100,
    individual_experience_reward INT DEFAULT 25,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. 團隊任務完成記錄表
CREATE TABLE team_challenge_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    challenge_id VARCHAR(50) NOT NULL,
    submitted_code LONGTEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    completion_time_minutes INT DEFAULT 0,
    team_score INT DEFAULT 0, -- 團隊總分
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES collaboration_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES team_challenges(id) ON DELETE CASCADE,
    INDEX idx_room_completed (room_id, completed_at),
    INDEX idx_challenge_completed (challenge_id, completed_at)
) ENGINE=InnoDB;

-- 7. 個人貢獻記錄表
CREATE TABLE member_contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    contribution_type ENUM('code_edit', 'bug_fix', 'idea_suggestion', 'test_case') NOT NULL,
    contribution_description TEXT,
    points_earned INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES collaboration_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_user (room_id, user_id),
    INDEX idx_user_contributions (user_id, created_at)
) ENGINE=InnoDB;

-- 8. 即時協作狀態表（用於WebSocket）
CREATE TABLE collaboration_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    cursor_position INT DEFAULT 0,
    selected_text_start INT DEFAULT 0,
    selected_text_end INT DEFAULT 0,
    is_typing BOOLEAN DEFAULT FALSE,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES collaboration_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_user_session (room_id, user_id),
    INDEX idx_room_active_sessions (room_id, last_heartbeat)
) ENGINE=InnoDB;

-- 插入示例團隊任務
INSERT INTO team_challenges (id, title, description, min_team_size, max_team_size, team_experience_reward) VALUES
('team_calculator', '團隊計算器', '與隊友合作開發一個多功能計算器，包含基本運算、科學計算等功能', 2, 4, 200),
('team_game', '簡易遊戲開發', '團隊協作開發一個猜數字遊戲，需要包含用戶界面和遊戲邏輯', 3, 4, 300),
('team_data_analysis', '數據分析專案', '分析提供的數據集，生成圖表和報告，展示數據洞察', 2, 3, 250),
('team_web_scraper', '網頁爬蟲專案', '開發一個網頁爬蟲程式，抓取指定網站的數據並整理', 2, 4, 350);

-- 創建協作統計視圖
CREATE VIEW collaboration_stats AS
SELECT 
    u.id as user_id,
    u.username,
    u.display_name,
    COUNT(DISTINCT rm.room_id) as rooms_joined,
    COUNT(DISTINCT tcc.room_id) as challenges_completed,
    SUM(mc.points_earned) as total_contribution_points,
    AVG(rm.contribution_score) as avg_contribution_score
FROM users u
LEFT JOIN room_members rm ON u.id = rm.user_id
LEFT JOIN team_challenge_completions tcc ON rm.room_id = tcc.room_id
LEFT JOIN member_contributions mc ON u.id = mc.user_id
GROUP BY u.id, u.username, u.display_name;

-- 創建房間活躍度視圖
CREATE VIEW room_activity_stats AS
SELECT 
    cr.id as room_id,
    cr.room_name,
    cr.current_members,
    COUNT(DISTINCT ccv.id) as code_versions,
    COUNT(DISTINCT rcm.id) as chat_messages,
    MAX(rm.last_active) as last_activity,
    TIMESTAMPDIFF(MINUTE, cr.created_at, COALESCE(tcc.completed_at, NOW())) as duration_minutes
FROM collaboration_rooms cr
LEFT JOIN room_members rm ON cr.id = rm.room_id
LEFT JOIN collaboration_code_versions ccv ON cr.id = ccv.room_id
LEFT JOIN room_chat_messages rcm ON cr.id = rcm.room_id
LEFT JOIN team_challenge_completions tcc ON cr.id = tcc.room_id
GROUP BY cr.id, cr.room_name, cr.current_members, cr.created_at, tcc.completed_at; 