<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理CORS預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 載入必要的類
require_once '../classes/UserManager.php';

try {
    $userManager = new UserManager();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 獲取學習進度
        $userId = $_GET['user_id'] ?? 'anonymous';
        
        // 確保用戶存在
        $userManager->getOrCreateUser($userId);
        
        // 獲取用戶進度
        $progress = $userManager->getUserProgress($userId);
        $stats = $userManager->getUserStats($userId);
        
        echo json_encode([
            'success' => true,
            'user_id' => $userId,
            'progress' => $progress,
            'stats' => $stats
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 更新學習進度
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('無效的JSON格式');
        }
        
        $userId = $data['user_id'] ?? 'anonymous';
        $lessonId = $data['lesson_id'] ?? null;
        $progressPercent = $data['progress_percent'] ?? 0;
        $completed = $data['completed'] ?? false;
        
        if (!$lessonId) {
            throw new Exception('缺少lesson_id參數');
        }
        
        // 確保用戶存在
        $userManager->getOrCreateUser($userId);
        
        // 更新進度
        $result = $userManager->updateProgress($userId, $lessonId, $progressPercent, $completed);
        
        if (!$result) {
            throw new Exception('更新進度失敗');
        }
        
        // 獲取更新後的進度
        $updatedProgress = $userManager->getUserProgress($userId);
        
        echo json_encode([
            'success' => true,
            'message' => '學習進度已更新',
            'user_id' => $userId,
            'updated_progress' => $updatedProgress
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => '不支援的請求方法']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>