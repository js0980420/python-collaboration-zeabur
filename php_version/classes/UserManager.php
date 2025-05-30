<?php
require_once 'config/database.php';

class UserManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * 創建或獲取用戶
     */
    public function getOrCreateUser($userId, $email = null, $name = null) {
        // 先檢查用戶是否存在
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if (!$user) {
            // 創建新用戶
            $stmt = $this->db->prepare("
                INSERT INTO users (user_id, email, name) 
                VALUES (:user_id, :email, :name)
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            
            // 返回新創建的用戶
            return [
                'user_id' => $userId,
                'email' => $email,
                'name' => $name,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $user;
    }
    
    /**
     * 更新用戶學習進度
     */
    public function updateProgress($userId, $lessonId, $progressPercent, $completed = false) {
        $stmt = $this->db->prepare("
            INSERT INTO user_progress (user_id, lesson_id, progress_percent, completed_at) 
            VALUES (:user_id, :lesson_id, :progress_percent, :completed_at)
            ON DUPLICATE KEY UPDATE 
                progress_percent = VALUES(progress_percent),
                completed_at = VALUES(completed_at),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $completedAt = $completed ? date('Y-m-d H:i:s') : null;
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':lesson_id', $lessonId);
        $stmt->bindParam(':progress_percent', $progressPercent);
        $stmt->bindParam(':completed_at', $completedAt);
        
        return $stmt->execute();
    }
    
    /**
     * 獲取用戶學習進度
     */
    public function getUserProgress($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                up.lesson_id,
                up.progress_percent,
                up.completed_at,
                l.title,
                l.difficulty
            FROM user_progress up
            JOIN lessons l ON up.lesson_id = l.lesson_id
            WHERE up.user_id = :user_id
            ORDER BY l.order_index
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * 獲取用戶學習統計
     */
    public function getUserStats($userId) {
        // 更新統計數據
        $this->updateUserStats($userId);
        
        $stmt = $this->db->prepare("SELECT * FROM learning_stats WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        if (!$stats) {
            return [
                'user_id' => $userId,
                'total_conversations' => 0,
                'total_code_submissions' => 0,
                'topics_covered' => [],
                'last_activity' => null
            ];
        }
        
        $stats['topics_covered'] = json_decode($stats['topics_covered'], true) ?: [];
        return $stats;
    }
    
    /**
     * 更新用戶統計數據
     */
    private function updateUserStats($userId) {
        // 計算對話數量
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM ai_conversations 
            WHERE user_id = :user_id AND message_type = 'user'
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $conversations = $stmt->fetch()['count'];
        
        // 計算程式碼提交數量
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM code_submissions 
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $codeSubmissions = $stmt->fetch()['count'];
        
        // 分析涵蓋的主題
        $stmt = $this->db->prepare("
            SELECT message 
            FROM ai_conversations 
            WHERE user_id = :user_id AND message_type = 'user'
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $topicsCovered = $this->analyzeTopics($messages);
        
        // 獲取最後活動時間
        $stmt = $this->db->prepare("
            SELECT MAX(created_at) as last_activity
            FROM (
                SELECT created_at FROM ai_conversations WHERE user_id = :user_id
                UNION ALL
                SELECT created_at FROM code_submissions WHERE user_id = :user_id
            ) as activities
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $lastActivity = $stmt->fetch()['last_activity'];
        
        // 更新或插入統計數據
        $stmt = $this->db->prepare("
            INSERT INTO learning_stats 
            (user_id, total_conversations, total_code_submissions, topics_covered, last_activity) 
            VALUES (:user_id, :conversations, :submissions, :topics, :last_activity)
            ON DUPLICATE KEY UPDATE 
                total_conversations = VALUES(total_conversations),
                total_code_submissions = VALUES(total_code_submissions),
                topics_covered = VALUES(topics_covered),
                last_activity = VALUES(last_activity),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':conversations', $conversations);
        $stmt->bindParam(':submissions', $codeSubmissions);
        $stmt->bindParam(':topics', json_encode($topicsCovered));
        $stmt->bindParam(':last_activity', $lastActivity);
        
        return $stmt->execute();
    }
    
    /**
     * 分析用戶涵蓋的學習主題
     */
    private function analyzeTopics($messages) {
        $topics = [];
        $topicKeywords = [
            '變數與資料型別' => ['變數', 'variable', '資料型別', 'int', 'str', 'list'],
            '函數' => ['函數', 'function', 'def', 'return'],
            '迴圈' => ['迴圈', 'loop', 'for', 'while'],
            '條件判斷' => ['條件', 'if', 'else', 'elif'],
            '錯誤處理' => ['錯誤', 'error', 'try', 'except'],
            '檔案操作' => ['檔案', 'file', 'open', 'read', 'write']
        ];
        
        foreach ($messages as $message) {
            $content = strtolower($message);
            foreach ($topicKeywords as $topicName => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($content, $keyword) !== false) {
                        $topics[] = $topicName;
                        break;
                    }
                }
            }
        }
        
        return array_unique($topics);
    }
}
?> 