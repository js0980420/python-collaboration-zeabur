<?php
/**
 * Python協作教學平台 - WebSocket即時服務器 (Ratchet版)
 * 實現<0.5秒延遲的實時協作編程
 */

require_once __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * Python協作教學 WebSocket 處理器
 */
class PythonCollaborationHandler implements MessageComponentInterface {
    
    /** @var \SplObjectStorage 所有連接的客戶端 */
    protected $clients;
    
    /** @var array 房間管理 - 房間ID => [連接對象...] */
    protected $rooms;
    
    /** @var array 用戶信息 - 連接ID => 用戶資料 */
    protected $users;
    
    /** @var array 代碼狀態 - 房間ID => 代碼內容 */
    protected $codeStates;
    
    /** @var array 版本管理 - 房間ID => 版本號 */
    protected $versions;
    
    /** @var \PDO 數據庫連接 */
    protected $pdo;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->users = [];
        $this->codeStates = [];
        $this->versions = [];
        
        // 連接數據庫
        $this->connectDatabase();
        
        $this->log("🚀 WebSocket服務器初始化完成");
    }
    
    /**
     * 連接數據庫
     */
    protected function connectDatabase() {
        try {
            // Zeabur環境變量 或 本地XAMPP設置
            $host = $_ENV['DB_HOST'] ?? getenv('MYSQL_HOST') ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? getenv('MYSQL_PORT') ?? '3306';
            $dbname = $_ENV['DB_NAME'] ?? getenv('MYSQL_DATABASE') ?? 'python_collaboration';
            $username = $_ENV['DB_USER'] ?? getenv('MYSQL_USERNAME') ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '';
            
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log("✅ 數據庫連接成功 ({$host}:{$port}/{$dbname})");
        } catch (PDOException $e) {
            $this->log("❌ 數據庫連接失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 新連接建立
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->user_id = null;
        $conn->room_id = null;
        $conn->last_ping = time();
        
        $this->log("🔗 新連接建立: {$conn->resourceId}");
        
        // 發送歡迎消息
        $this->sendToConnection($conn, [
            'type' => 'system',
            'action' => 'welcome',
            'message' => '歡迎使用Python協作教學平台 - WebSocket即時版！',
            'connection_id' => $conn->resourceId,
            'server_time' => microtime(true) * 1000
        ]);
    }
    
    /**
     * 收到消息
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $startTime = microtime(true);
        
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                throw new Exception("無效的JSON消息");
            }
            
            $action = $data['action'] ?? '';
            $this->log("📨 收到消息: {$action} from {$from->resourceId}");
            
            switch ($action) {
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($from, $data);
                    break;
                    
                case 'code_change':
                    $this->handleCodeChange($from, $data);
                    break;
                    
                case 'cursor_change':
                    $this->handleCursorChange($from, $data);
                    break;
                    
                case 'chat_message':
                    $this->handleChatMessage($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from, $data);
                    break;
                    
                case 'get_room_status':
                    $this->handleGetRoomStatus($from, $data);
                    break;
                    
                default:
                    $this->sendError($from, "不支援的操作: {$action}");
            }
            
            // 計算處理延遲
            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->log("⏱️ 處理 {$action} 耗時: {$processingTime}ms");
            
        } catch (Exception $e) {
            $this->log("❌ 處理消息錯誤: " . $e->getMessage());
            $this->sendError($from, "處理消息錯誤: " . $e->getMessage());
        }
    }
    
    /**
     * 處理加入房間
     */
    protected function handleJoinRoom(ConnectionInterface $conn, $data) {
        try {
            $roomCode = $data['room'] ?? 'default';
            $userId = $data['user_id'] ?? 'anonymous_' . bin2hex(random_bytes(4));
            $userName = $data['user_name'] ?? '匿名用戶';
            
            $this->log("👤 處理加入房間請求: 用戶={$userName}, 房間={$roomCode}");
            
            // 離開當前房間
            if ($conn->room_id) {
                $this->leaveRoom($conn);
            }
            
            // 加入新房間
            $conn->room_id = $roomCode;
            $conn->user_id = $userId;
            $conn->user_name = $userName;
            
            if (!isset($this->rooms[$roomCode])) {
                $this->rooms[$roomCode] = [];
                $this->loadRoomCode($roomCode);
            }
            
            $this->rooms[$roomCode][] = $conn;
            $this->users[$conn->resourceId] = [
                'user_id' => $userId,
                'user_name' => $userName,
                'room' => $roomCode,
                'join_time' => time()
            ];
            
            // 發送房間狀態
            $roomState = [
                'type' => 'room_state',
                'action' => 'joined',
                'room' => $roomCode,
                'code' => $this->codeStates[$roomCode] ?? $this->getInitialCode(),
                'version' => $this->versions[$roomCode] ?? 1,
                'users' => $this->getRoomUsers($roomCode),
                'timestamp' => microtime(true) * 1000
            ];
            
            $this->log("📤 發送房間狀態給用戶 {$userName}");
            $this->sendToConnection($conn, $roomState);
            
            // 通知房間其他用戶
            $userJoinedMessage = [
                'type' => 'user_joined',
                'user_id' => $userId,
                'user_name' => $userName,
                'users' => $this->getRoomUsers($roomCode),
                'timestamp' => microtime(true) * 1000
            ];
            
            $this->log("📢 廣播用戶加入消息到房間 {$roomCode}");
            $this->broadcastToRoom($roomCode, $userJoinedMessage, $conn);
            
            // 異步記錄到數據庫（不阻塞響應）
            try {
                $this->saveUserActivity($roomCode, $userId, $userName, 'joined');
            } catch (Exception $dbError) {
                $this->log("⚠️ 數據庫操作警告: " . $dbError->getMessage());
                // 不阻塞，繼續處理
            }
            
            $this->log("✅ 用戶 {$userName}({$userId}) 成功加入房間 {$roomCode}");
            
        } catch (Exception $e) {
            $this->log("❌ 處理加入房間錯誤: " . $e->getMessage());
            $this->sendError($conn, "加入房間失敗: " . $e->getMessage());
            // 不要關閉連接，只發送錯誤消息
        }
    }
    
    /**
     * 處理代碼變更
     */
    protected function handleCodeChange(ConnectionInterface $conn, $data) {
        $roomCode = $conn->room_id;
        if (!$roomCode) {
            $this->sendError($conn, "請先加入房間");
            return;
        }
        
        $newCode = $data['code'] ?? '';
        $clientVersion = $data['version'] ?? 0;
        
        // 更新房間代碼狀態
        $this->codeStates[$roomCode] = $newCode;
        $this->versions[$roomCode] = ($this->versions[$roomCode] ?? 0) + 1;
        $newVersion = $this->versions[$roomCode];
        
        // 立即廣播給房間所有其他用戶
        $this->broadcastToRoom($roomCode, [
            'type' => 'code_change',
            'user_id' => $conn->user_id,
            'user_name' => $conn->user_name,
            'code' => $newCode,
            'version' => $newVersion,
            'client_version' => $clientVersion,
            'timestamp' => microtime(true) * 1000
        ], $conn);
        
        // 異步保存到數據庫（不阻塞實時響應）
        $this->saveCodeSnapshot($roomCode, $newCode, $newVersion, $conn->user_id, $conn->user_name);
        
        $this->log("📝 代碼更新 - 房間:{$roomCode}, 版本:{$newVersion}, 用戶:{$conn->user_name}");
    }
    
    /**
     * 處理游標變更
     */
    protected function handleCursorChange(ConnectionInterface $conn, $data) {
        $roomCode = $conn->room_id;
        if (!$roomCode) return;
        
        $cursorData = $data['cursor'] ?? null;
        
        // 立即廣播游標位置
        $this->broadcastToRoom($roomCode, [
            'type' => 'cursor_change',
            'user_id' => $conn->user_id,
            'user_name' => $conn->user_name,
            'cursor' => $cursorData,
            'timestamp' => microtime(true) * 1000
        ], $conn);
    }
    
    /**
     * 處理聊天消息
     */
    protected function handleChatMessage(ConnectionInterface $conn, $data) {
        $roomCode = $conn->room_id;
        if (!$roomCode) {
            $this->sendError($conn, "請先加入房間");
            return;
        }
        
        $message = $data['message'] ?? '';
        if (empty($message)) return;
        
        // 廣播聊天消息
        $this->broadcastToRoom($roomCode, [
            'type' => 'chat_message',
            'user_id' => $conn->user_id,
            'user_name' => $conn->user_name,
            'message' => $message,
            'timestamp' => microtime(true) * 1000
        ], null); // 包含發送者
        
        // 保存聊天記錄
        $this->saveChatMessage($roomCode, $conn->user_id, $conn->user_name, $message);
        
        $this->log("💬 聊天消息 - {$conn->user_name}: {$message}");
    }
    
    /**
     * 處理心跳檢測
     */
    protected function handlePing(ConnectionInterface $conn, $data) {
        $conn->last_ping = time();
        
        $this->sendToConnection($conn, [
            'type' => 'pong',
            'client_timestamp' => $data['timestamp'] ?? 0,
            'server_timestamp' => microtime(true) * 1000
        ]);
    }
    
    /**
     * 處理獲取房間狀態
     */
    protected function handleGetRoomStatus(ConnectionInterface $conn, $data) {
        $roomCode = $data['room'] ?? $conn->room_id ?? 'default';
        
        $this->sendToConnection($conn, [
            'type' => 'room_status',
            'room' => $roomCode,
            'code' => $this->codeStates[$roomCode] ?? $this->getInitialCode(),
            'version' => $this->versions[$roomCode] ?? 1,
            'users' => $this->getRoomUsers($roomCode),
            'timestamp' => microtime(true) * 1000
        ]);
    }
    
    /**
     * 連接關閉
     */
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if ($conn->room_id && $conn->user_id) {
            // 通知房間其他用戶
            $this->broadcastToRoom($conn->room_id, [
                'type' => 'user_left',
                'user_id' => $conn->user_id,
                'user_name' => $conn->user_name,
                'users' => $this->getRoomUsers($conn->room_id, $conn),
                'timestamp' => microtime(true) * 1000
            ], $conn);
            
            $this->saveUserActivity($conn->room_id, $conn->user_id, $conn->user_name, 'left');
        }
        
        $this->leaveRoom($conn);
        unset($this->users[$conn->resourceId]);
        
        $this->log("🔌 連接關閉: {$conn->resourceId}");
    }
    
    /**
     * 錯誤處理
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("❌ 連接錯誤: " . $e->getMessage());
        $conn->close();
    }
    
    // === 輔助方法 ===
    
    /**
     * 離開房間
     */
    protected function leaveRoom(ConnectionInterface $conn) {
        if (!$conn->room_id) return;
        
        $roomCode = $conn->room_id;
        if (isset($this->rooms[$roomCode])) {
            $this->rooms[$roomCode] = array_filter($this->rooms[$roomCode], function($c) use ($conn) {
                return $c !== $conn;
            });
            
            // 如果房間空了，清理房間狀態
            if (empty($this->rooms[$roomCode])) {
                unset($this->rooms[$roomCode]);
                // 注意：保留代碼狀態以備後續用戶加入
            }
        }
    }
    
    /**
     * 廣播消息到房間
     */
    protected function broadcastToRoom($roomCode, $message, $excludeConn = null) {
        if (!isset($this->rooms[$roomCode])) return;
        
        $messageJson = json_encode($message, JSON_UNESCAPED_UNICODE);
        
        foreach ($this->rooms[$roomCode] as $conn) {
            if ($excludeConn && $conn === $excludeConn) continue;
            
            try {
                $conn->send($messageJson);
            } catch (Exception $e) {
                $this->log("廣播錯誤: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 發送消息到特定連接
     */
    protected function sendToConnection(ConnectionInterface $conn, $message) {
        try {
            $conn->send(json_encode($message, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            $this->log("發送消息錯誤: " . $e->getMessage());
        }
    }
    
    /**
     * 發送錯誤消息
     */
    protected function sendError(ConnectionInterface $conn, $error) {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'message' => $error,
            'timestamp' => microtime(true) * 1000
        ]);
    }
    
    /**
     * 獲取房間用戶列表
     */
    protected function getRoomUsers($roomCode, $excludeConn = null) {
        if (!isset($this->rooms[$roomCode])) return [];
        
        $users = [];
        foreach ($this->rooms[$roomCode] as $conn) {
            if ($excludeConn && $conn === $excludeConn) continue;
            
            $users[] = [
                'user_id' => $conn->user_id,
                'user_name' => $conn->user_name,
                'connection_id' => $conn->resourceId
            ];
        }
        return $users;
    }
    
    /**
     * 獲取初始代碼
     */
    protected function getInitialCode() {
        return "# 🚀 Python協作教學平台 - WebSocket即時版\n# 實時協作，延遲<0.5秒！\n\ndef realtime_fibonacci(n):\n    '''\n    實時協作斐波那契數列生成器\n    現在支持真正的即時同步！\n    '''\n    if n <= 0:\n        return []\n    elif n == 1:\n        return [0]\n    elif n == 2:\n        return [0, 1]\n    \n    # 使用WebSocket實現毫秒級同步\n    sequence = [0, 1]\n    for i in range(2, n):\n        next_num = sequence[i-1] + sequence[i-2]\n        sequence.append(next_num)\n        \n    return sequence\n\ndef analyze_performance():\n    '''分析實時協作性能'''\n    import time\n    \n    start_time = time.time()\n    \n    # 測試實時同步速度\n    fib_numbers = realtime_fibonacci(20)\n    \n    end_time = time.time()\n    execution_time = (end_time - start_time) * 1000\n    \n    print(f\"🔥 WebSocket版本性能分析:\")\n    print(f\"📊 計算時間: {execution_time:.2f} 毫秒\")\n    print(f\"📈 數列長度: {len(fib_numbers)}\")\n    print(f\"⚡ 同步延遲: <0.5秒 (WebSocket)\")\n    \n    return {\n        'execution_time_ms': execution_time,\n        'sequence_length': len(fib_numbers),\n        'sync_delay': '<0.5s',\n        'technology': 'WebSocket + Ratchet'\n    }\n\n# 主程式 - WebSocket版本\nif __name__ == \"__main__\":\n    print(\"🚀 WebSocket協作編程示例啟動！\")\n    \n    # 生成斐波那契數列\n    numbers = realtime_fibonacci(15)\n    print(f\"前15個斐波那契數: {numbers}\")\n    \n    # 性能分析\n    performance = analyze_performance()\n    print(\"\\n✨ 即時協作就是這麼快！\")\n    \n    # 💡 WebSocket版本特色：\n    # 1. 真正的即時同步（<0.5秒延遲）\n    # 2. 雙向通信，無需輪詢\n    # 3. 支持大量並發用戶\n    # 4. 自動斷線重連\n    # 5. 完整的用戶狀態管理\n    \n    print(\"\\n🎯 開始協作編程吧！\")";
    }
    
    // === 數據庫操作 ===
    
    /**
     * 加載房間代碼
     */
    protected function loadRoomCode($roomCode) {
        if (!$this->pdo) return;
        
        try {
            // 獲取房間ID
            $stmt = $this->pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmt->execute([$roomCode]);
            $room = $stmt->fetch();
            
            if (!$room) {
                // 創建新房間
                $stmt = $this->pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
                $stmt->execute([$roomCode, $roomCode, "WebSocket房間: {$roomCode}"]);
                $roomId = $this->pdo->lastInsertId();
            } else {
                $roomId = $room['id'];
            }
            
            // 加載最新代碼
            $stmt = $this->pdo->prepare("SELECT code_content, version FROM room_code_snapshots WHERE room_id = ? ORDER BY version DESC LIMIT 1");
            $stmt->execute([$roomId]);
            $snapshot = $stmt->fetch();
            
            if ($snapshot) {
                $this->codeStates[$roomCode] = $snapshot['code_content'];
                $this->versions[$roomCode] = intval($snapshot['version']);
            } else {
                $this->codeStates[$roomCode] = $this->getInitialCode();
                $this->versions[$roomCode] = 1;
            }
            
        } catch (Exception $e) {
            $this->log("加載房間代碼錯誤: " . $e->getMessage());
        }
    }
    
    /**
     * 保存代碼快照
     */
    protected function saveCodeSnapshot($roomCode, $code, $version, $userId, $userName) {
        if (!$this->pdo) return;
        
        try {
            // 獲取或創建房間
            $stmt = $this->pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmt->execute([$roomCode]);
            $room = $stmt->fetch();
            
            if (!$room) {
                $stmt = $this->pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
                $stmt->execute([$roomCode, $roomCode, "WebSocket房間: {$roomCode}"]);
                $roomId = $this->pdo->lastInsertId();
            } else {
                $roomId = $room['id'];
            }
            
            // 保存代碼快照
            $stmt = $this->pdo->prepare("INSERT INTO room_code_snapshots (room_id, code_content, version, created_by_user_id, created_by_user_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$roomId, $code, $version, $userId, $userName]);
            
        } catch (Exception $e) {
            $this->log("保存代碼快照錯誤: " . $e->getMessage());
        }
    }
    
    /**
     * 保存用戶活動
     */
    protected function saveUserActivity($roomCode, $userId, $userName, $action) {
        if (!$this->pdo) return;
        
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmt->execute([$roomCode]);
            $room = $stmt->fetch();
            
            if ($room) {
                $roomId = $room['id'];
                
                if ($action === 'joined') {
                    $stmt = $this->pdo->prepare("INSERT INTO room_participants (room_id, user_id, user_name, last_active) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), last_active = NOW()");
                    $stmt->execute([$roomId, $userId, $userName]);
                } elseif ($action === 'left') {
                    $stmt = $this->pdo->prepare("DELETE FROM room_participants WHERE room_id = ? AND user_id = ?");
                    $stmt->execute([$roomId, $userId]);
                }
            }
            
        } catch (Exception $e) {
            $this->log("保存用戶活動錯誤: " . $e->getMessage());
        }
    }
    
    /**
     * 保存聊天消息
     */
    protected function saveChatMessage($roomCode, $userId, $userName, $message) {
        if (!$this->pdo) return;
        
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmt->execute([$roomCode]);
            $room = $stmt->fetch();
            
            if ($room) {
                // 這裡可以加入聊天記錄表的保存邏輯
                $this->log("聊天記錄保存: {$roomCode} - {$userName}: {$message}");
            }
            
        } catch (Exception $e) {
            $this->log("保存聊天消息錯誤: " . $e->getMessage());
        }
    }
    
    /**
     * 記錄日誌
     */
    protected function log($message) {
        $timestamp = date('Y-m-d H:i:s.u');
        $logFile = __DIR__ . '/websocket_debug.log';
        error_log("[$timestamp] WS_SERVER: $message\n", 3, $logFile);
        echo "[$timestamp] $message\n";
    }
}

// 啟動WebSocket服務器
$port = 8080;
$host = '0.0.0.0';

$handler = new PythonCollaborationHandler();

$server = IoServer::factory(
    new HttpServer(
        new WsServer($handler)
    ),
    $port,
    $host
);

echo "\n";
echo "🚀 Python協作教學平台 - WebSocket服務器啟動\n";
echo "===============================================\n";
echo "📡 監聽地址: ws://{$host}:{$port}\n";
echo "⚡ 目標延遲: <0.5秒\n";
echo "🛠️ 技術棧: Ratchet + ReactPHP\n";
echo "💾 數據庫: MySQL (python_collaboration)\n";
echo "===============================================\n";
echo "按 Ctrl+C 停止服務器\n\n";

$server->run(); 