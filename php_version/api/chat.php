<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理CORS預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允許POST請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允許POST請求']);
    exit();
}

// 載入必要的類
require_once '../classes/XAIService.php';
require_once '../classes/ConversationManager.php';
require_once '../classes/UserManager.php';

try {
    // 獲取JSON輸入
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('無效的JSON格式');
    }
    
    // 驗證必要參數
    if (empty($data['message'])) {
        throw new Exception('請輸入訊息');
    }
    
    $userMessage = trim($data['message']);
    $userId = $data['user_id'] ?? 'anonymous_' . uniqid();
    $sessionId = $data['session_id'] ?? null;
    
    // 確保用戶存在
    $userManager = new UserManager();
    $userManager->getOrCreateUser($userId);
    
    // 處理對話
    $conversationManager = new ConversationManager();
    $result = $conversationManager->handleConversation($userId, $userMessage, $sessionId);
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    // 返回成功回應
    echo json_encode([
        'success' => true,
        'response' => $result['response'],
        'session_id' => $result['session_id'],
        'user_id' => $userId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 