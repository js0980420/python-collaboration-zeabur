<?php
// 🎭 模擬AI助手回應系統 - 演示專用，無API成本
// 提供豐富的預設回應，讓客戶體驗完整功能

header('Content-Type: application/json; charset=utf-8');

class DemoAIResponses {
    private $codePatterns;
    private $responses;
    private $suggestions;
    private $errorFixes;
    
    public function __construct() {
        $this->initializeResponses();
    }
    
    private function initializeResponses() {
        // 程式碼模式匹配
        $this->codePatterns = [
            'hello_world' => [
                'pattern' => '/print\s*\(\s*["\'].*hello.*["\'].*\)/i',
                'responses' => [
                    '很好的開始！這是Python的基本輸出語句。',
                    '完美的Hello World程式！建議嘗試加入變數。',
                    '基礎語法正確！可以試試格式化字串：f"Hello, {name}!"'
                ]
            ],
            'variables' => [
                'pattern' => '/\w+\s*=\s*.+/',
                'responses' => [
                    '變數定義正確！Python的動態型別讓程式設計更靈活。',
                    '好的變數命名！建議使用有意義的變數名稱。',
                    '變數賦值語法正確！記得Python區分大小寫。'
                ]
            ],
            'functions' => [
                'pattern' => '/def\s+\w+\s*\([^)]*\)\s*:/',
                'responses' => [
                    '函數定義語法正確！記得在函數內容前加上縮排。',
                    '很好的函數結構！建議加入文檔字串說明功能。',
                    '函數定義完整！可以考慮加入型別提示提高可讀性。'
                ]
            ],
            'loops' => [
                'pattern' => '/(for\s+\w+\s+in\s+|while\s+.+:)/',
                'responses' => [
                    '迴圈語法正確！注意縮排和冒號。',
                    '很好的迴圈結構！記得考慮迴圈終止條件。',
                    '迴圈使用得當！可以考慮使用enumerate()獲取索引。'
                ]
            ],
            'conditionals' => [
                'pattern' => '/if\s+.+:/',
                'responses' => [
                    '條件判斷語法正確！記得使用elif和else處理其他情況。',
                    '很好的邏輯判斷！建議使用括號明確運算優先級。',
                    '條件語句完整！可以考慮使用三元運算符簡化簡單判斷。'
                ]
            ],
            'imports' => [
                'pattern' => '/import\s+\w+|from\s+\w+\s+import/',
                'responses' => [
                    '模組導入正確！Python豐富的標準庫讓開發更高效。',
                    '很好的模組使用！建議將導入語句放在檔案開頭。',
                    '導入語法正確！可以使用as關鍵字為模組取別名。'
                ]
            ]
        ];
        
        // 錯誤修復建議
        $this->errorFixes = [
            'syntax_error' => [
                'missing_colon' => [
                    'error' => '缺少冒號',
                    'fix' => '在if、for、while、def語句後加上冒號(:)',
                    'example' => "if x > 5:\n    print('x大於5')"
                ],
                'indentation' => [
                    'error' => '縮排錯誤',
                    'fix' => 'Python使用縮排表示程式碼區塊，建議使用4個空格',
                    'example' => "if True:\n    print('正確的縮排')"
                ],
                'parentheses' => [
                    'error' => '括號不匹配',
                    'fix' => '檢查所有開括號都有對應的閉括號',
                    'example' => "print('Hello World')"
                ]
            ],
            'name_error' => [
                'undefined_variable' => [
                    'error' => '變數未定義',
                    'fix' => '在使用變數前先定義它',
                    'example' => "name = 'Python'\nprint(name)"
                ]
            ],
            'type_error' => [
                'string_number' => [
                    'error' => '字串和數字運算',
                    'fix' => '使用str()或int()進行型別轉換',
                    'example' => "age = 25\nprint('我今年' + str(age) + '歲')"
                ]
            ]
        ];
        
        // 智能建議
        $this->suggestions = [
            'beginner' => [
                '嘗試使用變數儲存資料，讓程式更靈活',
                '學習使用函數組織程式碼，提高重用性',
                '練習使用迴圈處理重複性任務',
                '掌握條件判斷，讓程式能做出決策',
                '學習列表和字典，處理複雜資料結構'
            ],
            'intermediate' => [
                '考慮使用列表推導式簡化程式碼',
                '學習異常處理，讓程式更穩定',
                '使用模組組織大型程式',
                '掌握檔案操作，處理外部資料',
                '學習物件導向程式設計概念'
            ],
            'advanced' => [
                '使用生成器提高記憶體效率',
                '學習裝飾器增強函數功能',
                '掌握上下文管理器確保資源正確釋放',
                '使用型別提示提高程式碼可讀性',
                '學習非同步程式設計處理並發任務'
            ]
        ];
    }
    
