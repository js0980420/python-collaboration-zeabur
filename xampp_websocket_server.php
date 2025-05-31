<?php
/**
 * 🏠 XAMPP本地版 - Python協作教學平台 WebSocket服務器
 * 
 * 專為XAMPP環境優化的WebSocket服務器
 * - 使用本地MySQL數據庫 (localhost:3306, root用戶無密碼)
 * - 監聽本地端口8080
 * - 支援多人實時協作編程
 * - 整合AI助教功能
 */

require_once __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class XamppCollaborationServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $codeStates;
    protected $versions;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->codeStates = [];
        $this->versions = [];
        
        echo "🏠 XAMPP本地版 WebSocket服務器啟動中...\n";
        echo "📋 環境檢查:\n";
        echo "   PHP版本: " . phpversion() . "\n";
        echo "   時間: " . date('Y-m-d H:i:s') . "\n";
        
        // 檢查必要的PHP擴展
        $required_extensions = ['pdo', 'pdo_mysql', 'json', 'sockets'];
        foreach ($required_extensions as $ext) {
            $status = extension_loaded($ext) ? "✅" : "❌";
            echo "   擴展 {$ext}: {$status}\n";
        }
        
        $this->initDatabase();
    }

    private function initDatabase() {
        try {
            // XAMPP標準配置
            $host = 'localhost';
            $port = '3306';
            $dbname = 'python_collaboration';
            $username = 'root';
            $password = '';  // XAMPP默認無密碼
            
            echo "🔍 XAMPP MySQL連接參數:\n";
            echo "   主機: {$host}:{$port}\n";
            echo "   數據庫: {$dbname}\n";
            echo "   用戶: {$username}\n";
            echo "   密碼: " . (empty($password) ? '(無密碼)' : '***') . "\n";
            
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 測試連接
            $stmt = $this->pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            if ($result && $result['test'] == 1) {
                echo "✅ XAMPP MySQL連接成功\n";
                
                // 檢查必要的表
                $tables = ['rooms', 'room_code_snapshots', 'room_participants'];
                foreach ($tables as $table) {
                    $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    if ($stmt->fetch()) {
                        echo "📋 表 {$table}: ✅\n";
                    } else {
                        echo "📋 表 {$table}: ❌ (需要運行初始化腳本)\n";
                    }
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ XAMPP MySQL連接失敗: " . $e->getMessage() . "\n";
            echo "🔍 請檢查:\n";
            echo "   1. XAMPP MySQL服務是否啟動\n";
            echo "   2. 數據庫 'python_collaboration' 是否存在\n";
            echo "   3. 是否運行了初始化腳本\n";
            exit(1);
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->user_id = null;
        $conn->user_name = null;
        $conn->room_code = null;
        
        echo "🔗 新連接: {$conn->resourceId} (總連接數: " . count($this->clients) . ")\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                throw new Exception("無效的JSON消息");
            }

            $type = $data['type'] ?? '';
            echo "📨 收到消息: {$type} 來自 {$from->resourceId}\n";

            switch ($type) {
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
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
                case 'ai_request':
                    $this->handleAIRequest($from, $data);
                    break;
                default:
                    echo "⚠️ 未知消息類型: {$type}\n";
            }
        } catch (Exception $e) {
            echo "❌ 處理消息錯誤: " . $e->getMessage() . "\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    protected function handleJoinRoom(ConnectionInterface $conn, $data) {
        $roomCode = $data['room'] ?? 'default';
        $userId = $data['userId'] ?? 'user_' . $conn->resourceId;
        $userName = $data['userName'] ?? '用戶' . $conn->resourceId;

        // 設置連接屬性
        $conn->user_id = $userId;
        $conn->user_name = $userName;
        $conn->room_code = $roomCode;

        // 添加到房間
        if (!isset($this->rooms[$roomCode])) {
            $this->rooms[$roomCode] = new \SplObjectStorage;
        }
        $this->rooms[$roomCode]->attach($conn);

        echo "👤 用戶 {$userName} 加入房間 {$roomCode}\n";

        // 發送當前代碼狀態
        if (isset($this->codeStates[$roomCode])) {
            $conn->send(json_encode([
                'type' => 'code_sync',
                'data' => [
                    'code' => $this->codeStates[$roomCode],
                    'version' => $this->versions[$roomCode] ?? 1
                ]
            ]));
        }

        // 廣播用戶加入事件
        $this->broadcastToRoom($roomCode, [
            'type' => 'user_joined',
            'userId' => $userId,
            'userName' => $userName,
            'timestamp' => microtime(true) * 1000
        ], $conn);

        // 發送房間用戶列表
        $this->sendRoomUsers($roomCode);
    }

    protected function handleCodeChange(ConnectionInterface $conn, $data) {
        $roomCode = $conn->room_code;
        if (!$roomCode) return;

        $codeContent = $data['data']['code'] ?? '';
        $version = $this->versions[$roomCode] ?? 0;
        $newVersion = $version + 1;

        // 更新代碼狀態
        $this->codeStates[$roomCode] = $codeContent;
        $this->versions[$roomCode] = $newVersion;

        echo "📝 代碼更新: 房間 {$roomCode}, 版本 {$newVersion}, 長度 " . strlen($codeContent) . "\n";

        // 保存到數據庫
        $this->saveCodeSnapshot($roomCode, $codeContent, $newVersion, $conn->user_id, $conn->user_name);

        // 廣播給房間其他用戶
        $this->broadcastToRoom($roomCode, [
            'type' => 'code_change',
            'userId' => $conn->user_id,
            'userName' => $conn->user_name,
            'data' => [
                'code' => $codeContent,
                'version' => $newVersion
            ],
            'timestamp' => microtime(true) * 1000
        ], $conn);
    }

    protected function handleChatMessage(ConnectionInterface $conn, $data) {
        $roomCode = $conn->room_code;
        if (!$roomCode) return;

        $message = $data['data']['message'] ?? '';
        if (empty($message)) return;

        echo "💬 聊天消息: {$conn->user_name} 在 {$roomCode}: {$message}\n";

        // 廣播聊天消息
        $this->broadcastToRoom($roomCode, [
            'type' => 'chat_message',
            'userId' => $conn->user_id,
            'userName' => $conn->user_name,
            'data' => [
                'message' => $message,
                'timestamp' => microtime(true) * 1000
            ],
            'timestamp' => microtime(true) * 1000
        ]);
    }

    protected function saveCodeSnapshot($roomCode, $code, $version, $userId, $userName) {
        try {
            if (!$this->pdo) return;

            // 獲取或創建房間ID
            $stmt = $this->pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmt->execute([$roomCode]);
            $room = $stmt->fetch();

            if (!$room) {
                $stmt = $this->pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
                $stmt->execute([$roomCode, $roomCode, "XAMPP本地房間: {$roomCode}"]);
                $roomId = $this->pdo->lastInsertId();
            } else {
                $roomId = $room['id'];
            }

            // 保存代碼快照
            $stmt = $this->pdo->prepare("
                INSERT INTO room_code_snapshots 
                (room_id, code_content, version, created_by_user_id, created_by_user_name, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$roomId, $code, $version, $userId, $userName]);

        } catch (PDOException $e) {
            echo "❌ 保存代碼快照失敗: " . $e->getMessage() . "\n";
        }
    }

    protected function broadcastToRoom($roomCode, $message, $exclude = null) {
        if (!isset($this->rooms[$roomCode])) return;

        $messageJson = json_encode($message);
        foreach ($this->rooms[$roomCode] as $client) {
            if ($client !== $exclude) {
                $client->send($messageJson);
            }
        }
    }

    protected function sendRoomUsers($roomCode) {
        if (!isset($this->rooms[$roomCode])) return;

        $users = [];
        foreach ($this->rooms[$roomCode] as $client) {
            $users[] = [
                'userId' => $client->user_id,
                'userName' => $client->user_name
            ];
        }

        $this->broadcastToRoom($roomCode, [
            'type' => 'room_users',
            'data' => $users,
            'timestamp' => microtime(true) * 1000
        ]);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if ($conn->room_code && isset($this->rooms[$conn->room_code])) {
            $this->rooms[$conn->room_code]->detach($conn);
            
            // 廣播用戶離開事件
            $this->broadcastToRoom($conn->room_code, [
                'type' => 'user_left',
                'userId' => $conn->user_id,
                'userName' => $conn->user_name,
                'timestamp' => microtime(true) * 1000
            ]);

            // 更新房間用戶列表
            $this->sendRoomUsers($conn->room_code);
        }

        echo "🔌 連接關閉: {$conn->resourceId} ({$conn->user_name})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "❌ 連接錯誤: " . $e->getMessage() . "\n";
        $conn->close();
    }
}

// 啟動XAMPP WebSocket服務器
$port = 8080;
$host = '127.0.0.1';  // 僅本地訪問

echo "🚀 啟動XAMPP WebSocket服務器...\n";
echo "📍 監聽地址: {$host}:{$port}\n";
echo "🌐 WebSocket URL: ws://{$host}:{$port}\n";
echo "⏰ 啟動時間: " . date('Y-m-d H:i:s') . "\n";
echo "📝 日誌級別: 詳細模式\n";
echo "🔄 按 Ctrl+C 停止服務器\n";
echo str_repeat("=", 50) . "\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new XamppCollaborationServer()
        )
    ),
    $port,
    $host
);

$server->run();
?> 