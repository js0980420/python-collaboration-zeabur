# 🚀 Python協作教學平台 - WebSocket即時版部署說明

## 📋 版本對比

### HTTP輪詢版 vs WebSocket版

| 特性 | HTTP輪詢版 | WebSocket版 |
|------|------------|-------------|
| **同步延遲** | 2秒間隔輪詢 | <0.5秒即時同步 |
| **技術架構** | PHP + MySQL + HTTP | PHP + Ratchet + WebSocket |
| **連接方式** | 客戶端主動輪詢 | 雙向即時通信 |
| **資源消耗** | 較高（頻繁HTTP請求） | 較低（持久連接） |
| **並發支援** | 較少（受HTTP限制） | 更多（事件驅動） |
| **斷線處理** | 自動重連 | 自動重連 + 心跳檢測 |
| **部署複雜度** | 簡單（僅需XAMPP） | 中等（需要額外Node.js庫） |

## 🎯 WebSocket版本優勢

### 1. 真正的實時協作
- **目標延遲**: <0.5秒
- **即時同步**: 代碼修改立即廣播
- **雙向通信**: 服務器可主動推送更新
- **事件驅動**: 高效的消息處理機制

### 2. 更好的用戶體驗
- **實時游標**: 看到其他用戶的編輯位置
- **即時聊天**: 無延遲的聊天功能
- **用戶狀態**: 實時的在線用戶列表
- **連接狀態**: 明確的連接狀態指示

### 3. 優化的性能
- **減少服務器負載**: 無需頻繁輪詢
- **降低網路開銷**: 持久連接復用
- **更好的擴展性**: 支援更多並發用戶

## 🏗️ 技術架構

### 核心組件
```
┌─────────────────┐    WebSocket     ┌──────────────────┐
│   前端客戶端     │ ←──────────────→ │  Ratchet服務器    │
│ (JavaScript)    │      ws://       │  (PHP + ReactPHP) │
└─────────────────┘                  └──────────────────┘
                                               │
                                               ▼
                                     ┌──────────────────┐
                                     │   MySQL數據庫     │
                                     │  (代碼快照存儲)   │
                                     └──────────────────┘
```

### 依賴庫
- **Ratchet/Ratchet**: WebSocket服務器框架
- **ReactPHP**: 異步事件驅動框架
- **CodeMirror**: 前端代碼編輯器
- **Bootstrap**: UI框架

## 📦 部署步驟

### 1. 環境準備
```bash
# 確保已安裝XAMPP
# 確保PHP版本 >= 7.4
# 確保MySQL服務正在運行
```

### 2. 安裝依賴
```bash
# 進入websocket_version目錄
cd websocket_version

# 運行自動化安裝腳本
啟動WebSocket服務器.bat
```

### 3. 手動安裝（如果自動化失敗）
```bash
# 安裝Composer（如果未安裝）
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# 安裝依賴
php composer.phar install --no-dev --optimize-autoloader
```

### 4. 啟動服務
```bash
# 啟動WebSocket服務器（端口8080）
php websocket_server.php

# 在瀏覽器訪問
http://localhost/collaboration/websocket_collaboration_platform.html
```

## 🔧 配置說明

### WebSocket服務器配置
```php
// websocket_server.php
$port = 8080;                    // WebSocket端口
$host = '0.0.0.0';              // 監聽地址
$maxReconnectAttempts = 5;       // 最大重連次數
$pingInterval = 5000;            // 心跳間隔(ms)
```

### 數據庫配置
```php
// MySQL連接配置
$dsn = "mysql:host=localhost;port=3306;dbname=python_collaboration;charset=utf8mb4";
$username = 'root';
$password = '';  // XAMPP預設無密碼
```

### 前端配置
```javascript
// WebSocket連接地址
const wsUrl = 'ws://localhost:8080';

// 重連設置
this.maxReconnectAttempts = 5;
this.pingInterval = 5000;  // 5秒心跳
```

## 🧪 測試驗證

### 1. 自動化延遲測試
```bash
# 運行延遲測試腳本
測試WebSocket延遲.bat
```

