# 🚀 Python協作教學平台 - Zeabur雲端部署指南

## 📋 部署概述

本指南將幫助您將Python協作教學平台部署到Zeabur雲端服務，實現全球可訪問的實時協作編程環境。

### 🎯 部署特色
- ⚡ **WebSocket實時協作** - 延遲 <0.5秒
- 🌐 **全球訪問** - 自動HTTPS + CDN加速  
- 🔧 **自動化部署** - Git推送即部署
- 💾 **雲端數據庫** - MySQL 8.0自動配置
- 🔒 **安全加密** - SSL/TLS自動証書

## 🔧 前置要求

1. **GitHub賬號** - 用於代碼託管
2. **Zeabur賬號** - [註冊地址](https://zeabur.com)
3. **Git客戶端** - 本地代碼管理

## 📂 項目結構

```
PythonLearn Web/
├── Dockerfile                     # Docker容器配置
├── supervisord.conf               # 進程管理配置  
├── zeabur.json                   # Zeabur部署配置
├── .htaccess                     # Apache配置
├── websocket_version/            # WebSocket服務器
│   ├── websocket_server.php      # 主服務器文件
│   ├── composer.json             # PHP依賴
│   └── websocket_collaboration_platform.html
├── mysql/                        # 數據庫初始化
│   └── init_zeabur.sql          # 雲端數據庫結構
└── *.php                        # HTTP API文件
```

## 🚀 部署步驟

### Step 1: 準備GitHub倉庫

1. **創建新的GitHub倉庫**：
   ```bash
   # 在GitHub創建倉庫：python-collaboration-platform
   ```

2. **推送代碼到GitHub**：
   ```bash
   cd "C:\Users\js098\Project\PythonLearn Web"
   git init
   git add .
   git commit -m "🚀 初始化Python協作教學平台"
   git branch -M main
   git remote add origin https://github.com/YOUR_USERNAME/python-collaboration-platform.git
   git push -u origin main
   ```

### Step 2: 連接Zeabur

1. **登錄Zeabur控制台**：
   - 訪問 [zeabur.com](https://zeabur.com)
   - 使用GitHub賬號登錄

2. **創建新項目**：
   - 點擊「Create Project」
   - 選擇「Import from GitHub」
   - 選擇您的 `python-collaboration-platform` 倉庫

### Step 3: 配置服務

#### 3.1 配置MySQL數據庫

1. **添加MySQL服務**：
   - 在項目中點擊「Add Service」
   - 選擇「MySQL」
   - 等待服務啟動

2. **配置數據庫**：
   - 數據庫名稱：`python_collaboration`
   - 用戶名：`collab_user`  
   - 密碼：自動生成（Zeabur會自動設置環境變量）

#### 3.2 配置應用服務

1. **部署主應用**：
   - Zeabur會自動檢測 `Dockerfile`
   - 確認構建配置並部署

2. **設置環境變量**：
   ```
   DB_HOST=${MYSQL_HOST}
   DB_PORT=${MYSQL_PORT}  
   DB_NAME=${MYSQL_DATABASE}
   DB_USER=${MYSQL_USERNAME}
   DB_PASSWORD=${MYSQL_PASSWORD}
   WEBSOCKET_PORT=8080
   ```

3. **配置端口**：
   - HTTP端口：80 (自動配置)
   - WebSocket端口：8080 (需要手動開放)

### Step 4: 域名配置

1. **自動域名**：
   - Zeabur會自動分配 `*.zeabur.app` 域名
   - 例如：`python-collab-abc123.zeabur.app`

2. **自定義域名**（可選）：
   - 在服務設置中添加自定義域名
   - 配置DNS指向Zeabur提供的CNAME

### Step 5: 驗證部署

1. **檢查服務狀態**：
   ```
   ✅ MySQL數據庫服務
   ✅ PHP應用服務  
   ✅ WebSocket服務器
   ✅ SSL證書
   ```

2. **測試功能**：
   - 訪問主頁：`https://您的域名.zeabur.app`
   - 測試WebSocket：`wss://您的域名.zeabur.app:8080`
   - 測試協作功能

## 🔍 故障排除

### 常見問題

#### WebSocket連接失敗
```bash
# 檢查WebSocket端口配置
# 確保8080端口已在Zeabur中開放
```

#### 數據庫連接錯誤
```bash
# 檢查環境變量設置
# 確保MySQL服務正常運行
```

#### 構建失敗
```bash
# 檢查Dockerfile語法
# 檢查composer.json依賴
```

### 日誌查看

1. **應用日誌**：
   - Zeabur控制台 → 服務 → Logs

2. **WebSocket日誌**：
   - 容器內路徑：`/var/log/websocket.log`

3. **Apache日誌**：
   - 容器內路徑：`/var/log/apache2/access.log`

## 📊 性能監控

### 關鍵指標

- **響應時間**：< 200ms
- **WebSocket延遲**：< 500ms  
- **並發用戶**：100+
- **可用性**：99.9%

### 監控工具

1. **Zeabur內建監控**：
   - CPU使用率
   - 記憶體使用
   - 網絡流量

2. **自定義監控**：
   - API響應時間
   - WebSocket連接數
   - 數據庫查詢性能

## 🛠️ 維護與更新

### 自動部署

每次推送到GitHub main分支會自動觸發部署：

```bash
git add .
git commit -m "更新功能"
git push origin main
# Zeabur會自動部署更新
```

### 數據庫備份

1. **自動備份**：
   - Zeabur提供自動每日備份

2. **手動備份**：
   ```bash
   # 通過Zeabur控制台導出數據庫
   ```

### 擴展配置

```bash
# 升級服務器規格
# 增加併發處理能力
# 配置CDN加速
```

## 💰 成本估算

### Zeabur定價（參考）

- **免費方案**：
  - 適合測試和小型項目
  - 有資源和時間限制

- **專業方案**：
  - $10-50/月（根據使用量）
  - 適合生產環境
  - 無限制使用

### 優化成本

1. **資源監控**：定期檢查資源使用
2. **合理配置**：選擇適當的服務器規格
3. **緩存策略**：減少數據庫查詢負載

## 🔗 相關鏈接

- **Zeabur官網**：https://zeabur.com
- **Zeabur文檔**：https://zeabur.com/docs
- **項目源碼**：https://github.com/YOUR_USERNAME/python-collaboration-platform
- **支援Discord**：https://discord.gg/zeabur

## 📞 技術支援

遇到問題時的聯繫方式：

- **Zeabur支援**：support@zeabur.com
- **項目Issues**：GitHub Issues頁面
- **社群討論**：Discord群組

---

**🎉 恭喜！您已成功部署Python協作教學平台到Zeabur雲端！**

現在全球用戶都可以訪問您的實時協作編程平台了！ 🌍✨ 