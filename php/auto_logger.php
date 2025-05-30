<?php
/**
 * 🤖 自動化修改記錄器
 * 監控文件變化並自動記錄到教學系統
 * 
 * @author AI Assistant
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'change_tracker.php';

class AutoLogger {
    private $tracker;
    private $watchedDirectories;
    private $fileHashes;
    private $logFile;
    private $configFile;
    
    public function __construct() {
        $this->configFile = 'auto_logger_config.json';
        $this->logFile = '../docs/changes/auto_log_' . date('Y-m-d') . '.log';
        $this->loadConfig();
        $this->initializeTracker();
        $this->loadFileHashes();
    }
    
    /**
     * 載入配置
     */
    private function loadConfig() {
        $defaultConfig = [
            'watched_directories' => [
                '../php/',
                '../mysql/',
                '../websocket_server/',
                '../docs/',
                '../assets/'
            ],
            'ignored_extensions' => ['.log', '.tmp', '.cache', '.lock'],
            'ignored_files' => ['.DS_Store', 'Thumbs.db', '.gitignore'],
            'scan_interval' => 30, // 秒
            'auto_commit' => false,
            'notification_webhook' => '',
            'max_file_size' => 1048576 // 1MB
        ];
        
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            $this->config = array_merge($defaultConfig, $config);
        } else {
            $this->config = $defaultConfig;
            $this->saveConfig();
        }
        
        $this->watchedDirectories = $this->config['watched_directories'];
    }
    
    /**
     * 保存配置
     */
    private function saveConfig() {
        file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 初始化追蹤器
     */
    private function initializeTracker() {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=python_teaching', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->tracker = new ChangeTracker($pdo);
        } catch (Exception $e) {
            $this->log("資料庫連接失敗: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * 載入文件哈希
     */
    private function loadFileHashes() {
        $hashFile = 'file_hashes.json';
        if (file_exists($hashFile)) {
            $this->fileHashes = json_decode(file_get_contents($hashFile), true) ?: [];
        } else {
            $this->fileHashes = [];
            $this->scanAllFiles();
        }
    }
    
    /**
     * 保存文件哈希
     */
    private function saveFileHashes() {
        file_put_contents('file_hashes.json', json_encode($this->fileHashes, JSON_PRETTY_PRINT));
    }
    
    /**
     * 掃描所有文件
     */
    private function scanAllFiles() {
        foreach ($this->watchedDirectories as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectory($dir);
            }
        }
        $this->saveFileHashes();
    }
    
    /**
     * 掃描目錄
     */
    private function scanDirectory($dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldWatchFile($file->getPathname())) {
                $this->fileHashes[$file->getPathname()] = [
                    'hash' => md5_file($file->getPathname()),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime()
                ];
            }
        }
    }
    
    /**
     * 檢查是否應該監控文件
     */
    private function shouldWatchFile($filePath) {
        // 檢查文件大小
        if (filesize($filePath) > $this->config['max_file_size']) {
            return false;
        }
        
        // 檢查擴展名
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array('.' . $extension, $this->config['ignored_extensions'])) {
            return false;
        }
        
        // 檢查文件名
        $filename = basename($filePath);
        if (in_array($filename, $this->config['ignored_files'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 開始監控
     */
    public function startWatching() {
        $this->log("開始監控文件變化...", 'INFO');
        
        while (true) {
            try {
                $this->checkForChanges();
                sleep($this->config['scan_interval']);
            } catch (Exception $e) {
                $this->log("監控過程中發生錯誤: " . $e->getMessage(), 'ERROR');
                sleep(60); // 錯誤後等待1分鐘再繼續
            }
        }
    }
    
    /**
     * 檢查文件變化
     */
    private function checkForChanges() {
        $changes = [];
        
        foreach ($this->watchedDirectories as $dir) {
            if (is_dir($dir)) {
                $changes = array_merge($changes, $this->checkDirectoryChanges($dir));
            }
        }
        
        if (!empty($changes)) {
            $this->processChanges($changes);
        }
    }
    
    /**
     * 檢查目錄變化
     */
    private function checkDirectoryChanges($dir) {
        $changes = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $currentFiles = [];
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldWatchFile($file->getPathname())) {
                $filePath = $file->getPathname();
                $currentHash = md5_file($filePath);
                $currentSize = $file->getSize();
                $currentModified = $file->getMTime();
                
                $currentFiles[$filePath] = true;
                
                if (!isset($this->fileHashes[$filePath])) {
                    // 新文件
                    $changes[] = [
                        'type' => '新增',
                        'file' => $filePath,
                        'size_before' => 0,
                        'size_after' => $currentSize,
                        'hash_before' => '',
                        'hash_after' => $currentHash
                    ];
                    
                    $this->fileHashes[$filePath] = [
                        'hash' => $currentHash,
                        'size' => $currentSize,
                        'modified' => $currentModified
                    ];
                    
                } elseif ($this->fileHashes[$filePath]['hash'] !== $currentHash) {
                    // 文件修改
                    $changes[] = [
                        'type' => '修改',
                        'file' => $filePath,
                        'size_before' => $this->fileHashes[$filePath]['size'],
                        'size_after' => $currentSize,
                        'hash_before' => $this->fileHashes[$filePath]['hash'],
                        'hash_after' => $currentHash
                    ];
                    
                    $this->fileHashes[$filePath] = [
                        'hash' => $currentHash,
                        'size' => $currentSize,
                        'modified' => $currentModified
                    ];
                }
            }
        }
        
        // 檢查刪除的文件
        foreach ($this->fileHashes as $filePath => $info) {
            if (strpos($filePath, $dir) === 0 && !isset($currentFiles[$filePath]) && !file_exists($filePath)) {
                $changes[] = [
                    'type' => '刪除',
                    'file' => $filePath,
                    'size_before' => $info['size'],
                    'size_after' => 0,
                    'hash_before' => $info['hash'],
                    'hash_after' => ''
                ];
                
                unset($this->fileHashes[$filePath]);
            }
        }
        
        return $changes;
    }
    
    /**
     * 處理變化
     */
    private function processChanges($changes) {
        foreach ($changes as $change) {
            $this->recordChange($change);
        }
        
        $this->saveFileHashes();
        
        // 發送通知
        if (!empty($this->config['notification_webhook'])) {
            $this->sendNotification($changes);
        }
    }
    
    /**
     * 記錄變化
     */
    private function recordChange($change) {
        try {
            $filePath = $change['file'];
            $changeType = $change['type'];
            
            // 分析變化內容
            $analysis = $this->analyzeChange($change);
            
            // 記錄到追蹤系統
            $changeId = $this->tracker->logChange(
                $changeType,
                $filePath,
                $analysis['reason'],
                $analysis['content'],
                $analysis['test_result'],
                $analysis['teaching_value'],
                'AutoLogger'
            );
            
            $this->log("記錄變化: {$changeType} - {$filePath} (ID: {$changeId})", 'INFO');
            
            // 如果是bug相關的修改，嘗試記錄bug修復
            if ($this->isBugFix($analysis)) {
                $this->recordBugFix($analysis, $filePath);
            }
            
        } catch (Exception $e) {
            $this->log("記錄變化失敗: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * 分析變化
     */
    private function analyzeChange($change) {
        $filePath = $change['file'];
        $changeType = $change['type'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $analysis = [
            'reason' => '自動檢測到文件變化',
            'content' => '',
            'test_result' => '待測試',
            'teaching_value' => ''
        ];
        
        // 根據文件類型分析
        switch ($extension) {
            case 'php':
                $analysis = $this->analyzePHPChange($change);
                break;
            case 'js':
                $analysis = $this->analyzeJSChange($change);
                break;
            case 'sql':
                $analysis = $this->analyzeSQLChange($change);
                break;
            case 'md':
                $analysis = $this->analyzeMarkdownChange($change);
                break;
            case 'html':
                $analysis = $this->analyzeHTMLChange($change);
                break;
            case 'css':
                $analysis = $this->analyzeCSSChange($change);
                break;
            default:
                $analysis['content'] = "文件 {$filePath} 發生 {$changeType} 變化";
        }
        
        // 添加通用信息
        $analysis['content'] .= "\n\n**變化詳情:**\n";
        $analysis['content'] .= "- 變化類型: {$changeType}\n";
        $analysis['content'] .= "- 文件大小: {$change['size_before']} → {$change['size_after']} bytes\n";
        $analysis['content'] .= "- 檢測時間: " . date('Y-m-d H:i:s') . "\n";
        
        return $analysis;
    }
    
    /**
     * 分析PHP文件變化
     */
    private function analyzePHPChange($change) {
        $filePath = $change['file'];
        $changeType = $change['type'];
        
        $analysis = [
            'reason' => 'PHP代碼更新',
            'content' => '',
            'test_result' => '需要進行語法檢查和功能測試',
            'teaching_value' => 'PHP開發實踐案例'
        ];
        
        if ($changeType === '新增') {
            $analysis['reason'] = '新增PHP功能模組';
            $analysis['content'] = "新增PHP文件: {$filePath}\n\n這個新文件可能包含:\n- 新的類或函數\n- 業務邏輯實現\n- API端點\n- 資料處理邏輯";
            $analysis['teaching_value'] = '展示PHP項目結構和模組化開發';
        } elseif ($changeType === '修改') {
            $analysis['reason'] = 'PHP代碼優化或bug修復';
            $analysis['content'] = "修改PHP文件: {$filePath}\n\n可能的修改內容:\n- 修復邏輯錯誤\n- 性能優化\n- 新增功能\n- 代碼重構";
            $analysis['teaching_value'] = '展示代碼維護和優化過程';
        } elseif ($changeType === '刪除') {
            $analysis['reason'] = '移除過時的PHP代碼';
            $analysis['content'] = "刪除PHP文件: {$filePath}\n\n可能原因:\n- 功能已廢棄\n- 代碼重構\n- 安全性考量";
            $analysis['teaching_value'] = '展示代碼清理和項目維護';
        }
        
        // 嘗試進行語法檢查
        if ($changeType !== '刪除' && file_exists($filePath)) {
            $syntaxCheck = $this->checkPHPSyntax($filePath);
            $analysis['test_result'] = $syntaxCheck['valid'] ? '語法檢查通過' : '語法錯誤: ' . $syntaxCheck['error'];
        }
        
        return $analysis;
    }
    
    /**
     * 分析JavaScript文件變化
     */
    private function analyzeJSChange($change) {
        $filePath = $change['file'];
        $changeType = $change['type'];
        
        return [
            'reason' => 'JavaScript前端代碼更新',
            'content' => "JavaScript文件變化: {$filePath}\n\n前端功能可能涉及:\n- 用戶界面交互\n- AJAX請求處理\n- 數據視覺化\n- 即時通信功能",
            'test_result' => '需要在瀏覽器中測試功能',
            'teaching_value' => '前端開發和用戶體驗設計案例'
        ];
    }
    
    /**
     * 分析SQL文件變化
     */
    private function analyzeSQLChange($change) {
        $filePath = $change['file'];
        $changeType = $change['type'];
        
        return [
            'reason' => '資料庫結構或數據更新',
            'content' => "SQL文件變化: {$filePath}\n\n可能包含:\n- 資料表結構變更\n- 索引優化\n- 數據遷移\n- 查詢優化",
            'test_result' => '需要在測試資料庫中驗證',
            'teaching_value' => '資料庫設計和SQL優化教學'
        ];
    }
    
    /**
     * 分析Markdown文件變化
     */
    private function analyzeMarkdownChange($change) {
        $filePath = $change['file'];
        $changeType = $change['type'];
        
        return [
            'reason' => '文檔更新',
            'content' => "文檔變化: {$filePath}\n\n文檔更新可能包含:\n- 新增教學內容\n- 修正錯誤信息\n- 更新操作指南\n- 改進說明文字",
            'test_result' => '文檔內容已更新',
            'teaching_value' => '技術文檔撰寫和維護示範'
        ];
    }
    
    /**
     * 分析HTML文件變化
     */
    private function analyzeHTMLChange($change) {
        return [
            'reason' => '用戶界面更新',
            'content' => "HTML文件變化: {$change['file']}\n\n界面更新可能包含:\n- 新增頁面元素\n- 修改布局結構\n- 優化用戶體驗\n- 修復顯示問題",
            'test_result' => '需要檢查頁面顯示效果',
            'teaching_value' => 'Web前端開發和UI設計'
        ];
    }
    
    /**
     * 分析CSS文件變化
     */
    private function analyzeCSSChange($change) {
        return [
            'reason' => '樣式和布局調整',
            'content' => "CSS文件變化: {$change['file']}\n\n樣式更新可能包含:\n- 視覺效果改進\n- 響應式設計調整\n- 顏色和字體優化\n- 動畫效果添加",
            'test_result' => '需要檢查各種設備上的顯示效果',
            'teaching_value' => 'CSS設計和響應式布局教學'
        ];
    }
    
    /**
     * 檢查PHP語法
     */
    private function checkPHPSyntax($filePath) {
        $output = [];
        $returnCode = 0;
        
        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);
        
        return [
            'valid' => $returnCode === 0,
            'error' => $returnCode !== 0 ? implode("\n", $output) : ''
        ];
    }
    
    /**
     * 檢查是否為bug修復
     */
    private function isBugFix($analysis) {
        $keywords = ['修復', 'fix', 'bug', '錯誤', 'error', '問題', 'issue'];
        $text = strtolower($analysis['reason'] . ' ' . $analysis['content']);
        
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 記錄bug修復
     */
    private function recordBugFix($analysis, $filePath) {
        try {
            $title = "自動檢測的Bug修復 - " . basename($filePath);
            $description = $analysis['content'];
            $solution = "通過修改文件 {$filePath} 解決";
            
            $bugId = $this->tracker->logBugFix(
                $title,
                $description,
                $solution,
                '自動檢測',
                '建議進行回歸測試',
                '中',
                $filePath
            );
            
            $this->log("記錄Bug修復: {$title} (ID: {$bugId})", 'INFO');
            
        } catch (Exception $e) {
            $this->log("記錄Bug修復失敗: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * 發送通知
     */
    private function sendNotification($changes) {
        $message = "檢測到 " . count($changes) . " 個文件變化:\n\n";
        
        foreach ($changes as $change) {
            $message .= "- {$change['type']}: {$change['file']}\n";
        }
        
        $data = [
            'text' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'changes' => $changes
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($this->config['notification_webhook'], false, $context);
        
        if ($result === false) {
            $this->log("發送通知失敗", 'WARNING');
        } else {
            $this->log("通知已發送", 'INFO');
        }
    }
    
    /**
     * 記錄日誌
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
        
        // 寫入文件
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // 輸出到控制台
        echo $logEntry;
    }
    
    /**
     * 手動觸發掃描
     */
    public function manualScan() {
        $this->log("開始手動掃描...", 'INFO');
        $this->checkForChanges();
        $this->log("手動掃描完成", 'INFO');
    }
    
    /**
     * 獲取統計信息
     */
    public function getStats() {
        return [
            'watched_directories' => count($this->watchedDirectories),
            'tracked_files' => count($this->fileHashes),
            'config' => $this->config,
            'last_scan' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * 更新配置
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        $this->saveConfig();
        $this->watchedDirectories = $this->config['watched_directories'];
        $this->log("配置已更新", 'INFO');
    }
}

// 命令行使用
if (php_sapi_name() === 'cli') {
    $autoLogger = new AutoLogger();
    
    $command = $argv[1] ?? 'watch';
    
    switch ($command) {
        case 'watch':
            echo "🤖 啟動自動監控...\n";
            $autoLogger->startWatching();
            break;
            
        case 'scan':
            echo "🔍 執行手動掃描...\n";
            $autoLogger->manualScan();
            break;
            
        case 'stats':
            echo "📊 統計信息:\n";
            print_r($autoLogger->getStats());
            break;
            
        default:
            echo "用法: php auto_logger.php [watch|scan|stats]\n";
            echo "  watch - 開始監控文件變化\n";
            echo "  scan  - 執行一次手動掃描\n";
            echo "  stats - 顯示統計信息\n";
    }
}

// Web API使用
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    try {
        $autoLogger = new AutoLogger();
        $action = $_POST['action'];
        
        switch ($action) {
            case 'manual_scan':
                $autoLogger->manualScan();
                echo json_encode(['success' => true, 'message' => '手動掃描完成']);
                break;
                
            case 'get_stats':
                $stats = $autoLogger->getStats();
                echo json_encode(['success' => true, 'data' => $stats]);
                break;
                
            case 'update_config':
                $config = json_decode($_POST['config'], true);
                $autoLogger->updateConfig($config);
                echo json_encode(['success' => true, 'message' => '配置已更新']);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => '未知操作']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?> 