<?php
/**
 * Python協作教學平台 - AI助教API處理器 (XAMPP版)
 * 提供代碼解釋、錯誤檢查、改進建議等AI助教功能
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

// 從環境變數或配置文件讀取API Key
function getOpenAIKey() {
    // 首先嘗試從環境變數讀取
    $api_key = getenv('OPENAI_API_KEY');
    // 如果環境變數是預設佔位符，忽略環境變數
    if ($api_key === 'your-openai-api-key-here') {
        logMessage("環境變數 API Key 為佔位符，忽略環境變數。");
        $api_key = '';
    }
    
    // 如果環境變數沒有，嘗試從配置文件讀取
    if (!$api_key) {
        $config_file = __DIR__ . '/ai_config.json';
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
            $api_key = $config['openai_api_key'] ?? '';
        }
    }
    
    if (empty($api_key)) {
        logMessage("API Key未設置，請檢查環境變數或配置文件。");
        // 如果還是沒有，使用預設的演示Key（僅供測試）
        $api_key = 'demo-key-replace-with-real-key';
    } else {
        logMessage("API Key已成功讀取。");
    }
    
    return $api_key;
}

/**
 * 記錄日誌
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/ai_debug.log';
    error_log("[$timestamp] AI_API: $message\n", 3, $logFile);
}

/**
 * 使用Mock AI回應（當沒有真實API Key時）
 */
function getMockAIResponse($action, $code) {
    // 如果是檢查錯誤，進行實際的代碼分析
    if ($action === 'bugs' && !empty($code)) {
        return analyzeCodeForBugs($code);
    }
    
    $responses = [
        'explain' => "## 🔍 程式碼解釋\n\n**功能概述:**\n這段Python程式碼實現了斐波那契數列的生成和分析功能。\n\n**主要組件:**\n1. `fibonacci_sequence(n)` - 生成指定長度的斐波那契數列\n2. `analyze_sequence(sequence)` - 分析數列的統計特性\n3. 主程式 - 執行演示和測試\n\n**程式邏輯:**\n- 使用迭代方式生成斐波那契數列，避免遞歸的性能問題\n- 提供完整的數列統計分析\n- 包含錯誤處理和邊界條件檢查\n\n💡 這是一個很好的Python學習範例，展示了函數定義、迴圈控制、錯誤處理等核心概念！",
        
        'bugs' => "## 🐛 程式碼檢查\n\n**檢查結果:** ✅ 程式碼品質良好\n\n**優點:**\n- 函數設計清晰，有明確的文檔字符串\n- 邊界條件處理完善\n- 變數命名有意義\n- 程式碼結構清晰\n\n**建議改進:**\n1. 可以加入類型提示 (Type Hints)\n2. 考慮將 `analyze_sequence` 的返回值標準化\n3. 可以加入更多的單元測試\n\n**安全性:** 無明顯安全問題\n**性能:** 對於中等規模數據表現良好",
        
        'improve' => "## 🚀 優化建議\n\n**性能優化:**\n1. 對於大型數列，可以使用生成器 (Generator) 節省記憶體\n2. 考慮使用 NumPy 進行數值計算\n\n**程式碼改進:**\n```python\ndef fibonacci_generator(n: int) -> Iterator[int]:\n    \"\"\"使用生成器優化記憶體使用\"\"\"\n    a, b = 0, 1\n    for _ in range(n):\n        yield a\n        a, b = b, a + b\n```\n\n**架構建議:**\n- 建立 `FibonacciAnalyzer` 類別，封裝相關功能\n- 加入配置文件支援\n- 實現結果快取機制\n\n**協作建議:**\n- 加入更多註釋便於團隊理解\n- 建立標準的測試案例",
        
        'help' => "## 🎓 協作學習指導\n\n**這段程式碼的教學價值:**\n\n**適合學習的概念:**\n1. **函數設計** - 看看如何將複雜問題分解成小函數\n2. **迴圈控制** - 理解 `for` 迴圈的應用\n3. **資料結構** - 清單的操作和處理\n4. **錯誤處理** - 邊界條件的考慮\n\n**協作建議:**\n- 一人負責 `fibonacci_sequence` 函數\n- 另一人負責 `analyze_sequence` 函數\n- 共同討論主程式邏輯\n\n**練習方向:**\n1. 嘗試實現其他數列（如質數數列）\n2. 加入圖表視覺化功能\n3. 實現不同的數列分析方法\n\n💡 記住：程式設計是團隊運動，互相學習才能進步更快！"
    ];
    
    return $responses[$action] ?? $responses['explain'];
}

