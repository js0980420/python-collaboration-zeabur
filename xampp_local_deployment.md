# 🏠 XAMPP本地部署指南 - Python協作教學平台

## 📋 系統要求
- Windows 10/11 或 macOS 或 Linux
- XAMPP 7.4+ (包含 Apache + MySQL + PHP)
- 至少 4GB RAM
- 2GB 可用磁碟空間

## 🚀 一鍵部署步驟

### 1. 下載並安裝XAMPP
```bash
# Windows: 下載 XAMPP for Windows
https://www.apachefriends.org/download.html

# 安裝到默認路徑: C:\xampp\
```

### 2. 啟動XAMPP服務
```bash
# 啟動 Apache 和 MySQL 服務
# 在XAMPP控制面板中點擊 "Start" 按鈕
```

### 3. 部署協作平台
```bash
# 1. 將專案文件複製到 XAMPP 目錄
複製整個專案到: C:\xampp\htdocs\python_collaboration\

# 2. 運行自動部署腳本
.\deploy_xampp_local.bat
```

## 📁 XAMPP版本文件結構
```
C:\xampp\htdocs\python_collaboration\
├── index.html                          # 首頁導航
├── xampp_collaboration_platform.html   # XAMPP版協作平台
├── xampp_websocket_server.php         # XAMPP版WebSocket服務器
├── code_sync_handler.php              # 代碼同步處理器
├── ai_api_handler.php                  # AI助教處理器
├── mysql_init_xampp.sql               # XAMPP MySQL初始化
├── deploy_xampp_local.bat             # 一鍵部署腳本
└── README_XAMPP.md                     # XAMPP版使用說明
```

## 🔧 配置說明

### MySQL數據庫配置
```php
// XAMPP版本的數據庫配置
$host = 'localhost';
$port = '3306';
$dbname = 'python_collaboration';
$username = 'root';
$password = '';  // XAMPP默認無密碼
```

### WebSocket服務器配置
```php
// XAMPP版本監聽本地端口
$port = 8080;
$host = '127.0.0.1';  // 僅本地訪問
```

## 🌐 訪問地址
- **主頁**: http://localhost/python_collaboration/
- **協作平台**: http://localhost/python_collaboration/xampp_collaboration_platform.html
- **WebSocket**: ws://localhost:8080

## ✅ 測試檢查清單
- [ ] Apache服務正常啟動 (端口80)
- [ ] MySQL服務正常啟動 (端口3306)
- [ ] 數據庫 `python_collaboration` 創建成功
- [ ] WebSocket服務器啟動 (端口8080)
- [ ] 協作功能正常工作
- [ ] AI助教功能正常回應

## 🛠️ 故障排除

### 常見問題
1. **端口80被占用**
   ```bash
   # 檢查端口占用
   netstat -ano | findstr :80
   # 停止占用進程或更改Apache端口
   ```

2. **MySQL連接失敗**
   ```bash
   # 確認MySQL服務啟動
   # 檢查用戶名密碼配置
   ```

3. **WebSocket連接失敗**
   ```bash
   # 確認防火牆設置
   # 檢查端口8080是否可用
   ```

## 📞 技術支援
- 📧 Email: support@pythonteaching.com
- 📱 電話: +886-123-456-789
- 🌐 文檔: https://docs.pythonteaching.com/xampp 