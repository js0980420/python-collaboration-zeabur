<?php
/**
 * Python協作教學平台 - 代碼同步處理器 (HTTP 輪詢版)
 * 使用 PHP + MySQL 實現代碼同步、用戶狀態和版本管理
 */

// 設置CORS標頭
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// 處理OPTIONS預檢請求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// XAMPP MySQL 連接設定
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'python_collaboration',
    'username' => 'root',
    'password' => '',  // XAMPP 預設無密碼
    'charset' => 'utf8mb4'
];

// 全局PDO對象
$pdo = null;

/**
 * 記錄日誌
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = 'C:/xampp/htdocs/collaboration/sync_debug.log';
    error_log("[$timestamp] CODE_SYNC: $message\n", 3, $logFile);
}

/**
 * 連接數據庫 (使用全局PDO)
 */
function connectDB() {
    global $pdo, $db_config;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
            $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            logMessage("數據庫連接成功。");
        } catch (PDOException $e) {
            logMessage("數據庫連接失敗: " . $e->getMessage());
            throw new Exception("數據庫連接失敗: " . $e->getMessage());
        }
    }
    return $pdo;
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
 * 獲取初始代碼模板
 */
function getInitialCode() {
    return "# 🐍 Python協作教學平台 - XAMPP + PHP後端版\n# 歡迎使用完整的後端整合協作編程環境！\n\ndef fibonacci_sequence(n):\n    '''\n    生成斐波那契數列\n    \n    參數:\n        n (int): 要生成的數列長度\n    \n    返回:\n        list: 斐波那契數列\n    '''\n    if n <= 0:\n        return []\n    elif n == 1:\n        return [0]\n    elif n == 2:\n        return [0, 1]\n    \n    sequence = [0, 1]\n    for i in range(2, n):\n        next_num = sequence[i-1] + sequence[i-2]\n        sequence.append(next_num)\n    \n    return sequence\n\ndef analyze_sequence(sequence):\n    '''分析數列的特性'''\n    if not sequence:\n        return \"數列為空\"\n    \n    total = sum(sequence)\n    average = total / len(sequence)\n    max_num = max(sequence)\n    \n    print(f\"數列長度: {len(sequence)}\")\n    print(f\"總和: {total}\")\n    print(f\"平均值: {average:.2f}\")\n    print(f\"最大值: {max_num}\")\n    \n    return {\n        'length': len(sequence),\n        'sum': total,\n        'average': average,\n        'max': max_num\n    }\n\n# 主程式\nif __name__ == \"__main__\":\n    print(\"🔥 XAMPP協作編程示例：斐波那契數列分析\")\n    \n    # 生成前15個斐波那契數\n    fib_sequence = fibonacci_sequence(15)\n    print(f\"前15個斐波那契數: {fib_sequence}\")\n    \n    # 分析數列特性\n    analysis = analyze_sequence(fib_sequence)\n    print(\"\\n📊 數列分析完成！\")\n    \n    # 💡 試試看：\n    # 1. 點擊\"解釋程式碼\"讓AI助教說明這個程式\n    # 2. 點擊\"檢查錯誤\"讓AI檢查程式是否有問題  \n    # 3. 點擊\"改進建議\"獲得程式碼優化建議\n    # 4. 在聊天區域與同伴討論程式碼\n    # 5. 代碼會自動保存到MySQL數據庫！";
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
 * 處理 get_updates 請求
 */
function handleGetUpdates($input) {
    $roomCode = $input['room'] ?? 'default_room';
    $lastKnownVersion = isset($input['lastVersion']) ? intval($input['lastVersion']) : 0;
    $userId = $input['userId'] ?? 'anonymous_'.bin2hex(random_bytes(4));
    $userName = $input['userName'] ?? '匿名用戶';

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

    // 3. 獲取游標更新（需要先添加相關數據庫字段）
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

    return [
        'success' => true,
        'updates' => $updates,
        'latestVersion' => $currentVersion,
        'serverTimestamp' => time() * 1000
    ];
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
        
        default:
            throw new Exception("未知的更新類型: {$type}");
    }
}

// 主要處理邏輯
try {
    connectDB();
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