/**
 * 分析代碼錯誤（Mock版本）
 * 檢查常見的Python語法錯誤和問題
 */
function analyzeCodeForBugs($code) {
    $errors = [];
    $warnings = [];
    $suggestions = [];
    
    // 將代碼分行
    $lines = explode("\n", $code);
    $lineNumber = 1;
    
    // 基本語法檢查
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // 檢查空行
        if (empty($trimmedLine)) {
            $lineNumber++;
            continue;
        }
        
        // 檢查行開頭的無效字符
        if (preg_match('/^[a-zA-Z]+[^a-zA-Z0-9_\s#]/', $trimmedLine)) {
            // 檢查是否是無意義的字符序列（如 tTt）
            if (preg_match('/^[a-zA-Z]{2,}(?![a-zA-Z0-9_])/', $trimmedLine, $matches)) {
                $errors[] = "第 {$lineNumber} 行: 發現無效的字符序列 '{$matches[0]}'，這不是有效的Python語法";
            }
        }
        
        // 檢查是否以無效字符開頭（不是註釋、關鍵字、函數名等）
        if (preg_match('/^([a-zA-Z]+)([^a-zA-Z0-9_\s(=])/', $trimmedLine, $matches)) {
            $invalidWord = $matches[1];
            $invalidChar = $matches[2];
            
            // Python關鍵字和常見函數名檢查
            $validKeywords = ['def', 'if', 'elif', 'else', 'for', 'while', 'try', 'except', 'finally', 
                            'import', 'from', 'class', 'return', 'pass', 'break', 'continue', 
                            'print', 'len', 'range', 'str', 'int', 'float', 'list', 'dict'];
            
            if (!in_array($invalidWord, $validKeywords)) {
                $errors[] = "第 {$lineNumber} 行: 無效的語法 '{$invalidWord}{$invalidChar}'，可能是多餘的字符或拼寫錯誤";
            }
        }
        
        // 檢查縮進問題
        $indentLevel = strlen($line) - strlen(ltrim($line));
        if ($indentLevel % 4 !== 0 && $indentLevel > 0) {
            $warnings[] = "第 {$lineNumber} 行: 縮進不是4的倍數，建議使用4個空格進行縮進";
        }
        
        // 檢查常見語法錯誤
        if (strpos($trimmedLine, '=') !== false && strpos($trimmedLine, '==') === false) {
            // 檢查是否在 if 語句中使用 = 而不是 ==
            if (preg_match('/if\s+.*=(?!=)/', $trimmedLine)) {
                $warnings[] = "第 {$lineNumber} 行: 在條件判斷中使用了賦值運算符 '='，您可能想使用比較運算符 '=='";
            }
        }
        
        // 檢查括號匹配
        $openParens = substr_count($trimmedLine, '(');
        $closeParens = substr_count($trimmedLine, ')');
        if ($openParens !== $closeParens) {
            $errors[] = "第 {$lineNumber} 行: 括號不匹配（開括號: {$openParens}，閉括號: {$closeParens}）";
        }
        
        // 檢查引號匹配
        $singleQuotes = substr_count($trimmedLine, "'");
        $doubleQuotes = substr_count($trimmedLine, '"');
        if ($singleQuotes % 2 !== 0) {
            $errors[] = "第 {$lineNumber} 行: 單引號不匹配";
        }
        if ($doubleQuotes % 2 !== 0) {
            $errors[] = "第 {$lineNumber} 行: 雙引號不匹配";
        }
        
        // 檢查冒號後是否有換行（函數、類、條件語句等）
        if (preg_match('/^(def|class|if|elif|else|for|while|try|except|finally|with)\s.*:$/', $trimmedLine)) {
            // 這是正確的，不需要警告
        } elseif (preg_match('/^(def|class|if|elif|else|for|while|try|except|finally|with)\s.*[^:]$/', $trimmedLine)) {
            $warnings[] = "第 {$lineNumber} 行: 語句可能缺少冒號 ':'";
        }
        
        $lineNumber++;
    }
    
    // 檢查整體結構
    $codeStr = $code;
    
    // 檢查是否有函數但沒有主程式執行
    if (strpos($codeStr, 'def ') !== false && strpos($codeStr, '__main__') === false) {
        $suggestions[] = "建議加入 `if __name__ == '__main__':` 來執行主程式";
    }
    
    // 生成報告
    $report = "## 🐛 程式碼檢查報告\n\n";
    
    if (!empty($errors)) {
        $report .= "### ❌ 發現的錯誤:\n";
        foreach ($errors as $error) {
            $report .= "- " . $error . "\n";
        }
        $report .= "\n";
    }
    
    if (!empty($warnings)) {
        $report .= "### ⚠️ 警告事項:\n";
        foreach ($warnings as $warning) {
            $report .= "- " . $warning . "\n";
        }
        $report .= "\n";
    }
    
    if (!empty($suggestions)) {
        $report .= "### 💡 改進建議:\n";
        foreach ($suggestions as $suggestion) {
            $report .= "- " . $suggestion . "\n";
        }
        $report .= "\n";
    }
    
    if (empty($errors) && empty($warnings)) {
        $report .= "### ✅ 檢查結果: 程式碼看起來很不錯！\n\n";
        $report .= "**優點:**\n";
        $report .= "- 沒有發現明顯的語法錯誤\n";
        $report .= "- 程式碼結構清晰\n";
        $report .= "- 符合基本的Python語法規範\n\n";
    }
    
    $report .= "### 🎓 學習提示:\n";
    $report .= "- 在協作編程時，保持代碼整潔和一致的風格很重要\n";
    $report .= "- 建議使用Python IDE的語法檢查功能\n";
    $report .= "- 定期運行程式碼確保沒有隱藏的錯誤\n\n";
    
    $report .= "*這是AI助教的模擬檢查結果，實際開發中建議使用專業的IDE和linter工具。*";
    
    return $report;
}

