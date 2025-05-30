-- 🎮 Python教學網站 - 遊戲化系統資料庫結構
-- 適用於 MySQL 5.7+ 或 MariaDB 10.2+

-- 創建資料庫
CREATE DATABASE IF NOT EXISTS python_teaching_gamified 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE python_teaching_gamified;

-- 1. 用戶表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    avatar_emoji VARCHAR(10) DEFAULT '🌟',
    level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    total_code_executions INT DEFAULT 0,
    total_lessons_completed INT DEFAULT 0,
    total_exercises_completed INT DEFAULT 0,
    learning_streak_days INT DEFAULT 0,
    last_active_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_level (level),
    INDEX idx_experience (experience_points),
    INDEX idx_last_active (last_active_date)
) ENGINE=InnoDB;

-- 2. 成就定義表
CREATE TABLE achievements (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon_class VARCHAR(50) NOT NULL,
    rarity ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    experience_reward INT DEFAULT 0,
    unlock_condition JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. 用戶成就表
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id VARCHAR(50) NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_unlocked (user_id, unlocked_at)
) ENGINE=InnoDB;

-- 4. 課程表
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    content LONGTEXT NOT NULL,
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    estimated_duration INT DEFAULT 30, -- 預估完成時間（分鐘）
    experience_reward INT DEFAULT 50,
    order_index INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_difficulty (difficulty),
    INDEX idx_order (order_index)
) ENGINE=InnoDB;

-- 5. 用戶學習進度表
CREATE TABLE user_lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    progress_percentage INT DEFAULT 0,
    time_spent_minutes INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (user_id, lesson_id),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB;

-- 6. 程式碼執行記錄表
CREATE TABLE code_executions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NULL,
    code_content LONGTEXT NOT NULL,
    execution_result LONGTEXT,
    execution_status ENUM('success', 'error', 'timeout') DEFAULT 'success',
    execution_time_ms INT DEFAULT 0,
    experience_gained INT DEFAULT 10,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    INDEX idx_user_executed (user_id, executed_at),
    INDEX idx_lesson_executed (lesson_id, executed_at)
) ENGINE=InnoDB;

-- 7. 挑戰任務表
CREATE TABLE challenges (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'easy',
    starter_code LONGTEXT,
    solution_code LONGTEXT,
    test_cases JSON,
    experience_reward INT DEFAULT 25,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 8. 用戶挑戰完成記錄表
CREATE TABLE user_challenge_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_id VARCHAR(50) NOT NULL,
    submitted_code LONGTEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    attempts_count INT DEFAULT 1,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    INDEX idx_user_completed (user_id, completed_at),
    INDEX idx_challenge_completed (challenge_id, completed_at)
) ENGINE=InnoDB;

-- 9. 學習統計表（每日統計）
CREATE TABLE daily_learning_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stat_date DATE NOT NULL,
    code_executions_count INT DEFAULT 0,
    lessons_completed_count INT DEFAULT 0,
    challenges_completed_count INT DEFAULT 0,
    total_study_time_minutes INT DEFAULT 0,
    experience_gained INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, stat_date),
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB;

-- 10. 系統設定表
CREATE TABLE system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================================
-- 插入初始數據
-- ================================

-- 插入成就定義
INSERT INTO achievements (id, name, description, icon_class, rarity, experience_reward) VALUES
('first_code', '第一行程式碼', '執行你的第一個Python程式', 'fas fa-code', 'bronze', 50),
('hello_world', 'Hello World', '成功輸出Hello World', 'fas fa-globe', 'bronze', 25),
('loop_master', '迴圈大師', '完成10個迴圈練習', 'fas fa-sync', 'silver', 100),
('function_expert', '函數專家', '創建並使用5個自定義函數', 'fas fa-cogs', 'gold', 150),
('level_5', '初學者', '達到Level 5', 'fas fa-seedling', 'bronze', 100),
('level_10', '進階學習者', '達到Level 10', 'fas fa-tree', 'silver', 200),
('streak_7', '七日連續', '連續7天學習Python', 'fas fa-fire', 'gold', 150),
('speed_demon', '程式飛手', '在30秒內完成一個挑戰', 'fas fa-bolt', 'silver', 75),
('perfectionist', '完美主義者', '連續10次程式碼執行無錯誤', 'fas fa-star', 'gold', 200);

