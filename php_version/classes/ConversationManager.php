<?php
require_once 'config/database.php';

class ConversationManager {
    private $db;
    private $maxHistoryLength = 20; // 保留最近20條消息
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * 保存對話消息
     */
    public function saveMessage($userId, $sessionId, $messageType, $message) {
        $stmt = $this->db->prepare("
            INSERT INTO ai_conversations (user_id, session_id, message_type, message) 
            VALUES (:user_id, :session_id, :message_type, :message)
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->bindParam(':message_type', $messageType);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }
    
    /**
     * 獲取用戶會話歷史
     */
    public function getConversationHistory($userId, $sessionId = null) {
        if ($sessionId) {
            $stmt = $this->db->prepare("
                SELECT message_type, message, created_at 
                FROM ai_conversations 
                WHERE user_id = :user_id AND session_id = :session_id 
                ORDER BY created_at ASC 
                LIMIT :limit
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':session_id', $sessionId);
        } else {
            $stmt = $this->db->prepare("
                SELECT message_type, message, created_at 
                FROM ai_conversations 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit
            ");
            $stmt->bindParam(':user_id', $userId);
        }
        
        $stmt->bindParam(':limit', $this->maxHistoryLength, PDO::PARAM_INT);
        $stmt->execute();
        
        $history = $stmt->fetchAll();
        
        // 如果沒有sessionId，則反轉順序（最新的在前）
        if (!$sessionId) {
            $history = array_reverse($history);
        }
        
        return $history;
    }
    
    /**
     * 構建對話消息數組（用於AI API）
     */
    public function buildMessagesForAI($userId, $sessionId, $newUserMessage) {
        $messages = [];
        
        // 添加系統消息
        $messages[] = [
            'role' => 'system',
            'content' => '你是一個專業的Python程式設計助教。請用繁體中文回答，提供清楚的解釋和實用的程式碼範例。保持友善和耐心的教學態度。記住學生的學習進度，提供個人化指導。'
        ];
        
        // 獲取歷史對話
        $history = $this->getConversationHistory($userId, $sessionId);
        
        // 添加歷史消息到消息數組
        foreach ($history as $item) {
            if ($item['message_type'] !== 'system') {
                $messages[] = [
                    'role' => $item['message_type'],
                    'content' => $item['message']
                ];
            }
        }
        
        // 添加新的用戶消息
        $messages[] = [
            'role' => 'user',
            'content' => $newUserMessage
        ];
        
        // 限制消息數量（保留系統消息 + 最近的對話）
        if (count($messages) > $this->maxHistoryLength + 1) {
            $systemMessage = array_shift($messages); // 保存系統消息
            $messages = array_slice($messages, -$this->maxHistoryLength); // 保留最近的消息
            array_unshift($messages, $systemMessage); // 重新添加系統消息
        }
        
        return $messages;
    }
    
    /**
     * 處理完整的AI對話流程
     */
    public function handleConversation($userId, $userMessage, $sessionId = null) {
        try {
            // 如果沒有提供sessionId，生成一個
            if (!$sessionId) {
                $sessionId = $this->generateSessionId($userId);
            }
            
            // 構建AI消息數組
            $messages = $this->buildMessagesForAI($userId, $sessionId, $userMessage);
            
            // 調用XAI API
            $xaiService = new XAIService();
            $response = $xaiService->chat($messages);
            
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new Exception('無效的AI回應格式');
            }
            
            $aiResponse = $response['choices'][0]['message']['content'];
            
            // 保存用戶消息
            $this->saveMessage($userId, $sessionId, 'user', $userMessage);
            
            // 保存AI回應
            $this->saveMessage($userId, $sessionId, 'assistant', $aiResponse);
            
            return [
                'success' => true,
                'response' => $aiResponse,
                'session_id' => $sessionId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '對話處理失敗：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 生成會話ID
     */
    private function generateSessionId($userId) {
        return $userId . '_' . uniqid() . '_' . time();
    }
    
    /**
     * 清理舊的對話記錄（可定期執行）
     */
    public function cleanOldConversations($daysToKeep = 30) {
        $stmt = $this->db->prepare("
            DELETE FROM ai_conversations 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->bindParam(':days', $daysToKeep, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * 獲取用戶會話統計
     */
    public function getConversationStats($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_messages,
                COUNT(DISTINCT session_id) as total_sessions,
                MAX(created_at) as last_conversation
            FROM ai_conversations 
            WHERE user_id = :user_id AND message_type = 'user'
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch();
    }
}
?> 