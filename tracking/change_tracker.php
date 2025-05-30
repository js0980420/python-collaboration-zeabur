<?php
/**
 * 修改追蹤系統
 * 文件: change_tracker.php
 * 創建時間: 2024-01-01 10:00:00
 * 功能: 追蹤所有代碼修改，生成教學報告
 */

class ChangeTracker {
    private $db;
    private $config;
    
    public function __construct() {
        $this->config = [
            'db_host' => 'localhost',
            'db_name' => 'python_teaching_web',
            'db_user' => 'root',
            'db_pass' => ''
        ];
        $this->initDatabase();
    }
    
    /**
     * 初始化資料庫連接
     */
    private function initDatabase() {
        try {
            $dsn = "mysql:host={$this->config['db_host']};dbname={$this->config['db_name']};charset=utf8mb4";
            $this->db = new PDO($dsn, $this->config['db_user'], $this->config['db_pass']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            error_log("資料庫連接失敗: " . $e->getMessage());
            throw new Exception("資料庫連接失敗");
        }
    }
    
    /**
     * 創建追蹤表
     */
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS change_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(500) NOT NULL,
            change_type ENUM('CREATE', 'MODIFY', 'DELETE', 'REFACTOR') NOT NULL,
            change_reason ENUM('BUG_FIX', 'FEATURE_ADD', 'PERFORMANCE', 'TEACHING', 'REPEATED_BUG') NOT NULL,
            change_description TEXT NOT NULL,
            code_before LONGTEXT,
            code_after LONGTEXT,
            affected_files JSON,
            test_status ENUM('PENDING', 'PASSED', 'FAILED') DEFAULT 'PENDING',
            repeat_count INT DEFAULT 1,
            session_id VARCHAR(100),
            user_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_file_path (file_path),
            INDEX idx_change_type (change_type),
            INDEX idx_created_at (created_at),
            INDEX idx_session_id (session_id)
        );
        