-- 插入基礎課程
INSERT INTO lessons (title, description, content, difficulty, experience_reward, order_index) VALUES
('Python基礎語法', '學習Python的基本語法和概念', '# Python基礎語法\n\n## 變數和資料型別\n\n在Python中，變數不需要事先宣告型別...', 'beginner', 50, 1),
('條件判斷', '學習if-else條件判斷語句', '# 條件判斷\n\n## if語句\n\nif語句用於根據條件執行不同的程式碼...', 'beginner', 50, 2),
('迴圈結構', '學習for和while迴圈', '# 迴圈結構\n\n## for迴圈\n\nfor迴圈用於重複執行程式碼...', 'beginner', 75, 3),
('函數定義', '學習如何定義和使用函數', '# 函數定義\n\n## 什麼是函數\n\n函數是可重複使用的程式碼塊...', 'intermediate', 100, 4),
('資料結構', '學習列表、字典等資料結構', '# 資料結構\n\n## 列表(List)\n\n列表是Python中最常用的資料結構...', 'intermediate', 100, 5);

-- 插入挑戰任務
INSERT INTO challenges (id, title, description, difficulty, starter_code, solution_code, experience_reward) VALUES
('basic_print', '基礎輸出', '使用print()函數輸出你的名字', 'easy', 'print("你的名字")', 'print("小明")', 20),
('simple_math', '簡單計算', '計算兩個數字的和並輸出結果', 'easy', 'a = 5\nb = 3\n# 在這裡計算並輸出a+b', 'a = 5\nb = 3\nprint(a + b)', 25),
('for_loop', '迴圈練習', '使用for迴圈輸出1到5的數字', 'medium', '# 使用for迴圈輸出1到5', 'for i in range(1, 6):\n    print(i)', 40),
('function_def', '函數定義', '定義一個函數來計算圓的面積', 'hard', '# 定義計算圓面積的函數\n# 使用公式: π * r²', 'import math\n\ndef circle_area(radius):\n    return math.pi * radius ** 2\n\nprint(circle_area(5))', 60);

-- 插入系統設定
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('exp_per_code_execution', '10', '每次執行程式碼獲得的經驗值'),
('exp_per_lesson_completion', '50', '完成課程獲得的經驗值'),
('exp_per_challenge_completion', '25', '完成挑戰獲得的經驗值'),
('level_up_formula', 'level * 100 + 50', '升級所需經驗值計算公式'),
('max_daily_executions', '100', '每日最大程式碼執行次數限制');

-- ================================
-- 創建視圖和索引
-- ================================

-- 用戶排行榜視圖
CREATE VIEW user_leaderboard AS
SELECT 
    u.id,
    u.username,
    u.display_name,
    u.avatar_emoji,
    u.level,
    u.experience_points,
    u.total_code_executions,
    u.total_lessons_completed,
    u.learning_streak_days,
    RANK() OVER (ORDER BY u.experience_points DESC) as rank_position
FROM users u
WHERE u.last_active_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
ORDER BY u.experience_points DESC;

-- 用戶成就統計視圖
CREATE VIEW user_achievement_stats AS
SELECT 
    u.id as user_id,
    u.username,
    COUNT(ua.achievement_id) as total_achievements,
    COUNT(CASE WHEN a.rarity = 'bronze' THEN 1 END) as bronze_count,
    COUNT(CASE WHEN a.rarity = 'silver' THEN 1 END) as silver_count,
    COUNT(CASE WHEN a.rarity = 'gold' THEN 1 END) as gold_count,
    COUNT(CASE WHEN a.rarity = 'platinum' THEN 1 END) as platinum_count
FROM users u
LEFT JOIN user_achievements ua ON u.id = ua.user_id
LEFT JOIN achievements a ON ua.achievement_id = a.id
GROUP BY u.id, u.username;

-- ================================
-- 創建觸發器（自動更新統計）
-- ================================

-- 程式碼執行後自動更新用戶統計
DELIMITER //
CREATE TRIGGER update_user_stats_after_code_execution
AFTER INSERT ON code_executions
FOR EACH ROW
BEGIN
    -- 更新用戶總執行次數和經驗值
    UPDATE users 
    SET 
        total_code_executions = total_code_executions + 1,
        experience_points = experience_points + NEW.experience_gained,
        last_active_date = CURRENT_DATE
    WHERE id = NEW.user_id;
    
    -- 更新每日統計
    INSERT INTO daily_learning_stats (user_id, stat_date, code_executions_count, experience_gained)
    VALUES (NEW.user_id, CURRENT_DATE, 1, NEW.experience_gained)
    ON DUPLICATE KEY UPDATE 
        code_executions_count = code_executions_count + 1,
        experience_gained = experience_gained + NEW.experience_gained;
