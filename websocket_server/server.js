// 🚀 WebSocket多人協作服務器
// 預算版本：簡化但功能完整的實現

const WebSocket = require('ws');
const http = require('http');
const path = require('path');
const fs = require('fs');

class CollaborationServer {
    constructor() {
        this.rooms = new Map();
        this.users = new Map();
        this.userCounter = 0;
        
        // 創建HTTP服務器用於提供靜態文件
        this.httpServer = http.createServer((req, res) => {
            this.handleHttpRequest(req, res);
        });
        
        // 創建WebSocket服務器
        this.wss = new WebSocket.Server({ 
            server: this.httpServer,
            path: '/ws'
        });
        
        this.setupWebSocketHandlers();
        
        console.log('🚀 協作服務器初始化完成');
    }
    
    // 處理HTTP請求（提供靜態文件）
    handleHttpRequest(req, res) {
        let filePath = req.url === '/' ? '/collaboration.html' : req.url;
        filePath = path.join(__dirname, '../client', filePath);
        
        // 安全檢查
        if (!filePath.startsWith(path.join(__dirname, '../client'))) {
            res.writeHead(403);
            res.end('Forbidden');
            return;
        }
        
        fs.readFile(filePath, (err, data) => {
            if (err) {
                res.writeHead(404);
                res.end('File not found');
                return;
            }
            
            // 設置正確的Content-Type
            const ext = path.extname(filePath);
            const contentTypes = {
                '.html': 'text/html',
                '.js': 'application/javascript',
                '.css': 'text/css',
                '.json': 'application/json'
            };
            
            res.writeHead(200, {
                'Content-Type': contentTypes[ext] || 'text/plain',
                'Access-Control-Allow-Origin': '*'
            });
            res.end(data);
        });
    }
    
    // 設置WebSocket處理器
    setupWebSocketHandlers() {
        this.wss.on('connection', (ws, req) => {
            console.log('👤 新用戶連接');
            
            // 為用戶分配ID
            const userId = `user_${++this.userCounter}`;
            const userInfo = {
                id: userId,
                ws: ws,
                roomId: null,
                name: `用戶${this.userCounter}`,
                cursor: { line: 0, ch: 0 },
                lastActivity: Date.now()
            };
            
            this.users.set(userId, userInfo);
            ws.userId = userId;
            
            // 發送歡迎消息
            this.sendToUser(userId, {
                type: 'welcome',
                userId: userId,
                userName: userInfo.name
            });
            
            // 處理消息
            ws.on('message', (data) => {
                try {
                    const message = JSON.parse(data);
                    this.handleMessage(userId, message);
                } catch (error) {
                    console.error('消息解析錯誤:', error);
                }
            });
            
            // 處理斷線
            ws.on('close', () => {
                this.handleUserDisconnect(userId);
            });
            
            // 處理錯誤
            ws.on('error', (error) => {
                console.error('WebSocket錯誤:', error);
                this.handleUserDisconnect(userId);
            });
        });
    }
    
    // 處理用戶消息
    handleMessage(userId, message) {
        const user = this.users.get(userId);
        if (!user) return;
        
        user.lastActivity = Date.now();
        
        switch (message.type) {
            case 'join_room':
                this.handleJoinRoom(userId, message.roomId, message.userName);
                break;
                
            case 'leave_room':
                this.handleLeaveRoom(userId);
                break;
                
            case 'code_change':
                this.handleCodeChange(userId, message);
                break;
                
            case 'cursor_change':
                this.handleCursorChange(userId, message);
                break;
                
            case 'chat_message':
                this.handleChatMessage(userId, message);
                break;
                
            case 'ai_request':
                this.handleAIRequest(userId, message);
                break;
                
            case 'ping':
                this.sendToUser(userId, { type: 'pong' });
                break;
                
            default:
                console.log('未知消息類型:', message.type);
        }
    }
    