    // 主要AI回應處理
    public function getAIResponse($action, $data) {
        switch ($action) {
            case 'analyze_code':
                return $this->analyzeCode($data['code']);
                
            case 'check_syntax':
                return $this->checkSyntax($data['code']);
                
            case 'help_stuck':
                return $this->helpStuck($data['code'], $data['stuck_duration'] ?? 0);
                
            case 'context_suggestion':
                return $this->getContextSuggestion($data['code'], $data['cursor_position'] ?? 0);
                
            case 'improve_code':
                return $this->improveCode($data['code']);
                
            case 'explain_concept':
                return $this->explainConcept($data['concept']);
                
            case 'debug_error':
                return $this->debugError($data['error_message'], $data['code']);
                
            default:
                return $this->getGeneralHelp();
        }
    }
    
    // 程式碼分析
    private function analyzeCode($code) {
        $analysis = [
            'score' => $this->calculateCodeScore($code),
            'errors' => $this->findErrors($code),
            'suggestions' => $this->getSuggestions($code),
            'improvements' => $this->getImprovements($code),
            'complexity' => $this->analyzeComplexity($code)
        ];
        
        return [
            'success' => true,
            'data' => $analysis,
            'message' => '程式碼分析完成'
        ];
    }
    
    // 語法檢查
    private function checkSyntax($code) {
        $errors = [];
        
        // 檢查常見語法錯誤
        if (preg_match('/(if|for|while|def|class)\s+[^:]*[^:]$/', $code)) {
            $errors[] = [
                'type' => 'syntax_error',
                'message' => '語句後缺少冒號',
                'line' => $this->findErrorLine($code, '缺少冒號'),
                'suggestion' => '在if、for、while、def語句後加上冒號(:)'
            ];
        }
        
        // 檢查括號匹配
        if (substr_count($code, '(') !== substr_count($code, ')')) {
            $errors[] = [
                'type' => 'syntax_error',
                'message' => '括號不匹配',
                'line' => 1,
                'suggestion' => '檢查所有開括號都有對應的閉括號'
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'errors' => $errors,
                'is_valid' => empty($errors)
            ]
        ];
    }
    
    // 卡住幫助
    private function helpStuck($code, $stuckDuration) {
        $suggestions = [
            '試著將問題分解成更小的步驟',
            '檢查變數名稱是否正確拼寫',
            '確認縮排是否一致',
            '使用print()語句調試程式執行流程',
            '查看錯誤訊息，它通常會指出問題所在',
            '嘗試在網上搜尋相關的Python教學',
            '考慮使用更簡單的方法實現相同功能'
        ];
        
        $codeExamples = [
            "# 調試技巧：使用print查看變數值\nprint(f'變數x的值是: {x}')",
            "# 分步驟解決問題\n# 步驟1: 獲取輸入\n# 步驟2: 處理資料\n# 步驟3: 輸出結果",
            "# 使用try-except處理錯誤\ntry:\n    # 你的程式碼\n    pass\nexcept Exception as e:\n    print(f'發生錯誤: {e}')"
        ];
        
        $suggestion = $suggestions[array_rand($suggestions)];
        $example = $codeExamples[array_rand($codeExamples)];
        
        return [
            'success' => true,
            'data' => [
                'suggestion' => $suggestion,
                'code_example' => $example,
                'encouragement' => '別擔心，每個程式設計師都會遇到困難！堅持下去，你一定能解決的！'
            ]
        ];
    }
    
