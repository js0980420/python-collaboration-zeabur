<?php
/**
 * Python協作教學平台 - 完整數據庫初始化腳本
 * 支援多人協作、游標同步、用戶統計等進階功能
 */
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python協作平台 - 完整數據庫初始化</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .info { color: #3498db; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #3498db; overflow-x: auto; }
        .table-info { background: #e8f4f8; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #2980b9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐍 Python協作教學平台 - 完整數據庫初始化</h1>
        <pre>
<?php
// 數據庫配置
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'python_collaboration';
$charset = 'utf8mb4';

echo "🔗 正在連接到 XAMPP MySQL...\n";

try {
    // 創建PDO連接
    $dsn = "mysql:host=$host;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ 成功連接到 MySQL\n\n";
    
    // 創建數據庫（如果不存在）
    echo "📊 創建數據庫...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
    $pdo->exec("USE $database");
    echo "✅ 數據庫 '$database' 創建成功\n\n";
    
    // 1. 用戶表 (增強版)
    echo "👥 創建增強用戶表...\n";
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            display_name VARCHAR(100),
            role ENUM('teacher', 'student', 'admin') DEFAULT 'student',
            avatar_color VARCHAR(7) DEFAULT '#3498db',
            total_coding_time INT DEFAULT 0 COMMENT '總編程時間(秒)',
            lines_written INT DEFAULT 0 COMMENT '總編寫行數',
            collaboration_count INT DEFAULT 0 COMMENT '協作次數',
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            preferences JSON COMMENT '用戶偏好設置'
        )
    ");
    echo "✅ 增強用戶表創建成功\n\n";
    
    // 2. 房間表 (增強版)
    echo "🏠 創建增強房間表...\n";
    $pdo->exec("DROP TABLE IF EXISTS rooms");
    $pdo->exec("
        CREATE TABLE rooms (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_name VARCHAR(100) NOT NULL,
            room_code VARCHAR(20) UNIQUE NOT NULL,
            description TEXT,
            max_participants INT DEFAULT 10,
            is_public BOOLEAN DEFAULT TRUE,
            password_hash VARCHAR(255) COMMENT '房間密碼',
            created_by_user_id INT,
            language VARCHAR(20) DEFAULT 'python',
            theme VARCHAR(20) DEFAULT 'vs-dark',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            settings JSON COMMENT '房間設置',
            INDEX idx_room_code (room_code),
            INDEX idx_created_by (created_by_user_id)
        )
    ");
    echo "✅ 增強房間表創建成功\n\n";
    
    // 3. 房間參與者表 (增強版，支援游標同步)
    echo "👫 創建增強參與者表...\n";
    $pdo->exec("DROP TABLE IF EXISTS room_participants");
    $pdo->exec("
        CREATE TABLE room_participants (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            user_color VARCHAR(7) DEFAULT '#3498db',
            role ENUM('owner', 'moderator', 'participant') DEFAULT 'participant',
            cursor_data JSON COMMENT '游標位置數據',
            cursor_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            selection_data JSON COMMENT '選取範圍數據',
            last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            total_edits INT DEFAULT 0,
            lines_added INT DEFAULT 0,
            lines_deleted INT DEFAULT 0,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_room_user (room_id, user_id),
            INDEX idx_room_id (room_id),
            INDEX idx_user_id (user_id),
            INDEX idx_last_active (last_active),
            INDEX idx_cursor_updated (cursor_updated_at)
        )
    ");
    echo "✅ 增強參與者表創建成功 (支援游標同步)\n\n";
    
    // 4. 代碼快照表 (增強版)
    echo "📝 創建增強代碼快照表...\n";
    $pdo->exec("DROP TABLE IF EXISTS room_code_snapshots");
    $pdo->exec("
        CREATE TABLE room_code_snapshots (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            version INT NOT NULL DEFAULT 1,
            code_content LONGTEXT NOT NULL,
            code_language VARCHAR(20) DEFAULT 'python',
            code_length INT GENERATED ALWAYS AS (CHAR_LENGTH(code_content)) STORED,
            line_count INT GENERATED ALWAYS AS ((CHAR_LENGTH(code_content) - CHAR_LENGTH(REPLACE(code_content, '\\n', '')) + 1)) STORED,
            created_by_user_id VARCHAR(100),
            created_by_user_name VARCHAR(100),
            change_type ENUM('create', 'edit', 'delete', 'restore') DEFAULT 'edit',
            diff_data JSON COMMENT '變更差異數據',
            execution_result TEXT COMMENT '代碼執行結果',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_version (room_id, version),
            INDEX idx_room_id (room_id),
            INDEX idx_created_by (created_by_user_id),
            INDEX idx_created_at (created_at),
            UNIQUE KEY unique_room_version (room_id, version)
        )
    ");
    echo "✅ 增強代碼快照表創建成功\n\n";
    
    // 5. 聊天消息表 (增強版)
    echo "💬 創建增強聊天消息表...\n";
    $pdo->exec("DROP TABLE IF EXISTS chat_messages");
    $pdo->exec("
        CREATE TABLE chat_messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            message_content TEXT NOT NULL,
            message_type ENUM('text', 'code', 'system', 'ai_help') DEFAULT 'text',
            reply_to_message_id INT COMMENT '回覆的消息ID',
            code_reference JSON COMMENT '引用的代碼片段',
            attachments JSON COMMENT '附件信息',
            is_edited BOOLEAN DEFAULT FALSE,
            edited_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_id (room_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_message_type (message_type)
        )
    ");
    echo "✅ 增強聊天消息表創建成功\n\n";
    
    // 6. 代碼執行記錄表
    echo "🚀 創建代碼執行記錄表...\n";
    $pdo->exec("DROP TABLE IF EXISTS code_executions");
    $pdo->exec("
        CREATE TABLE code_executions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            code_content TEXT NOT NULL,
            execution_output TEXT,
            execution_error TEXT,
            execution_time_ms INT COMMENT '執行時間(毫秒)',
            execution_status ENUM('success', 'error', 'timeout') DEFAULT 'success',
            python_version VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_id (room_id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (execution_status),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✅ 代碼執行記錄表創建成功\n\n";
    
    // 7. 學習統計表
    echo "📊 創建學習統計表...\n";
    $pdo->exec("DROP TABLE IF EXISTS learning_statistics");
    $pdo->exec("
        CREATE TABLE learning_statistics (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id VARCHAR(100) NOT NULL,
            room_id INT NOT NULL,
            date DATE NOT NULL,
            coding_time_seconds INT DEFAULT 0,
            lines_written INT DEFAULT 0,
            code_executions INT DEFAULT 0,
            collaboration_time_seconds INT DEFAULT 0,
            messages_sent INT DEFAULT 0,
            concepts_learned JSON COMMENT '學習的概念',
            progress_score FLOAT DEFAULT 0.0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_room_date (user_id, room_id, date),
            INDEX idx_user_id (user_id),
            INDEX idx_room_id (room_id),
            INDEX idx_date (date)
        )
    ");
    echo "✅ 學習統計表創建成功\n\n";
    
    // 8. AI助教記錄表
    echo "🤖 創建AI助教記錄表...\n";
    $pdo->exec("DROP TABLE IF EXISTS ai_assistance");
    $pdo->exec("
        CREATE TABLE ai_assistance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id VARCHAR(100) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            request_type ENUM('explain', 'debug', 'improve', 'question') NOT NULL,
            user_question TEXT NOT NULL,
            code_context TEXT,
            ai_response TEXT NOT NULL,
            response_quality ENUM('excellent', 'good', 'fair', 'poor') COMMENT '用戶評價',
            processing_time_ms INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_id (room_id),
            INDEX idx_user_id (user_id),
            INDEX idx_request_type (request_type),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✅ AI助教記錄表創建成功\n\n";
    
    // 插入示例數據
    echo "🌱 插入進階示例數據...\n";
    
    // 插入測試用戶 (增強版)
    $users = [
        ['teacher1', password_hash('teacher123', PASSWORD_DEFAULT), 'teacher@example.com', '張老師', 'teacher', '#e74c3c', 0, 0, 0],
        ['student1', password_hash('student123', PASSWORD_DEFAULT), 'student1@example.com', '小明', 'student', '#3498db', 0, 0, 0],
        ['student2', password_hash('student123', PASSWORD_DEFAULT), 'student2@example.com', '小華', 'student', '#2ecc71', 0, 0, 0],
        ['student3', password_hash('student123', PASSWORD_DEFAULT), 'student3@example.com', '小美', 'student', '#f39c12', 0, 0, 0],
        ['admin1', password_hash('admin123', PASSWORD_DEFAULT), 'admin@example.com', '系統管理員', 'admin', '#9b59b6', 0, 0, 0]
    ];
    
    $userStmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, email, display_name, role, avatar_color, total_coding_time, lines_written, collaboration_count) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($users as $user) {
        $userStmt->execute($user);
    }
    echo "✅ 5個增強測試用戶創建成功\n";
    
    // 插入測試房間 (增強版)
    $rooms = [
        ['Python基礎學習', 'PY001', 'Python語法基礎和編程概念學習', 5, 1, '{}'],
        ['演算法練習', 'PY002', '數據結構與演算法實作練習', 8, 1, '{}'],
        ['專案開發', 'PY003', '小組協作開發Python專案', 10, 1, '{}'],
        ['AI程式設計', 'AI001', '機器學習和AI應用開發', 6, 1, '{}'],
        ['Web開發實戰', 'WEB001', 'Django/Flask Web開發實戰', 8, 1, '{}']
    ];
    
    $roomStmt = $pdo->prepare("
        INSERT INTO rooms (room_name, room_code, description, max_participants, created_by_user_id, settings) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($rooms as $room) {
        $roomStmt->execute($room);
    }
    echo "✅ 5個增強測試房間創建成功\n";
    
    // 插入初始代碼快照
    $initialCode = "# 🐍 Python協作教學平台 - 完整功能版\n# 歡迎使用具備進階統計和AI助教的協作編程環境！\n\nimport json\nimport datetime\nfrom typing import List, Dict, Any\n\nclass CollaborativeLearning:\n    '''\n    協作學習系統核心類別\n    \n    功能:\n    - 多人即時代碼協作\n    - 學習進度追蹤\n    - AI智能助教\n    - 統計分析報告\n    '''\n    \n    def __init__(self, room_code: str, user_name: str):\n        self.room_code = room_code\n        self.user_name = user_name\n        self.start_time = datetime.datetime.now()\n        self.code_history = []\n        self.collaboration_stats = {\n            'lines_written': 0,\n            'edits_made': 0,\n            'collaboration_time': 0,\n            'concepts_learned': []\n        }\n    \n    def log_code_change(self, new_code: str, change_type: str = 'edit'):\n        '''記錄代碼變更'''\n        timestamp = datetime.datetime.now()\n        change_record = {\n            'timestamp': timestamp.isoformat(),\n            'change_type': change_type,\n            'code_length': len(new_code),\n            'line_count': new_code.count('\\n') + 1\n        }\n        self.code_history.append(change_record)\n        self.collaboration_stats['edits_made'] += 1\n        \n        # 計算新增行數\n        if len(self.code_history) > 1:\n            prev_lines = self.code_history[-2]['line_count']\n            current_lines = change_record['line_count']\n            if current_lines > prev_lines:\n                self.collaboration_stats['lines_written'] += (current_lines - prev_lines)\n        \n        print(f\"📝 代碼變更記錄: {change_type}, 當前行數: {change_record['line_count']}\")\n    \n    def get_learning_progress(self) -> Dict[str, Any]:\n        '''獲取學習進度報告'''\n        current_time = datetime.datetime.now()\n        session_duration = (current_time - self.start_time).total_seconds()\n        \n        progress_report = {\n            'user_name': self.user_name,\n            'room_code': self.room_code,\n            'session_duration_minutes': round(session_duration / 60, 2),\n            'total_edits': self.collaboration_stats['edits_made'],\n            'lines_written': self.collaboration_stats['lines_written'],\n            'average_edit_interval': round(session_duration / max(1, len(self.code_history)), 2),\n            'coding_activity': 'Active' if len(self.code_history) > 5 else 'Moderate'\n        }\n        \n        return progress_report\n    \n    def simulate_ai_assistance(self, question: str) -> str:\n        '''模擬AI助教回答'''\n        ai_responses = {\n            'explain': '這段程式碼使用了物件導向程式設計的概念，透過類別封裝相關的資料和方法...',\n            'debug': '我發現可能的問題：請檢查變數名稱是否正確，以及是否有語法錯誤...',\n            'improve': '建議優化：可以使用列表推導式來簡化程式碼，或考慮使用更descriptive的變數名...',\n            'general': '很好的問題！在Python中，這個概念可以這樣理解...'\n        }\n        \n        # 簡單關鍵字匹配\n        if 'explain' in question.lower() or '解釋' in question:\n            return ai_responses['explain']\n        elif 'debug' in question.lower() or '錯誤' in question or 'error' in question.lower():\n            return ai_responses['debug']\n        elif 'improve' in question.lower() or '改進' in question or '優化' in question:\n            return ai_responses['improve']\n        else:\n            return ai_responses['general']\n\n# 使用示例\nif __name__ == \"__main__\":\n    print(\"🚀 啟動Python協作學習系統\")\n    \n    # 創建學習會話\n    learning_session = CollaborativeLearning('PY001', '學習者')\n    \n    # 模擬編程活動\n    sample_code_v1 = \"print('Hello, Collaborative Learning!')\"\n    learning_session.log_code_change(sample_code_v1, 'create')\n    \n    sample_code_v2 = sample_code_v1 + \"\\nprint('This is version 2')\"\n    learning_session.log_code_change(sample_code_v2, 'edit')\n    \n    # 獲取進度報告\n    progress = learning_session.get_learning_progress()\n    print(\"\\n📊 學習進度報告:\")\n    print(json.dumps(progress, indent=2, ensure_ascii=False))\n    \n    # AI助教示例\n    print(\"\\n🤖 AI助教互動:\")\n    ai_response = learning_session.simulate_ai_assistance(\"請解釋這段程式碼\")\n    print(f\"AI助教: {ai_response}\")\n    \n    print(\"\\n✨ 協作功能:\")\n    print(\"- 即時代碼同步 (HTTP輪詢, 2-5秒延遲)\")\n    print(\"- 多用戶游標顯示\")\n    print(\"- 聊天討論功能\")\n    print(\"- 學習統計追蹤\")\n    print(\"- AI智能助教\")\n    print(\"- 代碼執行記錄\")\n    \n    # 💡 進階功能提示:\n    # 1. 點擊\"解釋程式碼\"讓AI助教詳細說明\n    # 2. 點擊\"檢查錯誤\"進行程式碼檢查\n    # 3. 點擊\"改進建議\"獲得優化建議\n    # 4. 使用聊天功能與同伴討論\n    # 5. 查看詳細的學習統計報告\n    # 6. 所有數據自動保存到MySQL數據庫！";
    
    $codeStmt = $pdo->prepare("
        INSERT INTO room_code_snapshots (room_id, version, code_content, created_by_user_id, created_by_user_name, change_type) 
        VALUES (1, 1, ?, 'admin1', '系統管理員', 'create')
    ");
    $codeStmt->execute([$initialCode]);
    echo "✅ 進階初始代碼快照創建成功\n";
    
    // 插入示例聊天消息
    $chatMessages = [
        [1, 'teacher1', '張老師', '歡迎大家來到Python協作學習！今天我們要學習物件導向程式設計。', 'text'],
        [1, 'student1', '小明', '老師好！我對類別和物件的概念還不太清楚。', 'text'],
        [1, 'teacher1', '張老師', '沒問題！我們從基礎開始，先看看這個CollaborativeLearning類別...', 'text'],
        [1, 'student2', '小華', '這個__init__方法是做什麼用的？', 'text'],
        [1, 'teacher1', '張老師', '很好的問題！__init__是建構函式，用來初始化物件的屬性。', 'text']
    ];
    
    $chatStmt = $pdo->prepare("
        INSERT INTO chat_messages (room_id, user_id, user_name, message_content, message_type) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($chatMessages as $msg) {
        $chatStmt->execute($msg);
    }
    echo "✅ 5條示例聊天消息創建成功\n";
    
    echo "\n🎉 完整數據庫初始化完成！\n\n";
    
    // 統計信息
    $tables = ['users', 'rooms', 'room_participants', 'room_code_snapshots', 'chat_messages', 'code_executions', 'learning_statistics', 'ai_assistance'];
    echo "📋 數據表統計:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "   📊 $table: $count 條記錄\n";
    }
    
    echo "\n📋 系統信息:\n";
    echo "   - 數據庫名稱: $database\n";
    echo "   - 字符集: $charset\n";
    echo "   - 引擎: InnoDB\n";
    echo "   - 功能特色: 多人協作、游標同步、AI助教、學習統計\n\n";
    
    echo "👥 測試帳號:\n";
    echo "   管理員: admin1 / admin123\n";
    echo "   教師: teacher1 / teacher123\n";
    echo "   學生: student1 / student123\n";
    echo "   學生: student2 / student123\n";
    echo "   學生: student3 / student123\n\n";
    
    echo "🏠 測試房間:\n";
    echo "   PY001 - Python基礎學習\n";
    echo "   PY002 - 演算法練習\n";
    echo "   PY003 - 專案開發\n";
    echo "   AI001 - AI程式設計\n";
    echo "   WEB001 - Web開發實戰\n\n";
    
    echo "✨ 進階功能:\n";
    echo "   ✓ 即時游標同步\n";
    echo "   ✓ 用戶顏色識別\n";
    echo "   ✓ 代碼執行記錄\n";
    echo "   ✓ 學習統計分析\n";
    echo "   ✓ AI智能助教\n";
    echo "   ✓ 多房間管理\n";
    echo "   ✓ 進階聊天功能\n\n";
    
    echo "✅ 協作平台已準備就緒，可以開始使用！\n";
    
} catch (PDOException $e) {
    echo "❌ 數據庫錯誤: " . $e->getMessage() . "\n";
    echo "🔧 請檢查:\n";
    echo "   1. XAMPP MySQL 服務是否啟動\n";
    echo "   2. 數據庫連接參數是否正確\n";
    echo "   3. 用戶權限是否足夠\n";
} catch (Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}
?>
        </pre>
        
        <div class="stats">
            <div class="stat-card">
                <h3>📊 數據表數量</h3>
                <p><strong>8 個</strong>功能表</p>
            </div>
            <div class="stat-card">
                <h3>👥 測試用戶</h3>
                <p><strong>5 個</strong>角色帳號</p>
            </div>
            <div class="stat-card">
                <h3>🏠 測試房間</h3>
                <p><strong>5 個</strong>學習房間</p>
            </div>
            <div class="stat-card">
                <h3>✨ 進階功能</h3>
                <p><strong>全面</strong>支援</p>
            </div>
        </div>
        
        <div class="table-info">
            <h3>🗃️ 數據表結構說明:</h3>
            <ul>
                <li><strong>users</strong> - 增強用戶管理 (頭像顏色、統計數據)</li>
                <li><strong>rooms</strong> - 進階房間設置 (主題、語言、設定)</li>
                <li><strong>room_participants</strong> - 參與者管理 (游標同步、編輯統計)</li>
                <li><strong>room_code_snapshots</strong> - 代碼版本控制 (差異追蹤、執行結果)</li>
                <li><strong>chat_messages</strong> - 聊天系統 (回覆、附件、編輯)</li>
                <li><strong>code_executions</strong> - 代碼執行記錄 (輸出、錯誤、性能)</li>
                <li><strong>learning_statistics</strong> - 學習統計分析 (進度、時間、概念)</li>
                <li><strong>ai_assistance</strong> - AI助教記錄 (問答、評價、性能)</li>
            </ul>
        </div>
        
        <p>
            <a href="index.html" class="btn">🚀 返回協作平台</a>
            <a href="code_sync_handler.php?action=status" class="btn">📊 查看系統狀態</a>
            <a href="network_test.html" class="btn">🔗 網路測試</a>
        </p>
    </div>
</body>
</html> 