END//
DELIMITER ;

-- 課程完成後自動更新統計
DELIMITER //
CREATE TRIGGER update_user_stats_after_lesson_completion
AFTER UPDATE ON user_lesson_progress
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- 更新用戶完成課程數
        UPDATE users 
        SET 
            total_lessons_completed = total_lessons_completed + 1,
            last_active_date = CURRENT_DATE
        WHERE id = NEW.user_id;
        
        -- 更新每日統計
        INSERT INTO daily_learning_stats (user_id, stat_date, lessons_completed_count)
        VALUES (NEW.user_id, CURRENT_DATE, 1)
        ON DUPLICATE KEY UPDATE 
            lessons_completed_count = lessons_completed_count + 1;
    END IF;
END//
DELIMITER ;

-- ================================
-- 創建存儲過程
-- ================================

-- 檢查並解鎖成就
DELIMITER //
CREATE PROCEDURE CheckAndUnlockAchievements(IN p_user_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_achievement_id VARCHAR(50);
    DECLARE v_experience_reward INT;
    
    -- 檢查各種成就條件
    DECLARE achievement_cursor CURSOR FOR
        SELECT a.id, a.experience_reward
        FROM achievements a
        WHERE a.is_active = TRUE
        AND a.id NOT IN (
            SELECT ua.achievement_id 
            FROM user_achievements ua 
            WHERE ua.user_id = p_user_id
        );
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN achievement_cursor;
    
    achievement_loop: LOOP
        FETCH achievement_cursor INTO v_achievement_id, v_experience_reward;
        IF done THEN
            LEAVE achievement_loop;
        END IF;
        
        -- 檢查具體成就條件
        CASE v_achievement_id
            WHEN 'first_code' THEN
                IF (SELECT total_code_executions FROM users WHERE id = p_user_id) >= 1 THEN
                    CALL UnlockAchievement(p_user_id, v_achievement_id, v_experience_reward);
                END IF;
                
            WHEN 'level_5' THEN
                IF (SELECT level FROM users WHERE id = p_user_id) >= 5 THEN
                    CALL UnlockAchievement(p_user_id, v_achievement_id, v_experience_reward);
                END IF;
                
            WHEN 'level_10' THEN
                IF (SELECT level FROM users WHERE id = p_user_id) >= 10 THEN
                    CALL UnlockAchievement(p_user_id, v_achievement_id, v_experience_reward);
                END IF;
                
            WHEN 'loop_master' THEN
                IF (SELECT COUNT(*) FROM code_executions WHERE user_id = p_user_id AND code_content LIKE '%for %') >= 10 THEN
                    CALL UnlockAchievement(p_user_id, v_achievement_id, v_experience_reward);
                END IF;
                
            ELSE
                BEGIN END; -- 其他成就條件
        END CASE;
        
    END LOOP;
    
    CLOSE achievement_cursor;
END//
DELIMITER ;

-- 解鎖成就
DELIMITER //
CREATE PROCEDURE UnlockAchievement(
    IN p_user_id INT, 
    IN p_achievement_id VARCHAR(50),
    IN p_experience_reward INT
)
BEGIN
    -- 插入成就記錄
    INSERT IGNORE INTO user_achievements (user_id, achievement_id)
    VALUES (p_user_id, p_achievement_id);
    
    -- 如果成功插入（新成就），則給予經驗值獎勵
    IF ROW_COUNT() > 0 THEN
        UPDATE users 
        SET experience_points = experience_points + p_experience_reward
        WHERE id = p_user_id;
    END IF;
END//
DELIMITER ;

-- ================================
-- 示例查詢
-- ================================

-- 查看排行榜前10名
-- SELECT * FROM user_leaderboard LIMIT 10;

-- 查看用戶成就統計
-- SELECT * FROM user_achievement_stats WHERE user_id = 1;

-- 查看用戶學習進度
-- SELECT l.title, ulp.status, ulp.progress_percentage 
-- FROM user_lesson_progress ulp
-- JOIN lessons l ON ulp.lesson_id = l.id
-- WHERE ulp.user_id = 1;

-- 查看每日學習統計
-- SELECT * FROM daily_learning_stats 
-- WHERE user_id = 1 
-- ORDER BY stat_date DESC LIMIT 7; 