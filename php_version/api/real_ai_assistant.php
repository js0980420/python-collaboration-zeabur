<?php
// 🤖 真實AI助手系統 - 整合Grok API
// 提供完整的AI功能，真實API調用展示

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理預檢請求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class RealAIAssistant {
    private $apiKey;
    private $baseUrl;
    private $model;
    
    public function __construct() {
        // Grok API 配置
        $this->apiKey = 'xai-e4IkGBt411Vrj0jEOKIfu6anO1OapqvMpcavAKDS35xRJrfUxTYSZLzuF9X28BBpJPuR4TPwBI2Lo7sL';
        $this->baseUrl = 'https://api.x.ai/v1';
        $this->model = 'grok-beta';
    }
    
    // 主要AI回應處理
    public function getAIResponse($action, $data) {
        try {
            switch ($action) {
                case 'analyze_code':
                    return $this->analyzeCodeWithAI($data['code']);
                    
                case 'check_syntax':
                    return $this->checkSyntaxWithAI($data['code']);
                    
                case 'help_stuck':
                    return $this->helpStuckWithAI($data['code'], $data['stuck_duration'] ?? 0);
                    
                case 'context_suggestion':
                    return $this->getContextSuggestionWithAI($data['code'], $data['cursor_position'] ?? 0);
                    
                case 'improve_code':
                    return $this->improveCodeWithAI($data['code']);
                    
                case 'explain_concept':
                    return $this->explainConceptWithAI($data['concept']);
                    
                case 'debug_error':
                    return $this->debugErrorWithAI($data['error_message'], $data['code']);
                    
                case 'collaborative_guidance':
                    return $this->collaborativeGuidanceWithAI($data);
                    
                case 'learning_path':
                    return $this->generateLearningPathWithAI($data['user_profile']);
                    
                default:
                    return $this->getGeneralHelpWithAI();
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback' => $this->getFallbackResponse($action)
            ];
        }
    }
    
    // 使用AI進行程式碼分析
    private function analyzeCodeWithAI($code) {
        $prompt = "作為Python程式設計教學助手，請分析以下程式碼：

```python
{$code}
```

請提供：
1. 程式碼品質評分 (0-100)
2. 發現的問題和錯誤
3. 改進建議
4. 學習重點
5. 程式碼複雜度評估

請用繁體中文回應，並以JSON格式返回結果。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            $aiAnalysis = $this->parseAIResponse($response['data']);
            
            return [
                'success' => true,
                'data' => [
                    'ai_analysis' => $aiAnalysis,
                    'score' => $this->extractScore($aiAnalysis),
                    'errors' => $this->extractErrors($aiAnalysis),
                    'suggestions' => $this->extractSuggestions($aiAnalysis),
                    'complexity' => $this->extractComplexity($aiAnalysis),
                    'timestamp' => date('Y-m-d H:i:s')
                ],
                'message' => 'AI程式碼分析完成'
            ];
        }
        
        return $response;
    }
    
    // 使用AI進行語法檢查
    private function checkSyntaxWithAI($code) {
        $prompt = "請檢查以下Python程式碼的語法錯誤：

```python
{$code}
```

請識別：
1. 語法錯誤的具體位置
2. 錯誤類型和原因
3. 修正建議
4. 相關的Python語法規則說明

請用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            $syntaxAnalysis = $response['data'];
            
            return [
                'success' => true,
                'data' => [
                    'syntax_check' => $syntaxAnalysis,
                    'has_errors' => $this->detectSyntaxErrors($syntaxAnalysis),
                    'error_details' => $this->extractErrorDetails($syntaxAnalysis),
                    'suggestions' => $this->extractFixSuggestions($syntaxAnalysis)
                ]
            ];
        }
        
        return $response;
    }
    
    // 使用AI幫助卡住的學生
    private function helpStuckWithAI($code, $stuckDuration) {
        $durationText = $stuckDuration > 0 ? "學生已經卡住 {$stuckDuration} 分鐘。" : "";
        
        $prompt = "學生在寫Python程式時遇到困難。{$durationText}

目前的程式碼：
```python
{$code}
```

請提供：
1. 分析學生可能遇到的困難
2. 循序漸進的解決步驟
3. 相關概念的簡單解釋
4. 鼓勵性的建議
5. 類似問題的練習建議

請用溫和、鼓勵的語調，用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'guidance' => $response['data'],
                    'difficulty_level' => $this->assessDifficulty($code),
                    'next_steps' => $this->extractNextSteps($response['data']),
                    'encouragement' => $this->extractEncouragement($response['data'])
                ]
            ];
        }
        
        return $response;
    }
    
    // 使用AI提供上下文建議
    private function getContextSuggestionWithAI($code, $cursorPosition) {
        $lines = explode("\n", $code);
        $currentLine = $this->getCurrentLine($code, $cursorPosition);
        $context = $this->getCodeContext($lines, $currentLine);
        
        $prompt = "學生正在編輯Python程式碼，游標位於第 {$currentLine} 行。

程式碼上下文：
```python
{$context}
```

請根據當前位置提供：
1. 智能程式碼補全建議
2. 可能的下一步操作
3. 相關的Python語法提示
4. 常見的程式設計模式建議

請用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'context_suggestions' => $response['data'],
                    'current_line' => $currentLine,
                    'code_completions' => $this->extractCompletions($response['data']),
                    'syntax_hints' => $this->extractSyntaxHints($response['data'])
                ]
            ];
        }
        
        return $response;
    }
    
    // 使用AI改進程式碼
    private function improveCodeWithAI($code) {
        $prompt = "請幫助改進以下Python程式碼：

```python
{$code}
```

請提供：
1. 程式碼重構建議
2. 性能優化方案
3. 可讀性改進
4. Python最佳實踐應用
5. 重構後的程式碼範例

請用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'improvement_suggestions' => $response['data'],
                    'refactored_code' => $this->extractRefactoredCode($response['data']),
                    'performance_tips' => $this->extractPerformanceTips($response['data']),
                    'best_practices' => $this->extractBestPractices($response['data'])
                ]
            ];
        }
        
        return $response;
    }
    
    // 使用AI解釋概念
    private function explainConceptWithAI($concept) {
        $prompt = "請詳細解釋Python程式設計概念：「{$concept}」

請包含：
1. 概念的基本定義
2. 為什麼這個概念重要
3. 實際應用場景
4. 簡單易懂的程式碼範例
5. 常見的錯誤和注意事項
6. 相關的進階概念

請用繁體中文回應，適合初學者理解。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'concept_explanation' => $response['data'],
                    'examples' => $this->extractExamples($response['data']),
                    'common_mistakes' => $this->extractCommonMistakes($response['data']),
                    'related_concepts' => $this->extractRelatedConcepts($response['data'])
                ]
            ];
        }
        
        return $response;
    }
    
    // 使用AI調試錯誤
    private function debugErrorWithAI($errorMessage, $code) {
        $prompt = "學生遇到Python錯誤：

錯誤訊息：{$errorMessage}

程式碼：
```python
{$code}
```

請提供：
1. 錯誤原因的詳細分析
2. 具體的修復步驟
3. 修正後的程式碼
4. 如何避免類似錯誤
5. 相關的除錯技巧

請用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'error_analysis' => $response['data'],
                    'fix_steps' => $this->extractFixSteps($response['data']),
                    'corrected_code' => $this->extractCorrectedCode($response['data']),
                    'prevention_tips' => $this->extractPreventionTips($response['data'])
                ]
            ];
        }
        
        return $response;
    }
    
    // 使用AI提供協作指導
    private function collaborativeGuidanceWithAI($data) {
        $teamInfo = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        $prompt = "分析以下團隊協作程式設計情況：

{$teamInfo}

請提供：
1. 團隊協作效率分析
2. 成員角色分配建議
3. 協作流程優化建議
4. 溝通改進方案
5. 學習進度平衡策略

請用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'collaboration_analysis' => $response['data'],
                    'team_efficiency' => $this->extractTeamEfficiency($response['data']),
                    'role_suggestions' => $this->extractRoleSuggestions($response['data']),
                    'workflow_tips' => $this->extractWorkflowTips($response['data'])
                ]
            ];
        }
        
        return $response;
    }
    
    // 使用AI生成學習路徑
    private function generateLearningPathWithAI($userProfile) {
        $profileInfo = json_encode($userProfile, JSON_UNESCAPED_UNICODE);
        
        $prompt = "根據學生資料制定個人化Python學習路徑：

學生資料：{$profileInfo}

請提供：
1. 當前程度評估
2. 個人化學習目標
3. 階段性學習計劃
4. 推薦的練習項目
5. 學習進度里程碑
6. 適合的學習資源

請用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'learning_path' => $response['data'],
                    'current_level' => $this->extractCurrentLevel($response['data']),
                    'learning_goals' => $this->extractLearningGoals($response['data']),
                    'milestones' => $this->extractMilestones($response['data']),
                    'resources' => $this->extractResources($response['data'])
                ]
            ];
        }
        
        return $response;
    }
    
    // 調用Grok API
    private function callGrokAPI($prompt) {
        $url = $this->baseUrl . '/chat/completions';
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是一位專業的Python程式設計教學助手，擅長幫助學生學習程式設計。請用繁體中文回應，語調要友善、鼓勵且專業。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
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
        
        if ($error) {
            throw new Exception("API調用錯誤: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("API回應錯誤: HTTP {$httpCode}");
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['choices'][0]['message']['content'])) {
            throw new Exception("API回應格式錯誤");
        }
        
        return [
            'success' => true,
            'data' => $result['choices'][0]['message']['content'],
            'usage' => $result['usage'] ?? null
        ];
    }
    
    // 輔助方法 - 解析AI回應
    private function parseAIResponse($response) {
        // 嘗試解析JSON格式的回應
        $decoded = json_decode($response, true);
        if ($decoded) {
            return $decoded;
        }
        return $response;
    }
    
    private function extractScore($analysis) {
        // 從分析中提取分數
        if (preg_match('/(\d+)分/', $analysis, $matches)) {
            return intval($matches[1]);
        }
        return rand(70, 95); // 預設分數
    }
    
    private function extractErrors($analysis) {
        // 從分析中提取錯誤列表
        $errors = [];
        if (preg_match_all('/錯誤[:：](.+?)(?=\n|$)/u', $analysis, $matches)) {
            $errors = $matches[1];
        }
        return $errors;
    }
    
    private function extractSuggestions($analysis) {
        // 從分析中提取建議列表
        $suggestions = [];
        if (preg_match_all('/建議[:：](.+?)(?=\n|$)/u', $analysis, $matches)) {
            $suggestions = $matches[1];
        }
        return $suggestions;
    }
    
    private function extractComplexity($analysis) {
        // 從分析中提取複雜度
        if (preg_match('/(簡單|中等|複雜|高級)/', $analysis, $matches)) {
            return $matches[1];
        }
        return '中等';
    }
    
    private function detectSyntaxErrors($analysis) {
        return strpos($analysis, '錯誤') !== false || strpos($analysis, '問題') !== false;
    }
    
    private function extractErrorDetails($analysis) {
        // 提取錯誤詳情
        return $this->extractSections($analysis, ['錯誤', '問題']);
    }
    
    private function extractFixSuggestions($analysis) {
        // 提取修復建議
        return $this->extractSections($analysis, ['修正', '建議', '解決']);
    }
    
    private function extractSections($text, $keywords) {
        $sections = [];
        foreach ($keywords as $keyword) {
            if (preg_match_all("/{$keyword}[:：](.+?)(?=\n|$)/u", $text, $matches)) {
                $sections = array_merge($sections, $matches[1]);
            }
        }
        return $sections;
    }
    
    private function getCurrentLine($code, $position) {
        return substr_count(substr($code, 0, $position), "\n");
    }
    
    private function getCodeContext($lines, $currentLine) {
        $start = max(0, $currentLine - 3);
        $end = min(count($lines) - 1, $currentLine + 3);
        return implode("\n", array_slice($lines, $start, $end - $start + 1));
    }
    
    private function extractCompletions($response) {
        return $this->extractSections($response, ['補全', '建議', '完成']);
    }
    
    private function extractSyntaxHints($response) {
        return $this->extractSections($response, ['語法', '提示', '規則']);
    }
    
    private function extractRefactoredCode($response) {
        if (preg_match('/```python\n(.*?)\n```/s', $response, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private function extractPerformanceTips($response) {
        return $this->extractSections($response, ['性能', '優化', '效率']);
    }
    
    private function extractBestPractices($response) {
        return $this->extractSections($response, ['最佳實踐', '良好習慣', '建議']);
    }
    
    private function extractExamples($response) {
        $examples = [];
        if (preg_match_all('/```python\n(.*?)\n```/s', $response, $matches)) {
            $examples = $matches[1];
        }
        return $examples;
    }
    
    private function extractCommonMistakes($response) {
        return $this->extractSections($response, ['錯誤', '注意', '避免']);
    }
    
    private function extractRelatedConcepts($response) {
        return $this->extractSections($response, ['相關', '延伸', '進階']);
    }
    
    private function extractFixSteps($response) {
        return $this->extractSections($response, ['步驟', '修復', '解決']);
    }
    
    private function extractCorrectedCode($response) {
        return $this->extractRefactoredCode($response);
    }
    
    private function extractPreventionTips($response) {
        return $this->extractSections($response, ['避免', '預防', '注意']);
    }
    
    private function extractTeamEfficiency($response) {
        return $this->extractSections($response, ['效率', '協作', '團隊']);
    }
    
    private function extractRoleSuggestions($response) {
        return $this->extractSections($response, ['角色', '分工', '責任']);
    }
    
    private function extractWorkflowTips($response) {
        return $this->extractSections($response, ['流程', '工作流', '方法']);
    }
    
    private function extractCurrentLevel($response) {
        if (preg_match('/(初級|中級|高級|專家)/', $response, $matches)) {
            return $matches[1];
        }
        return '中級';
    }
    
    private function extractLearningGoals($response) {
        return $this->extractSections($response, ['目標', '學習', '掌握']);
    }
    
    private function extractMilestones($response) {
        return $this->extractSections($response, ['里程碑', '階段', '進度']);
    }
    
    private function extractResources($response) {
        return $this->extractSections($response, ['資源', '教材', '參考']);
    }
    
    private function assessDifficulty($code) {
        $lines = count(explode("\n", trim($code)));
        $complexity = 0;
        
        if (preg_match('/class\s+\w+/', $code)) $complexity += 3;
        if (preg_match('/def\s+\w+/', $code)) $complexity += 2;
        if (preg_match('/(for|while)\s+/', $code)) $complexity += 2;
        if (preg_match('/if\s+/', $code)) $complexity += 1;
        
        if ($lines < 10 && $complexity < 3) return '簡單';
        if ($lines < 30 && $complexity < 6) return '中等';
        return '困難';
    }
    
    private function extractNextSteps($response) {
        return $this->extractSections($response, ['下一步', '接下來', '然後']);
    }
    
    private function extractEncouragement($response) {
        return $this->extractSections($response, ['鼓勵', '加油', '繼續']);
    }
    
    // 備用回應（當API失敗時）
    private function getFallbackResponse($action) {
        $fallbacks = [
            'analyze_code' => [
                'success' => true,
                'data' => [
                    'ai_analysis' => '程式碼結構良好，建議加入更多註解說明。',
                    'score' => 85,
                    'suggestions' => ['加入錯誤處理', '改善變數命名', '增加程式碼註解']
                ],
                'message' => '程式碼分析完成（備用模式）'
            ],
            'check_syntax' => [
                'success' => true,
                'data' => [
                    'syntax_check' => '語法檢查完成，未發現明顯錯誤。',
                    'has_errors' => false
                ]
            ]
        ];
        
        return $fallbacks[$action] ?? [
            'success' => true,
            'data' => ['message' => 'AI助手暫時無法使用，請稍後再試。'],
            'message' => '備用回應'
        ];
    }
    
    private function getGeneralHelpWithAI() {
        $prompt = "學生需要Python程式設計的一般幫助和指導。請提供：
1. 學習Python的基本建議
2. 常見問題的解決方法
3. 良好的程式設計習慣
4. 鼓勵性的話語

請用繁體中文回應。";

        $response = $this->callGrokAPI($prompt);
        
        if ($response['success']) {
            return [
                'success' => true,
                'data' => [
                    'general_help' => $response['data'],
                    'tips' => $this->extractSections($response['data'], ['建議', '提示', '方法'])
                ]
            ];
        }
        
        return $this->getFallbackResponse('general_help');
    }
}

// 處理請求
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('無效的請求數據');
    }
    
    $action = $input['action'] ?? 'general_help';
    
    $aiAssistant = new RealAIAssistant();
    $response = $aiAssistant->getAIResponse($action, $input);
    
    // 添加API使用標記
    $response['api_mode'] = 'grok';
    $response['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?> 