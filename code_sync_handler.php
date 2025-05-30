<?php
/**
 * Python協作教學平台 - 代碼同步處理器
 * 使用 PHP + MySQL 實現代碼同步和版本管理
 */

// 設置CORS標頭
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://192.168.31.32' // 添加筆記本電腦的IP地址
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    // 如果來源不在允許列表中，可以選擇拒絕或使用默認值（例如*，或不設置）
    // 為了安全，這裡我們不設置，或者可以記錄未授權的嘗試
    // header('Access-Control-Allow-Origin: *'); // 如果仍希望允許所有，請取消註釋此行
}

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'); // 添加常用的頭部
header('Access-Control-Allow-Credentials: true'); // 如果需要處理cookies
header('Content-Type: application/json; charset=utf-8');

// 處理OPTIONS預檢請求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Zeabur 或 XAMPP MySQL 連接設定
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? getenv('MYSQL_HOST') ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? getenv('MYSQL_PORT') ?? '3306',
    'dbname' => $_ENV['DB_NAME'] ?? getenv('MYSQL_DATABASE') ?? 'python_collaboration',
    'username' => $_ENV['DB_USER'] ?? getenv('MYSQL_USERNAME') ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '',
    'charset' => 'utf8mb4'
];

/**
 * 記錄日誌
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/sync_debug.log';
    error_log("[$timestamp] CODE_SYNC: $message\n", 3, $logFile);
}

/**
 * 連接數據庫
 */
function connectDB() {
    global $db_config;
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}",
            $db_config['username'],
            $db_config['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        logMessage("數據庫連接失敗: " . $e->getMessage());
        throw new Exception("數據庫連接失敗");
    }
}

/**
 * 獲取房間ID，如果不存在則創建
 */
function getOrCreateRoomId($roomCode) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$roomCode]);
    $room = $stmt->fetch();

    if (!$room) {
        $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
        $stmt->execute([$roomCode, $roomCode, "自動創建房間: {$roomCode}"]);
        $roomId = $pdo->lastInsertId();
        logMessage("創建新房間: {$roomCode}, ID: {$roomId}");
        return $roomId;
    }
    return $room['id'];
}

/**
 * 更新或插入用戶在房間的活動狀態
 */
function updateUserActivity($roomId, $userId, $userName) {
    $pdo = connectDB();
    
    $sql = "INSERT INTO room_participants (room_id, user_id, user_name, last_active) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), last_active = NOW()";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$roomId, $userId, $userName]);
    logMessage("更新用戶活動: RoomID {$roomId}, UserID {$userId}, UserName {$userName}");
}

/**
 * 保存代碼快照
 */
function saveCodeSnapshot($roomCode, $codeContent, $userId = 1) {
    $pdo = connectDB();
    
    // 查找或創建房間
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$roomCode]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        // 創建房間
        $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
        $stmt->execute([$roomCode, $roomCode, "自動創建的協作房間"]);
        $roomId = $pdo->lastInsertId();
    } else {
        $roomId = $room['id'];
    }
    
    // 獲取當前版本號
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(version), 0) as max_version FROM room_code_snapshots WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $newVersion = $result['max_version'] + 1;
    
    // 保存代碼快照
    $stmt = $pdo->prepare("
        INSERT INTO room_code_snapshots (room_id, code_content, version, created_by, created_at) 
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        code_content = VALUES(code_content), 
        created_by = VALUES(created_by), 
        created_at = NOW()
    ");
    
    $stmt->execute([$roomId, $codeContent, $newVersion, $userId]);
    
    logMessage("代碼已保存 - 房間: $roomCode, 版本: $newVersion, 長度: " . strlen($codeContent));
    
    return [
        'room_id' => $roomId,
        'version' => $newVersion,
        'code_length' => strlen($codeContent),
        'timestamp' => time()
    ];
}

/**
 * 載入代碼快照
 */
