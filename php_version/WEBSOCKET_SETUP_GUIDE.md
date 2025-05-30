# 🔌 WebSocket多人協作功能部署指南

## 📋 概述

本指南將幫助您在Python教學平台中部署WebSocket服務器，實現多人即時協作編程功能。

## 🛠️ 系統要求

### 必要軟件
- **Node.js** 14.0.0 或更高版本
- **npm** 或 **yarn** 包管理器
- **MySQL** 5.7 或更高版本
- **PHP** 7.4 或更高版本

### 推薦配置
- **內存**: 至少 2GB RAM
- **CPU**: 雙核心或更高
- **網絡**: 穩定的網絡連接

## 🚀 快速部署

### 步驟 1: 安裝Node.js依賴

```bash
# 進入WebSocket服務器目錄
cd php_version/websocket-server

# 安裝依賴包
npm install

# 或使用yarn
yarn install
```

### 步驟 2: 配置資料庫

確保MySQL資料庫已經運行，並且已經執行了協作相關的SQL腳本：

```bash
# 執行協作資料庫結構
mysql -u root -p python_teaching_gamified < ../sql/collaboration_schema.sql
```

### 步驟 3: 配置WebSocket服務器

編輯 `server.js` 中的資料庫配置：

```javascript
const DB_CONFIG = {
    host: 'localhost',        // 資料庫主機
    user: 'root',            // 資料庫用戶名
    password: 'your_password', // 資料庫密碼
    database: 'python_teaching_gamified'
};
```

### 步驟 4: 啟動WebSocket服務器

```bash
# 開發模式（自動重啟）
npm run dev

# 生產模式
npm start
```

服務器將在端口 8080 上運行：
- WebSocket地址: `ws://localhost:8080/collaboration`

### 步驟 5: 配置前端連接

確保前端JavaScript中的WebSocket地址正確：

```javascript
// 在 websocket-client.js 中
const wsUrl = `ws://localhost:8080/collaboration?room=${this.roomId}&user=${this.userId}`;
```

## 🌐 網頁使用方法

### 1. **創建協作房間**

```html
<!-- 訪問協作頁面 -->
http://localhost/python-teaching/pages/collaboration.php?room=my-room-123
```

### 2. **加入現有房間**

多個用戶可以使用相同的房間ID加入同一個協作會話：

```html
<!-- 用戶A -->
http://localhost/python-teaching/pages/collaboration.php?room=team-project-1

<!-- 用戶B -->
http://localhost/python-teaching/pages/collaboration.php?room=team-project-1
```

### 3. **WebSocket連接狀態**

頁面右上角會顯示連接狀態：
- 🟡 **連接中...** - 正在建立連接
- 🟢 **已連接** - 連接成功，可以協作
- 🔴 **連接錯誤** - 連接失敗，請檢查服務器

## 🔧 功能說明

### 即時協作功能

1. **程式碼同步**
   - 任何用戶的程式碼修改會即時同步到所有參與者
   - 支援多人同時編輯，避免衝突

2. **游標追蹤**
   - 顯示其他用戶的游標位置
   - 每個用戶有不同的顏色標識

3. **即時聊天**
   - 房間內成員可以即時交流
   - 支援系統消息通知

4. **成員管理**
   - 顯示當前房間內的所有成員
   - 實時更新成員加入/離開狀態

### JavaScript API使用

```javascript
// 初始化協作
const collaboration = new CollaborationWebSocket(roomId, userId, username);

// 設置事件回調
collaboration.onUserJoined = (data) => {
    console.log('用戶加入:', data.username);
};

collaboration.onCodeChanged = (data) => {
    console.log('程式碼更新:', data.code);
};

// 發送程式碼變更
collaboration.sendCodeChange(code, changeInfo);

// 發送聊天消息
collaboration.sendChatMessage('Hello, team!');

