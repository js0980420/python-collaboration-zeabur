# Railway 部署指南

## 🚀 快速部署到Railway

### 1. 準備GitHub倉庫

1. **創建GitHub倉庫**
   ```bash
   # 在GitHub上創建新倉庫: python-teaching-php
   ```

2. **上傳PHP版本代碼**
   ```bash
   # 進入php_version目錄
   cd php_version
   
   # 初始化git倉庫
   git init
   git add .
   git commit -m "Initial commit: PHP+MySQL version"
   
   # 添加遠程倉庫
   git remote add origin https://github.com/YOUR_USERNAME/python-teaching-php.git
   git branch -M main
   git push -u origin main
   ```

### 2. Railway部署步驟

1. **登錄Railway**
   - 訪問 [railway.app](https://railway.app)
   - 使用GitHub帳號登錄

2. **創建新項目**
   - 點擊 "New Project"
   - 選擇 "Deploy from GitHub repo"
   - 選擇你的 `python-teaching-php` 倉庫

3. **添加MySQL數據庫**
   - 在項目中點擊 "Add Service"
   - 選擇 "Database" → "MySQL"
   - Railway會自動創建MySQL實例

4. **設置環境變數**
   在Railway項目設置中添加以下環境變數：
   ```
   DB_HOST=mysql容器的內部主機名
   DB_NAME=railway
   DB_USERNAME=root
   DB_PASSWORD=自動生成的密碼
   XAI_API_KEY=你的XAI API密鑰
   APP_DEBUG=false
   APP_URL=https://你的railway域名
   ```

5. **數據庫初始化**
   Railway會自動檢測到 `sql/schema.sql` 文件，但需要手動執行：
   - 在Railway控制台中打開MySQL數據庫
   - 執行 `sql/schema.sql` 中的SQL語句

### 3. 自動部署配置

Railway會讀取以下配置文件：

- `railway.json` - Railway部署配置
- `composer.json` - PHP依賴和版本要求
- `Dockerfile` - 容器化部署（備用）

### 4. 驗證部署

1. **檢查服務狀態**
   - 在Railway控制台查看部署日志
   - 確認PHP服務正常啟動

2. **測試API端點**
   ```bash
   # 測試聊天API
   curl -X POST https://你的域名/api/chat \
     -H "Content-Type: application/json" \
     -d '{"message":"Hello"}'
   
   # 測試首頁
   curl https://你的域名/
   ```

3. **檢查數據庫連接**
   - 訪問 `/api/analytics` 檢查數據庫是否正常

### 5. 環境變數獲取

Railway MySQL數據庫的連接信息：
```bash
# 在Railway控制台的MySQL服務中查看
DB_HOST=containers-us-west-xxx.railway.app
DB_NAME=railway
DB_USERNAME=root
DB_PASSWORD=自動生成的密碼
DB_PORT=端口號
```

### 6. 常見問題解決

1. **數據庫連接失敗**
   - 檢查環境變數是否正確設置
   - 確認MySQL服務正在運行

2. **PHP擴展缺失**
   - Railway會根據 `composer.json` 自動安裝所需擴展

3. **API調用失敗**
   - 檢查 `XAI_API_KEY` 是否正確設置
   - 確認cURL擴展已啟用

### 7. 自定義域名（可選）

1. 在Railway項目設置中：
   - 點擊 "Settings" → "Domains"
   - 添加自定義域名
   - 配置DNS記錄

### 8. 性能優化

1. **啟用OPCache**（Railway自動配置）
2. **數據庫索引優化**（已在schema.sql中包含）
3. **靜態資源CDN**（可選）

## 🔧 本地開發

```bash
# 克隆倉庫
git clone https://github.com/YOUR_USERNAME/python-teaching-php.git
cd python-teaching-php

# 配置環境
cp env.example .env
# 編輯.env文件設置本地數據庫

# 啟動PHP開發服務器
php -S localhost:8000
```

## 📊 監控和日志

1. **Railway提供**:
   - 實時日志查看
   - CPU和內存使用監控
   - 請求統計

2. **應用內監控**:
   - 訪問 `/api/analytics` 查看使用統計
   - 檢查錯誤日志

## 🎯 成功部署檢查清單

- [ ] GitHub倉庫已創建並推送代碼
- [ ] Railway項目已創建
- [ ] MySQL數據庫已添加
- [ ] 環境變數已配置
- [ ] 數據庫schema已執行
- [ ] 應用服務正常運行
- [ ] API端點測試通過
- [ ] 前端頁面正常顯示
- [ ] AI功能正常工作

部署完成後，你將擁有一個完全功能的Python教學網站，包括AI助教、程式碼檢查和學習進度追蹤功能！ 