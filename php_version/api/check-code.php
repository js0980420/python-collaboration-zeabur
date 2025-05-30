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
require_once '../classes/UserManager.php';
require_once '../config/database.php';

try {
    // 獲取JSON輸入
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('無效的JSON格式');
    }
    
    // 驗證必要參數
    if (empty($data['code'])) {
        throw new Exception('請輸入程式碼');
    }
    
    $code = trim($data['code']);
    $userId = $data['user_id'] ?? 'anonymous_' . uniqid();
    
    // 確保用戶存在
    $userManager = new UserManager();
    $userManager->getOrCreateUser($userId);
    
    // 基本程式碼分析
    $analysis = analyzeCode($code);
    
    // 使用XAI進行深度代碼分析
    $aiFeedback = '';
    try {
        $xaiService = new XAIService();
        $aiResponse = $xaiService->checkCode($code);
        
        if (isset($aiResponse['choices'][0]['message']['content'])) {
            $aiFeedback = $aiResponse['choices'][0]['message']['content'];
        }
    } catch (Exception $e) {
        $aiFeedback = '無法連接AI服務，僅提供基本檢查';
    }
    
    // 生成改進建議
    $suggestions = generateSuggestions($code, $analysis);
    
    // 保存程式碼提交記錄
    saveCodeSubmission($userId, $code, [], $analysis, $aiFeedback, $suggestions);
    
    // 返回回應
    echo json_encode([
        'success' => true,
        'syntax_errors' => [], // PHP無法直接檢查Python語法，由AI處理
        'analysis' => $analysis,
        'ai_feedback' => $aiFeedback,
        'suggestions' => $suggestions,
        'user_id' => $userId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 分析程式碼結構
 */
function analyzeCode($code) {
    $lines = explode("\n", $code);
    $lineCount = count($lines);
    
    $analysis = [
        'line_count' => $lineCount,
        'has_functions' => strpos($code, 'def ') !== false,
        'has_loops' => (strpos($code, 'for ') !== false || strpos($code, 'while ') !== false),
        'has_conditionals' => (strpos($code, 'if ') !== false || strpos($code, 'elif ') !== false || strpos($code, 'else:') !== false),
        'imports' => []
    ];
    
    // 提取import語句
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        if (strpos($trimmedLine, 'import ') === 0 || strpos($trimmedLine, 'from ') === 0) {
            $analysis['imports'][] = $trimmedLine;
        }
    }
    
    return $analysis;
}

/**
 * 生成改進建議
 */
function generateSuggestions($code, $analysis) {
    $suggestions = [];
    
    if (!$analysis['has_functions'] && $analysis['line_count'] > 10) {
        $suggestions[] = '考慮將代碼組織成函數，提高可讀性和重用性';
    }
    
    if (strpos($code, 'print(') !== false) {
        $suggestions[] = '除了print外，也可以學習使用logging模組';
    }
    
    if (empty($analysis['imports']) && $analysis['line_count'] > 5) {
        $suggestions[] = '可以考慮使用Python標準庫或第三方庫來簡化代碼';
    }
    
    if (strpos($code, '# ') === false && strpos($code, '"""') === false) {
        $suggestions[] = '建議添加註釋來解釋代碼的功能和邏輯';
    }
    
    return $suggestions;
}

/**
 * 保存程式碼提交記錄
 */
function saveCodeSubmission($userId, $code, $syntaxErrors, $analysis, $aiFeedback, $suggestions) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO code_submissions 
            (user_id, code, syntax_errors, analysis, ai_feedback, suggestions, has_errors) 
            VALUES (:user_id, :code, :syntax_errors, :analysis, :ai_feedback, :suggestions, :has_errors)
        ");
        
        $hasErrors = !empty($syntaxErrors);
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':syntax_errors', json_encode($syntaxErrors));
        $stmt->bindParam(':analysis', json_encode($analysis));
        $stmt->bindParam(':ai_feedback', $aiFeedback);
        $stmt->bindParam(':suggestions', json_encode($suggestions));
        $stmt->bindParam(':has_errors', $hasErrors, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to save code submission: " . $e->getMessage());
        return false;
    }
}
?> 