function loadCodeSnapshot($roomCode, $version = null) {
    $pdo = connectDB();
    
    // 查找房間
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$roomCode]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        // 返回初始代碼
        return [
            'code_content' => getInitialCode(),
            'version' => 1,
            'room_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'is_default' => true
        ];
    }
    
    // 獲取指定版本或最新版本的代碼
    if ($version) {
        $sql = "SELECT * FROM room_code_snapshots WHERE room_id = ? AND version = ?";
        $params = [$room['id'], $version];
    } else {
        $sql = "SELECT * FROM room_code_snapshots WHERE room_id = ? ORDER BY version DESC LIMIT 1";
        $params = [$room['id']];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $snapshot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$snapshot) {
        // 如果沒有快照，創建初始快照
        $initialCode = getInitialCode();
        saveCodeSnapshot($roomCode, $initialCode);
        
        return [
            'code_content' => $initialCode,
            'version' => 1,
            'room_id' => $room['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'is_default' => true
        ];
    }
    
    logMessage("代碼已載入 - 房間: $roomCode, 版本: {$snapshot['version']}");
    
    return [
        'code_content' => $snapshot['code_content'],
        'version' => $snapshot['version'],
        'room_id' => $snapshot['room_id'],
        'created_at' => $snapshot['created_at'],
        'is_default' => false
    ];
}

/**
 * 記錄代碼變更
 */
function recordCodeChange($roomCode, $userId, $changeType, $position, $content, $version) {
    $pdo = connectDB();
    
    // 查找房間ID
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$roomCode]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        throw new Exception("房間不存在: $roomCode");
    }
    
    // 記錄變更
    $stmt = $pdo->prepare("
        INSERT INTO code_changes (room_id, user_id, change_type, position, content, version, timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$room['id'], $userId, $changeType, $position, $content, $version]);
    
    logMessage("代碼變更已記錄 - 房間: $roomCode, 類型: $changeType, 版本: $version");
    
    return $pdo->lastInsertId();
}

/**
 * 獲取代碼變更歷史
 */
function getCodeChangeHistory($roomCode, $limit = 50) {
    $pdo = connectDB();
    
    // 查找房間ID
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$roomCode]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        return [];
    }
    
    // 獲取變更歷史
    $stmt = $pdo->prepare("
        SELECT 
            cc.*,
            UNIX_TIMESTAMP(cc.timestamp) as unix_timestamp
        FROM code_changes cc 
        WHERE cc.room_id = ? 
        ORDER BY cc.timestamp DESC 
        LIMIT ?
    ");
    
    $stmt->execute([$room['id'], $limit]);
    $changes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("獲取變更歷史 - 房間: $roomCode, 數量: " . count($changes));
    
    return $changes;
}

/**
 * 獲取初始代碼模板
 */
function getInitialCode() {
    return `# 🐍 Python協作教學平台 - XAMPP + PHP後端版
# 歡迎使用完整的後端整合協作編程環境！

def fibonacci_sequence(n):
    """
    生成斐波那契數列
    
    參數:
        n (int): 要生成的數列長度
    
    返回:
        list: 斐波那契數列
    """
    if n <= 0:
        return []
    elif n == 1:
        return [0]
    elif n == 2:
        return [0, 1]
    
    sequence = [0, 1]
    for i in range(2, n):
        next_num = sequence[i-1] + sequence[i-2]
        sequence.append(next_num)
    
    return sequence

def analyze_sequence(sequence):
    """分析數列的特性"""
    if not sequence:
        return "數列為空"
    
    total = sum(sequence)
    average = total / len(sequence)
    max_num = max(sequence)
    
    print(f"數列長度: {len(sequence)}")
    print(f"總和: {total}")
    print(f"平均值: {average:.2f}")
    print(f"最大值: {max_num}")
    
    return {
        'length': len(sequence),
        'sum': total,
        'average': average,
        'max': max_num
    }

# 主程式
if __name__ == "__main__":
    print("🔥 XAMPP協作編程示例：斐波那契數列分析")
    
    # 生成前15個斐波那契數
    fib_sequence = fibonacci_sequence(15)
    print(f"前15個斐波那契數: {fib_sequence}")
    
    # 分析數列特性
    analysis = analyze_sequence(fib_sequence)
    print("\\n📊 數列分析完成！")
    
    # 💡 試試看：
    # 1. 點擊"解釋程式碼"讓AI助教說明這個程式
    # 2. 點擊"檢查錯誤"讓AI檢查程式是否有問題  
    # 3. 點擊"改進建議"獲得程式碼優化建議
    # 4. 在聊天區域與同伴討論程式碼
    # 5. 代碼會自動保存到MySQL數據庫！`;
}

