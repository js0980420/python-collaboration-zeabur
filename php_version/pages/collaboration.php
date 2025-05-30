<?php
session_start();
require_once '../config/database.php';

// 檢查用戶是否登入
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$roomId = $_GET['room'] ?? 'demo-room';
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>多人協作編程 - Python教學平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
    <style>
        .collaboration-container {
            height: 100vh;
            overflow: hidden;
        }
        
        .code-editor-container {
            position: relative;
            height: 400px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .code-editor {
            width: 100%;
            height: 100%;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border: none;
            outline: none;
            padding: 15px;
            resize: none;
            background-color: #f8f9fa;
        }
        
        .user-cursor {
            position: absolute;
            width: 2px;
            height: 20px;
            z-index: 1000;
            pointer-events: none;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        .cursor-label {
            position: absolute;
            top: -25px;
            left: 0;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .members-panel {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            height: 400px;
            overflow-y: auto;
        }
        
        .member-item {
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            background-color: white;
            border: 1px solid #e9ecef;
        }
        
        .chat-panel {
            background-color: #f8f9fa;
            border-radius: 8px;
            height: 300px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background-color: white;
            border-radius: 8px 8px 0 0;
        }
        
        .chat-input-area {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 0 0 8px 8px;
            border-top: 1px solid #e9ecef;
        }
        
        .chat-message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 8px;
        }
        
        .own-message {
            background-color: #007bff;
            color: white;
            margin-left: 20%;
        }
        
        .other-message {
            background-color: #e9ecef;
            margin-right: 20%;
        }
        
        .system-message {
            background-color: #fff3cd;
            color: #856404;
            font-style: italic;
            text-align: center;
        }
        
        .connection-status {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .typing-indicator {
            font-style: italic;
            color: #6c757d;
            font-size: 12px;
            padding: 5px 15px;
            display: none;
        }
        
        .output-panel {
            background-color: #212529;
            color: #ffffff;
            font-family: 'Courier New', monospace;
            padding: 15px;
            border-radius: 8px;
            height: 200px;
            overflow-y: auto;
        }
        
        .toolbar {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="collaboration-container">
        <!-- 連接狀態指示器 -->
        <div class="connection-status">
            <span id="connection-status" class="badge bg-warning">連接中...</span>
        </div>
        
        <!-- 頂部工具欄 -->
        <div class="toolbar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> 
                        協作房間: <span class="text-primary"><?= htmlspecialchars($roomId) ?></span>
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success btn-sm" id="run-code">
                        <i class="fas fa-play"></i> 執行程式碼
                    </button>
                    <button class="btn btn-secondary btn-sm" id="save-code">
                        <i class="fas fa-save"></i> 保存
                    </button>
                    <button class="btn btn-info btn-sm" onclick="requestAIHelp()">
                        <i class="fas fa-robot"></i> AI助手
                    </button>
                    <button class="btn btn-outline-danger btn-sm" id="leave-room">
                        <i class="fas fa-sign-out-alt"></i> 離開房間
                    </button>
                </div>
            </div>
        </div>
        
        <div class="container-fluid">
            <div class="row">
                <!-- 左側：程式碼編輯器 -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-code"></i> 協作程式碼編輯器
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="code-editor-container">
                                <textarea 
                                    id="code-editor" 
                                    class="code-editor" 
                                    placeholder="在這裡輸入Python程式碼...&#10;&#10;# 示例：&#10;print('Hello, 協作編程!')&#10;name = input('請輸入您的名字: ')&#10;print(f'歡迎, {name}!')">print('Hello, 協作編程!')
name = input('請輸入您的名字: ')
print(f'歡迎, {name}!')</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 程式執行結果 -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-terminal"></i> 執行結果
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="output-panel" class="output-panel">
                                <div class="text-muted">點擊「執行程式碼」查看結果...</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 右側：成員列表和聊天 -->
                <div class="col-md-4">
                    <!-- 房間成員 -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-users"></i> 房間成員
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="members-list" class="members-panel">
                                <!-- 動態載入成員列表 -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- 即時聊天 -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-comments"></i> 即時聊天
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="chat-panel">
                                <div id="chat-messages" class="chat-messages">
                                    <div class="system-message">
                                        <small><i>歡迎來到協作房間！</i></small>
                                    </div>
                                </div>
                                <div id="typing-indicator" class="typing-indicator"></div>
                                <div class="chat-input-area">
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            id="chat-input" 
                                            class="form-control" 
                                            placeholder="輸入消息..."
                                            maxlength="500"
                                        >
                                        <button class="btn btn-primary" id="send-chat">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script src="../static/js/websocket-client.js"></script>
    <script src="../static/js/ai-collaboration-helper.js"></script>
    
    <script>
        // 初始化協作環境
        document.addEventListener('DOMContentLoaded', function() {
            const roomId = '<?= $roomId ?>';
            const userId = <?= $userId ?>;
            const username = '<?= $username ?>';
            
            // 初始化WebSocket連接
            initCollaboration(roomId, userId, username);
            
            // 初始化AI協作助手
            initAICollaborationHelper(roomId, userId);
            
            // 綁定按鈕事件
            bindButtonEvents();
            
            // 顯示歡迎消息
            showWelcomeMessage();
        });
        
        function bindButtonEvents() {
            // 執行程式碼
            document.getElementById('run-code').addEventListener('click', function() {
                const code = document.getElementById('code-editor').value;
                executeCode(code);
            });
            
            // 保存程式碼
            document.getElementById('save-code').addEventListener('click', function() {
                const code = document.getElementById('code-editor').value;
                saveCode(code);
            });
            
            // 離開房間
            document.getElementById('leave-room').addEventListener('click', function() {
                if (confirm('確定要離開協作房間嗎？')) {
                    leaveRoom();
                }
            });
        }
        
        function executeCode(code) {
            const outputPanel = document.getElementById('output-panel');
            outputPanel.innerHTML = '<div class="text-info">正在執行程式碼...</div>';
            
            // 模擬程式碼執行（實際應用中可以調用後端API）
            setTimeout(() => {
                try {
                    // 簡單的Python程式碼模擬執行
                    let output = '';
                    
                    if (code.includes('print(')) {
                        const printMatches = code.match(/print\((.*?)\)/g);
                        if (printMatches) {
                            printMatches.forEach(match => {
                                const content = match.match(/print\((.*?)\)/)[1];
                                const cleanContent = content.replace(/['"]/g, '');
                                output += cleanContent + '\n';
                            });
                        }
                    }
                    
                    if (code.includes('input(')) {
                        output += '模擬用戶輸入: 小明\n';
                    }
                    
                    if (!output) {
                        output = '程式碼執行完成（無輸出）';
                    }
                    
                    outputPanel.innerHTML = `<div class="text-success">${output}</div>`;
                    
                    // 通知其他用戶程式碼已執行
                    if (collaborationWS) {
                        collaborationWS.sendChatMessage(`執行了程式碼 ✅`);
                    }
                    
                } catch (error) {
                    outputPanel.innerHTML = `<div class="text-danger">執行錯誤: ${error.message}</div>`;
                }
            }, 1000);
        }
        
        function saveCode(code) {
            // 保存程式碼到服務器
            fetch('../api/collaboration_api.php?action=save_code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_id: '<?= $roomId ?>',
                    user_id: <?= $userId ?>,
                    code: code
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('程式碼已保存', 'success');
                } else {
                    showNotification('保存失敗: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showNotification('保存失敗: ' + error.message, 'danger');
            });
        }
        
        function leaveRoom() {
            if (collaborationWS) {
                collaborationWS.disconnect();
            }
            window.location.href = '../index.php';
        }
        
        function showWelcomeMessage() {
            const chatMessages = document.getElementById('chat-messages');
            const welcomeMsg = document.createElement('div');
            welcomeMsg.className = 'system-message mb-2';
            welcomeMsg.innerHTML = `<small><i>您已加入協作房間 ${roomId}</i></small>`;
            chatMessages.appendChild(welcomeMsg);
        }
        
        function showNotification(message, type) {
            // 創建通知元素
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show`;
            notification.style.cssText = `
                position: fixed;
                top: 70px;
                right: 20px;
                z-index: 1050;
                min-width: 300px;
            `;
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // 3秒後自動移除
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
        
        // 鍵盤快捷鍵
        document.addEventListener('keydown', function(event) {
            // Ctrl+Enter 執行程式碼
            if (event.ctrlKey && event.key === 'Enter') {
                event.preventDefault();
                document.getElementById('run-code').click();
            }
            
            // Ctrl+S 保存程式碼
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault();
                document.getElementById('save-code').click();
            }
        });
        
        // 頁面可見性變化處理
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // 頁面隱藏時暫停某些功能
                console.log('頁面隱藏');
            } else {
                // 頁面顯示時恢復功能
                console.log('頁面顯示');
            }
        });
    </script>
</body>
</html> 