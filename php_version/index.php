<?php
session_start();

// 載入環境變數（如果有.env檔）
if (file_exists('.env')) {
    $env = file_get_contents('.env');
    $lines = explode("\n", $env);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// 載入必要的類
require_once 'config/database.php';
require_once 'classes/UserManager.php';

// 簡單路由系統
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// 移除開頭的斜線
$path = ltrim($path, '/');

// 如果路徑為空，設為首頁
if (empty($path) || $path === 'index.php') {
    $path = 'home';
}

// 處理API請求
if (strpos($path, 'api/') === 0) {
    $apiFile = str_replace('api/', '', $path) . '.php';
    $apiPath = __DIR__ . '/api/' . $apiFile;
    
    if (file_exists($apiPath)) {
        include $apiPath;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API端點不存在']);
    }
    exit();
}

// 定義頁面路由
$routes = [
    'home' => 'pages/index.php',
    '' => 'pages/index.php',
    'lessons' => 'pages/lessons.php',
    'ai-tutor' => 'pages/ai_tutor.php',
    'practice' => 'pages/practice.php'
];

// 處理靜態資源
if (strpos($path, 'static/') === 0) {
    $staticFile = __DIR__ . '/' . $path;
    if (file_exists($staticFile)) {
        $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml'
        ];
        
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        
        readfile($staticFile);
        exit();
    }
}

// 獲取課程數據的函數
function getLessons() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM lessons ORDER BY order_index");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// 處理頁面路由
if (isset($routes[$path])) {
    $pageFile = $routes[$path];
    
    if (file_exists($pageFile)) {
        include $pageFile;
    } else {
        http_response_code(404);
        echo '<h1>404 - 頁面不存在</h1>';
    }
} else {
    http_response_code(404);
    include 'pages/404.php';
}
?> 