<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理CORS預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允許GET請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => '只允許GET請求']);
    exit();
}

// 載入必要的類
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 全體用戶統計
    $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as total_users FROM users");
    $stmt->execute();
    $totalUsers = $stmt->fetch()['total_users'];
    
    // 總對話數量
    $stmt = $db->prepare("SELECT COUNT(*) as total_conversations FROM ai_conversations WHERE message_type = 'user'");
    $stmt->execute();
    $totalConversations = $stmt->fetch()['total_conversations'];
    
    // 總程式碼提交數量
    $stmt = $db->prepare("SELECT COUNT(*) as total_submissions FROM code_submissions");
    $stmt->execute();
    $totalSubmissions = $stmt->fetch()['total_submissions'];
    
    // 計算平均對話數
    $avgConversations = $totalUsers > 0 ? round($totalConversations / $totalUsers, 2) : 0;
    
    // 熱門主題分析
    $popularTopics = getPopularTopics($db);
    
    // 最近活動
    $recentActivity = getRecentActivity($db);
    
    // 學習統計
    $learningStats = getLearningStats($db);
    
    echo json_encode([
        'success' => true,
        'overview' => [
            'total_users' => $totalUsers,
            'total_conversations' => $totalConversations,
            'total_code_submissions' => $totalSubmissions,
            'avg_conversations_per_user' => $avgConversations
        ],
        'popular_topics' => $popularTopics,
        'recent_activity' => $recentActivity,
        'learning_stats' => $learningStats
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '數據分析錯誤：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 獲取熱門主題
 */
function getPopularTopics($db) {
    $stmt = $db->prepare("
        SELECT message FROM ai_conversations 
        WHERE message_type = 'user' 
        ORDER BY created_at DESC 
        LIMIT 1000
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $topicMentions = [];
    $topicKeywords = [
        '變數與資料型別' => ['變數', 'variable', '資料型別', 'int', 'str', 'list'],
        '函數' => ['函數', 'function', 'def', 'return'],
        '迴圈' => ['迴圈', 'loop', 'for', 'while'],
        '條件判斷' => ['條件', 'if', 'else', 'elif'],
        '錯誤處理' => ['錯誤', 'error', 'try', 'except'],
        '檔案操作' => ['檔案', 'file', 'open', 'read', 'write'],
        '列表字典' => ['list', 'dict', '列表', '字典', 'append'],
        '類別物件' => ['class', '類別', 'object', '物件', '__init__']
    ];
    
    foreach ($messages as $message) {
        $content = strtolower($message);
        foreach ($topicKeywords as $topicName => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    $topicMentions[$topicName] = ($topicMentions[$topicName] ?? 0) + 1;
                    break;
                }
            }
        }
    }
    
    // 排序並取前5名
    arsort($topicMentions);
    return array_slice($topicMentions, 0, 5, true);
}

/**
 * 獲取最近活動
 */
function getRecentActivity($db) {
    $stmt = $db->prepare("
        SELECT 
            SUBSTRING(user_id, 1, 8) as user_id,
            'conversation' as activity_type,
            created_at 
        FROM ai_conversations 
        WHERE message_type = 'user' 
        
        UNION ALL
        
        SELECT 
            SUBSTRING(user_id, 1, 8) as user_id,
            'code_submission' as activity_type,
            created_at 
        FROM code_submissions 
        
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * 獲取學習統計
 */
function getLearningStats($db) {
    // 課程完成統計
    $stmt = $db->prepare("
        SELECT 
            l.title as lesson_title,
            COUNT(up.user_id) as users_started,
            COUNT(CASE WHEN up.completed_at IS NOT NULL THEN 1 END) as users_completed
        FROM lessons l
        LEFT JOIN user_progress up ON l.lesson_id = up.lesson_id
        GROUP BY l.id, l.title
        ORDER BY l.order_index
    ");
    $stmt->execute();
    $lessonStats = $stmt->fetchAll();
    
    // 程式碼提交錯誤率
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_submissions,
            COUNT(CASE WHEN has_errors = 1 THEN 1 END) as error_submissions
        FROM code_submissions
    ");
    $stmt->execute();
    $codeStats = $stmt->fetch();
    
    $errorRate = $codeStats['total_submissions'] > 0 
        ? round(($codeStats['error_submissions'] / $codeStats['total_submissions']) * 100, 2)
        : 0;
    
    // 活躍用戶統計（最近7天）
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT user_id) as active_users
        FROM (
            SELECT user_id FROM ai_conversations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION
            SELECT user_id FROM code_submissions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) as active_users_union
    ");
    $stmt->execute();
    $activeUsers = $stmt->fetch()['active_users'];
    
    return [
        'lesson_stats' => $lessonStats,
        'code_error_rate' => $errorRate,
        'active_users_7_days' => $activeUsers
    ];
}
?> 