/**
 * 處理 send_update 請求
 */
function handleSendUpdate($input) {
    $roomCode = $input['room'] ?? 'default_room';
    $userId = $input['userId'] ?? 'anonymous_'.bin2hex(random_bytes(4));
    $userName = $input['userName'] ?? '匿名用戶';
    $type = $input['type'] ?? '';
    $data = $input['data'] ?? [];

    $roomId = getOrCreateRoomId($roomCode);
    updateUserActivity($roomId, $userId, $userName);

    $pdo = connectDB();

    switch ($type) {
        case 'code_change':
            $codeContent = $data['code'] ?? '';
            $clientVersion = isset($data['version']) ? intval($data['version']) : 0;
            
            if (empty($codeContent)) {
                throw new Exception('代碼內容不能為空');
            }
            
            // 獲取服務器當前版本號
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(version), 0) as max_version FROM room_code_snapshots WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $serverVersion = intval($stmt->fetch()['max_version']);
            
            $newVersion = $serverVersion + 1;

            // 保存新的代碼快照
            $stmt = $pdo->prepare("
                INSERT INTO room_code_snapshots (room_id, code_content, version, created_by_user_id, created_by_user_name, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$roomId, $codeContent, $newVersion, $userId, $userName]);
            logMessage("SendUpdate: RoomID {$roomId}, UserID {$userId} - 保存代碼, 新版本 {$newVersion} (客戶端版本 {$clientVersion}), 長度: " . strlen($codeContent));
            
            return ['success' => true, 'message' => '代碼更新已保存', 'newVersion' => $newVersion, 'serverVersion' => $newVersion];

        case 'cursor_change':
            $cursorPos = $data['cursor'] ?? null;
            if ($cursorPos) {
                $stmt = $pdo->prepare("UPDATE room_participants SET cursor_data = ?, cursor_updated_at = NOW() WHERE room_id = ? AND user_id = ?");
                $stmt->execute([json_encode($cursorPos), $roomId, $userId]);
                logMessage("SendUpdate: RoomID {$roomId}, UserID {$userId} - 更新游標: " . json_encode($cursorPos));
            }
            return ['success' => true, 'message' => '游標更新已收到並記錄'];

        case 'user_join':
            logMessage("SendUpdate: RoomID {$roomId}, UserID {$userId}, UserName {$userName} - 用戶加入事件");
            return ['success' => true, 'message' => '用戶加入事件已記錄'];

        case 'user_leave':
            $stmt = $pdo->prepare("DELETE FROM room_participants WHERE room_id = ? AND user_id = ?");
            $stmt->execute([$roomId, $userId]);
            logMessage("SendUpdate: RoomID {$roomId}, UserID {$userId} - 用戶離開事件");
            return ['success' => true, 'message' => '用戶離開事件已記錄'];
        
        case 'chat_message':
            $message = $data['message'] ?? '';
            $timestamp = $data['timestamp'] ?? time() * 1000;
            
            if (empty($message)) {
                throw new Exception('聊天消息內容不能為空');
            }
            
            // 保存聊天消息到數據庫
            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (room_id, user_id, user_name, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$roomId, $userId, $userName, $message]);
            $chatId = $pdo->lastInsertId();
            
            logMessage("SendUpdate: RoomID {$roomId}, UserID {$userId} - 保存聊天消息, ChatID {$chatId}, 內容: {$message}");
            return ['success' => true, 'message' => '聊天消息已保存', 'chatId' => $chatId];
        
        default:
            throw new Exception("未知的更新類型: {$type}");
    }
}