// 發送游標位置
collaboration.sendCursorPosition({ line: 5, column: 10 });
```

## 🔒 安全配置

### 1. **身份驗證**

在 `server.js` 中添加身份驗證：

```javascript
const wss = new WebSocket.Server({ 
    server,
    verifyClient: (info) => {
        // 驗證用戶token
        const token = info.req.headers.authorization;
        return validateToken(token);
    }
});
```

### 2. **房間權限控制**

```javascript
// 檢查用戶是否有權限加入房間
async function checkRoomPermission(userId, roomId) {
    const sql = "SELECT * FROM room_members WHERE user_id = ? AND room_id = ?";
    const [rows] = await db.execute(sql, [userId, roomId]);
    return rows.length > 0;
}
```

### 3. **消息過濾**

```javascript
// 過濾惡意消息
function sanitizeMessage(message) {
    return message.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
}
```

## 📊 監控與日誌

### 1. **連接監控**

```javascript
// 監控WebSocket連接數
setInterval(() => {
    console.log(`當前連接數: ${wss.clients.size}`);
    console.log(`活躍房間數: ${roomManager.rooms.size}`);
}, 60000);
```

### 2. **錯誤日誌**

```javascript
// 記錄錯誤到文件
const fs = require('fs');

function logError(error, context) {
    const logEntry = `${new Date().toISOString()} - ${context}: ${error.message}\n`;
    fs.appendFileSync('websocket-errors.log', logEntry);
}
```

## 🚀 生產環境部署

### 1. **使用PM2管理進程**

```bash
# 安裝PM2
npm install -g pm2

# 啟動WebSocket服務器
pm2 start server.js --name "websocket-server"

# 設置開機自啟
pm2 startup
pm2 save
```

### 2. **Nginx反向代理**

```nginx
# /etc/nginx/sites-available/websocket
server {
    listen 80;
    server_name your-domain.com;
    
    location /collaboration {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 3. **SSL/HTTPS配置**

```nginx
# HTTPS配置
server {
    listen 443 ssl;
    server_name your-domain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    location /collaboration {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        # ... 其他配置
    }
}
```

## 🐛 故障排除

### 常見問題

1. **WebSocket連接失敗**
   ```bash
   # 檢查端口是否被占用
   netstat -an | grep 8080
   
   # 檢查防火牆設置
   sudo ufw allow 8080
   ```

2. **資料庫連接錯誤**
   ```bash
   # 檢查MySQL服務狀態
   sudo systemctl status mysql
   
   # 測試資料庫連接
   mysql -u root -p -e "SELECT 1"
   ```

3. **Node.js版本問題**
   ```bash
   # 檢查Node.js版本
   node --version
   
   # 使用nvm管理版本
   nvm install 18
   nvm use 18
   ```

### 調試模式

```bash
# 啟用詳細日誌
DEBUG=* npm start

# 或設置環境變數
export DEBUG=websocket:*
npm start
```

## 📈 性能優化

### 1. **連接池優化**

```javascript
// 資料庫連接池配置
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: 'password',
    database: 'python_teaching_gamified',
    connectionLimit: 10,
    queueLimit: 0
});
```

### 2. **消息壓縮**

```javascript
const WebSocket = require('ws');

const wss = new WebSocket.Server({
    port: 8080,
    perMessageDeflate: {
        zlibDeflateOptions: {
            level: 3,
            chunkSize: 1024,
        },
        threshold: 1024,
        concurrencyLimit: 10,
    }
});
```

### 3. **內存使用監控**

```javascript
// 監控內存使用
setInterval(() => {
    const used = process.memoryUsage();
    console.log('Memory usage:', {
        rss: Math.round(used.rss / 1024 / 1024) + ' MB',
        heapTotal: Math.round(used.heapTotal / 1024 / 1024) + ' MB',
        heapUsed: Math.round(used.heapUsed / 1024 / 1024) + ' MB'
    });
}, 30000);
```

## 📞 技術支援

如果遇到問題，請檢查：

1. **服務器日誌**: `tail -f websocket-errors.log`
2. **瀏覽器控制台**: F12 → Console
3. **網絡連接**: 確保WebSocket端口可訪問
4. **資料庫狀態**: 確保MySQL正常運行

## 🎯 下一步

- 集成語音聊天功能
- 添加屏幕共享
- 實現程式碼版本控制
- 支援更多編程語言
- 添加協作統計分析

---

**注意**: 這是一個基礎的WebSocket實現，生產環境中建議添加更多的安全措施和錯誤處理機制。 