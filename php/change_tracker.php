<?php
/**
 * 🎓 修改追蹤系統
 * 記錄所有代碼修改、bug修復和教學文檔更新
 * 
 * @author AI Assistant
 * @version 1.0.0
 * @since 2024-01-01
 */

class ChangeTracker {
    private $db;
    private $logFile;
    
    public function __construct($database) {
        $this->db = $database;
        $this->logFile = '../docs/changes/' . date('Y-m-d') . '.md';
        $this->initializeDatabase();
    }
    
    /**
     * 初始化資料庫表結構
     */
    private function initializeDatabase() {
        $sql = "
        CREATE TABLE IF NOT EXISTS change_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            change_type ENUM('新增', '修改', '刪除', '修復', '重構', '文檔') NOT NULL,
            affected_files TEXT NOT NULL,
            change_reason TEXT NOT NULL,
            change_content TEXT NOT NULL,
            test_result TEXT,
            teaching_value TEXT,
            author VARCHAR(100) DEFAULT 'Developer',
            commit_hash VARCHAR(40),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS bug_fixes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bug_title VARCHAR(255) NOT NULL,
            bug_description TEXT NOT NULL,
            occurrence_count INT DEFAULT 1,
            first_occurrence DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_occurrence DATETIME DEFAULT CURRENT_TIMESTAMP,
            solutions JSON,
            root_cause TEXT,
            prevention_measures TEXT,
            severity ENUM('低', '中', '高', '緊急') DEFAULT '中',
            status ENUM('開放', '進行中', '已解決', '已驗證') DEFAULT '開放',
            affected_files TEXT,
            related_changes JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS teaching_docs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doc_title VARCHAR(255) NOT NULL,
            doc_type ENUM('教學指南', '故障排除', '部署指南', '用戶手冊', 'API文檔') NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            content_summary TEXT,
            related_changes JSON,
            view_count INT DEFAULT 0,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS visualization_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chart_type VARCHAR(100) NOT NULL,
            chart_data JSON NOT NULL,
            generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            file_path VARCHAR(500),
            description TEXT
        );
        ";
        
