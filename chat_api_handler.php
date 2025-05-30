<?php
/**
 * Python協作教學平台 - 聊天API處理器
 * 使用 PHP + MySQL 實現即時聊天功能
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

/**
 * 記錄日誌
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] CHAT_API: $message");
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
 * 發送消息
 */
function sendMessage($roomCode, $userId, $userName, $message, $messageType = 'user') {
    $pdo = connectDB();
    
    // 查找房間ID
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$roomCode]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        // 如果房間不存在，創建一個
        $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_code, description) VALUES (?, ?, ?)");
        $stmt->execute([$roomCode, $roomCode, "自動創建的協作房間"]);
        $roomId = $pdo->lastInsertId();
    } else {
        $roomId = $room['id'];
    }
    
    // 插入聊天消息
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (room_id, user_id, message_type, content, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    // 使用臨時用戶ID（在實際系統中應該使用真實用戶ID）
    $tempUserId = 1; // 可以根據需要改為動態用戶
    
    $stmt->execute([$roomId, $tempUserId, $messageType, $message]);
    
    logMessage("消息已發送 - 房間: $roomCode, 用戶: $userName, 內容: " . substr($message, 0, 50));
    
    return [
        'id' => $pdo->lastInsertId(),
        'room_id' => $roomId,
        'user_name' => $userName,
        'message' => $message,
        'message_type' => $messageType,
        'timestamp' => time()
    ];
}

/**
 * 獲取房間消息
 */
function getRoomMessages($roomCode, $limit = 50, $lastMessageId = 0) {
    $pdo = connectDB();
    
    // 查找房間ID
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$roomCode]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        return [];
    }
    
    // 獲取消息
    $sql = "
        SELECT 
            cm.id,
            cm.content as message,
            cm.message_type,
            cm.created_at,
            UNIX_TIMESTAMP(cm.created_at) as timestamp,
            'unknown_user' as user_name
        FROM chat_messages cm 
        WHERE cm.room_id = ? AND cm.id > ?
        ORDER BY cm.created_at ASC 
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room['id'], $lastMessageId, $limit]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("獲取消息 - 房間: $roomCode, 數量: " . count($messages));
    
    return $messages;
}

/**
 * 獲取房間用戶列表
 */
function getRoomUsers($roomCode) {
    // 模擬用戶列表（在實際系統中應該跟蹤在線用戶）
    $users = [
        ['id' => 1, 'name' => '您', 'status' => 'online'],
        ['id' => 2, 'name' => '張同學', 'status' => 'online'],
        ['id' => 3, 'name' => '李同學', 'status' => 'away'],
        ['id' => 4, 'name' => '王老師', 'status' => 'online']
    ];
    
    // 隨機返回部分用戶模擬真實場景
    $randomCount = rand(2, 4);
    return array_slice($users, 0, $randomCount);
}

/**
 * 清理舊消息
 */
function cleanOldMessages($days = 7) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$days]);
    
    $deletedCount = $stmt->rowCount();
    logMessage("清理舊消息: 刪除了 $deletedCount 條消息");
    
    return $deletedCount;
}

// 主要處理邏輯
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? $input['action'] ?? '';
    
    switch ($action) {
        case 'send':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('發送消息需要使用POST方法');
            }
            
            $roomCode = $input['room'] ?? 'demo-room';
            $userId = $input['userId'] ?? 'anonymous';
            $userName = $input['userName'] ?? '匿名用戶';
            $message = $input['message'] ?? '';
            $messageType = $input['messageType'] ?? 'user';
            
            if (empty($message)) {
                throw new Exception('消息內容不能為空');
            }
            
            $result = sendMessage($roomCode, $userId, $userName, $message, $messageType);
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get':
            $roomCode = $_GET['room'] ?? 'demo-room';
            $limit = intval($_GET['limit'] ?? 50);
            $lastMessageId = intval($_GET['last_id'] ?? 0);
            
            $messages = getRoomMessages($roomCode, $limit, $lastMessageId);
            
            echo json_encode([
                'success' => true,
                'data' => $messages,
                'count' => count($messages),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'users':
            $roomCode = $_GET['room'] ?? 'demo-room';
            $users = getRoomUsers($roomCode);
            
            echo json_encode([
                'success' => true,
                'data' => $users,
                'count' => count($users),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'clean':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('清理操作需要使用POST方法');
            }
            
            $days = intval($input['days'] ?? 7);
            $deletedCount = cleanOldMessages($days);
            
            echo json_encode([
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => "已清理 $deletedCount 條舊消息",
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'status':
            // 返回聊天系統狀態
            $pdo = connectDB();
            $stmt = $pdo->query("SELECT COUNT(*) as total_messages FROM chat_messages");
            $totalMessages = $stmt->fetch(PDO::FETCH_ASSOC)['total_messages'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
            $totalRooms = $stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'];
            
            echo json_encode([
                'success' => true,
                'status' => 'online',
                'total_messages' => $totalMessages,
                'total_rooms' => $totalRooms,
                'server_time' => date('Y-m-d H:i:s'),
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception("不支援的操作: $action");
    }
    
} catch (Exception $e) {
    logMessage("錯誤: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?> 