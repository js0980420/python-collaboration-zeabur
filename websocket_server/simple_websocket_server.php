<?php
/**
 * 🚀 Python協作教學平台 - 簡化版WebSocket服務器
 * 技術棧：純PHP + ReactPHP + XAMPP MySQL
 * 預算：NT$5000 經濟實用版
 */

// 檢查是否有Ratchet依賴，如果沒有就使用ReactPHP
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $useRatchet = class_exists('Ratchet\Server\IoServer');
} else {
    $useRatchet = false;
}

if ($useRatchet) {
    // 使用Ratchet版本
    echo "🚀 使用Ratchet WebSocket服務器\n";
    require_once 'websocket_server.php';
} else {
    // 使用純PHP實現
    echo "🚀 使用純PHP WebSocket服務器\n";
    
    class SimpleCollaborationServer {
        private $socket;
        private $clients = [];
        private $rooms = [];
        private $userCounter = 0;
        private $db;
        private $openai_api_key;
        
        public function __construct() {
            $this->openai_api_key = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? 'YOUR_API_KEY_HERE';
            $this->initDatabase();
            $this->initWebSocketServer();
        }
        
        private function initDatabase() {
            try {
                // 使用XAMPP默認設置
                $this->db = new PDO('mysql:host=localhost;dbname=python_collaborate;charset=utf8mb4', 'root', '');
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "✅ 資料庫連接成功\n";
                $this->createTables();
            } catch (PDOException $e) {
                echo "❌ 資料庫連接失敗: " . $e->getMessage() . "\n";
                echo "💡 請確保XAMPP MySQL服務已啟動\n";
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
            echo "✅ 資料表初始化完成\n";
        }
        
        private function initWebSocketServer() {
            // 創建WebSocket服務器
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
            socket_bind($this->socket, '0.0.0.0', 8080);
            socket_listen($this->socket, 5);
            
            socket_set_nonblock($this->socket);
            
            echo "🚀 WebSocket服務器啟動成功\n";
            echo "📡 監聽地址: ws://localhost:8080\n";
            echo "🌐 測試頁面: http://localhost/PythonLearn%20Web/collaboration_test.html\n";
            echo "⚡ 按Ctrl+C停止服務器\n\n";
        }
        
        public function run() {
            while (true) {
                $read = array_merge([$this->socket], array_column($this->clients, 'socket'));
                $write = [];
                $except = [];
                
                if (socket_select($read, $write, $except, 0, 200000) > 0) {
                    // 處理新連接
                    if (in_array($this->socket, $read)) {
                        $this->handleNewConnection();
                        $key = array_search($this->socket, $read);
                        unset($read[$key]);
                    }
                    
                    // 處理客戶端消息
                    foreach ($read as $socket) {
                        $this->handleClientMessage($socket);
                    }
                }
                
                // 清理斷開的連接
                $this->cleanupConnections();
                
                usleep(10000); // 10ms延遲
            }
        }
        
        private function handleNewConnection() {
            $client = socket_accept($this->socket);
            if ($client === false) return;
            
            socket_set_nonblock($client);
            
            $this->userCounter++;
            $userId = "user_" . $this->userCounter;
            
            $this->clients[$userId] = [
                'socket' => $client,
                'id' => $userId,
                'name' => "用戶{$this->userCounter}",
                'room_id' => null,
                'handshake' => false,
                'last_activity' => time()
            ];
            
            echo "👤 新用戶連接: {$userId}\n";
        }
        
        private function handleClientMessage($socket) {
            $userId = $this->getUserIdBySocket($socket);
            if (!$userId || !isset($this->clients[$userId])) return;
            
            $client = &$this->clients[$userId];
            $data = @socket_read($socket, 2048);
            
            if ($data === false || $data === '') {
                $this->removeClient($userId);
                return;
            }
            
            if (!$client['handshake']) {
                $this->performHandshake($userId, $data);
                return;
            }
            
            $message = $this->decodeWebSocketFrame($data);
            if ($message) {
                $this->processMessage($userId, $message);
            }
        }
        
        private function performHandshake($userId, $data) {
            $lines = explode("\n", $data);
            $headers = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $headers[trim($key)] = trim($value);
                }
            }
            
            if (isset($headers['Sec-WebSocket-Key'])) {
                $key = $headers['Sec-WebSocket-Key'];
                $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
                
                $response = "HTTP/1.1 101 Switching Protocols\r\n";
                $response .= "Upgrade: websocket\r\n";
                $response .= "Connection: Upgrade\r\n";
                $response .= "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";
                
                socket_write($this->clients[$userId]['socket'], $response, strlen($response));
                $this->clients[$userId]['handshake'] = true;
                
                // 發送歡迎消息
                $this->sendToUser($userId, [
                    'type' => 'welcome',
                    'userId' => $userId,
                    'userName' => $this->clients[$userId]['name']
                ]);
                
                echo "🤝 用戶 {$userId} 握手完成\n";
            }
        }
        
        private function processMessage($userId, $message) {
            try {
                $data = json_decode($message, true);
                if (!$data) return;
                
                switch ($data['type']) {
                    case 'join_room':
                        $this->handleJoinRoom($userId, $data['roomId'], $data['userName'] ?? null);
                        break;
                    case 'code_change':
                        $this->handleCodeChange($userId, $data);
                        break;
                    case 'chat_message':
                        $this->handleChatMessage($userId, $data);
                        break;
                    case 'ping':
                        $this->sendToUser($userId, ['type' => 'pong']);
                        break;
                }
            } catch (Exception $e) {
                echo "消息處理錯誤: " . $e->getMessage() . "\n";
            }
        }
        
        private function handleJoinRoom($userId, $roomId, $userName = null) {
            if (!isset($this->clients[$userId])) return;
            
            $client = &$this->clients[$userId];
            
            if ($userName) {
                $client['name'] = $userName;
            }
            $client['room_id'] = $roomId;
            
            // 創建或獲取房間
            if (!isset($this->rooms[$roomId])) {
                $this->rooms[$roomId] = [
                    'id' => $roomId,
                    'users' => [],
                    'code' => "# 歡迎來到Python協作教學平台！\n# 開始編寫您的Python程式碼\n\ndef hello_world():\n    print(\"Hello, World!\")\n    return \"協作愉快！\"",
                    'version' => 0
                ];
                
                // 保存到資料庫
                $stmt = $this->db->prepare("INSERT INTO rooms (room_id, code, version) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP");
                $stmt->execute([$roomId, $this->rooms[$roomId]['code'], 0]);
            }
            
            $this->rooms[$roomId]['users'][] = $userId;
            
            // 發送房間信息
            $this->sendToUser($userId, [
                'type' => 'room_joined',
                'roomId' => $roomId,
                'code' => $this->rooms[$roomId]['code'],
                'version' => $this->rooms[$roomId]['version'],
                'users' => $this->getRoomUsers($roomId)
            ]);
            
            // 通知其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_joined',
                'userId' => $userId,
                'userName' => $client['name']
            ], $userId);
            
            echo "🏠 用戶 {$userId} 加入房間 {$roomId}\n";
        }
        
        private function handleCodeChange($userId, $data) {
            if (!isset($this->clients[$userId])) return;
            
            $client = $this->clients[$userId];
            $roomId = $client['room_id'];
            
            if (!$roomId || !isset($this->rooms[$roomId])) return;
            
            $newVersion = $data['version'] ?? ($this->rooms[$roomId]['version'] + 1);
            
            // 檢查版本衝突
            if ($newVersion <= $this->rooms[$roomId]['version']) {
                // 發送衝突處理
                $this->sendToUser($userId, [
                    'type' => 'conflict_detected',
                    'serverCode' => $this->rooms[$roomId]['code'],
                    'serverVersion' => $this->rooms[$roomId]['version'],
                    'clientCode' => $data['code']
                ]);
                return;
            }
            
            // 更新代碼
            $this->rooms[$roomId]['code'] = $data['code'];
            $this->rooms[$roomId]['version'] = $newVersion;
            
            // 保存到資料庫
            $stmt = $this->db->prepare("UPDATE rooms SET code = ?, version = ? WHERE room_id = ?");
            $stmt->execute([$data['code'], $newVersion, $roomId]);
            
            $stmt = $this->db->prepare("INSERT INTO code_changes (room_id, user_id, code, version) VALUES (?, ?, ?, ?)");
            $stmt->execute([$roomId, $userId, $data['code'], $newVersion]);
            
            // 廣播給其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'code_updated',
                'code' => $data['code'],
                'version' => $newVersion,
                'userId' => $userId
            ], $userId);
        }
        
        private function handleChatMessage($userId, $data) {
            if (!isset($this->clients[$userId])) return;
            
            $client = $this->clients[$userId];
            $roomId = $client['room_id'];
            
            if (!$roomId) return;
            
            // 保存聊天記錄
            $stmt = $this->db->prepare("INSERT INTO chat_messages (room_id, user_id, user_name, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$roomId, $userId, $client['name'], $data['message']]);
            
            // 廣播聊天消息
            $this->broadcastToRoom($roomId, [
                'type' => 'chat_message',
                'userId' => $userId,
                'userName' => $client['name'],
                'message' => $data['message'],
                'timestamp' => date('H:i:s')
            ]);
        }
        
        private function getRoomUsers($roomId) {
            if (!isset($this->rooms[$roomId])) return [];
            
            $users = [];
            foreach ($this->rooms[$roomId]['users'] as $userId) {
                if (isset($this->clients[$userId])) {
                    $users[] = [
                        'id' => $userId,
                        'name' => $this->clients[$userId]['name']
                    ];
                }
            }
            return $users;
        }
        
        private function sendToUser($userId, $message) {
            if (!isset($this->clients[$userId])) return;
            
            $data = $this->encodeWebSocketFrame(json_encode($message));
            @socket_write($this->clients[$userId]['socket'], $data, strlen($data));
        }
        
        private function broadcastToRoom($roomId, $message, $excludeUserId = null) {
            if (!isset($this->rooms[$roomId])) return;
            
            foreach ($this->rooms[$roomId]['users'] as $userId) {
                if ($userId !== $excludeUserId) {
                    $this->sendToUser($userId, $message);
                }
            }
        }
        
        private function encodeWebSocketFrame($message) {
            $length = strlen($message);
            
            if ($length <= 125) {
                return chr(129) . chr($length) . $message;
            } elseif ($length <= 65535) {
                return chr(129) . chr(126) . pack('n', $length) . $message;
            } else {
                return chr(129) . chr(127) . pack('J', $length) . $message;
            }
        }
        
        private function decodeWebSocketFrame($data) {
            if (strlen($data) < 2) return false;
            
            $firstByte = ord($data[0]);
            $secondByte = ord($data[1]);
            
            $opcode = $firstByte & 15;
            $masked = ($secondByte >> 7) & 1;
            $payloadLength = $secondByte & 127;
            
            if ($opcode !== 1) return false; // 只處理文本幀
            
            $offset = 2;
            
            if ($payloadLength === 126) {
                $payloadLength = unpack('n', substr($data, $offset, 2))[1];
                $offset += 2;
            } elseif ($payloadLength === 127) {
                $payloadLength = unpack('J', substr($data, $offset, 8))[1];
                $offset += 8;
            }
            
            if ($masked) {
                $mask = substr($data, $offset, 4);
                $offset += 4;
                $payload = substr($data, $offset, $payloadLength);
                
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[$i] = $payload[$i] ^ $mask[$i % 4];
                }
            } else {
                $payload = substr($data, $offset, $payloadLength);
            }
            
            return $payload;
        }
        
        private function getUserIdBySocket($socket) {
            foreach ($this->clients as $userId => $client) {
                if ($client['socket'] === $socket) {
                    return $userId;
                }
            }
            return null;
        }
        
        private function removeClient($userId) {
            if (!isset($this->clients[$userId])) return;
            
            $client = $this->clients[$userId];
            
            // 從房間移除
            if ($client['room_id'] && isset($this->rooms[$client['room_id']])) {
                $key = array_search($userId, $this->rooms[$client['room_id']]['users']);
                if ($key !== false) {
                    unset($this->rooms[$client['room_id']]['users'][$key]);
                }
                
                // 通知其他用戶
                $this->broadcastToRoom($client['room_id'], [
                    'type' => 'user_left',
                    'userId' => $userId,
                    'userName' => $client['name']
                ]);
            }
            
            @socket_close($client['socket']);
            unset($this->clients[$userId]);
            
            echo "👋 用戶 {$userId} 已斷開連接\n";
        }
        
        private function cleanupConnections() {
            foreach ($this->clients as $userId => $client) {
                $error = socket_last_error($client['socket']);
                if ($error !== 0) {
                    $this->removeClient($userId);
                }
            }
        }
    }
    
    // 啟動服務器
    $server = new SimpleCollaborationServer();
    $server->run();
} 