/**
 * 處理 get_updates 請求
 */
function handleGetUpdates($input) {
    $roomCode = $input['room'] ?? 'default_room';
    $lastKnownVersion = isset($input['lastVersion']) ? intval($input['lastVersion']) : 0;
    $userId = $input['userId'] ?? 'anonymous_'.bin2hex(random_bytes(4));
    $userName = $input['userName'] ?? '匿名用戶';
    $lastChatId = isset($input['lastChatId']) ? intval($input['lastChatId']) : 0;

    $roomId = getOrCreateRoomId($roomCode);
    updateUserActivity($roomId, $userId, $userName);

    $pdo = connectDB();
    $updates = [];

    // 1. 獲取最新的代碼快照
    $stmt = $pdo->prepare("SELECT * FROM room_code_snapshots WHERE room_id = ? ORDER BY version DESC LIMIT 1");
    $stmt->execute([$roomId]);
    $latestSnapshot = $stmt->fetch();

    $currentVersion = 0;
    $currentCode = '';

    if ($latestSnapshot) {
        $currentVersion = intval($latestSnapshot['version']);
        $currentCode = $latestSnapshot['code_content'];
        
        if ($currentVersion > $lastKnownVersion) {
            $updates[] = [
                'type' => 'code_change',
                'userId' => $latestSnapshot['created_by_user_id'] ?? $userId, 
                'userName' => $latestSnapshot['created_by_user_name'] ?? $userName,
                'data' => [
                    'code' => $currentCode,
                    'version' => $currentVersion,
                ],
                'timestamp' => strtotime($latestSnapshot['created_at']) * 1000
            ];
            logMessage("GetUpdates: RoomID {$roomId} - 發送代碼更新, 版本 {$currentVersion} > {$lastKnownVersion}");
        }
    } else {
        // 如果沒有快照，創建一個初始的
        $initialCode = getInitialCode();
        $stmt = $pdo->prepare("
            INSERT INTO room_code_snapshots (room_id, code_content, version, created_by_user_id, created_by_user_name, created_at) 
            VALUES (?, ?, 1, ?, ?, NOW())
        ");
        $stmt->execute([$roomId, $initialCode, $userId, $userName]);
        $currentVersion = 1;
        
        $updates[] = [
            'type' => 'code_change',
            'userId' => $userId,
            'userName' => $userName,
            'data' => [
                'code' => $initialCode,
                'version' => $currentVersion,
            ],
            'timestamp' => time() * 1000
        ];
        logMessage("GetUpdates: RoomID {$roomId} - 無快照, 發送初始代碼, 版本 1");
    }
    
    // 2. 獲取活躍用戶列表
    $activeParticipants = [];
    $stmt = $pdo->prepare("SELECT user_id, user_name FROM room_participants WHERE room_id = ? AND last_active >= NOW() - INTERVAL 1 MINUTE");
    $stmt->execute([$roomId]);
    $participants = $stmt->fetchAll();
    
    foreach($participants as $p) {
        $activeParticipants[] = [
            'userId' => $p['user_id'],
            'userName' => $p['user_name']
        ];
    }
    
    if (!empty($activeParticipants)) {
        $updates[] = [
            'type' => 'active_users_list',
            'data' => $activeParticipants,
            'timestamp' => time() * 1000
        ];
    }
    logMessage("GetUpdates: RoomID {$roomId} - 發送用戶列表, 數量: " . count($participants));

    // 3. 獲取游標更新
    $stmt = $pdo->prepare("SELECT user_id, user_name, cursor_data FROM room_participants WHERE room_id = ? AND user_id != ? AND cursor_data IS NOT NULL AND cursor_updated_at >= NOW() - INTERVAL 5 SECOND");
    $stmt->execute([$roomId, $userId]);
    $cursorUpdates = $stmt->fetchAll();
    
    foreach($cursorUpdates as $cu) {
        $updates[] = [
            'type' => 'cursor_change',
            'userId' => $cu['user_id'],
            'userName' => $cu['user_name'],
            'data' => ['cursor' => json_decode($cu['cursor_data'], true)],
            'timestamp' => time() * 1000
        ];
    }
    
    if (count($cursorUpdates) > 0) {
        logMessage("GetUpdates: RoomID {$roomId} - 發送游標更新, 數量: " . count($cursorUpdates));
    }

    // 4. 獲取聊天消息更新
    $stmt = $pdo->prepare("SELECT id, user_id, user_name, message, created_at FROM chat_messages WHERE room_id = ? AND id > ? ORDER BY created_at ASC LIMIT 20");
    $stmt->execute([$roomId, $lastChatId]);
    $chatUpdates = $stmt->fetchAll();
    
    foreach($chatUpdates as $chat) {
        $updates[] = [
            'type' => 'chat_message',
            'userId' => $chat['user_id'],
            'userName' => $chat['user_name'],
            'data' => [
                'message' => $chat['message'],
                'chatId' => intval($chat['id']),
                'timestamp' => strtotime($chat['created_at']) * 1000
            ],
            'timestamp' => strtotime($chat['created_at']) * 1000
        ];
    }
    
    if (count($chatUpdates) > 0) {
        logMessage("GetUpdates: RoomID {$roomId} - 發送聊天消息更新, 數量: " . count($chatUpdates) . ", lastChatId: {$lastChatId}");
    }

    return [
        'success' => true,
        'updates' => $updates,
        'latestVersion' => $currentVersion,
        'serverTimestamp' => time() * 1000
    ];
}

// 主要處理邏輯
try {
    $inputJSON = file_get_contents('php://input');
    logMessage("收到請求: " . $inputJSON . " | GET params: " . json_encode($_GET));
    $input = json_decode($inputJSON, true);

    if (json_last_error() !== JSON_ERROR_NONE && !empty($inputJSON)) {
        throw new Exception("無效的JSON輸入: " . json_last_error_msg());
    }
    
    $action = $_GET['action'] ?? $input['action'] ?? '';
    logMessage("解析的 Action: {$action}");

    $response = null;

    switch ($action) {
        case 'get_updates':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('get_updates 需要使用POST方法');
            }
            $response = handleGetUpdates($input);
            break;
            
        case 'send_update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('send_update 需要使用POST方法');
            }
            $response = handleSendUpdate($input);
            break;
            
        case 'status':
            $pdo = connectDB();
            $stmtTotalSnapshots = $pdo->query("SELECT COUNT(*) as total_snapshots FROM room_code_snapshots");
            $totalSnapshots = $stmtTotalSnapshots->fetch()['total_snapshots'];
            
            $stmtActiveUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) as total_active FROM room_participants WHERE last_active >= NOW() - INTERVAL 5 MINUTE");
            $totalActiveUsers = $stmtActiveUsers->fetch()['total_active'];
            
            $response = [
                'success' => true,
                'status' => 'online',
                'total_snapshots' => $totalSnapshots,
                'active_users_total' => $totalActiveUsers,
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => phpversion(),
                'timestamp' => time()
            ];
            break;
            
        default:
            if (!empty($action)) {
                throw new Exception("不支援的操作: {$action}");
            } else {
                $response = [
                    'success' => true, 
                    'message' => 'Code Sync Handler is active. Use POST requests for get_updates or send_update.',
                    'available_actions' => ['get_updates (POST)', 'send_update (POST)', 'status (GET/POST)']
                ];
            }
    }

    if ($response === null) {
        throw new Exception("操作 {$action} 未返回任何響應。");
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    logMessage("處理請求時發生錯誤: \"{$e->getMessage()}\" --- Input: " . ($inputJSON ?? 'N/A') . " --- Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action_received' => $action ?? 'unknown_or_missing',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?> 