        CREATE TABLE IF NOT EXISTS repeated_issues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_hash VARCHAR(64) UNIQUE,
            issue_description TEXT NOT NULL,
            first_occurrence TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_occurrence TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            occurrence_count INT DEFAULT 1,
            resolution_attempts JSON,
            final_solution TEXT,
            teaching_notes TEXT,
            INDEX idx_issue_hash (issue_hash),
            INDEX idx_occurrence_count (occurrence_count)
        );
        
        CREATE TABLE IF NOT EXISTS teaching_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            change_log_id INT,
            document_type ENUM('FAQ', 'GUIDE', 'TUTORIAL', 'TROUBLESHOOT') NOT NULL,
            document_path VARCHAR(500),
            update_content TEXT NOT NULL,
            auto_generated BOOLEAN DEFAULT TRUE,
            reviewed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (change_log_id) REFERENCES change_logs(id) ON DELETE CASCADE,
            INDEX idx_document_type (document_type),
            INDEX idx_auto_generated (auto_generated)
        );
        
        CREATE TABLE IF NOT EXISTS modification_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(100) UNIQUE,
            start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            end_time TIMESTAMP NULL,
            total_changes INT DEFAULT 0,
            bug_fixes INT DEFAULT 0,
            repeated_bugs INT DEFAULT 0,
            session_notes TEXT,
            INDEX idx_session_id (session_id),
            INDEX idx_start_time (start_time)
        );
        ";
        
        $this->db->exec($sql);
    }
    
    /**
     * 記錄代碼修改
     */
    public function logChange($data) {
        $sessionId = $this->getCurrentSessionId();
        
        // 檢查是否為重複問題
        $issueHash = $this->generateIssueHash($data);
        $repeatCount = $this->checkRepeatedIssue($issueHash, $data);
        
        $sql = "INSERT INTO change_logs (
            file_path, change_type, change_reason, change_description,
            code_before, code_after, affected_files, session_id,
            user_id, repeat_count
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['file_path'],
            $data['change_type'],
            $data['change_reason'],
            $data['description'],
            $data['code_before'] ?? null,
            $data['code_after'] ?? null,
            json_encode($data['affected_files'] ?? []),
            $sessionId,
            $data['user_id'] ?? 'system',
            $repeatCount
        ]);
        
        $changeId = $this->db->lastInsertId();
        
        // 更新會話統計
        $this->updateSessionStats($sessionId, $data['change_reason']);
        
        // 自動生成教學文檔更新
        $this->generateTeachingUpdate($changeId, $data);
        
        return $changeId;
    }
    
    /**
     * 生成問題哈希值
     */
    private function generateIssueHash($data) {
        $hashData = $data['file_path'] . '|' . $data['change_reason'] . '|' . 
                   substr($data['description'], 0, 100);
        return hash('sha256', $hashData);
    }
    
    /**
     * 檢查重複問題
     */
    private function checkRepeatedIssue($issueHash, $data) {
        $sql = "SELECT occurrence_count FROM repeated_issues WHERE issue_hash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$issueHash]);
        $result = $stmt->fetch();
        
        if ($result) {
            // 更新重複次數
            $newCount = $result['occurrence_count'] + 1;
            $sql = "UPDATE repeated_issues SET 
                    occurrence_count = ?, 
                    last_occurrence = CURRENT_TIMESTAMP,
                    resolution_attempts = JSON_ARRAY_APPEND(
                        COALESCE(resolution_attempts, JSON_ARRAY()), 
                        '$', ?
                    )
                    WHERE issue_hash = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newCount, $data['description'], $issueHash]);
            return $newCount;
        } else {
            // 新問題
            $sql = "INSERT INTO repeated_issues (
                issue_hash, issue_description, resolution_attempts
            ) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $issueHash, 
                $data['description'],
                json_encode([$data['description']])
            ]);
            return 1;
        }
    }
    
    /**
     * 獲取當前會話ID
     */
    private function getCurrentSessionId() {
        if (!isset($_SESSION['modification_session_id'])) {
            $_SESSION['modification_session_id'] = uniqid('session_', true);
            
            $sql = "INSERT INTO modification_sessions (session_id) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['modification_session_id']]);
        }
        
        return $_SESSION['modification_session_id'];
    }
    
    /**
     * 更新會話統計
     */
    private function updateSessionStats($sessionId, $changeReason) {
        $sql = "UPDATE modification_sessions SET 
                total_changes = total_changes + 1,
                bug_fixes = bug_fixes + IF(? = 'BUG_FIX', 1, 0),
                repeated_bugs = repeated_bugs + IF(? = 'REPEATED_BUG', 1, 0)
                WHERE session_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$changeReason, $changeReason, $sessionId]);
    }
    
    /**
     * 生成教學文檔更新
     */
    private function generateTeachingUpdate($changeId, $data) {
        $updateContent = $this->generateUpdateContent($data);
        $documentType = $this->determineDocumentType($data);
        
        $sql = "INSERT INTO teaching_updates (
            change_log_id, document_type, update_content
        ) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$changeId, $documentType, $updateContent]);
        
        // 實際更新文檔文件
        $this->updateDocumentFile($documentType, $updateContent, $data);
    }
    
    /**
     * 生成更新內容
     */
    private function generateUpdateContent($data) {
        $timestamp = date('Y-m-d H:i:s');
        $content = "## 修改記錄 - {$timestamp}\n\n";
        $content .= "**文件**: `{$data['file_path']}`\n";
        $content .= "**類型**: {$data['change_type']}\n";
        $content .= "**原因**: {$data['change_reason']}\n";
        $content .= "**描述**: {$data['description']}\n\n";
        
        if ($data['change_reason'] === 'BUG_FIX' || $data['change_reason'] === 'REPEATED_BUG') {
            $content .= "### 解決方案\n";
            $content .= "1. 問題現象: {$data['description']}\n";
            $content .= "2. 解決步驟: 請參考代碼修改\n";
            $content .= "3. 預防措施: 建議加強相關測試\n\n";
        }
        
        return $content;
    }
    
    /**
     * 確定文檔類型
     */
    private function determineDocumentType($data) {
        if ($data['change_reason'] === 'BUG_FIX' || $data['change_reason'] === 'REPEATED_BUG') {
            return 'TROUBLESHOOT';
        } elseif ($data['change_reason'] === 'TEACHING') {
            return 'TUTORIAL';
        } elseif ($data['change_reason'] === 'FEATURE_ADD') {
            return 'GUIDE';
        } else {
            return 'FAQ';
        }
    }
    
    /**
     * 更新文檔文件
     */
    private function updateDocumentFile($documentType, $content, $data) {
        $docPath = $this->getDocumentPath($documentType);
        
        if (!file_exists(dirname($docPath))) {
            mkdir(dirname($docPath), 0755, true);
        }
        
        $existingContent = file_exists($docPath) ? file_get_contents($docPath) : '';
        $newContent = $content . "\n---\n\n" . $existingContent;
        
        file_put_contents($docPath, $newContent);
        
        // 更新資料庫記錄
        $sql = "UPDATE teaching_updates SET document_path = ? WHERE change_log_id = (
            SELECT id FROM change_logs WHERE file_path = ? ORDER BY created_at DESC LIMIT 1
        )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$docPath, $data['file_path']]);
    }
    
    /**
     * 獲取文檔路徑
     */
    private function getDocumentPath($documentType) {
        $basePath = dirname(__DIR__) . '/docs/';
        
        switch ($documentType) {
            case 'FAQ':
                return $basePath . 'FAQ.md';
            case 'GUIDE':
                return $basePath . 'DEVELOPMENT_GUIDE.md';
            case 'TUTORIAL':
                return $basePath . 'TEACHING_GUIDE.md';
            case 'TROUBLESHOOT':
                return $basePath . 'TROUBLESHOOTING.md';
            default:
                return $basePath . 'CHANGE_LOG.md';
        }
    }
    
    /**
     * 獲取修改統計
     */
    public function getChangeStats($timeframe = '24 HOUR') {
        $sql = "SELECT 
            change_type,
            change_reason,
            COUNT(*) as count,
            AVG(repeat_count) as avg_repeats
            FROM change_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$timeframe})
            GROUP BY change_type, change_reason
            ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 獲取重複問題報告
     */
    public function getRepeatedIssues($limit = 10) {
        $sql = "SELECT 
            issue_description,
            occurrence_count,
            first_occurrence,
            last_occurrence,
            resolution_attempts,
            final_solution
            FROM repeated_issues 
            WHERE occurrence_count > 1
            ORDER BY occurrence_count DESC, last_occurrence DESC
            LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 獲取會話報告
     */
    public function getSessionReport($sessionId = null) {
        $sessionId = $sessionId ?? $this->getCurrentSessionId();
        
        $sql = "SELECT 
            ms.*,
            COUNT(cl.id) as total_logs,
            GROUP_CONCAT(DISTINCT cl.file_path) as modified_files
            FROM modification_sessions ms
            LEFT JOIN change_logs cl ON ms.session_id = cl.session_id
            WHERE ms.session_id = ?
            GROUP BY ms.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 結束當前會話
     */
    public function endSession($notes = '') {
        $sessionId = $this->getCurrentSessionId();
        
        $sql = "UPDATE modification_sessions SET 
                end_time = CURRENT_TIMESTAMP,
                session_notes = ?
                WHERE session_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$notes, $sessionId]);
        
        unset($_SESSION['modification_session_id']);
        
        return $this->getSessionReport($sessionId);
    }
    
    /**
     * 導出修改報告
     */
    public function exportReport($format = 'json', $timeframe = '7 DAY') {
        $data = [
            'stats' => $this->getChangeStats($timeframe),
            'repeated_issues' => $this->getRepeatedIssues(),
            'recent_changes' => $this->getRecentChanges($timeframe),
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            case 'csv':
                return $this->convertToCSV($data);
            default:
                return $data;
        }
    }
    
    /**
     * 獲取最近修改
     */
    private function getRecentChanges($timeframe) {
        $sql = "SELECT 
            file_path,
            change_type,
            change_reason,
            change_description,
            repeat_count,
            created_at
            FROM change_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$timeframe})
            ORDER BY created_at DESC
            LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 轉換為CSV格式
     */
    private function convertToCSV($data) {
        $csv = "時間,文件,類型,原因,描述,重複次數\n";
        foreach ($data['recent_changes'] as $change) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%d\n",
                $change['created_at'],
                $change['file_path'],
                $change['change_type'],
                $change['change_reason'],
                str_replace(',', ';', $change['change_description']),
                $change['repeat_count']
            );
        }
        return $csv;
    }
}

// 使用示例
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    try {
        $tracker = new ChangeTracker();
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'log_change':
                $changeId = $tracker->logChange($_POST);
                echo json_encode(['success' => true, 'change_id' => $changeId]);
                break;
                
            case 'get_stats':
                $stats = $tracker->getChangeStats($_POST['timeframe'] ?? '24 HOUR');
                echo json_encode(['success' => true, 'data' => $stats]);
                break;
                
            case 'get_repeated_issues':
                $issues = $tracker->getRepeatedIssues($_POST['limit'] ?? 10);
                echo json_encode(['success' => true, 'data' => $issues]);
                break;
                
            case 'end_session':
                $report = $tracker->endSession($_POST['notes'] ?? '');
                echo json_encode(['success' => true, 'report' => $report]);
                break;
                
            case 'export_report':
                $report = $tracker->exportReport($_POST['format'] ?? 'json', $_POST['timeframe'] ?? '7 DAY');
                echo json_encode(['success' => true, 'report' => $report]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => '未知操作']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?> 