### 2. 手動功能測試
1. **多用戶協作測試**
   - 打開兩個瀏覽器標籤頁
   - 使用相同房間代碼，不同用戶名
   - 在一個標籤頁編輯代碼
   - 觀察另一個標籤頁的即時更新

2. **連接穩定性測試**
   - 檢查連接狀態指示器
   - 測試網路中斷後的自動重連
   - 觀察延遲顯示是否正常

3. **功能完整性測試**
   - 即時聊天功能
   - AI助教功能
   - 用戶列表更新
   - 代碼版本管理

## 📊 性能指標

### 目標性能
- **同步延遲**: <500ms
- **連接建立**: <200ms
- **並發用戶**: 50+
- **消息處理**: <100ms

### 監控方法
- 工具欄實時延遲顯示
- 瀏覽器開發者工具WebSocket面板
- 服務器日誌文件(`websocket_debug.log`)

## 🔀 版本切換指南

### 從HTTP輪詢版切換到WebSocket版
1. **保持HTTP版本運行**（用於對比）
2. **啟動WebSocket服務器**
3. **訪問WebSocket前端頁面**
4. **對比兩個版本的使用體驗**

### 訪問地址
- **HTTP輪詢版**: `http://localhost/collaboration/dual_collaboration_platform.html`
- **WebSocket版**: `http://localhost/collaboration/websocket_collaboration_platform.html`

## 🛠️ 故障排除

### 常見問題

#### 1. WebSocket連接失敗
**症狀**: 顯示"🔴 未連接"
**解決方案**:
```bash
# 檢查WebSocket服務器是否運行
netstat -an | findstr :8080

# 檢查防火牆設置
# Windows防火牆可能阻止8080端口

# 重新啟動WebSocket服務器
php websocket_server.php
```

#### 2. 依賴安裝失敗
**症狀**: Composer安裝錯誤
**解決方案**:
```bash
# 清理並重新安裝
rm -rf vendor composer.lock
composer install

# 或使用本地composer.phar
php composer.phar install --no-dev
```

#### 3. 高延遲問題
**症狀**: 延遲>500ms
**排查步驟**:
1. 檢查本機性能（CPU/記憶體）
2. 檢查MySQL數據庫性能
3. 檢查網路連接狀況
4. 查看服務器日誌錯誤

#### 4. 瀏覽器兼容性
**支援的瀏覽器**:
- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## 📁 文件結構

```
websocket_version/
├── composer.json                           # 依賴配置
├── websocket_server.php                    # WebSocket服務器
├── websocket_collaboration_platform.html   # 前端頁面
├── 啟動WebSocket服務器.bat                  # 自動化部署腳本
├── 測試WebSocket延遲.bat                   # 延遲測試工具
├── WebSocket版本部署說明.md                # 本文檔
├── vendor/                                 # Composer依賴（自動生成）
└── websocket_debug.log                     # 服務器日誌（自動生成）
```

## 🚀 部署到生產環境

### 1. Zeabur雲端部署
```json
// zeabur.json
{
  "build": {
    "commands": [
      "composer install --no-dev --optimize-autoloader"
    ]
  },
  "start": "php websocket_server.php",
  "env": {
    "PORT": "8080",
    "DB_HOST": "${MYSQL_HOST}",
    "DB_NAME": "${MYSQL_DATABASE}"
  }
}
```

### 2. 本地生產環境
```bash
# 使用進程管理器（如PM2）
npm install -g pm2
pm2 start websocket_server.php --name python-collab-ws

# 設置開機自啟
pm2 startup
pm2 save
```

## 📈 後續優化方向

### 短期改進
- 加入代碼衝突解決算法（Operational Transformation）
- 實現多用戶游標顯示
- 優化大文件同步性能

### 長期規劃
- 支援視頻通話協作
- 加入代碼歷史回放功能
- 實現協作學習分析面板

---

## 🎉 開始使用

準備好體驗真正的實時協作編程了嗎？

1. **啟動服務器**: 運行 `啟動WebSocket服務器.bat`
2. **測試延遲**: 運行 `測試WebSocket延遲.bat`
3. **開始協作**: 訪問 WebSocket版本頁面
4. **享受<0.5秒的即時同步體驗**！

**🔗 快速訪問**: `http://localhost/collaboration/websocket_collaboration_platform.html` 