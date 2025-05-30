-- Python教學網站數據庫結構
CREATE DATABASE IF NOT EXISTS python_teaching CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE python_teaching;

-- 用戶表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255),
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);

-- 課程表
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    difficulty INT DEFAULT 1,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lesson_id (lesson_id)
);

-- 學習進度表
CREATE TABLE user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    lesson_id VARCHAR(50) NOT NULL,
    progress_percent DECIMAL(5,2) DEFAULT 0.00,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (user_id, lesson_id),
    INDEX idx_user_id (user_id),
    INDEX idx_lesson_id (lesson_id)
);

-- AI對話記錄表
CREATE TABLE ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    session_id VARCHAR(100),
    message_type ENUM('user', 'assistant', 'system') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
);

-- 程式碼提交記錄表
CREATE TABLE code_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    code TEXT NOT NULL,
    syntax_errors JSON,
    analysis JSON,
    ai_feedback TEXT,
    suggestions JSON,
    has_errors BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- 學習統計表
CREATE TABLE learning_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    total_conversations INT DEFAULT 0,
    total_code_submissions INT DEFAULT 0,
    topics_covered JSON,
    last_activity TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_stats (user_id),
    INDEX idx_user_id (user_id)
);

-- 插入初始課程數據
INSERT INTO lessons (lesson_id, title, content, difficulty, order_index) VALUES
('python-basics', 'Python基礎語法', '學習Python的基本語法規則、變數宣告和基本資料型別', 1, 1),
('variables-types', '變數與資料型別', '深入了解Python中的各種資料型別：整數、浮點數、字串、布林值', 1, 2),
('operators', '運算符與表達式', '學習算術、比較、邏輯運算符的使用方法', 1, 3),
('conditionals', '條件判斷', '掌握if、elif、else語句的使用，實現程式的邏輯分支', 2, 4),
('loops', '迴圈結構', '學習for和while迴圈，實現重複執行的程式邏輯', 2, 5),
('functions', '函數定義', '學習如何定義和使用函數，理解參數傳遞和返回值', 3, 6),
('data-structures', '資料結構', '掌握列表、字典、元組和集合的使用方法', 3, 7); 