/**
 * 呼叫OpenAI API
 */
function callOpenAI($prompt, $action = 'general') {
    $api_key = getOpenAIKey();
    
    // 新增日誌，檢查 API Key 的實際值和類型
    logMessage("callOpenAI: 取得的 API Key 值: '" . $api_key . "'");
    logMessage("callOpenAI: 取得的 API Key 類型: " . gettype($api_key));
    
    // 如果是演示Key，返回Mock回應
    if ($api_key === 'demo-key-replace-with-real-key') {
        logMessage("使用Mock AI回應 - Action: $action (原因: API Key完全匹配演示Key)");
        return getMockAIResponse($action, $prompt);
    }
    
    logMessage("呼叫OpenAI API - Action: $action (使用 API Key: '" . substr($api_key, 0, 7) . "..." . substr($api_key, -4) . "')"); // 只記錄部分Key以保護隱私
    
    // 根據動作類型調整系統提示
    $system_prompts = [
        'explain' => '你是一個專業的Python程式設計助教，專門解釋程式碼功能。請用繁體中文詳細說明程式碼的作用、邏輯和每個部分的功能。使用Markdown格式，加入適當的表情符號使內容更生動。',
        'bugs' => '你是一個專業的Python程式碼審查助教，專門找出程式問題。請用繁體中文仔細檢查程式碼，指出可能的錯誤、問題或需要改進的地方。使用Markdown格式，用清單標示問題。',
        'improve' => '你是一個專業的Python程式碼優化助教，專門提供改進建議。請用繁體中文為程式碼提供優化建議，包括性能改進、程式碼可讀性和最佳實踐。使用Markdown格式，提供具體的改進範例。',
        'help' => '你是一個專業的Python協作學習助教，專門指導多人協作編程。請用繁體中文提供協作建議和學習指導，幫助學生更好地進行團隊編程。重點強調學習價值和協作技巧。',
        'general' => '你是一個友善的Python程式設計助教，專門幫助學生學習Python並提供協作編程指導。請用繁體中文回答，保持友善、鼓勵的語調。使用Markdown格式讓回答更清楚。'
    ];
    
    $system_prompt = $system_prompts[$action] ?? $system_prompts['general'];
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => $system_prompt
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 1000,
        'temperature' => 0.7
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        logMessage("CURL錯誤: $error");
        throw new Exception("網路連接錯誤: $error");
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        logMessage("OpenAI API錯誤 - HTTP Code: $http_code, Response: $response");
        throw new Exception("API請求失敗 (HTTP $http_code)");
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices']) || !isset($result['choices'][0]['message']['content'])) {
        logMessage("OpenAI API回應格式錯誤: $response");
        throw new Exception("API回應格式錯誤");
    }
    
    return $result['choices'][0]['message']['content'];
}

