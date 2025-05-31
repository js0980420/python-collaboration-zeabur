#!/usr/local/bin/php
<?php
/**
 * Python協作教學平台 - WebSocket服務器 (Zeabur雲端版)
 * 
 * 支援Zeabur雲端部署和XAMPP本地部署
 * - 自動檢測環境並配置相應的端口和數據庫
 * - Zeabur環境：使用環境變量配置
 * - XAMPP環境：使用本地MySQL配置
 */

require_once __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class CollaborationServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $codeStates;
    protected $versions;
    protected $pdo;
    protected $isZeaburEnv;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->codeStates = [];
        $this->versions = [];
        
        // 檢測運行環境
        $this->isZeaburEnv = !empty(getenv('ZEABUR')) || !empty(getenv('DB_HOST'));
        
        echo "🚀 Python協作教學平台 WebSocket服務器啟動中...\n";
        echo "🌍 運行環境: " . ($this->isZeaburEnv ? "Zeabur雲端" : "XAMPP本地") . "\n";
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
            if ($this->isZeaburEnv) {
                // Zeabur雲端環境配置
                $host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
                $port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3306';
                $dbname = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'python_collaboration';
                $username = getenv('DB_USER') ?: getenv('MYSQL_USERNAME') ?: 'root';
                $password = getenv('DB_PASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
                
                echo "☁️ Zeabur雲端MySQL連接參數:\n";
            } else {
                // XAMPP本地環境配置
                $host = 'localhost';
                $port = '3306';
                $dbname = 'python_collaboration';
                $username = 'root';
                $password = '';
                
                echo "🏠 XAMPP本地MySQL連接參數:\n";
            }
            
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
                echo "✅ MySQL連接成功\n";
                
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
            echo "❌ MySQL連接失敗: " . $e->getMessage() . "\n";
            echo "🔍 請檢查:\n";
            if ($this->isZeaburEnv) {
                echo "   1. Zeabur MySQL服務是否正常\n";
                echo "   2. 環境變量是否正確設置\n";
                echo "   3. 數據庫是否已初始化\n";
            } else {
                echo "   1. XAMPP MySQL服務是否啟動\n";
                echo "   2. 數據庫 'python_collaboration' 是否存在\n";
                echo "   3. 是否運行了初始化腳本\n";
            }
            // 在Zeabur環境中不要退出，讓容器繼續運行
            if (!$this->isZeaburEnv) {
                exit(1);
            }
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
        } else {
            // 發送初始代碼
            $initialCode = $this->getInitialCode();
            $this->codeStates[$roomCode] = $initialCode;
            $this->versions[$roomCode] = 1;
            
            $conn->send(json_encode([
                'type' => 'code_sync',
                'data' => [
                    'code' => $initialCode,
                    'version' => 1
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

    protected function handleCursorChange(ConnectionInterface $conn, $data) {
        $roomCode = $conn->room_code;
        if (!$roomCode) return;

        // 廣播游標變更給房間其他用戶
        $this->broadcastToRoom($roomCode, [
            'type' => 'cursor_change',
            'userId' => $conn->user_id,
            'userName' => $conn->user_name,
            'data' => $data['data'],
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

    protected function handleAIRequest(ConnectionInterface $conn, $data) {
        // AI請求處理 - 可以在這裡整合AI服務
        echo "🤖 AI請求: {$conn->user_name} 在 {$conn->room_code}\n";
        
        // 廣播AI請求給房間所有用戶
        $this->broadcastToRoom($conn->room_code, [
            'type' => 'ai_response',
            'userId' => $conn->user_id,
            'userName' => $conn->user_name,
            'data' => [
                'request' => $data['data'] ?? [],
                'response' => '🤖 AI助教功能開發中...'
            ],
            'timestamp' => microtime(true) * 1000
        ]);
    }

    protected function getInitialCode() {
        $env = $this->isZeaburEnv ? "Zeabur雲端" : "XAMPP本地";
        return "# 🚀 Python協作教學平台 - {$env}版\n# 歡迎使用WebSocket實時協作環境！\n\ndef fibonacci_sequence(n):\n    '''\n    生成斐波那契數列\n    \n    參數:\n        n (int): 要生成的數列長度\n    \n    返回:\n        list: 斐波那契數列\n    '''\n    if n <= 0:\n        return []\n    elif n == 1:\n        return [0]\n    elif n == 2:\n        return [0, 1]\n    \n    sequence = [0, 1]\n    for i in range(2, n):\n        next_num = sequence[i-1] + sequence[i-2]\n        sequence.append(next_num)\n    \n    return sequence\n\ndef analyze_sequence(sequence):\n    '''分析數列的特性'''\n    if not sequence:\n        return \"數列為空\"\n    \n    total = sum(sequence)\n    average = total / len(sequence)\n    max_num = max(sequence)\n    \n    print(f\"數列長度: {len(sequence)}\")\n    print(f\"總和: {total}\")\n    print(f\"平均值: {average:.2f}\")\n    print(f\"最大值: {max_num}\")\n    \n    return {\n        'length': len(sequence),\n        'sum': total,\n        'average': average,\n        'max': max_num\n    }\n\n# 主程式 - {$env}版\nif __name__ == \"__main__\":\n    print(\"🚀 {$env}協作編程示例：斐波那契數列分析\")\n    \n    # 生成前15個斐波那契數\n    fib_sequence = fibonacci_sequence(15)\n    print(f\"前15個斐波那契數: {fib_sequence}\")\n    \n    # 分析數列特性\n    analysis = analyze_sequence(fib_sequence)\n    print(\"\\n📊 數列分析完成！\")\n    \n    # 💡 試試看：\n    # 1. 點擊\\\"解釋程式碼\\\"讓AI助教說明這個程式\n    # 2. 點擊\\\"檢查錯誤\\\"讓AI檢查程式是否有問題  \n    # 3. 點擊\\\"改進建議\\\"獲得程式碼優化建議\n    # 4. 在聊天區域與同伴討論程式碼\n    # 5. 代碼會自動保存到MySQL數據庫！";
    }

    protected function saveCodeSnapshot($roomCode, $code, $version, $userId, $userName) {
        try {
            if (!$this->pdo) return;

            // 獲取或創建房間ID
            $stmt = $this->pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmt->execute([$roomCode]);
            $room = $stmt->fetch();

            if (!$room) {
                $env = $this->isZeaburEnv ? "Zeabur雲端" : "XAMPP本地";
                $stmt = $this->pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
                $stmt->execute([$roomCode, $roomCode, "{$env}房間: {$roomCode}"]);
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

// 啟動WebSocket服務器
error_log("---------- [WebSocketService] Attempting to start Ratchet server on 0.0.0.0:8080 ----------");

try {
    $isZeaburEnv = !empty(getenv('ZEABUR')) || !empty(getenv('DB_HOST'));
    $port = 8080; // WebSocket服務器在容器內部始終監聽8080
    $host = '0.0.0.0'; // 在容器中監聽所有接口

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new CollaborationServer() // 確保 CollaborationServer 類已正確加載
            )
        ),
        $port,
        $host
    );

    error_log("---------- [WebSocketService] Ratchet server successfully listening on {$host}:{$port} ----------");
    $server->run();
    error_log("---------- [WebSocketService] Ratchet server has stopped. ----------");

} catch (\Throwable $e) { // 使用Throwable捕捉所有類型的錯誤和異常
    error_log("---------- [WebSocketService] CRITICAL STARTUP ERROR: " . $e->getMessage() . " ----------");
    error_log("---------- [WebSocketService] Trace: " . $e->getTraceAsString() . " ----------");
    // 可以在這裡決定是否需要exit(1)來讓Supervisor知道啟動失敗
}
?> 