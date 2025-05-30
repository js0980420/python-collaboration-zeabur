<?php
/**
 * 聊天API測試腳本
 * 測試聊天和代碼同步功能是否正常
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天API測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        button { padding: 10px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .result { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; white-space: pre-wrap; }
        input[type="text"] { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; width: 200px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Python協作平台 - 聊天API測試</h1>
        
        <div class="test-section">
            <h2>📡 API狀態檢查</h2>
            <button class="btn-primary" onclick="testChatStatus()">測試聊天API狀態</button>
            <button class="btn-primary" onclick="testCodeSyncStatus()">測試代碼同步API狀態</button>
            <div id="status-result" class="result"></div>
        </div>
        
        <div class="test-section">
            <h2>💬 聊天功能測試</h2>
            <input type="text" id="test-message" placeholder="輸入測試消息" value="這是一條測試消息">
            <input type="text" id="test-room" placeholder="房間代碼" value="test-room">
            <br>
            <button class="btn-success" onclick="sendTestMessage()">發送測試消息</button>
            <button class="btn-warning" onclick="getMessages()">獲取消息</button>
            <div id="chat-result" class="result"></div>
        </div>
        
        <div class="test-section">
            <h2>💾 代碼同步測試</h2>
            <textarea id="test-code" rows="5" style="width: 100%; padding: 10px;">
# 測試代碼
print("Hello, XAMPP!")
def test_function():
    return "協作測試成功"
            </textarea>
            <br>
            <button class="btn-success" onclick="saveTestCode()">保存代碼</button>
            <button class="btn-warning" onclick="loadTestCode()">載入代碼</button>
            <div id="code-result" class="result"></div>
        </div>
        
        <div class="test-section">
            <h2>🗄️ 資料庫狀態</h2>
            <button class="btn-primary" onclick="checkDatabase()">檢查資料庫</button>
            <div id="db-result" class="result"></div>
        </div>
    </div>

    <script>
        async function testChatStatus() {
            const resultDiv = document.getElementById('status-result');
            resultDiv.innerHTML = '正在測試聊天API狀態...';
            
            try {
                const response = await fetch('chat_api_handler.php?action=status');
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `<span class="success">✅ 聊天API正常</span>\n` +
                        `總消息數: ${result.total_messages}\n` +
                        `總房間數: ${result.total_rooms}\n` +
                        `伺服器時間: ${result.server_time}`;
                } else {
                    resultDiv.innerHTML = `<span class="error">❌ 聊天API錯誤: ${result.error}</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span class="error">❌ 連接失敗: ${error.message}</span>`;
            }
        }
        
        async function testCodeSyncStatus() {
            const resultDiv = document.getElementById('status-result');
            resultDiv.innerHTML = '正在測試代碼同步API狀態...';
            
            try {
                const response = await fetch('code_sync_handler.php?action=status');
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML += `\n<span class="success">✅ 代碼同步API正常</span>\n` +
                        `總快照數: ${result.total_snapshots}\n` +
                        `總變更數: ${result.total_changes}\n` +
                        `伺服器時間: ${result.server_time}`;
                } else {
                    resultDiv.innerHTML += `\n<span class="error">❌ 代碼同步API錯誤: ${result.error}</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML += `\n<span class="error">❌ 連接失敗: ${error.message}</span>`;
            }
        }
        
        async function sendTestMessage() {
            const message = document.getElementById('test-message').value;
            const room = document.getElementById('test-room').value;
            const resultDiv = document.getElementById('chat-result');
            
            resultDiv.innerHTML = '正在發送測試消息...';
            
            try {
                const response = await fetch('chat_api_handler.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'send',
                        room: room,
                        userId: 'test_user',
                        userName: '測試用戶',
                        message: message,
                        messageType: 'user'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `<span class="success">✅ 消息發送成功</span>\n` +
                        `消息ID: ${result.data.id}\n` +
                        `房間ID: ${result.data.room_id}\n` +
                        `時間戳: ${result.timestamp}`;
                } else {
                    resultDiv.innerHTML = `<span class="error">❌ 發送失敗: ${result.error}</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span class="error">❌ 連接失敗: ${error.message}</span>`;
            }
        }
        
        async function getMessages() {
            const room = document.getElementById('test-room').value;
            const resultDiv = document.getElementById('chat-result');
            
            resultDiv.innerHTML = '正在載入消息...';
            
            try {
                const response = await fetch(`chat_api_handler.php?action=get&room=${room}&limit=5`);
                const result = await response.json();
                
                if (result.success) {
                    let messages = result.data.map(msg => 
                        `[${new Date(msg.timestamp * 1000).toLocaleString()}] ${msg.user_name}: ${msg.message}`
                    ).join('\n');
                    
                    resultDiv.innerHTML = `<span class="success">✅ 載入了 ${result.count} 條消息</span>\n\n${messages}`;
                } else {
                    resultDiv.innerHTML = `<span class="error">❌ 載入失敗: ${result.error}</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span class="error">❌ 連接失敗: ${error.message}</span>`;
            }
        }
        
        async function saveTestCode() {
            const code = document.getElementById('test-code').value;
            const room = document.getElementById('test-room').value;
            const resultDiv = document.getElementById('code-result');
            
            resultDiv.innerHTML = '正在保存代碼...';
            
            try {
                const response = await fetch('code_sync_handler.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save',
                        room: room,
                        code: code,
                        userId: 1
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `<span class="success">✅ 代碼保存成功</span>\n` +
                        `房間ID: ${result.data.room_id}\n` +
                        `版本: ${result.data.version}\n` +
                        `代碼長度: ${result.data.code_length}\n` +
                        `消息: ${result.message}`;
                } else {
                    resultDiv.innerHTML = `<span class="error">❌ 保存失敗: ${result.error}</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span class="error">❌ 連接失敗: ${error.message}</span>`;
            }
        }
        
        async function loadTestCode() {
            const room = document.getElementById('test-room').value;
            const resultDiv = document.getElementById('code-result');
            
            resultDiv.innerHTML = '正在載入代碼...';
            
            try {
                const response = await fetch(`code_sync_handler.php?action=load&room=${room}`);
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('test-code').value = result.data.code_content;
                    resultDiv.innerHTML = `<span class="success">✅ 代碼載入成功</span>\n` +
                        `版本: ${result.data.version}\n` +
                        `房間ID: ${result.data.room_id || 'null'}\n` +
                        `創建時間: ${result.data.created_at}\n` +
                        `是否預設: ${result.data.is_default ? '是' : '否'}`;
                } else {
                    resultDiv.innerHTML = `<span class="error">❌ 載入失敗: ${result.error}</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span class="error">❌ 連接失敗: ${error.message}</span>`;
            }
        }
        
        async function checkDatabase() {
            const resultDiv = document.getElementById('db-result');
            resultDiv.innerHTML = '正在檢查資料庫...';
            
            try {
                // 檢查是否可以連接到初始化腳本
                const response = await fetch('init_database.php');
                const text = await response.text();
                
                if (text.includes('數據庫初始化完成')) {
                    resultDiv.innerHTML = `<span class="success">✅ 資料庫正常</span>\n資料庫已初始化並可正常連接`;
                } else {
                    resultDiv.innerHTML = `<span class="info">ℹ️ 資料庫狀態</span>\n可以連接到初始化腳本，建議重新初始化資料庫`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span class="error">❌ 資料庫連接失敗: ${error.message}</span>`;
            }
        }
        
        // 頁面載入時自動測試基本狀態
        window.onload = function() {
            testChatStatus();
            setTimeout(testCodeSyncStatus, 500);
        };
    </script>
</body>
</html> 