// 主要處理邏輯
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // GET請求返回狀態資訊
        echo json_encode([
            'success' => true,
            'status' => 'AI助教服務運行中',
            'available_actions' => ['explain', 'bugs', 'improve', 'help', 'general'],
            'api_key_configured' => (getOpenAIKey() !== 'demo-key-replace-with-real-key'),
            'demo_mode' => (getOpenAIKey() === 'demo-key-replace-with-real-key'),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('無效的JSON數據');
    }
    
    $action = $input['action'] ?? 'general';
    $code = $input['code'] ?? '';
    $custom_prompt = $input['custom_prompt'] ?? '';
    $user_id = $input['user_id'] ?? 'anonymous';
    $room_code = $input['room'] ?? 'default';
    
    logMessage("收到AI請求 - Action: $action, User: $user_id, Room: $room_code, Code length: " . strlen($code));
    
    // 構建提示詞
    $prompt = '';
    
    if (!empty($custom_prompt)) {
        // 自定義提示
        $prompt = $custom_prompt;
        if (!empty($code)) {
            $prompt .= "\n\n當前的Python程式碼是：\n```python\n" . $code . "\n```";
        }
    } else {
        // 預設提示
        switch ($action) {
            case 'explain':
                $prompt = "請用繁體中文詳細解釋這段Python程式碼的功能、邏輯和每個部分的作用：\n\n```python\n" . $code . "\n```";
                break;
                
            case 'bugs':
                $prompt = "請用繁體中文仔細檢查這段Python程式碼，找出可能的錯誤、問題或需要改進的地方：\n\n```python\n" . $code . "\n```";
                break;
                
            case 'improve':
                $prompt = "請用繁體中文為這段Python程式碼提供優化建議，包括性能改進、程式碼可讀性和最佳實踐：\n\n```python\n" . $code . "\n```";
                break;
                
            case 'help':
                $prompt = "在多人協作編程的情境下，請用繁體中文提供關於這段Python程式碼的協作建議和學習指導：\n\n```python\n" . $code . "\n```";
                break;
                
            default:
                $prompt = "請用繁體中文回答關於這段Python程式碼的問題：\n\n```python\n" . $code . "\n```";
        }
    }
    
    if (empty($prompt)) {
        throw new Exception('提示詞不能為空');
    }
    
    // 呼叫AI API (OpenAI 或 Mock)
    $ai_response = callOpenAI($prompt, $action);
    
    logMessage("AI回應成功 - Action: $action, Response length: " . strlen($ai_response));
    
    // 回傳成功結果
    echo json_encode([
        'success' => true,
        'response' => $ai_response,
        'action' => $action,
        'demo_mode' => (getOpenAIKey() === 'demo-key-replace-with-real-key'),
        'user_id' => $user_id,
        'room_code' => $room_code,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    logMessage("AI API錯誤: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'demo_available' => true,
        'suggestion' => '如果沒有OpenAI API Key，系統會使用演示模式提供模擬回應',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?> 