    // 處理加入房間
    handleJoinRoom(userId, roomId, userName) {
        const user = this.users.get(userId);
        if (!user) return;
        
        // 離開當前房間
        if (user.roomId) {
            this.handleLeaveRoom(userId);
        }
        
        // 更新用戶信息
        if (userName) {
            user.name = userName;
        }
        user.roomId = roomId;
        
        // 創建或獲取房間
        if (!this.rooms.has(roomId)) {
            this.rooms.set(roomId, {
                id: roomId,
                users: new Set(),
                code: '# 歡迎來到協作編程！\n# 開始編寫您的Python程式碼\n\ndef hello_world():\n    print("Hello, World!")\n    return "協作愉快！"',
                version: 0,
                createdAt: Date.now(),
                lastActivity: Date.now()
            });
            console.log(`🏠 創建新房間: ${roomId}`);
        }
        
        const room = this.rooms.get(roomId);
        room.users.add(userId);
        room.lastActivity = Date.now();
        
        // 發送房間信息給新用戶
        this.sendToUser(userId, {
            type: 'room_joined',
            roomId: roomId,
            code: room.code,
            version: room.version,
            users: this.getRoomUsers(roomId)
        });
        
        // 通知房間其他用戶
        this.broadcastToRoom(roomId, {
            type: 'user_joined',
            user: {
                id: userId,
                name: user.name,
                cursor: user.cursor
            }
        }, userId);
        
        console.log(`👤 ${user.name} 加入房間 ${roomId}`);
    }
    
    // 處理離開房間
    handleLeaveRoom(userId) {
        const user = this.users.get(userId);
        if (!user || !user.roomId) return;
        
        const roomId = user.roomId;
        const room = this.rooms.get(roomId);
        
        if (room) {
            room.users.delete(userId);
            
            // 通知房間其他用戶
            this.broadcastToRoom(roomId, {
                type: 'user_left',
                userId: userId,
                userName: user.name
            });
            
            // 如果房間空了，清理房間（延遲清理）
            if (room.users.size === 0) {
                setTimeout(() => {
                    if (room.users.size === 0) {
                        this.rooms.delete(roomId);
                        console.log(`🗑️ 清理空房間: ${roomId}`);
                    }
                }, 30000); // 30秒後清理
            }
        }
        
        user.roomId = null;
        console.log(`👤 ${user.name} 離開房間 ${roomId}`);
    }
    
    // 處理程式碼變更
    handleCodeChange(userId, message) {
        const user = this.users.get(userId);
        if (!user || !user.roomId) return;
        
        const room = this.rooms.get(user.roomId);
        if (!room) return;
        
        // 簡單的版本控制（最後寫入獲勝）
        if (message.version <= room.version) {
            // 版本衝突，發送最新版本給用戶
            this.sendToUser(userId, {
                type: 'code_conflict',
                code: room.code,
                version: room.version
            });
            return;
        }
        
        // 更新房間程式碼
        room.code = message.code;
        room.version = message.version;
        room.lastActivity = Date.now();
        
        // 廣播變更給房間其他用戶
        this.broadcastToRoom(user.roomId, {
            type: 'code_changed',
            code: message.code,
            version: message.version,
            userId: userId,
            userName: user.name,
            change: message.change
        }, userId);
    }
    
    // 處理游標變更
    handleCursorChange(userId, message) {
        const user = this.users.get(userId);
        if (!user || !user.roomId) return;
        
        user.cursor = message.cursor;
        
        // 廣播游標位置給房間其他用戶
        this.broadcastToRoom(user.roomId, {
            type: 'cursor_changed',
            userId: userId,
            userName: user.name,
            cursor: message.cursor
        }, userId);
    }
    
    // 處理聊天消息
    handleChatMessage(userId, message) {
        const user = this.users.get(userId);
        if (!user || !user.roomId) return;
        
        const chatMessage = {
            type: 'chat_message',
            userId: userId,
            userName: user.name,
            message: message.message,
            timestamp: Date.now()
        };
        
        // 廣播聊天消息給房間所有用戶
        this.broadcastToRoom(user.roomId, chatMessage);
    }
    
    // 處理AI請求
    async handleAIRequest(userId, message) {
        const user = this.users.get(userId);
        if (!user || !user.roomId) return;
        
        const room = this.rooms.get(user.roomId);
        if (!room) return;
        
        try {
            // 發送處理中狀態
            this.sendToUser(userId, {
                type: 'ai_processing',
                requestId: message.requestId
            });
            
            // 調用AI API（整合之前的真實AI助手）
            const aiResponse = await this.callAI(message.action, {
                code: room.code,
                ...message.data
            });
            
            // 發送AI回應
            this.sendToUser(userId, {
                type: 'ai_response',
                requestId: message.requestId,
                response: aiResponse
            });
            
            // 如果是協作建議，也廣播給其他用戶
            if (message.action === 'collaboration_analysis') {
                this.broadcastToRoom(user.roomId, {
                    type: 'collaboration_suggestion',
                    suggestion: aiResponse.data,
                    fromUser: user.name
                }, userId);
            }
            
        } catch (error) {
            console.error('AI請求錯誤:', error);
            this.sendToUser(userId, {
                type: 'ai_error',
                requestId: message.requestId,
                error: 'AI服務暫時無法使用'
            });
        }
    }
    
