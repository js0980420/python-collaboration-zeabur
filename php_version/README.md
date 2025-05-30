# Python教學網站 - PHP+MySQL版本

這是原本Python Flask應用的PHP+MySQL版本，保持了所有核心功能，包括XAI API整合、上下文記憶對話系統、程式碼檢查和學習進度追蹤。

## 🌟 主要功能

- 🎓 **系統化Python課程** - 7個主要學習章節
- 🤖 **AI智能助教** - 使用XAI Grok模型，支持上下文記憶
- 💻 **在線程式編輯器** - 即時語法檢查和AI反饋
- 📊 **學習進度追蹤** - 完整的用戶學習數據分析
- 📱 **響應式設計** - 完美支援手機和平板
- 🔍 **深度代碼審查** - AI驅動的程式碼品質分析

## 🛠 技術架構

### 後端技術
- **PHP 7.4+** - 核心後端語言
- **MySQL 5.7+** - 數據庫系統
- **PDO** - 數據庫抽象層
- **cURL** - HTTP客戶端（調用XAI API）

### 前端技術
- **Bootstrap 5** - UI框架
- **JavaScript ES6+** - 前端邏輯
- **Prism.js** - 程式碼語法高亮
- **Font Awesome** - 圖標庫

### API整合
- **XAI API** - Grok模型AI服務
- **RESTful API** - 標準API設計

## 📁 項目結構

```
php_version/
├── config/
│   └── database.php          # 數據庫配置
├── classes/
│   ├── XAIService.php        # XAI API服務類
│   ├── UserManager.php       # 用戶管理類
│   └── ConversationManager.php # 對話管理類
├── api/
│   ├── chat.php              # 聊天API端點
│   ├── check-code.php        # 程式碼檢查API
│   ├── progress.php          # 學習進度API
│   └── analytics.php         # 數據分析API
├── pages/
│   ├── index.php             # 首頁
│   ├── lessons.php           # 課程頁面
│   ├── ai_tutor.php          # AI助教頁面
│   └── practice.php          # 練習頁面
├── templates/
│   └── base.php              # 基礎模板
├── static/
│   ├── css/style.css         # 樣式文件
│   └── js/main.js            # JavaScript文件
├── sql/
│   └── schema.sql            # 數據庫結構
├── index.php                 # 主入口文件
└── README.md                 # 說明文檔
```

## 🚀 安裝指南

### 1. 環境需求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web伺服器 (Apache/Nginx)
- cURL擴展

### 2. 數據庫設置
```sql
-- 創建數據庫
mysql -u root -p < sql/schema.sql
```

### 3. 配置環境
```bash
# 複製環境變數文件
cp env.example .env

# 編輯配置
# 設置數據庫連接信息和XAI API密鑰
```

### 4. Web伺服器設置
將項目文件放置到Web伺服器文檔根目錄，確保`index.php`為入口文件。

#### Apache配置範例 (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx配置範例
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## 🗄 數據庫結構

### 主要數據表
- `users` - 用戶信息
- `lessons` - 課程內容
- `user_progress` - 學習進度
- `ai_conversations` - AI對話記錄
- `code_submissions` - 程式碼提交記錄
- `learning_stats` - 學習統計

## 🔌 API端點

### 聊天API
```
POST /api/chat
Content-Type: application/json

{
    "message": "用戶問題",
    "user_id": "用戶ID",
    "session_id": "會話ID"
}
```

### 程式碼檢查API
```
POST /api/check-code
Content-Type: application/json

{
    "code": "Python程式碼",
    "user_id": "用戶ID"
}
```

### 學習進度API
```
GET /api/progress?user_id=用戶ID
POST /api/progress
{
    "user_id": "用戶ID",
    "lesson_id": "課程ID",
    "progress_percent": 80,
    "completed": true
}
```

### 數據分析API
```
GET /api/analytics
```

## 🎯 功能特色

### 1. AI智能對話
- 使用XAI Grok模型
- 支持上下文記憶（最近20輪對話）
- 個人化學習指導
- 專業Python教學回答

### 2. 程式碼智能檢查
- 基本語法結構分析
- AI深度代碼審查
- 性能優化建議
- 安全性問題檢測
- 具體改進建議

### 3. 學習進度追蹤
- 個人學習統計
- 主題進度分析
- 活動時間記錄
- 成就系統

### 4. 數據分析儀表板
- 全體用戶統計
- 熱門主題分析
- 最近活動追蹤
- 學習效果評估

## 🔧 開發指南

### 添加新課程
1. 在數據庫`lessons`表中插入新課程記錄
2. 在`pages/lessons.php`中添加課程內容模板
3. 更新課程排序索引

### 擴展API功能
1. 在`api/`目錄創建新的PHP文件
2. 遵循現有API的結構和錯誤處理模式
3. 更新`index.php`路由（如需要）

### 自定義AI回應
修改`classes/XAIService.php`中的系統提示詞來調整AI行為。

## 📊 性能優化

- 使用PDO預編譯語句防止SQL注入
- 實現連接池復用數據庫連接
- API響應採用JSON格式壓縮
- 靜態資源CDN加速
- 數據庫查詢索引優化

## 🔒 安全考量

- 所有用戶輸入都經過驗證和清理
- 使用PDO防止SQL注入攻擊
- API端點包含適當的錯誤處理
- 敏感信息（如API密鑰）存儲在環境變數中
- CORS政策配置

## 🐛 故障排除

### 常見問題

1. **數據庫連接失敗**
   - 檢查`.env`文件中的數據庫配置
   - 確保MySQL服務正在運行
   - 驗證用戶權限

2. **XAI API錯誤**
   - 確認API密鑰正確
   - 檢查網絡連接
   - 查看API配額限制

3. **路由不工作**
   - 確保Web伺服器配置正確
   - 檢查`.htaccess`或Nginx配置
   - 驗證mod_rewrite已啟用

## 📈 未來規劃

- [ ] 增加用戶認證系統
- [ ] 實現課程評分和評論
- [ ] 添加程式碼協作功能
- [ ] 開發移動應用
- [ ] 集成更多AI模型選擇
- [ ] 添加視頻教學內容

## 🤝 貢獻指南

1. Fork項目
2. 創建功能分支
3. 提交更改
4. 發起Pull Request

## 📄 授權信息

本項目採用MIT授權條款。

## 📞 技術支援

如有問題或建議，請聯繫：
- Email: support@python-teaching.com
- 技術文檔: [項目Wiki](https://github.com/your-repo/wiki)

---

**注意**: 這是Flask應用的完整PHP轉換版本，保持了所有原有功能的同時，採用了PHP+MySQL的技術架構。 