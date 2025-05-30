# 🚀 一鍵部署指南

## 第一步：創建GitHub倉庫

1. 訪問 [GitHub](https://github.com)
2. 點擊右上角的 "+" → "New repository"
3. 倉庫名稱建議：`python-teaching-php`
4. 設為Public（免費用戶）
5. 不要勾選 "Add a README file"
6. 點擊 "Create repository"

## 第二步：獲取倉庫URL

創建後GitHub會顯示類似這樣的URL：
```
https://github.com/YOUR_USERNAME/python-teaching-php.git
```

## 第三步：推送代碼（在php_version目錄執行）

```bash
# 設置遠程倉庫
git remote add origin https://github.com/YOUR_USERNAME/python-teaching-php.git

# 推送代碼
git branch -M main
git push -u origin main
```

## 第四步：部署到Railway

1. 訪問 [Railway.app](https://railway.app)
2. 使用GitHub帳號登錄
3. 點擊 "New Project"
4. 選擇 "Deploy from GitHub repo"
5. 選擇你剛創建的 `python-teaching-php` 倉庫
6. Railway會自動開始部署

## 第五步：添加MySQL數據庫

1. 在Railway項目中點擊 "Add Service"
2. 選擇 "Database" → "MySQL"
3. Railway會自動創建MySQL實例

## 第六步：設置環境變數

在Railway項目設置中添加：
- `XAI_API_KEY` = `你的XAI API密鑰`
- `APP_DEBUG` = `false`

## 第七步：初始化數據庫

1. 在Railway控制台點擊MySQL服務
2. 選擇 "Connect" → "Query"
3. 複製 `sql/schema.sql` 的內容並執行

## 🎉 完成！

現在你的Python教學網站已經成功部署到Railway，包含以下功能：

- ✅ AI智能助教 (XAI Grok)
- ✅ 程式碼檢查和反饋
- ✅ 學習進度追蹤
- ✅ 響應式設計
- ✅ 完整的課程系統

訪問你的Railway部署域名即可使用！ 