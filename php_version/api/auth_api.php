<?php
// 🔐 用戶認證系統 API
// 處理註冊、登入、密碼重設、會話管理等功能

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

class AuthAPI {
    private $db;
    private $secretKey = 'your-secret-key-here'; // 實際使用時應該放在環境變數中
    
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
                case 'PUT':
                    $this->handlePut($action);
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
            case 'profile':
                $this->getUserProfile();
                break;
            case 'verify_session':
                $this->verifySession();
                break;
            case 'logout':
                $this->logout();
                break;
            default:
                $this->sendError('未知的操作', 400);
        }
    }
    
    // 處理POST請求
    private function handlePost($action) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'register':
                $this->register($input);
                break;
            case 'login':
                $this->login($input);
                break;
            case 'forgot_password':
                $this->forgotPassword($input);
                break;
            case 'verify_email':
                $this->verifyEmail($input);
                break;
            default:
                $this->sendError('未知的操作', 400);
        }
    }
    
    // 處理PUT請求
    private function handlePut($action) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'reset_password':
                $this->resetPassword($input);
                break;
            case 'update_profile':
                $this->updateProfile($input);
                break;
            case 'change_password':
                $this->changePassword($input);
                break;
            default:
                $this->sendError('未知的操作', 400);
        }
    }
    
    // 用戶註冊
    private function register($input) {
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $displayName = trim($input['display_name'] ?? $username);
        $avatarEmoji = $input['avatar_emoji'] ?? '🌟';
        
        // 驗證輸入
        $validation = $this->validateRegistration($username, $email, $password);
        if (!$validation['valid']) {
            $this->sendError($validation['message'], 400);
            return;
        }
        
        // 檢查用戶名和郵箱是否已存在
        if ($this->userExists($username, $email)) {
            $this->sendError('用戶名或郵箱已存在', 409);
            return;
        }
        
        // 創建用戶
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $verificationToken = $this->generateToken();
        
        $sql = "INSERT INTO users 
                (username, email, password_hash, display_name, avatar_emoji, email_verification_token, is_email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, 0)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $email, $passwordHash, $displayName, $avatarEmoji, $verificationToken]);
            
            $userId = $this->db->lastInsertId();
            
            // 發送驗證郵件（這裡只是模擬）
            $this->sendVerificationEmail($email, $verificationToken);
            
            // 記錄註冊行為
            $this->logUserActivity($userId, 'register', '用戶註冊');
            
            $response = [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email,
                'display_name' => $displayName,
                'message' => '註冊成功！請檢查您的郵箱以驗證帳戶。'
            ];
            
            $this->sendSuccess($response);
        } catch (PDOException $e) {
            $this->sendError('註冊失敗：' . $e->getMessage(), 500);
        }
    }
    
    // 用戶登入
    private function login($input) {
        $identifier = trim($input['identifier'] ?? ''); // 可以是用戶名或郵箱
        $password = $input['password'] ?? '';
        $rememberMe = $input['remember_me'] ?? false;
        
        if (empty($identifier) || empty($password)) {
            $this->sendError('用戶名/郵箱和密碼不能為空', 400);
            return;
        }
        
        // 查找用戶
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->sendError('用戶名/郵箱或密碼錯誤', 401);
            return;
        }
        
        // 檢查帳戶狀態
        if ($user['is_banned']) {
            $this->sendError('帳戶已被禁用，請聯繫管理員', 403);
            return;
        }
        
        // 創建會話
        $sessionToken = $this->generateToken();
        $expiresAt = $rememberMe ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // 保存會話到資料庫
        $this->createUserSession($user['id'], $sessionToken, $expiresAt);
        
        // 更新最後登入時間
        $sql = "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user['id']]);
        
        // 記錄登入行為
        $this->logUserActivity($user['id'], 'login', '用戶登入');
        
        // 設置會話
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['session_token'] = $sessionToken;
        
        $response = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'email' => $user['email'],
            'avatar_emoji' => $user['avatar_emoji'],
            'level' => $user['level'],
            'experience_points' => $user['experience_points'],
            'session_token' => $sessionToken,
            'expires_at' => $expiresAt,
            'message' => '登入成功！'
        ];
        
        $this->sendSuccess($response);
    }
    
    // 忘記密碼
    private function forgotPassword($input) {
        $email = trim($input['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendError('請提供有效的郵箱地址', 400);
            return;
        }
        
        // 查找用戶
        $sql = "SELECT id, username FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // 為了安全，即使用戶不存在也返回成功訊息
            $this->sendSuccess(['message' => '如果該郵箱存在，重設密碼連結已發送']);
            return;
        }
        
        // 生成重設令牌
        $resetToken = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // 保存重設令牌
        $sql = "UPDATE users SET 
                password_reset_token = ?, 
                password_reset_expires = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$resetToken, $expiresAt, $user['id']]);
        
        // 發送重設郵件（這裡只是模擬）
        $this->sendPasswordResetEmail($email, $resetToken);
        
        // 記錄行為
        $this->logUserActivity($user['id'], 'password_reset_request', '請求密碼重設');
        
        $this->sendSuccess(['message' => '密碼重設連結已發送到您的郵箱']);
    }
    
    // 重設密碼
    private function resetPassword($input) {
        $token = $input['token'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        
        if (empty($token) || empty($newPassword)) {
            $this->sendError('令牌和新密碼不能為空', 400);
            return;
        }
        
        if (!$this->validatePassword($newPassword)) {
            $this->sendError('密碼必須至少8個字符，包含字母和數字', 400);
            return;
        }
        
        // 驗證令牌
        $sql = "SELECT id FROM users 
                WHERE password_reset_token = ? 
                AND password_reset_expires > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->sendError('無效或已過期的重設令牌', 400);
            return;
        }
        
        // 更新密碼
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET 
                password_hash = ?, 
                password_reset_token = NULL, 
                password_reset_expires = NULL,
                password_updated_at = NOW()
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$passwordHash, $user['id']]);
        
        // 清除所有會話（強制重新登入）
        $this->clearUserSessions($user['id']);
        
        // 記錄行為
        $this->logUserActivity($user['id'], 'password_reset', '密碼重設成功');
        
        $this->sendSuccess(['message' => '密碼重設成功，請重新登入']);
    }
    
    // 獲取用戶資料
    private function getUserProfile() {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->sendError('未登入', 401);
            return;
        }
        
        $sql = "SELECT 
                    u.*,
                    COUNT(DISTINCT ua.achievement_id) as total_achievements,
                    COUNT(DISTINCT ulp.lesson_id) as completed_lessons,
                    COUNT(DISTINCT ce.id) as total_executions
                FROM users u
                LEFT JOIN user_achievements ua ON u.id = ua.user_id
                LEFT JOIN user_lesson_progress ulp ON u.id = ulp.user_id AND ulp.status = 'completed'
                LEFT JOIN code_executions ce ON u.id = ce.user_id
                WHERE u.id = ?
                GROUP BY u.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->sendError('用戶不存在', 404);
            return;
        }
        
        // 移除敏感信息
        unset($user['password_hash']);
        unset($user['password_reset_token']);
        unset($user['email_verification_token']);
        
        $this->sendSuccess($user);
    }
    
    // 驗證會話
    private function verifySession() {
        $token = $_GET['token'] ?? $_SESSION['session_token'] ?? '';
        
        if (empty($token)) {
            $this->sendError('未提供會話令牌', 401);
            return;
        }
        
        $sql = "SELECT us.*, u.username, u.display_name 
                FROM user_sessions us
                JOIN users u ON us.user_id = u.id
                WHERE us.session_token = ? 
                AND us.expires_at > NOW() 
                AND us.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            $this->sendError('無效或已過期的會話', 401);
            return;
        }
        
        // 更新最後活動時間
        $sql = "UPDATE user_sessions SET last_activity = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$session['id']]);
        
        $response = [
            'valid' => true,
            'user_id' => $session['user_id'],
            'username' => $session['username'],
            'display_name' => $session['display_name'],
            'expires_at' => $session['expires_at']
        ];
        
        $this->sendSuccess($response);
    }
    
    // 登出
    private function logout() {
        $userId = $this->getCurrentUserId();
        $token = $_SESSION['session_token'] ?? '';
        
        if ($userId && $token) {
            // 停用會話
            $sql = "UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND session_token = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $token]);
            
            // 記錄登出行為
            $this->logUserActivity($userId, 'logout', '用戶登出');
        }
        
        // 清除PHP會話
        session_destroy();
        
        $this->sendSuccess(['message' => '登出成功']);
    }
    
    // 輔助方法
    private function validateRegistration($username, $email, $password) {
        if (empty($username) || empty($email) || empty($password)) {
            return ['valid' => false, 'message' => '所有欄位都是必填的'];
        }
        
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['valid' => false, 'message' => '用戶名必須在3-50個字符之間'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => '請提供有效的郵箱地址'];
        }
        
        if (!$this->validatePassword($password)) {
            return ['valid' => false, 'message' => '密碼必須至少8個字符，包含字母和數字'];
        }
        
        return ['valid' => true];
    }
    
    private function validatePassword($password) {
        return strlen($password) >= 8 && 
               preg_match('/[A-Za-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    private function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $email]);
        return $stmt->fetch() !== false;
    }
    
    private function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    private function createUserSession($userId, $token, $expiresAt) {
        $sql = "INSERT INTO user_sessions 
                (user_id, session_token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId, 
            $token, 
            $expiresAt, 
            $_SERVER['REMOTE_ADDR'] ?? '', 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    private function clearUserSessions($userId) {
        $sql = "UPDATE user_sessions SET is_active = 0 WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    private function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    private function logUserActivity($userId, $action, $description) {
        $sql = "INSERT INTO user_activity_logs 
                (user_id, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId, 
                $action, 
                $description, 
                $_SERVER['REMOTE_ADDR'] ?? '', 
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (PDOException $e) {
            // 記錄失敗不影響主要功能
        }
    }
    
    private function sendVerificationEmail($email, $token) {
        // 實際應用中應該整合真實的郵件服務
        // 這裡只是模擬
        $verificationLink = "http://localhost/python-teaching/verify.php?token=" . $token;
        
        // 模擬發送郵件
        error_log("驗證郵件發送到: $email, 連結: $verificationLink");
    }
    
    private function sendPasswordResetEmail($email, $token) {
        // 實際應用中應該整合真實的郵件服務
        $resetLink = "http://localhost/python-teaching/reset-password.php?token=" . $token;
        
        // 模擬發送郵件
        error_log("密碼重設郵件發送到: $email, 連結: $resetLink");
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
$api = new AuthAPI();
$api->handleRequest();
?> 