    // 上下文建議
    private function getContextSuggestion($code, $cursorPosition) {
        $lines = explode("\n", $code);
        $currentLine = $this->getCurrentLine($code, $cursorPosition);
        $lineContent = $lines[$currentLine] ?? '';
        
        $suggestions = [];
        
        // 根據當前行內容提供建議
        if (strpos($lineContent, 'def ') !== false) {
            $suggestions[] = [
                'text' => '函數定義後記得加上文檔字串',
                'example' => 'def my_function():\n    """這個函數的功能說明"""\n    pass'
            ];
        } elseif (strpos($lineContent, 'for ') !== false) {
            $suggestions[] = [
                'text' => '可以使用enumerate()同時獲取索引和值',
                'example' => 'for i, item in enumerate(my_list):\n    print(f"索引{i}: {item}")'
            ];
        } elseif (strpos($lineContent, 'if ') !== false) {
            $suggestions[] = [
                'text' => '記得處理else情況',
                'example' => 'if condition:\n    # 條件為真時執行\nelse:\n    # 條件為假時執行'
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'suggestions' => $suggestions,
                'context' => '基於當前程式碼位置的智能建議'
            ]
        ];
    }
    
    // 程式碼改進
    private function improveCode($code) {
        $improvements = [
            [
                'type' => 'style',
                'message' => '建議使用更有意義的變數名稱',
                'example' => '使用 student_name 而不是 n'
            ],
            [
                'type' => 'efficiency',
                'message' => '可以使用列表推導式簡化程式碼',
                'example' => 'squares = [x**2 for x in range(10)]'
            ],
            [
                'type' => 'readability',
                'message' => '加入註解說明複雜邏輯',
                'example' => '# 計算平均分數\naverage = sum(scores) / len(scores)'
            ]
        ];
        
        return [
            'success' => true,
            'data' => [
                'improvements' => $improvements,
                'overall_score' => rand(70, 95)
            ]
        ];
    }
    
    // 概念解釋
    private function explainConcept($concept) {
        $explanations = [
            'variables' => [
                'title' => '變數 (Variables)',
                'explanation' => '變數是用來儲存資料的容器。在Python中，你可以直接賦值給變數，不需要事先宣告型別。',
                'example' => "name = 'Alice'  # 字串變數\nage = 25        # 整數變數\nheight = 165.5  # 浮點數變數"
            ],
            'functions' => [
                'title' => '函數 (Functions)',
                'explanation' => '函數是可重複使用的程式碼區塊，可以接收參數並返回結果。',
                'example' => "def greet(name):\n    return f'Hello, {name}!'\n\n# 呼叫函數\nmessage = greet('Alice')\nprint(message)"
            ],
            'loops' => [
                'title' => '迴圈 (Loops)',
                'explanation' => '迴圈用來重複執行程式碼。Python有for迴圈和while迴圈兩種。',
                'example' => "# for迴圈\nfor i in range(5):\n    print(i)\n\n# while迴圈\ncount = 0\nwhile count < 5:\n    print(count)\n    count += 1"
            ]
        ];
        
        $explanation = $explanations[$concept] ?? [
            'title' => '程式設計概念',
            'explanation' => '這是一個重要的程式設計概念，建議查閱相關文檔深入學習。',
            'example' => '# 範例程式碼\nprint("學習程式設計需要持續練習！")'
        ];
        
        return [
            'success' => true,
            'data' => $explanation
        ];
    }
    
