// 🚀 WebSocket服務器 - 多人協作後端
const WebSocket = require('ws');
const http = require('http');
const url = require('url');
const mysql = require('mysql2/promise');

// 配置
const PORT = 8080;
const DB_CONFIG = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'python_teaching_gamified'
};

// 創建HTTP服務器
const server = http.createServer();

// 創建WebSocket服務器
const wss = new WebSocket.Server({ 
    server,
    verifyClient: (info) => {
        // 可以在這裡添加身份驗證邏輯
        return true;
    }
});

// 房間管理
class RoomManager {
    constructor() {
        this.rooms = new Map(); // roomId -> Room
        this.userConnections = new Map(); // userId -> WebSocket
        this.db = null;
        this.initDatabase();
    }
    
    async initDatabase() {
        try {
            this.db = await mysql.createConnection(DB_CONFIG);
            console.log('資料庫連接成功');
        } catch (error) {
            console.error('資料庫連接失敗:', error);
        }
    }
    
    // 用戶加入房間
    async joinRoom(ws, roomId, userId, username) {
        try {
            // 檢查房間是否存在
            let room = this.rooms.get(roomId);
            if (!room) {
                room = new Room(roomId, this.db);
                this.rooms.set(roomId, room);
            }
            
            // 添加用戶到房間
            const user = { ws, userId, username, roomId };
            room.addUser(user);
            this.userConnections.set(userId, ws);
            
            // 設置WebSocket屬性
            ws.roomId = roomId;
            ws.userId = userId;
            ws.username = username;
            
            // 通知其他用戶
            room.broadcast({
                type: 'user_joined',
                user_id: userId,
                username: username,
                avatar_emoji: '👤',
                timestamp: Date.now()
            }, userId);
            
            // 發送房間成員列表給新用戶
            const members = room.getMembers();
            ws.send(JSON.stringify({
                type: 'room_members',
                members: members
            }));
            
            // 更新資料庫
            await this.updateRoomInDatabase(roomId, room);
            
            console.log(`用戶 ${username} 加入房間 ${roomId}`);
            
        } catch (error) {
            console.error('用戶加入房間失敗:', error);
            ws.send(JSON.stringify({
                type: 'error',
                message: '加入房間失敗'
            }));
        }
    }
    
    // 用戶離開房間
    async leaveRoom(ws) {
        try {
            const { roomId, userId, username } = ws;
            if (!roomId || !userId) return;
            
            const room = this.rooms.get(roomId);
            if (room) {
                room.removeUser(userId);
                
                // 通知其他用戶
                room.broadcast({
                    type: 'user_left',
                    user_id: userId,
                    username: username,
                    timestamp: Date.now()
                });
                
                // 如果房間為空，刪除房間
                if (room.isEmpty()) {
                    this.rooms.delete(roomId);
                    await this.updateRoomStatus(roomId, 'closed');
                } else {
                    await this.updateRoomInDatabase(roomId, room);
                }
            }
            
            this.userConnections.delete(userId);
            console.log(`用戶 ${username} 離開房間 ${roomId}`);
            
        } catch (error) {
            console.error('用戶離開房間失敗:', error);
        }
    }
    
    // 處理程式碼變更
    async handleCodeChange(ws, data) {
        try {
            const room = this.rooms.get(ws.roomId);
            if (!room) return;
            
            // 保存程式碼版本到資料庫
            await this.saveCodeVersion(ws.roomId, ws.userId, data.code, data.change_info);
            
            // 廣播給其他用戶
            room.broadcast({
                type: 'code_changed',
                user_id: ws.userId,
                username: ws.username,
                code: data.code,
                change_info: data.change_info,
                timestamp: data.timestamp
            }, ws.userId);
            
        } catch (error) {
            console.error('處理程式碼變更失敗:', error);
        }
    }
    