        $this->db->exec($sql);
    }
    
    /**
     * 記錄代碼修改
     */
    public function logChange($changeType, $affectedFiles, $reason, $content, $testResult = '', $teachingValue = '', $author = 'Developer') {
        try {
            // 插入資料庫記錄
            $stmt = $this->db->prepare("
                INSERT INTO change_logs 
                (change_type, affected_files, change_reason, change_content, test_result, teaching_value, author, commit_hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $commitHash = $this->getLatestCommitHash();
            $stmt->execute([$changeType, $affectedFiles, $reason, $content, $testResult, $teachingValue, $author, $commitHash]);
            
            $changeId = $this->db->lastInsertId();
            
            // 寫入Markdown日誌
            $this->writeMarkdownLog($changeId, $changeType, $affectedFiles, $reason, $content, $testResult, $teachingValue, $author);
            
            // 更新視覺化數據
            $this->updateVisualizationData();
            
            // 自動更新相關教學文檔
            $this->updateTeachingDocs($changeType, $affectedFiles, $content);
            
            return $changeId;
            
        } catch (Exception $e) {
            error_log("記錄修改失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 記錄Bug修復
     */
    public function logBugFix($title, $description, $solution, $rootCause = '', $prevention = '', $severity = '中', $affectedFiles = '') {
        try {
            // 檢查是否為重複bug
            $stmt = $this->db->prepare("SELECT * FROM bug_fixes WHERE bug_title = ? ORDER BY last_occurrence DESC LIMIT 1");
            $stmt->execute([$title]);
            $existingBug = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingBug) {
                // 更新現有bug記錄
                $occurrenceCount = $existingBug['occurrence_count'] + 1;
                $solutions = json_decode($existingBug['solutions'], true) ?: [];
                $solutions[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'solution' => $solution,
                    'root_cause' => $rootCause
                ];
                
                $stmt = $this->db->prepare("
                    UPDATE bug_fixes 
                    SET occurrence_count = ?, last_occurrence = NOW(), solutions = ?, 
                        root_cause = ?, prevention_measures = ?, affected_files = ?
                    WHERE id = ?
                ");
                $stmt->execute([$occurrenceCount, json_encode($solutions), $rootCause, $prevention, $affectedFiles, $existingBug['id']]);
                
                $bugId = $existingBug['id'];
                
                // 記錄重複bug警告
                $this->logChange('修復', $affectedFiles, "重複Bug修復 (第{$occurrenceCount}次)", 
                    "Bug: {$title}\n解決方案: {$solution}\n根本原因: {$rootCause}", 
                    '修復完成', "這是一個重複出現的bug，需要特別關注根本原因");
                
            } else {
                // 創建新bug記錄
                $solutions = [
                    [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'solution' => $solution,
                        'root_cause' => $rootCause
                    ]
                ];
                
                $stmt = $this->db->prepare("
                    INSERT INTO bug_fixes 
                    (bug_title, bug_description, solutions, root_cause, prevention_measures, severity, affected_files) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, json_encode($solutions), $rootCause, $prevention, $severity, $affectedFiles]);
                
                $bugId = $this->db->lastInsertId();
                
                // 記錄新bug修復
                $this->logChange('修復', $affectedFiles, "Bug修復: {$title}", 
                    "描述: {$description}\n解決方案: {$solution}\n根本原因: {$rootCause}", 
                    '修復完成', "新發現的bug，已記錄解決方案");
            }
            
            return $bugId;
            
        } catch (Exception $e) {
            error_log("記錄Bug修復失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 寫入Markdown日誌
     */
    private function writeMarkdownLog($changeId, $changeType, $affectedFiles, $reason, $content, $testResult, $teachingValue, $author) {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "
## 📝 修改記錄 #{$changeId}

**時間**: {$timestamp}  
**類型**: {$changeType}  
**作者**: {$author}  

### 📁 影響文件
```
{$affectedFiles}
```

### 🎯 修改原因
{$reason}

### 🔧 修改內容
{$content}

### ✅ 測試結果
{$testResult}

### 🎓 教學價值
{$teachingValue}

---

";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 更新視覺化數據
     */
    private function updateVisualizationData() {
        try {
            // 生成修改類型統計
            $stmt = $this->db->query("
                SELECT change_type, COUNT(*) as count 
                FROM change_logs 
                WHERE DATE(timestamp) >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY change_type
            ");
            $changeTypeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->saveVisualizationData('change_type_stats', $changeTypeData, '30天內修改類型統計');
            
            // 生成文件修改熱力圖數據
            $stmt = $this->db->query("
                SELECT affected_files, COUNT(*) as modification_count
                FROM change_logs 
                WHERE DATE(timestamp) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY affected_files
                ORDER BY modification_count DESC
                LIMIT 20
            ");
            $fileHeatmapData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->saveVisualizationData('file_heatmap', $fileHeatmapData, '7天內文件修改熱力圖');
            
            // 生成Bug修復統計
            $stmt = $this->db->query("
                SELECT severity, COUNT(*) as count, AVG(occurrence_count) as avg_occurrence
                FROM bug_fixes 
                GROUP BY severity
            ");
            $bugStatsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->saveVisualizationData('bug_stats', $bugStatsData, 'Bug修復統計');
            
            // 生成時間線數據
            $stmt = $this->db->query("
                SELECT DATE(timestamp) as date, change_type, COUNT(*) as count
                FROM change_logs 
                WHERE DATE(timestamp) >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(timestamp), change_type
                ORDER BY date DESC
            ");
            $timelineData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->saveVisualizationData('timeline', $timelineData, '30天開發時間線');
            
        } catch (Exception $e) {
            error_log("更新視覺化數據失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 保存視覺化數據
     */
    private function saveVisualizationData($chartType, $data, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO visualization_data (chart_type, chart_data, description, file_path) 
            VALUES (?, ?, ?, ?)
        ");
        
        $filePath = "../docs/visualizations/{$chartType}_" . date('Y-m-d') . ".json";
        $stmt->execute([$chartType, json_encode($data), $description, $filePath]);
        
        // 保存JSON文件
        $vizDir = dirname($filePath);
        if (!is_dir($vizDir)) {
            mkdir($vizDir, 0755, true);
        }
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 自動更新教學文檔
     */
    private function updateTeachingDocs($changeType, $affectedFiles, $content) {
        $docUpdates = [];
        
        // 根據修改類型決定需要更新的文檔
        switch ($changeType) {
            case '新增':
                $docUpdates[] = ['type' => '教學指南', 'reason' => '新功能教學'];
                break;
            case '修復':
                $docUpdates[] = ['type' => '故障排除', 'reason' => 'Bug修復記錄'];
                break;
            case '修改':
                if (strpos($affectedFiles, 'config') !== false) {
                    $docUpdates[] = ['type' => '部署指南', 'reason' => '配置變更'];
                }
                if (strpos($affectedFiles, '.html') !== false || strpos($affectedFiles, '.css') !== false) {
                    $docUpdates[] = ['type' => '用戶手冊', 'reason' => 'UI變更'];
                }
                break;
        }
        
        foreach ($docUpdates as $update) {
            $this->createOrUpdateTeachingDoc($update['type'], $affectedFiles, $content, $update['reason']);
        }
    }
    
    /**
     * 創建或更新教學文檔
     */
    private function createOrUpdateTeachingDoc($docType, $affectedFiles, $content, $reason) {
        $docTitle = $docType . ' - ' . date('Y-m-d');
        $filePath = "../docs/tutorials/" . strtolower(str_replace(' ', '_', $docType)) . "_" . date('Y-m-d') . ".md";
        
        // 檢查是否已存在
        $stmt = $this->db->prepare("SELECT * FROM teaching_docs WHERE doc_title = ? AND doc_type = ?");
        $stmt->execute([$docTitle, $docType]);
        $existingDoc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $relatedChanges = [
            'timestamp' => date('Y-m-d H:i:s'),
            'affected_files' => $affectedFiles,
            'content' => $content,
            'reason' => $reason
        ];
        
        if ($existingDoc) {
            // 更新現有文檔
            $changes = json_decode($existingDoc['related_changes'], true) ?: [];
            $changes[] = $relatedChanges;
            
            $stmt = $this->db->prepare("
                UPDATE teaching_docs 
                SET related_changes = ?, last_updated = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([json_encode($changes), $existingDoc['id']]);
        } else {
            // 創建新文檔
            $stmt = $this->db->prepare("
                INSERT INTO teaching_docs 
                (doc_title, doc_type, file_path, content_summary, related_changes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$docTitle, $docType, $filePath, $reason, json_encode([$relatedChanges])]);
        }
        
        // 生成Markdown文檔
        $this->generateTeachingMarkdown($docType, $filePath, $affectedFiles, $content, $reason);
    }
    
    /**
     * 生成教學Markdown文檔
     */
    private function generateTeachingMarkdown($docType, $filePath, $affectedFiles, $content, $reason) {
        $docDir = dirname($filePath);
        if (!is_dir($docDir)) {
            mkdir($docDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $markdown = "# {$docType} - " . date('Y-m-d') . "

## 📋 文檔概述

**生成時間**: {$timestamp}  
**更新原因**: {$reason}  
**相關文件**: {$affectedFiles}

## 🎯 教學內容

### 修改說明
{$content}

### 學習要點
- 理解修改的技術背景
- 掌握相關的實現方法
- 注意可能的問題和解決方案

### 實踐建議
1. 仔細閱讀修改內容
2. 在測試環境中重現修改
3. 理解修改的業務邏輯
4. 思考其他可能的實現方式

## 🔍 常見問題

### Q: 為什麼需要這個修改？
A: {$reason}

### Q: 如何驗證修改是否正確？
A: 建議進行以下測試...

## 📚 相關資源

- [項目文檔](../README.md)
- [開發指南](../DEVELOPMENT.md)
- [故障排除](../TROUBLESHOOTING.md)

---

*本文檔由系統自動生成，最後更新時間: {$timestamp}*
";
        
        file_put_contents($filePath, $markdown);
    }
    
    /**
     * 獲取最新的Git提交哈希
     */
    private function getLatestCommitHash() {
        if (function_exists('exec')) {
            $output = [];
            exec('git rev-parse HEAD 2>/dev/null', $output);
            return !empty($output) ? substr($output[0], 0, 8) : null;
        }
        return null;
    }
    
    /**
     * 獲取修改統計
     */
    public function getChangeStats($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                change_type,
                COUNT(*) as count,
                DATE(timestamp) as date
            FROM change_logs 
            WHERE DATE(timestamp) >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY change_type, DATE(timestamp)
            ORDER BY date DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 獲取Bug統計
     */
    public function getBugStats() {
        $stmt = $this->db->query("
            SELECT 
                severity,
                status,
                COUNT(*) as count,
                AVG(occurrence_count) as avg_occurrence
            FROM bug_fixes 
            GROUP BY severity, status
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 獲取最近的修改記錄
     */
    public function getRecentChanges($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM change_logs 
            ORDER BY timestamp DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 生成教學報告
     */
    public function generateTeachingReport() {
        $stats = $this->getChangeStats(7);
        $bugStats = $this->getBugStats();
        $recentChanges = $this->getRecentChanges(5);
        
        $reportPath = "../docs/reports/teaching_report_" . date('Y-m-d') . ".md";
        $reportDir = dirname($reportPath);
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        $report = "# 📊 教學報告 - " . date('Y-m-d') . "

## 📈 本週開發統計

### 修改類型分布
";
        
        $changeTypeCounts = [];
        foreach ($stats as $stat) {
            $changeTypeCounts[$stat['change_type']] = ($changeTypeCounts[$stat['change_type']] ?? 0) + $stat['count'];
        }
        
        foreach ($changeTypeCounts as $type => $count) {
            $report .= "- {$type}: {$count} 次\n";
        }
        
        $report .= "
### Bug修復統計
";
        
        foreach ($bugStats as $bug) {
            $report .= "- {$bug['severity']}嚴重度 ({$bug['status']}): {$bug['count']} 個\n";
        }
        
        $report .= "
## 🔍 最近修改記錄

";
        
        foreach ($recentChanges as $change) {
            $report .= "### {$change['change_type']} - {$change['timestamp']}
**文件**: {$change['affected_files']}  
**原因**: {$change['change_reason']}  
**教學價值**: {$change['teaching_value']}

";
        }
        
        $report .= "
## 🎓 教學建議

基於本週的開發活動，建議重點關注：
1. 最常修改的文件和功能
2. 重複出現的bug類型
3. 新增功能的教學價值
4. 代碼品質改進機會

---

*報告生成時間: " . date('Y-m-d H:i:s') . "*
";
        
        file_put_contents($reportPath, $report);
        return $reportPath;
    }
}

// 使用示例
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=python_teaching', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tracker = new ChangeTracker($pdo);
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'log_change':
                $result = $tracker->logChange(
                    $_POST['change_type'],
                    $_POST['affected_files'],
                    $_POST['reason'],
                    $_POST['content'],
                    $_POST['test_result'] ?? '',
                    $_POST['teaching_value'] ?? '',
                    $_POST['author'] ?? 'Developer'
                );
                echo json_encode(['success' => true, 'change_id' => $result]);
                break;
                
            case 'log_bug':
                $result = $tracker->logBugFix(
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['solution'],
                    $_POST['root_cause'] ?? '',
                    $_POST['prevention'] ?? '',
                    $_POST['severity'] ?? '中',
                    $_POST['affected_files'] ?? ''
                );
                echo json_encode(['success' => true, 'bug_id' => $result]);
                break;
                
            case 'get_stats':
                $stats = $tracker->getChangeStats($_POST['days'] ?? 30);
                echo json_encode(['success' => true, 'data' => $stats]);
                break;
                
            case 'generate_report':
                $reportPath = $tracker->generateTeachingReport();
                echo json_encode(['success' => true, 'report_path' => $reportPath]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => '未知操作']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?> 