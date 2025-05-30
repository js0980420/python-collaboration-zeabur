<?php
/**
 * 🚀 最基本的WebSocket測試服務器
 * 用於診斷連接問題
 */

echo "🔧 開始WebSocket基本測試...\n";
echo "📡 測試環境檢查:\n";

// 檢查PHP擴展
$required = ['sockets', 'json'];
$missing = [];

foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext} 擴展已安裝\n";
    } else {
        echo "❌ {$ext} 擴展未安裝\n";
        $missing[] = $ext;
    }
}

if (!empty($missing)) {
    echo "💡 請啟用缺少的PHP擴展\n";
    exit(1);
}

// 檢查端口是否可用
$testSocket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$testSocket) {
    echo "❌ 無法創建socket\n";
    exit(1);
}

$result = @socket_bind($testSocket, '0.0.0.0', 8080);
if (!$result) {
    echo "❌ 端口8080被占用\n";
    socket_close($testSocket);
    exit(1);
}

socket_close($testSocket);
echo "✅ 端口8080可用\n";

// 啟動簡單的TCP服務器
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, '0.0.0.0', 8080);
socket_listen($socket, 5);

echo "\n🚀 基本TCP服務器已啟動\n";
echo "📡 監聽地址: localhost:8080\n";
echo "🌐 測試方法: telnet localhost 8080\n";
echo "⚡ 按Ctrl+C停止\n\n";

$clients = [];

while (true) {
    $read = array_merge([$socket], $clients);
    $write = [];
    $except = [];
    
    if (socket_select($read, $write, $except, 1) > 0) {
        // 新連接
        if (in_array($socket, $read)) {
            $client = socket_accept($socket);
            if ($client !== false) {
                $clients[] = $client;
                socket_getpeername($client, $ip, $port);
                echo "👤 新連接: {$ip}:{$port}\n";
                
                // 發送簡單響應
                $response = "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nWebSocket Server Running!\n";
                socket_write($client, $response);
                socket_close($client);
                
                $key = array_search($client, $clients);
                unset($clients[$key]);
            }
            
            $key = array_search($socket, $read);
            unset($read[$key]);
        }
        
        // 處理客戶端數據
        foreach ($read as $client) {
            $data = @socket_read($client, 1024);
            if ($data === false || $data === '') {
                socket_close($client);
                $key = array_search($client, $clients);
                unset($clients[$key]);
                echo "👋 連接關閉\n";
            } else {
                echo "📨 收到數據: " . substr($data, 0, 100) . "\n";
            }
        }
    }
    
    usleep(100000); // 100ms
}

socket_close($socket);
?> 