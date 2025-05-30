<?php
// 🎮 遊戲化系統 API
// 處理經驗值、成就、排行榜等功能

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

class GamificationAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // 處理API請求
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($action);
                    break;
                case 'POST':
                    $this->handlePost($action);
                    break;
                default:
                    $this->sendError('不支援的請求方法', 405);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    // 處理GET請求
    private function handleGet($action) {
        switch ($action) {
            case 'user_stats':
                $this->getUserStats();
                break;
            case 'leaderboard':
                $this->getLeaderboard();
                break;
            case 'achievements':
                $this->getUserAchievements();
                break;
            case 'challenges':
                $this->getChallenges();
                break;
            default:
                $this->sendError('未知的操作', 400);
        }
    }
    
    // 處理POST請求
    private function handlePost($action) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'execute_code':
                $this->executeCode($input);
                break;
            case 'complete_lesson':
                $this->completeLesson($input);
                break;
            case 'complete_challenge':
                $this->completeChallenge($input);
                break;
            case 'register_user':
                $this->registerUser($input);
                break;
            default:
                $this->sendError('未知的操作', 400);
        }
    }
    
    // 獲取用戶統計數據
    private function getUserStats() {
        $userId = $_GET['user_id'] ?? 1; // 示例用戶ID
        
        $sql = "SELECT 
                    u.*,
                    (SELECT COUNT(*) FROM user_achievements WHERE user_id = u.id) as total_achievements,
                    FLOOR(u.experience_points / (u.level * 100 + 50) * 100) as exp_percentage
                FROM users u 
                WHERE u.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // 創建示例用戶
            $user = $this->createDemoUser($userId);
        }
        
        // 計算升級所需經驗值
        $expToNextLevel = $user['level'] * 100 + 50;
        $currentExp = $user['experience_points'] % $expToNextLevel;
        
        $response = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'avatar_emoji' => $user['avatar_emoji'],
            'level' => $user['level'],
            'experience_points' => $user['experience_points'],
            'current_level_exp' => $currentExp,
            'exp_to_next_level' => $expToNextLevel,
            'exp_percentage' => round(($currentExp / $expToNextLevel) * 100, 1),
            'total_achievements' => $user['total_achievements'],
            'total_code_executions' => $user['total_code_executions'],
            'total_lessons_completed' => $user['total_lessons_completed'],
            'learning_streak_days' => $user['learning_streak_days']
        ];
        
        $this->sendSuccess($response);
    }
    
    // 獲取排行榜
    private function getLeaderboard() {
        $limit = $_GET['limit'] ?? 10;
        
        $sql = "SELECT * FROM user_leaderboard LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 如果沒有數據，創建示例數據
        if (empty($leaderboard)) {
            $leaderboard = $this->createDemoLeaderboard();
        }
        
        $this->sendSuccess($leaderboard);
    }
    
    // 獲取用戶成就
    private function getUserAchievements() {
        $userId = $_GET['user_id'] ?? 1;
        
        $sql = "SELECT 
                    a.*,
                    ua.unlocked_at,
                    CASE WHEN ua.user_id IS NOT NULL THEN 1 ELSE 0 END as is_unlocked
                FROM achievements a
                LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
                WHERE a.is_active = 1
                ORDER BY a.rarity, a.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendSuccess($achievements);
    }
    
    // 獲取挑戰列表
    private function getChallenges() {
        $userId = $_GET['user_id'] ?? 1;
        
        $sql = "SELECT 
                    c.*,
                    CASE WHEN ucc.user_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
                FROM challenges c
                LEFT JOIN user_challenge_completions ucc ON c.id = ucc.challenge_id AND ucc.user_id = ?
                WHERE c.is_active = 1
                ORDER BY 
                    CASE c.difficulty 
                        WHEN 'easy' THEN 1 
                        WHEN 'medium' THEN 2 
                        WHEN 'hard' THEN 3 
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendSuccess($challenges);
    }
    
    // 執行程式碼
    private function executeCode($input) {
        $userId = $input['user_id'] ?? 1;
        $code = $input['code'] ?? '';
        $lessonId = $input['lesson_id'] ?? null;
        
        if (empty($code)) {
            $this->sendError('程式碼不能為空', 400);
            return;
        }
        
        // 模擬程式碼執行（實際應用中可以整合Python執行環境）
        $executionResult = $this->simulateCodeExecution($code);
        
        // 記錄執行
        $expGained = 10;
        $sql = "INSERT INTO code_executions 
                (user_id, lesson_id, code_content, execution_result, execution_status, experience_gained) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId, 
            $lessonId, 
            $code, 
            $executionResult['output'], 
            $executionResult['status'], 
            $expGained
        ]);
        
        // 檢查成就
        $this->checkAndUnlockAchievements($userId);
        
        $response = [
            'execution_result' => $executionResult,
            'experience_gained' => $expGained,
            'message' => '程式碼執行成功！獲得 ' . $expGained . ' 經驗值'
        ];
        
        $this->sendSuccess($response);
    }
    
    // 完成課程
    private function completeLesson($input) {
        $userId = $input['user_id'] ?? 1;
        $lessonId = $input['lesson_id'] ?? 0;
        
        // 更新課程進度
        $sql = "INSERT INTO user_lesson_progress 
                (user_id, lesson_id, status, progress_percentage, completed_at) 
                VALUES (?, ?, 'completed', 100, NOW())
                ON DUPLICATE KEY UPDATE 
                status = 'completed', 
                progress_percentage = 100, 
                completed_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $lessonId]);
        
        // 獲取課程獎勵
        $sql = "SELECT experience_reward FROM lessons WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $expReward = $lesson['experience_reward'] ?? 50;
        
        // 更新用戶經驗值
        $sql = "UPDATE users SET 
                experience_points = experience_points + ?,
                total_lessons_completed = total_lessons_completed + 1
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expReward, $userId]);
        
        // 檢查升級和成就
        $this->checkLevelUp($userId);
        $this->checkAndUnlockAchievements($userId);
        
        $response = [
            'message' => '課程完成！獲得 ' . $expReward . ' 經驗值',
            'experience_gained' => $expReward
        ];
        
        $this->sendSuccess($response);
    }
    
    // 完成挑戰
    private function completeChallenge($input) {
        $userId = $input['user_id'] ?? 1;
        $challengeId = $input['challenge_id'] ?? '';
        $submittedCode = $input['code'] ?? '';
        
        // 檢查答案（簡化版）
        $sql = "SELECT * FROM challenges WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$challengeId]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$challenge) {
            $this->sendError('挑戰不存在', 404);
            return;
        }
        
        $isCorrect = $this->checkChallengeAnswer($submittedCode, $challenge['solution_code']);
        
        // 記錄完成
        $sql = "INSERT INTO user_challenge_completions 
                (user_id, challenge_id, submitted_code, is_correct) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $challengeId, $submittedCode, $isCorrect]);
        
        $response = [
            'is_correct' => $isCorrect,
            'message' => $isCorrect ? '挑戰完成！' : '答案不正確，請再試一次'
        ];
        
        if ($isCorrect) {
            $expReward = $challenge['experience_reward'];
            
            // 更新經驗值
            $sql = "UPDATE users SET experience_points = experience_points + ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$expReward, $userId]);
            
            $response['experience_gained'] = $expReward;
            $response['message'] .= ' 獲得 ' . $expReward . ' 經驗值';
            
            $this->checkLevelUp($userId);
            $this->checkAndUnlockAchievements($userId);
        }
        
        $this->sendSuccess($response);
    }
    
    // 註冊用戶
    private function registerUser($input) {
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $displayName = $input['display_name'] ?? $username;
        
        if (empty($username) || empty($email) || empty($password)) {
            $this->sendError('用戶名、郵箱和密碼不能為空', 400);
            return;
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password_hash, display_name) 
                VALUES (?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $email, $passwordHash, $displayName]);
            
            $userId = $this->db->lastInsertId();
            
            $response = [
                'user_id' => $userId,
                'username' => $username,
                'message' => '註冊成功！'
            ];
            
            $this->sendSuccess($response);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // 重複鍵錯誤
                $this->sendError('用戶名或郵箱已存在', 409);
            } else {
                $this->sendError('註冊失敗', 500);
            }
        }
    }
    
    // 模擬程式碼執行
    private function simulateCodeExecution($code) {
        // 簡化的程式碼執行模擬
        $output = '';
        $status = 'success';
        
        try {
            // 檢查基本語法
            if (strpos($code, 'print(') !== false) {
                preg_match_all('/print\((.*?)\)/', $code, $matches);
                foreach ($matches[1] as $match) {
                    $content = trim($match, '"\'');
                    $output .= $content . "\n";
                }
            }
            
            // 檢查變數賦值
            if (preg_match('/(\w+)\s*=\s*(.+)/', $code, $matches)) {
                $output .= "變數 {$matches[1]} 已設定\n";
            }
            
            // 檢查迴圈
            if (strpos($code, 'for ') !== false) {
                $output .= "迴圈執行完成\n";
            }
            
            if (empty($output)) {
                $output = "程式碼執行完成";
            }
            
        } catch (Exception $e) {
            $output = "執行錯誤: " . $e->getMessage();
            $status = 'error';
        }
        
        return [
            'output' => trim($output),
            'status' => $status
        ];
    }
    
    // 檢查挑戰答案
    private function checkChallengeAnswer($submitted, $solution) {
        // 簡化的答案檢查
        $submitted = trim(preg_replace('/\s+/', ' ', $submitted));
        $solution = trim(preg_replace('/\s+/', ' ', $solution));
        
        return $submitted === $solution;
    }
    
    // 檢查升級
    private function checkLevelUp($userId) {
        $sql = "SELECT level, experience_points FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $currentLevel = $user['level'];
        $currentExp = $user['experience_points'];
        
        // 計算新等級
        $newLevel = $currentLevel;
        while ($currentExp >= ($newLevel * 100 + 50)) {
            $currentExp -= ($newLevel * 100 + 50);
            $newLevel++;
        }
        
        if ($newLevel > $currentLevel) {
            $sql = "UPDATE users SET level = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newLevel, $userId]);
        }
    }
    
    // 檢查並解鎖成就
    private function checkAndUnlockAchievements($userId) {
        try {
            $sql = "CALL CheckAndUnlockAchievements(?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            // 如果存儲過程不存在，使用簡化版本
            $this->simpleAchievementCheck($userId);
        }
    }
    
    // 簡化的成就檢查
    private function simpleAchievementCheck($userId) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $achievements = [];
        
        // 檢查各種成就
        if ($user['total_code_executions'] >= 1) {
            $achievements[] = 'first_code';
        }
        if ($user['level'] >= 5) {
            $achievements[] = 'level_5';
        }
        if ($user['level'] >= 10) {
            $achievements[] = 'level_10';
        }
        
        // 解鎖成就
        foreach ($achievements as $achievementId) {
            $sql = "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $achievementId]);
        }
    }
    
    // 創建示例用戶
    private function createDemoUser($userId) {
        $sql = "INSERT IGNORE INTO users 
                (id, username, email, password_hash, display_name, avatar_emoji) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            'demo_user_' . $userId,
            'demo' . $userId . '@example.com',
            password_hash('demo123', PASSWORD_DEFAULT),
            '示例用戶',
            '🌟'
        ]);
        
        return [
            'id' => $userId,
            'username' => 'demo_user_' . $userId,
            'display_name' => '示例用戶',
            'avatar_emoji' => '🌟',
            'level' => 1,
            'experience_points' => 0,
            'total_code_executions' => 0,
            'total_lessons_completed' => 0,
            'learning_streak_days' => 0,
            'total_achievements' => 0
        ];
    }
    
    // 創建示例排行榜
    private function createDemoLeaderboard() {
        return [
            [
                'rank_position' => 1,
                'username' => 'python_master',
                'display_name' => '小明',
                'avatar_emoji' => '👨‍💻',
                'level' => 15,
                'experience_points' => 2350,
                'total_code_executions' => 234,
                'total_lessons_completed' => 12,
                'learning_streak_days' => 15
            ],
            [
                'rank_position' => 2,
                'username' => 'code_ninja',
                'display_name' => '小華',
                'avatar_emoji' => '👩‍💻',
                'level' => 12,
                'experience_points' => 1890,
                'total_code_executions' => 189,
                'total_lessons_completed' => 10,
                'learning_streak_days' => 8
            ],
            [
                'rank_position' => 3,
                'username' => 'demo_user_1',
                'display_name' => '你',
                'avatar_emoji' => '🌟',
                'level' => 1,
                'experience_points' => 0,
                'total_code_executions' => 0,
                'total_lessons_completed' => 0,
                'learning_streak_days' => 0
            ]
        ];
    }
    
    // 發送成功響應
    private function sendSuccess($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }
    
    // 發送錯誤響應
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
    }
}

// 處理請求
$api = new GamificationAPI();
$api->handleRequest();
?> 