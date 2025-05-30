<?php
require_once __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * 🚀 PHP多人協作WebSocket服務器
 * 技術棧：PHP + Ratchet + MySQL + OpenAI API
 */
class CollaborationServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $users;
    protected $userCounter;
    protected $db;
    protected $openai_api_key;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->users = [];
        $this->userCounter = 0;
        
        // 初始化資料庫連接
        $this->initDatabase();
        
        // 設置OpenAI API密鑰
        $this->openai_api_key = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? 'YOUR_API_KEY_HERE';
        
        echo "🚀 PHP協作服務器初始化完成\n";
    }

    private function initDatabase() {
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=python_collaborate', 'root', '');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 創建必要的表
            $this->createTables();
            echo "✅ 資料庫連接成功\n";
        } catch (PDOException $e) {
            echo "❌ 資料庫連接失敗: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            room_id VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(50) UNIQUE NOT NULL,
            code TEXT,
            version INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(50) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS code_changes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(50) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            code TEXT NOT NULL,
            version INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ";
        
        $this->db->exec($sql);
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->userCounter++;
        $userId = "user_" . $this->userCounter;
        
        // 儲存連接信息
        $this->clients->attach($conn);
        $this->users[$userId] = [
            'id' => $userId,
            'conn' => $conn,
            'room_id' => null,
            'name' => "用戶{$this->userCounter}",
            'cursor' => ['line' => 0, 'ch' => 0],
            'last_activity' => time()
        ];
        
        $conn->userId = $userId;
        
        // 發送歡迎消息
        $this->sendToUser($userId, [
            'type' => 'welcome',
            'userId' => $userId,
            'userName' => $this->users[$userId]['name']
        ]);
        
        echo "👤 新用戶連接: {$userId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $userId = $from->userId;
        if (!isset($this->users[$userId])) return;
        
        try {
            $message = json_decode($msg, true);
            $this->users[$userId]['last_activity'] = time();
            
            switch ($message['type']) {
                case 'join_room':
                    $this->handleJoinRoom($userId, $message['roomId'], $message['userName'] ?? null);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($userId);
                    break;
                    
                case 'code_change':
                    $this->handleCodeChange($userId, $message);
                    break;
                    
                case 'cursor_change':
                    $this->handleCursorChange($userId, $message);
                    break;
                    
                case 'chat_message':
                    $this->handleChatMessage($userId, $message);
                    break;
                    
                case 'ai_request':
                    $this->handleAIRequest($userId, $message);
                    break;
                    
                case 'ping':
                    $this->sendToUser($userId, ['type' => 'pong']);
                    break;
                    
                default:
                    echo "未知消息類型: {$message['type']}\n";
            }
        } catch (Exception $e) {
            echo "消息處理錯誤: " . $e->getMessage() . "\n";
        }
    }

    private function handleJoinRoom($userId, $roomId, $userName = null) {
        $user = &$this->users[$userId];
        
        // 離開當前房間
        if ($user['room_id']) {
            $this->handleLeaveRoom($userId);
        }
        
        // 更新用戶信息
        if ($userName) {
            $user['name'] = $userName;
        }
        $user['room_id'] = $roomId;
        
        // 創建或獲取房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'id' => $roomId,
                'users' => [],
                'code' => "# 歡迎來到協作編程！\n# 開始編寫您的Python程式碼\n\ndef hello_world():\n    print(\"Hello, World!\")\n    return \"協作愉快！\"",
                'version' => 0,
                'created_at' => time(),
                'last_activity' => time()
            ];
            
            // 保存到資料庫
            $stmt = $this->db->prepare("INSERT INTO rooms (room_id, code, version) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP");
            $stmt->execute([$roomId, $this->rooms[$roomId]['code'], 0]);
            
            echo "🏠 創建新房間: {$roomId}\n";
        }
        
        $this->rooms[$roomId]['users'][] = $userId;
        $this->rooms[$roomId]['last_activity'] = time();
        
        // 更新資料庫用戶信息
        $stmt = $this->db->prepare("INSERT INTO users (user_id, name, room_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = ?, room_id = ?, last_activity = CURRENT_TIMESTAMP");
        $stmt->execute([$userId, $user['name'], $roomId, $user['name'], $roomId]);
        
        // 發送房間信息給新用戶
        $this->sendToUser($userId, [
            'type' => 'room_joined',
            'roomId' => $roomId,
            'code' => $this->rooms[$roomId]['code'],
            'version' => $this->rooms[$roomId]['version'],
            'users' => $this->getRoomUsers($roomId)
        ]);
        
        // 通知房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'userId' => $userId,
            'userName' => $user['name'],
            'users' => $this->getRoomUsers($roomId)
        ], $userId);
        
        echo "👥 用戶 {$userId} 加入房間 {$roomId}\n";
    }

    private function handleLeaveRoom($userId) {
        $user = &$this->users[$userId];
        if (!$user['room_id']) return;
        
        $roomId = $user['room_id'];
        
        // 從房間移除用戶
        if (isset($this->rooms[$roomId])) {
            $this->rooms[$roomId]['users'] = array_filter(
                $this->rooms[$roomId]['users'], 
                function($id) use ($userId) { return $id !== $userId; }
            );
            
            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'userId' => $userId,
                'userName' => $user['name'],
                'users' => $this->getRoomUsers($roomId)
            ], $userId);
            
            // 如果房間空了，清理房間
            if (empty($this->rooms[$roomId]['users'])) {
                unset($this->rooms[$roomId]);
                echo "🗑️ 清理空房間: {$roomId}\n";
            }
        }
        
        $user['room_id'] = null;
        
        // 更新資料庫
        $stmt = $this->db->prepare("UPDATE users SET room_id = NULL WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        echo "👋 用戶 {$userId} 離開房間 {$roomId}\n";
    }

    private function handleCodeChange($userId, $message) {
        $user = $this->users[$userId];
        if (!$user['room_id']) return;
        
        $roomId = $user['room_id'];
        $newCode = $message['code'];
        $clientVersion = $message['version'] ?? 0;
        
        if (!isset($this->rooms[$roomId])) return;
        
        $room = &$this->rooms[$roomId];
        
        // 簡單的衝突檢測：版本號檢查
        if ($clientVersion < $room['version']) {
            // 發生衝突，通知用戶
            $this->sendToUser($userId, [
                'type' => 'conflict_detected',
                'currentVersion' => $room['version'],
                'currentCode' => $room['code'],
                'message' => '⚠️ 檢測到衝突！其他用戶已修改程式碼，請選擇處理方式：'
            ]);
            
            echo "⚠️ 衝突檢測: 用戶 {$userId} 版本 {$clientVersion} vs 房間版本 {$room['version']}\n";
            return;
        }
        
        // 更新房間代碼和版本
        $room['code'] = $newCode;
        $room['version']++;
        $room['last_activity'] = time();
        
        // 保存到資料庫
        $stmt = $this->db->prepare("UPDATE rooms SET code = ?, version = ?, last_activity = CURRENT_TIMESTAMP WHERE room_id = ?");
        $stmt->execute([$newCode, $room['version'], $roomId]);
        
        // 記錄代碼變更歷史
        $stmt = $this->db->prepare("INSERT INTO code_changes (room_id, user_id, code, version) VALUES (?, ?, ?, ?)");
        $stmt->execute([$roomId, $userId, $newCode, $room['version']]);
        
        // 廣播給房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_updated',
            'code' => $newCode,
            'version' => $room['version'],
            'userId' => $userId,
            'userName' => $user['name'],
            'timestamp' => time()
        ], $userId);
        
        echo "📝 代碼更新: 房間 {$roomId} 版本 {$room['version']} by {$userId}\n";
    }

    private function handleCursorChange($userId, $message) {
        $user = &$this->users[$userId];
        if (!$user['room_id']) return;
        
        $user['cursor'] = $message['cursor'];
        
        // 廣播光標位置
        $this->broadcastToRoom($user['room_id'], [
            'type' => 'cursor_updated',
            'userId' => $userId,
            'userName' => $user['name'],
            'cursor' => $user['cursor']
        ], $userId);
    }

    private function handleChatMessage($userId, $message) {
        $user = $this->users[$userId];
        if (!$user['room_id']) return;
        
        $roomId = $user['room_id'];
        $chatMessage = $message['message'];
        
        // 保存聊天消息到資料庫
        $stmt = $this->db->prepare("INSERT INTO chat_messages (room_id, user_id, user_name, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$roomId, $userId, $user['name'], $chatMessage]);
        
        // 廣播聊天消息
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'userId' => $userId,
            'userName' => $user['name'],
            'message' => $chatMessage,
            'timestamp' => time()
        ]);
        
        echo "💬 聊天消息: [{$roomId}] {$user['name']}: {$chatMessage}\n";
    }

    private function handleAIRequest($userId, $message) {
        $user = $this->users[$userId];
        if (!$user['room_id']) return;
        
        $action = $message['action'];
        $data = $message['data'] ?? [];
        
        // 異步處理AI請求
        $this->processAIRequest($userId, $action, $data);
    }

    private function processAIRequest($userId, $action, $data) {
        try {
            $user = $this->users[$userId];
            $roomId = $user['room_id'];
            
            switch ($action) {
                case 'explain_code':
                    $prompt = "請用繁體中文解釋這段Python程式碼的功能和邏輯：\n\n" . ($data['code'] ?? '');
                    break;
                    
                case 'find_bugs':
                    $prompt = "請用繁體中文檢查這段Python程式碼是否有錯誤或可以改進的地方：\n\n" . ($data['code'] ?? '');
                    break;
                    
                case 'suggest_improvement':
                    $prompt = "請用繁體中文為這段Python程式碼提供改進建議：\n\n" . ($data['code'] ?? '');
                    break;
                    
                case 'help_collaboration':
                    $prompt = "在多人協作編程中，當遇到以下情況時，請用繁體中文提供建議：" . ($data['situation'] ?? '');
                    break;
                    
                default:
                    $prompt = $data['prompt'] ?? '請提供Python編程幫助';
            }
            
            $aiResponse = $this->callOpenAI($prompt);
            
            // 發送AI回應給用戶
            $this->sendToUser($userId, [
                'type' => 'ai_response',
                'action' => $action,
                'response' => $aiResponse,
                'timestamp' => time()
            ]);
            
            // 也廣播給房間其他用戶（可選）
            $this->broadcastToRoom($roomId, [
                'type' => 'ai_shared_response',
                'userId' => $userId,
                'userName' => $user['name'],
                'action' => $action,
                'response' => $aiResponse,
                'timestamp' => time()
            ], $userId);
            
            echo "🤖 AI回應: 用戶 {$userId} 動作 {$action}\n";
            
        } catch (Exception $e) {
            $this->sendToUser($userId, [
                'type' => 'ai_error',
                'message' => 'AI助教暫時無法回應，請稍後再試。',
                'error' => $e->getMessage()
            ]);
            
            echo "❌ AI處理錯誤: " . $e->getMessage() . "\n";
        }
    }

    private function callOpenAI($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是一個專業的Python程式設計助教，專門幫助學生學習Python並提供協作編程指導。請用繁體中文回答，並且保持友善、鼓勵的語調。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("OpenAI API錯誤: HTTP {$httpCode}");
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception("OpenAI回應格式錯誤");
        }
        
        return $result['choices'][0]['message']['content'];
    }

    private function getRoomUsers($roomId) {
        if (!isset($this->rooms[$roomId])) return [];
        
        $users = [];
        foreach ($this->rooms[$roomId]['users'] as $userId) {
            if (isset($this->users[$userId])) {
                $user = $this->users[$userId];
                $users[] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'cursor' => $user['cursor']
                ];
            }
        }
        return $users;
    }

    private function sendToUser($userId, $message) {
        if (!isset($this->users[$userId])) return;
        
        $conn = $this->users[$userId]['conn'];
        if ($conn->isWritable) {
            $conn->send(json_encode($message));
        }
    }

    private function broadcastToRoom($roomId, $message, $excludeUserId = null) {
        if (!isset($this->rooms[$roomId])) return;
        
        foreach ($this->rooms[$roomId]['users'] as $userId) {
            if ($userId !== $excludeUserId && isset($this->users[$userId])) {
                $this->sendToUser($userId, $message);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $userId = $conn->userId ?? null;
        if ($userId && isset($this->users[$userId])) {
            $this->handleLeaveRoom($userId);
            unset($this->users[$userId]);
            echo "👋 用戶斷線: {$userId}\n";
        }
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "❌ WebSocket錯誤: {$e->getMessage()}\n";
        $conn->close();
    }

    public function getStats() {
        return [
            'total_users' => count($this->users),
            'active_rooms' => count($this->rooms),
            'total_connections' => count($this->clients)
        ];
    }
}

// 啟動服務器
echo "🚀 啟動PHP WebSocket協作服務器...\n";
echo "📡 端口: 8080\n";
echo "🌐 測試地址: ws://localhost:8080\n\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new CollaborationServer()
        )
    ),
    8080
);

$server->run(); 