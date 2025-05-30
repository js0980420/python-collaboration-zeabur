<?php
// 🧪 Grok API 連接測試腳本
// 用於驗證API金鑰和基本功能

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='zh-TW'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Grok API 測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-result { margin: 10px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 3px; font-family: monospace; white-space: pre-wrap; }
        h1 { color: #333; text-align: center; }
        h2 { color: #666; border-bottom: 2px solid #eee; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🤖 Grok API 連接測試</h1>";

// API 配置
$apiKey = 'xai-e4IkGBt411Vrj0jEOKIfu6anO1OapqvMpcavAKDS35xRJrfUxTYSZLzuF9X28BBpJPuR4TPwBI2Lo7sL';
$baseUrl = 'https://api.x.ai/v1';
$model = 'grok-beta';

// 測試1：檢查PHP環境
echo "<h2>📋 環境檢查</h2>";

$phpVersion = phpversion();
echo "<div class='test-result info'>PHP版本: {$phpVersion}</div>";

if (extension_loaded('curl')) {
    echo "<div class='test-result success'>✅ cURL擴展已安裝</div>";
} else {
    echo "<div class='test-result error'>❌ cURL擴展未安裝 - API調用將失敗</div>";
    exit;
}

if (function_exists('json_encode') && function_exists('json_decode')) {
    echo "<div class='test-result success'>✅ JSON支援正常</div>";
} else {
    echo "<div class='test-result error'>❌ JSON支援異常</div>";
    exit;
}

// 測試2：網路連接
echo "<h2>🌐 網路連接測試</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='test-result error'>❌ 網路連接失敗: {$error}</div>";
} else {
    echo "<div class='test-result success'>✅ 可以連接到 xAI API (HTTP {$httpCode})</div>";
}

// 測試3：API金鑰驗證
echo "<h2>🔑 API金鑰測試</h2>";

function testGrokAPI($apiKey, $baseUrl, $model, $prompt) {
    $url = $baseUrl . '/chat/completions';
    
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => '你是一個測試助手，請簡短回應。'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 100
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => !$error && $httpCode === 200,
        'httpCode' => $httpCode,
        'error' => $error,
        'response' => $response
    ];
}

// 執行API測試
$testPrompt = "請說 '測試成功' 用繁體中文";
$startTime = microtime(true);
$result = testGrokAPI($apiKey, $baseUrl, $model, $testPrompt);
$endTime = microtime(true);
$responseTime = round(($endTime - $startTime) * 1000, 2);

if ($result['success']) {
    echo "<div class='test-result success'>✅ API金鑰有效，連接成功！</div>";
    echo "<div class='test-result info'>⏱️ 回應時間: {$responseTime}ms</div>";
    
    $responseData = json_decode($result['response'], true);
    if ($responseData && isset($responseData['choices'][0]['message']['content'])) {
        $aiResponse = $responseData['choices'][0]['message']['content'];
        echo "<div class='test-result success'>🤖 AI回應: {$aiResponse}</div>";
        
        if (isset($responseData['usage'])) {
            $usage = $responseData['usage'];
            echo "<div class='test-result info'>📊 Token使用: 輸入 {$usage['prompt_tokens']}, 輸出 {$usage['completion_tokens']}, 總計 {$usage['total_tokens']}</div>";
        }
    }
} else {
    echo "<div class='test-result error'>❌ API調用失敗</div>";
    echo "<div class='test-result error'>HTTP狀態碼: {$result['httpCode']}</div>";
    
    if ($result['error']) {
        echo "<div class='test-result error'>cURL錯誤: {$result['error']}</div>";
    }
    
    if ($result['response']) {
        echo "<div class='test-result error'>API回應:</div>";
        echo "<div class='code'>" . htmlspecialchars($result['response']) . "</div>";
    }
}

// 測試4：功能測試
if ($result['success']) {
    echo "<h2>🧪 功能測試</h2>";
    
    $tests = [
        [
            'name' => '程式碼分析',
            'prompt' => '請分析這段Python程式碼：\n```python\ndef hello():\n    print("Hello World")\n```\n請給出簡短評價。'
        ],
        [
            'name' => '語法檢查',
            'prompt' => '檢查這段程式碼的語法錯誤：\n```python\ndef test(\n    print("error")\n```'
        ],
        [
            'name' => '學習協助',
            'prompt' => '我是Python初學者，如何開始學習迴圈？請給簡短建議。'
        ]
    ];
    
    foreach ($tests as $test) {
        echo "<h3>🔍 {$test['name']}</h3>";
        
        $startTime = microtime(true);
        $testResult = testGrokAPI($apiKey, $baseUrl, $model, $test['prompt']);
        $endTime = microtime(true);
        $testResponseTime = round(($endTime - $startTime) * 1000, 2);
        
        if ($testResult['success']) {
            $responseData = json_decode($testResult['response'], true);
            if ($responseData && isset($responseData['choices'][0]['message']['content'])) {
                $aiResponse = $responseData['choices'][0]['message']['content'];
                echo "<div class='test-result success'>✅ 測試成功 ({$testResponseTime}ms)</div>";
                echo "<div class='test-result info'>🤖 AI回應: " . nl2br(htmlspecialchars($aiResponse)) . "</div>";
            }
        } else {
            echo "<div class='test-result error'>❌ 測試失敗 (HTTP {$testResult['httpCode']})</div>";
        }
    }
}

// 測試總結
echo "<h2>📋 測試總結</h2>";

if ($result['success']) {
    echo "<div class='test-result success'>
        <h3>🎉 所有測試通過！</h3>
        <p>您的Grok API配置正確，可以開始使用真實AI助手系統。</p>
        <p><strong>下一步：</strong></p>
        <ul>
            <li>訪問 <code>real_ai_demo.html</code> 開始演示</li>
            <li>測試各種AI功能</li>
            <li>向客戶展示真實API調用</li>
        </ul>
    </div>";
} else {
    echo "<div class='test-result error'>
        <h3>❌ 測試失敗</h3>
        <p>請檢查以下項目：</p>
        <ul>
            <li>API金鑰是否正確</li>
            <li>網路連接是否正常</li>
            <li>xAI帳戶是否有足夠額度</li>
            <li>服務器防火牆設置</li>
        </ul>
    </div>";
}

echo "<div class='test-result info'>
    <h3>📞 需要幫助？</h3>
    <p>如果測試失敗，請檢查：</p>
    <ul>
        <li><strong>API金鑰</strong>: 確保從xAI控制台複製正確</li>
        <li><strong>帳戶餘額</strong>: 確保有足夠的API額度</li>
        <li><strong>網路設置</strong>: 確保可以訪問外部API</li>
        <li><strong>PHP配置</strong>: 確保cURL和JSON支援正常</li>
    </ul>
</div>";

echo "    </div>
</body>
</html>";
?> 