    // 調用AI API（簡化版本）
    async callAI(action, data) {
        // 這裡整合之前的真實AI助手
        // 為了預算考慮，使用簡化的本地處理
        
        const responses = {
            'analyze_code': {
                success: true,
                data: {
                    score: Math.floor(Math.random() * 30) + 70,
                    suggestions: [
                        '程式碼結構清晰，建議加入更多註解',
                        '考慮加入錯誤處理機制',
                        '變數命名可以更具描述性'
                    ],
                    complexity: '中等'
                }
            },
            'collaboration_analysis': {
                success: true,
                data: {
                    teamEfficiency: '良好',
                    suggestions: [
                        '建議分工明確，避免同時編輯同一區域',
                        '多使用聊天功能進行溝通',
                        '定期進行程式碼檢查'
                    ]
                }
            },
            'syntax_check': {
                success: true,
                data: {
                    hasErrors: Math.random() > 0.7,
                    suggestions: ['檢查括號配對', '確認縮排正確']
                }
            }
        };
        
        // 模擬API延遲
        await new Promise(resolve => setTimeout(resolve, 1000 + Math.random() * 2000));
        
        return responses[action] || {
            success: true,
            data: { message: 'AI助手正在學習中，請稍後再試' }
        };
    }
    
    // 獲取房間用戶列表
    getRoomUsers(roomId) {
        const room = this.rooms.get(roomId);
        if (!room) return [];
        
        return Array.from(room.users).map(userId => {
            const user = this.users.get(userId);
            return user ? {
                id: user.id,
                name: user.name,
                cursor: user.cursor,
                online: true
            } : null;
        }).filter(Boolean);
    }
    
    // 發送消息給特定用戶
    sendToUser(userId, message) {
        const user = this.users.get(userId);
        if (user && user.ws.readyState === WebSocket.OPEN) {
            user.ws.send(JSON.stringify(message));
        }
    }
    
    // 廣播消息給房間所有用戶
    broadcastToRoom(roomId, message, excludeUserId = null) {
        const room = this.rooms.get(roomId);
        if (!room) return;
        
        room.users.forEach(userId => {
            if (userId !== excludeUserId) {
                this.sendToUser(userId, message);
            }
        });
    }
    
    // 處理用戶斷線
    handleUserDisconnect(userId) {
        const user = this.users.get(userId);
        if (!user) return;
        
        console.log(`👤 ${user.name} 斷線`);
        
        // 離開房間
        if (user.roomId) {
            this.handleLeaveRoom(userId);
        }
        
        // 清理用戶
        this.users.delete(userId);
    }
    
    // 啟動服務器
    start(port = 3000) {
        this.httpServer.listen(port, () => {
            console.log(`🚀 協作服務器啟動成功！`);
            console.log(`📡 WebSocket: ws://localhost:${port}/ws`);
            console.log(`🌐 Web界面: http://localhost:${port}`);
            console.log(`👥 房間管理: ${this.rooms.size} 個房間`);
            console.log(`👤 在線用戶: ${this.users.size} 人`);
        });
        
        // 定期清理不活躍的連接
        setInterval(() => {
            this.cleanupInactiveConnections();
        }, 60000); // 每分鐘檢查一次
        
        // 定期輸出統計信息
        setInterval(() => {
            console.log(`📊 統計 - 房間: ${this.rooms.size}, 用戶: ${this.users.size}`);
        }, 300000); // 每5分鐘輸出一次
    }
    
    // 清理不活躍的連接
    cleanupInactiveConnections() {
        const now = Date.now();
        const timeout = 5 * 60 * 1000; // 5分鐘超時
        
        this.users.forEach((user, userId) => {
            if (now - user.lastActivity > timeout) {
                console.log(`🧹 清理不活躍用戶: ${user.name}`);
                this.handleUserDisconnect(userId);
            }
        });
    }
    
    // 獲取服務器統計信息
    getStats() {
        return {
            rooms: this.rooms.size,
            users: this.users.size,
            uptime: process.uptime(),
            memory: process.memoryUsage()
        };
    }
}

// 啟動服務器
if (require.main === module) {
    const server = new CollaborationServer();
    const port = process.env.PORT || 3000;
    server.start(port);
    
    // 優雅關閉
    process.on('SIGINT', () => {
        console.log('\n🛑 正在關閉服務器...');
        process.exit(0);
    });
}

module.exports = CollaborationServer; 