    // 錯誤調試
    private function debugError($errorMessage, $code) {
        $commonErrors = [
            'NameError' => [
                'cause' => '變數名稱錯誤或變數未定義',
                'solution' => '檢查變數名稱拼寫，確保變數在使用前已定義',
                'example' => "# 錯誤\nprint(name)  # name未定義\n\n# 正確\nname = 'Alice'\nprint(name)"
            ],
            'SyntaxError' => [
                'cause' => '語法錯誤，通常是拼寫錯誤或缺少符號',
                'solution' => '檢查語法，特別注意冒號、括號和縮排',
                'example' => "# 錯誤\nif x > 5\n    print('大於5')\n\n# 正確\nif x > 5:\n    print('大於5')"
            ],
            'IndentationError' => [
                'cause' => '縮排錯誤',
                'solution' => 'Python使用縮排表示程式碼區塊，保持一致的縮排',
                'example' => "# 正確的縮排\nif True:\n    print('這是正確的縮排')\n    print('使用4個空格')"
            ]
        ];
        
        $errorType = 'SyntaxError'; // 預設錯誤類型
        foreach ($commonErrors as $type => $info) {
            if (strpos($errorMessage, $type) !== false) {
                $errorType = $type;
                break;
            }
        }
        
        return [
            'success' => true,
            'data' => $commonErrors[$errorType]
        ];
    }
    
    // 一般幫助
    private function getGeneralHelp() {
        $tips = [
            '記住Python區分大小寫，Name和name是不同的變數',
            '使用有意義的變數名稱，讓程式碼更易讀',
            '保持一致的縮排，建議使用4個空格',
            '多使用註解說明程式碼功能',
            '遇到錯誤時仔細閱讀錯誤訊息',
            '多練習，程式設計需要大量實作',
            '善用Python的內建函數和標準庫'
        ];
        
        return [
            'success' => true,
            'data' => [
                'tip' => $tips[array_rand($tips)],
                'message' => '持續學習，你會成為優秀的程式設計師！'
            ]
        ];
    }
    
    // 輔助方法
    private function calculateCodeScore($code) {
        $score = 50; // 基礎分數
        
        // 根據程式碼特徵加分
        if (preg_match('/def\s+\w+/', $code)) $score += 15; // 有函數定義
        if (preg_match('/if\s+.+:/', $code)) $score += 10;  // 有條件判斷
        if (preg_match('/for\s+.+:/', $code)) $score += 10; // 有迴圈
        if (preg_match('/#.*/', $code)) $score += 5;        // 有註解
        if (strlen($code) > 100) $score += 10;              // 程式碼長度
        
        return min($score, 100); // 最高100分
    }
    
    private function findErrors($code) {
        $errors = [];
        
        // 簡單的錯誤檢測
        if (preg_match('/print\s*\([^)]*[^)]$/', $code)) {
            $errors[] = [
                'type' => 'syntax',
                'message' => 'print語句括號不完整',
                'line' => 1
            ];
        }
        
        return $errors;
    }
    
    private function getSuggestions($code) {
        $level = $this->determineLevel($code);
        return array_slice($this->suggestions[$level], 0, 3);
    }
    
    private function getImprovements($code) {
        return [
            '考慮加入錯誤處理機制',
            '使用更具描述性的變數名稱',
            '加入適當的註解說明'
        ];
    }
    
    private function analyzeComplexity($code) {
        $lines = count(explode("\n", trim($code)));
        $functions = preg_match_all('/def\s+\w+/', $code);
        $conditions = preg_match_all('/(if|elif|else)/', $code);
        
        if ($lines < 10 && $functions == 0) return 'simple';
        if ($lines < 50 && $functions <= 2) return 'moderate';
        return 'complex';
    }
    
    private function determineLevel($code) {
        if (preg_match('/class\s+\w+|import\s+\w+/', $code)) return 'advanced';
        if (preg_match('/def\s+\w+|for\s+.+:|while\s+.+:/', $code)) return 'intermediate';
        return 'beginner';
    }
    
    private function findErrorLine($code, $errorType) {
        return rand(1, max(1, count(explode("\n", $code))));
    }
    
    private function getCurrentLine($code, $position) {
        return substr_count(substr($code, 0, $position), "\n");
    }
}

// 處理請求
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'general_help';

$aiDemo = new DemoAIResponses();
$response = $aiDemo->getAIResponse($action, $input);

// 添加演示標記
$response['demo_mode'] = true;
$response['message'] = ($response['message'] ?? '') . ' (演示模式)';

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 