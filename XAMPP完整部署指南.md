# 🐍 Python協作教學平台 - XAMPP 完整部署指南

## 📋 部署優勢分析

### 為什麼選擇 XAMPP 本地部署？
- ✅ **完全免費** - 不消耗 Zeabur 5美元額度
- ✅ **開發隱私** - 客戶無法看到開發和測試過程
- ✅ **快速迭代** - 修改後立即測試，無需等待部署
- ✅ **完整功能** - 支援 PHP + MySQL + Apache 全功能
- ✅ **離線工作** - 不依賴網路連接

## 🚀 一鍵部署步驟

### 第一步：確認 XAMPP 環境
```bat
# 執行現有的一鍵啟動腳本
一鍵啟動完整協作平台.bat
```

### 第二步：初始化數據庫
```bat
# 在瀏覽器訪問
http://localhost/collaboration/完整數據庫初始化.php
```

### 第三步：測試協作功能
```bat
# 打開協作平台
http://localhost/collaboration/dual_collaboration_platform.html
```

## 🔧 手動部署步驟 (如果自動化失敗)

### 1. 複製文件到 XAMPP 目錄
```
C:\xampp\htdocs\collaboration\
├── dual_collaboration_platform.html     # 主協作頁面
├── code_sync_handler.php                # API 處理器
├── ai_api_handler.php                   # AI 助教 API
├── 完整數據庫初始化.php                  # 數據庫初始化
├── ai_config.json                       # AI 配置
└── create_chat_table.sql                # 聊天表結構
```

### 2. 啟動 XAMPP 服務
```bat
# 管理員模式運行
C:\xampp\xampp-control.exe

# 或使用命令行
net start apache2.4
net start mysql
```

### 3. 創建數據庫
```sql
-- 在 phpMyAdmin 執行
CREATE DATABASE python_collaboration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. 初始化表結構
```php
// 訪問瀏覽器執行
http://localhost/collaboration/完整數據庫初始化.php
```

## 🧪 測試協作功能

### 單機多瀏覽器測試
1. **Chrome** 打開：`http://localhost/collaboration/dual_collaboration_platform.html`
2. **Firefox** 打開：`http://localhost/collaboration/dual_collaboration_platform.html` 
3. 使用不同用戶名加入同一房間
4. 測試代碼同步、聊天、AI助教功能

### 區域網路測試 
1. 獲取主機 IP：`ipconfig`
2. 其他設備訪問：`http://[主機IP]/collaboration/dual_collaboration_platform.html`
3. 確保防火牆允許 80 端口

## 📊 功能測試清單

### ✅ 基礎功能測試
- [ ] 用戶連接和房間管理
- [ ] 代碼實時同步 (2秒輪詢)
- [ ] 衝突檢測和處理
- [ ] 用戶狀態顯示
- [ ] 游標位置同步

### ✅ 進階功能測試  
- [ ] 聊天消息同步
- [ ] AI助教集成 (需配置 API)
- [ ] 代碼版本管理
- [ ] 用戶協作統計
- [ ] 數據持久化

### ✅ 性能測試
- [ ] 2-3人同時協作
- [ ] 大量代碼同步
- [ ] 長時間會話穩定性
- [ ] 網路中斷恢復

## 🔮 Zeabur 部署考量

### 何時考慮部署到 Zeabur？
1. **本地測試完全通過** ✅
2. **功能穩定無重大bug** ✅  
3. **需要公開演示** 📢
4. **客戶要求雲端部署** 👥

### Zeabur 部署準備
```json
// zeabur.json
{
  "name": "python-collaboration-platform",
  "type": "php",
  "runtime": "php8.1",
  "buildCommand": "composer install --no-dev",
  "startCommand": "apache2-foreground",
  "envs": {
    "DB_HOST": "localhost",
    "DB_NAME": "python_collaboration", 
    "DB_USER": "root",
    "DB_PASS": ""
  }
}
```

### 部署文件準備
```
deployment/
├── index.php                    # 入口文件
├── composer.json               # PHP 依賴
├── .htaccess                   # Apache 配置
├── zeabur.json                 # Zeabur 配置
└── src/                        # 源代碼目錄
```

## 💡 建議的開發流程

### 階段一：XAMPP 本地開發 (當前)
- 完善協作功能
- 修復已知問題
- 增加新功能
- 充分測試

### 階段二：私有 Zeabur 部署 (測試用)
- 使用您自己的 Zeabur 帳號
- 進行雲端環境測試
- 驗證部署配置

### 階段三：客戶 Zeabur 部署 (正式交付)
- 確保功能完整穩定
- 提供完整部署文檔
- 進行最終驗收測試

## ⚠️ 重要注意事項

### XAMPP 配置優化
```ini
# php.ini 優化
max_execution_time = 300
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
```

### MySQL 配置優化
```ini
# my.ini 優化
max_connections = 100
innodb_buffer_pool_size = 128M
query_cache_size = 64M
```

### 安全設置
```php
// 生產環境安全設置
error_reporting(0);
display_errors = Off
log_errors = On
```

## 🎯 測試成功標準

### 功能完整性
- 2-3人協作無衝突
- 所有API正常響應
- 數據正確保存和同步
- AI助教功能正常

### 性能表現
- 頁面載入 < 3秒
- 代碼同步延遲 < 2秒  
- 聊天消息實時性 < 3秒
- 系統穩定運行 > 2小時

### 用戶體驗
- 界面友好易用
- 錯誤提示清晰
- 操作流程順暢
- 功能引導完整

---

**總結：先在 XAMPP 環境完成所有功能開發和測試，確保系統穩定後再考慮雲端部署。這樣可以節省成本並確保交付品質。** 🚀 