    // 處理游標移動
    handleCursorMove(ws, data) {
        try {
            const room = this.rooms.get(ws.roomId);
            if (!room) return;
            
            // 廣播游標位置
            room.broadcast({
                type: 'cursor_moved',
                user_id: ws.userId,
                username: ws.username,
                position: data.position,
                color: data.color,
                timestamp: data.timestamp
            }, ws.userId);
            
        } catch (error) {
            console.error('處理游標移動失敗:', error);
        }
    }
    
    // 處理聊天消息
    async handleChatMessage(ws, data) {
        try {
            const room = this.rooms.get(ws.roomId);
            if (!room) return;
            
            // 保存聊天消息到資料庫
            await this.saveChatMessage(ws.roomId, ws.userId, data.message);
            
            // 廣播聊天消息
            room.broadcast({
                type: 'chat_message',
                user_id: ws.userId,
                username: ws.username,
                message: data.message,
                timestamp: data.timestamp
            });
            
        } catch (error) {
            console.error('處理聊天消息失敗:', error);
        }
    }
    
    // 處理打字狀態
    handleTypingStatus(ws, data) {
        try {
            const room = this.rooms.get(ws.roomId);
            if (!room) return;
            
            // 廣播打字狀態
            room.broadcast({
                type: 'typing_status',
                user_id: ws.userId,
                username: ws.username,
                is_typing: data.is_typing,
                timestamp: data.timestamp
            }, ws.userId);
            
        } catch (error) {
            console.error('處理打字狀態失敗:', error);
        }
    }
    
    // 資料庫操作
    async updateRoomInDatabase(roomId, room) {
        if (!this.db) return;
        
        try {
            const memberCount = room.getUserCount();
            await this.db.execute(
                'UPDATE collaboration_rooms SET current_members = ? WHERE id = ?',
                [memberCount, roomId]
            );
        } catch (error) {
            console.error('更新房間資料庫失敗:', error);
        }
    }
    
    async updateRoomStatus(roomId, status) {
        if (!this.db) return;
        
        try {
            await this.db.execute(
                'UPDATE collaboration_rooms SET status = ? WHERE id = ?',
                [status, roomId]
            );
        } catch (error) {
            console.error('更新房間狀態失敗:', error);
        }
    }
    
    async saveCodeVersion(roomId, userId, code, changeInfo) {
        if (!this.db) return;
        
        try {
            // 獲取下一個版本號
            const [rows] = await this.db.execute(
                'SELECT MAX(version_number) as max_version FROM collaboration_code_versions WHERE room_id = ?',
                [roomId]
            );
            
            const nextVersion = (rows[0].max_version || 0) + 1;
            
            await this.db.execute(
                'INSERT INTO collaboration_code_versions (room_id, user_id, version_number, code_content, change_description) VALUES (?, ?, ?, ?, ?)',
                [roomId, userId, nextVersion, code, JSON.stringify(changeInfo)]
            );
        } catch (error) {
            console.error('保存程式碼版本失敗:', error);
        }
    }
    
    async saveChatMessage(roomId, userId, message) {
        if (!this.db) return;
        
        try {
            await this.db.execute(
                'INSERT INTO room_chat_messages (room_id, user_id, message_content) VALUES (?, ?, ?)',
                [roomId, userId, message]
            );
        } catch (error) {
            console.error('保存聊天消息失敗:', error);
        }
    }
}

// 房間類
class Room {
    constructor(roomId, db) {
        this.roomId = roomId;
        this.users = new Map(); // userId -> user object
        this.db = db;
        this.createdAt = Date.now();
    }
    
    addUser(user) {
        this.users.set(user.userId, user);
    }
    
    removeUser(userId) {
        this.users.delete(userId);
    }
    
    getUser(userId) {
        return this.users.get(userId);
    }
    
    getUserCount() {
        return this.users.size;
    }
    
    isEmpty() {
        return this.users.size === 0;
    }
    
