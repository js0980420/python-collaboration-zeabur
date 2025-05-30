// 🔌 WebSocket客戶端 - 多人協作功能
class CollaborationWebSocket {
    constructor(roomId, userId, username) {
        this.roomId = roomId;
        this.userId = userId;
        this.username = username;
        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000; // 1秒
        
        // 事件回調
        this.onUserJoined = null;
        this.onUserLeft = null;
        this.onCodeChanged = null;
        this.onCursorMoved = null;
        this.onChatMessage = null;
        this.onTypingStatus = null;
        
        this.init();
    }
    
    // 初始化WebSocket連接
    init() {
        try {
            // WebSocket服務器地址（需要配置）
            const wsUrl = `ws://localhost:8080/collaboration?room=${this.roomId}&user=${this.userId}`;
            this.socket = new WebSocket(wsUrl);
            
            this.setupEventListeners();
        } catch (error) {
            console.error('WebSocket初始化失敗:', error);
            this.handleReconnect();
        }
    }
    
    // 設置事件監聽器
    setupEventListeners() {
        // 連接成功
        this.socket.onopen = (event) => {
            console.log('WebSocket連接成功');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            
            // 發送加入房間消息
            this.sendMessage({
                type: 'join_room',
                room_id: this.roomId,
                user_id: this.userId,
                username: this.username
            });
            
            // 顯示連接狀態
            this.updateConnectionStatus('已連接', 'success');
        };
        
        // 接收消息
        this.socket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.handleMessage(data);
            } catch (error) {
                console.error('解析WebSocket消息失敗:', error);
            }
        };
        
        // 連接關閉
        this.socket.onclose = (event) => {
            console.log('WebSocket連接關閉:', event.code, event.reason);
            this.isConnected = false;
            this.updateConnectionStatus('連接中斷', 'warning');
            
            // 自動重連
            if (!event.wasClean) {
                this.handleReconnect();
            }
        };
        
        // 連接錯誤
        this.socket.onerror = (error) => {
            console.error('WebSocket錯誤:', error);
            this.updateConnectionStatus('連接錯誤', 'danger');
        };
    }
    
    // 處理接收到的消息
    handleMessage(data) {
        switch (data.type) {
            case 'user_joined':
                this.handleUserJoined(data);
                break;
                
            case 'user_left':
                this.handleUserLeft(data);
                break;
                
            case 'code_changed':
                this.handleCodeChanged(data);
                break;
                
            case 'cursor_moved':
                this.handleCursorMoved(data);
                break;
                
            case 'chat_message':
                this.handleChatMessage(data);
                break;
                
            case 'typing_status':
                this.handleTypingStatus(data);
                break;
                
            case 'room_members':
                this.handleRoomMembers(data);
                break;
                
            default:
                console.log('未知消息類型:', data.type);
        }
    }
    
    // 處理用戶加入
    handleUserJoined(data) {
        console.log(`用戶 ${data.username} 加入房間`);
        
        // 添加用戶到成員列表
        this.addUserToMembersList(data.user_id, data.username, data.avatar_emoji);
        
        // 顯示系統消息
        this.addSystemMessage(`${data.username} 加入了協作`);
        
        // 觸發回調
        if (this.onUserJoined) {
            this.onUserJoined(data);
        }
    }
    
    // 處理用戶離開
    handleUserLeft(data) {
        console.log(`用戶 ${data.username} 離開房間`);
        
        // 從成員列表移除用戶
        this.removeUserFromMembersList(data.user_id);
        
        // 移除用戶游標
        this.removeUserCursor(data.user_id);
        
        // 顯示系統消息
        this.addSystemMessage(`${data.username} 離開了協作`);
        
        // 觸發回調
        if (this.onUserLeft) {
            this.onUserLeft(data);
        }
    }
    
    // 處理程式碼變更
    handleCodeChanged(data) {
        if (data.user_id === this.userId) {
            return; // 忽略自己的變更
        }
        
        console.log(`用戶 ${data.username} 修改了程式碼`);
        
        // 更新編輯器內容
        this.updateEditorContent(data.code, data.change_info);
        
        // 觸發回調
        if (this.onCodeChanged) {
            this.onCodeChanged(data);
        }
    }
    
    // 處理游標移動
    handleCursorMoved(data) {
        if (data.user_id === this.userId) {
            return; // 忽略自己的游標
        }
        
        // 更新其他用戶的游標位置
        this.updateUserCursor(data.user_id, data.username, data.position, data.color);
        
        // 觸發回調
        if (this.onCursorMoved) {
            this.onCursorMoved(data);
        }
    }
    
    // 處理聊天消息
    handleChatMessage(data) {
        console.log(`收到聊天消息: ${data.username}: ${data.message}`);
        
        // 添加消息到聊天區域
        this.addChatMessage(data.username, data.message, data.timestamp, data.user_id === this.userId);
        
        // 觸發回調
        if (this.onChatMessage) {
            this.onChatMessage(data);
        }
    }
    
    // 處理打字狀態
    handleTypingStatus(data) {
        if (data.user_id === this.userId) {
            return; // 忽略自己的打字狀態
        }
        
        // 顯示/隱藏打字指示器
        this.updateTypingIndicator(data.user_id, data.username, data.is_typing);
        
        // 觸發回調
        if (this.onTypingStatus) {
            this.onTypingStatus(data);
        }
    }
    
    // 處理房間成員列表
    handleRoomMembers(data) {
        console.log('房間成員列表:', data.members);
        
        // 清空並重新填充成員列表
        this.updateMembersList(data.members);
    }
    
    // 發送消息
    sendMessage(message) {
        if (this.isConnected && this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify(message));
        } else {
            console.warn('WebSocket未連接，無法發送消息');
        }
    }
    
    // 發送程式碼變更
    sendCodeChange(code, changeInfo) {
        this.sendMessage({
            type: 'code_changed',
            room_id: this.roomId,
            user_id: this.userId,
            username: this.username,
            code: code,
            change_info: changeInfo,
            timestamp: Date.now()
        });
    }
    
    // 發送游標位置
    sendCursorPosition(position) {
        this.sendMessage({
            type: 'cursor_moved',
            room_id: this.roomId,
            user_id: this.userId,
            username: this.username,
            position: position,
            color: this.getUserColor(),
            timestamp: Date.now()
        });
    }
    
    // 發送聊天消息
    sendChatMessage(message) {
        this.sendMessage({
            type: 'chat_message',
            room_id: this.roomId,
            user_id: this.userId,
            username: this.username,
            message: message,
            timestamp: Date.now()
        });
    }
    
    // 發送打字狀態
    sendTypingStatus(isTyping) {
        this.sendMessage({
            type: 'typing_status',
            room_id: this.roomId,
            user_id: this.userId,
            username: this.username,
            is_typing: isTyping,
            timestamp: Date.now()
        });
    }
    
    // 處理重連
    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`嘗試重連 (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            
            setTimeout(() => {
                this.init();
            }, this.reconnectDelay * this.reconnectAttempts);
        } else {
            console.error('重連失敗，已達到最大嘗試次數');
            this.updateConnectionStatus('連接失敗', 'danger');
        }
    }
    
    // UI更新方法
    updateConnectionStatus(status, type) {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.textContent = status;
            statusElement.className = `badge bg-${type}`;
        }
    }
    
    addUserToMembersList(userId, username, avatarEmoji) {
        const membersList = document.getElementById('members-list');
        if (!membersList) return;
        
        const memberElement = document.createElement('div');
        memberElement.id = `member-${userId}`;
        memberElement.className = 'member-item d-flex align-items-center mb-2';
        memberElement.innerHTML = `
            <span class="avatar-emoji me-2">${avatarEmoji || '👤'}</span>
            <span class="username">${username}</span>
            <span class="status-indicator ms-auto">
                <i class="fas fa-circle text-success"></i>
            </span>
        `;
        
        membersList.appendChild(memberElement);
    }
    
    removeUserFromMembersList(userId) {
        const memberElement = document.getElementById(`member-${userId}`);
        if (memberElement) {
            memberElement.remove();
        }
    }
    
    updateUserCursor(userId, username, position, color) {
        // 移除舊游標
        this.removeUserCursor(userId);
        
        // 創建新游標
        const editor = document.getElementById('code-editor');
        if (!editor) return;
        
        const cursor = document.createElement('div');
        cursor.id = `cursor-${userId}`;
        cursor.className = 'user-cursor';
        cursor.style.cssText = `
            position: absolute;
            width: 2px;
            height: 20px;
            background-color: ${color};
            z-index: 1000;
            pointer-events: none;
        `;
        
        // 添加用戶名標籤
        const label = document.createElement('div');
        label.className = 'cursor-label';
        label.textContent = username;
        label.style.cssText = `
            position: absolute;
            top: -25px;
            left: 0;
            background-color: ${color};
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            white-space: nowrap;
        `;
        
        cursor.appendChild(label);
        
        // 計算游標位置
        const cursorPos = this.calculateCursorPosition(position);
        cursor.style.left = cursorPos.x + 'px';
        cursor.style.top = cursorPos.y + 'px';
        
        editor.appendChild(cursor);
    }
    
    removeUserCursor(userId) {
        const cursor = document.getElementById(`cursor-${userId}`);
        if (cursor) {
            cursor.remove();
        }
    }
    
    addChatMessage(username, message, timestamp, isOwnMessage) {
        const chatMessages = document.getElementById('chat-messages');
        if (!chatMessages) return;
        
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${isOwnMessage ? 'own-message' : 'other-message'} mb-2`;
        
        const time = new Date(timestamp).toLocaleTimeString();
        messageElement.innerHTML = `
            <div class="message-header">
                <strong>${username}</strong>
                <small class="text-muted ms-2">${time}</small>
            </div>
            <div class="message-content">${this.escapeHtml(message)}</div>
        `;
        
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    addSystemMessage(message) {
        const chatMessages = document.getElementById('chat-messages');
        if (!chatMessages) return;
        
        const messageElement = document.createElement('div');
        messageElement.className = 'system-message text-center text-muted mb-2';
        messageElement.innerHTML = `<small><i>${message}</i></small>`;
        
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    updateTypingIndicator(userId, username, isTyping) {
        const indicator = document.getElementById('typing-indicator');
        if (!indicator) return;
        
        if (isTyping) {
            indicator.textContent = `${username} 正在輸入...`;
            indicator.style.display = 'block';
        } else {
            indicator.style.display = 'none';
        }
    }
    
    // 輔助方法
    getUserColor() {
        // 根據用戶ID生成顏色
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'];
        return colors[this.userId % colors.length];
    }
    
    calculateCursorPosition(position) {
        // 這裡需要根據實際的編輯器實現來計算游標位置
        // 示例實現
        const lineHeight = 20;
        const charWidth = 8;
        
        return {
            x: position.column * charWidth,
            y: position.line * lineHeight
        };
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    updateEditorContent(code, changeInfo) {
        // 這裡需要根據實際使用的編輯器來實現
        // 例如：CodeMirror, Monaco Editor等
        const editor = document.getElementById('code-editor');
        if (editor && editor.setValue) {
            editor.setValue(code);
        }
    }
    
    updateMembersList(members) {
        const membersList = document.getElementById('members-list');
        if (!membersList) return;
        
        membersList.innerHTML = '';
        members.forEach(member => {
            this.addUserToMembersList(member.user_id, member.username, member.avatar_emoji);
        });
    }
    
    // 關閉連接
    disconnect() {
        if (this.socket) {
            this.socket.close();
        }
    }
}

// 使用示例
let collaborationWS = null;

function initCollaboration(roomId, userId, username) {
    collaborationWS = new CollaborationWebSocket(roomId, userId, username);
    
    // 設置事件回調
    collaborationWS.onUserJoined = (data) => {
        console.log('用戶加入回調:', data);
    };
    
    collaborationWS.onCodeChanged = (data) => {
        console.log('程式碼變更回調:', data);
    };
    
    // 綁定編輯器事件
    bindEditorEvents();
    
    // 綁定聊天事件
    bindChatEvents();
}

function bindEditorEvents() {
    const editor = document.getElementById('code-editor');
    if (!editor) return;
    
    // 程式碼變更事件
    editor.addEventListener('input', (event) => {
        if (collaborationWS) {
            const code = editor.value;
            const changeInfo = {
                type: 'insert',
                position: editor.selectionStart,
                text: event.data
            };
            collaborationWS.sendCodeChange(code, changeInfo);
        }
    });
    
    // 游標移動事件
    editor.addEventListener('selectionchange', (event) => {
        if (collaborationWS) {
            const position = {
                line: getCurrentLine(editor),
                column: getCurrentColumn(editor)
            };
            collaborationWS.sendCursorPosition(position);
        }
    });
}

function bindChatEvents() {
    const chatInput = document.getElementById('chat-input');
    const sendButton = document.getElementById('send-chat');
    
    if (!chatInput || !sendButton) return;
    
    // 發送消息
    function sendMessage() {
        const message = chatInput.value.trim();
        if (message && collaborationWS) {
            collaborationWS.sendChatMessage(message);
            chatInput.value = '';
        }
    }
    
    sendButton.addEventListener('click', sendMessage);
    
    chatInput.addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            sendMessage();
        }
    });
    
    // 打字狀態
    let typingTimer;
    chatInput.addEventListener('input', () => {
        if (collaborationWS) {
            collaborationWS.sendTypingStatus(true);
            
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                collaborationWS.sendTypingStatus(false);
            }, 1000);
        }
    });
}

// 輔助函數
function getCurrentLine(editor) {
    const text = editor.value.substring(0, editor.selectionStart);
    return text.split('\n').length - 1;
}

function getCurrentColumn(editor) {
    const text = editor.value.substring(0, editor.selectionStart);
    const lines = text.split('\n');
    return lines[lines.length - 1].length;
}

// 頁面卸載時關閉連接
window.addEventListener('beforeunload', () => {
    if (collaborationWS) {
        collaborationWS.disconnect();
    }
}); 