    getMembers() {
        return Array.from(this.users.values()).map(user => ({
            user_id: user.userId,
            username: user.username,
            avatar_emoji: '👤'
        }));
    }
    
    // 廣播消息給房間內所有用戶（可排除特定用戶）
    broadcast(message, excludeUserId = null) {
        const messageStr = JSON.stringify(message);
        
        this.users.forEach((user, userId) => {
            if (userId !== excludeUserId && user.ws.readyState === WebSocket.OPEN) {
                try {
                    user.ws.send(messageStr);
                } catch (error) {
                    console.error(`發送消息給用戶 ${userId} 失敗:`, error);
                    // 移除無效連接
                    this.users.delete(userId);
                }
            }
        });
    }
    
    // 發送消息給特定用戶
    sendToUser(userId, message) {
        const user = this.users.get(userId);
        if (user && user.ws.readyState === WebSocket.OPEN) {
            try {
                user.ws.send(JSON.stringify(message));
            } catch (error) {
                console.error(`發送消息給用戶 ${userId} 失敗:`, error);
                this.users.delete(userId);
            }
        }
    }
}

// 創建房間管理器
const roomManager = new RoomManager();

// WebSocket連接處理
wss.on('connection', (ws, request) => {
    console.log('新的WebSocket連接');
    
    // 解析URL參數
    const query = url.parse(request.url, true).query;
    const roomId = query.room;
    const userId = query.user;
    
    if (!roomId || !userId) {
        ws.close(1008, '缺少必要參數');
        return;
    }
    
    // 心跳檢測
    ws.isAlive = true;
    ws.on('pong', () => {
        ws.isAlive = true;
    });
    
    // 消息處理
    ws.on('message', async (message) => {
        try {
            const data = JSON.parse(message);
            
            switch (data.type) {
                case 'join_room':
                    await roomManager.joinRoom(ws, roomId, userId, data.username);
                    break;
                    
                case 'code_changed':
                    await roomManager.handleCodeChange(ws, data);
                    break;
                    
                case 'cursor_moved':
                    roomManager.handleCursorMove(ws, data);
                    break;
                    
                case 'chat_message':
                    await roomManager.handleChatMessage(ws, data);
                    break;
                    
                case 'typing_status':
                    roomManager.handleTypingStatus(ws, data);
                    break;
                    
                default:
                    console.log('未知消息類型:', data.type);
            }
        } catch (error) {
            console.error('處理WebSocket消息失敗:', error);
            ws.send(JSON.stringify({
                type: 'error',
                message: '消息處理失敗'
            }));
        }
    });
    
    // 連接關閉處理
    ws.on('close', async () => {
        console.log('WebSocket連接關閉');
        await roomManager.leaveRoom(ws);
    });
    
    // 錯誤處理
    ws.on('error', (error) => {
        console.error('WebSocket錯誤:', error);
    });
});

// 心跳檢測（每30秒）
const heartbeat = setInterval(() => {
    wss.clients.forEach((ws) => {
        if (!ws.isAlive) {
            console.log('移除無響應的連接');
            return ws.terminate();
        }
        
        ws.isAlive = false;
        ws.ping();
    });
}, 30000);

// 服務器關閉時清理
wss.on('close', () => {
    clearInterval(heartbeat);
});

// 啟動服務器
server.listen(PORT, () => {
    console.log(`WebSocket服務器運行在端口 ${PORT}`);
    console.log(`WebSocket地址: ws://localhost:${PORT}/collaboration`);
});

// 優雅關閉
process.on('SIGTERM', () => {
    console.log('正在關閉WebSocket服務器...');
    wss.close(() => {
        server.close(() => {
            console.log('WebSocket服務器已關閉');
            process.exit(0);
        });
    });
});

process.on('SIGINT', () => {
    console.log('正在關閉WebSocket服務器...');
    wss.close(() => {
        server.close(() => {
            console.log('WebSocket服務器已關閉');
            process.exit(0);
        });
    });
}); 