# 🚀 XAMPP快速部署指南

## 📋 部署步驟總覽

```
1. 下載安裝XAMPP (5分鐘)
2. 啟動服務 (1分鐘)
3. 部署網站文件 (2分鐘)
4. 設置資料庫 (3分鐘)
5. 測試運行 (1分鐘)
```

**總計：約12分鐘完成部署！**

---

## 🔽 步驟1：下載安裝XAMPP

### 1.1 下載XAMPP
- 訪問官網：https://www.apachefriends.org/
- 選擇適合您系統的版本（Windows/Mac/Linux）
- 下載最新版本（推薦PHP 8.0+）

### 1.2 安裝XAMPP
```
Windows用戶：
1. 雙擊下載的 .exe 文件
2. 選擇安裝組件：
   ✅ Apache
   ✅ MySQL
   ✅ PHP
   ✅ phpMyAdmin
   ❌ Mercury（不需要）
   ❌ FileZilla（可選）
3. 選擇安裝路徑（建議默認：C:\xampp）
4. 點擊安裝並等待完成
```

---

## ▶️ 步驟2：啟動服務

### 2.1 打開XAMPP控制面板
- Windows：開始菜單 → XAMPP → XAMPP Control Panel
- 或直接運行：`C:\xampp\xampp-control.exe`

### 2.2 啟動必要服務
```
XAMPP Control Panel
┌─────────────────────────────────────────┐
│ Module    │ Status │ Action             │
├─────────────────────────────────────────┤
│ Apache    │ 停止   │ [啟動] [停止] [配置] │ ← 點擊啟動
│ MySQL     │ 停止   │ [啟動] [停止] [配置] │ ← 點擊啟動
│ FileZilla │ 停止   │ [啟動] [停止] [配置] │ ← 不需要
└─────────────────────────────────────────┘
```

### 2.3 驗證服務運行
- Apache啟動後，訪問：http://localhost
- 應該看到XAMPP歡迎頁面
- MySQL啟動後，可以訪問：http://localhost/phpmyadmin

---

## 📁 步驟3：部署網站文件

### 3.1 找到htdocs目錄
```
Windows默認路徑：C:\xampp\htdocs\
Mac默認路徑：/Applications/XAMPP/htdocs/
Linux默認路徑：/opt/lampp/htdocs/
```

### 3.2 複製項目文件
```
1. 將整個 php_version 文件夾複製到 htdocs 目錄
2. 重命名為 python-teaching（可選）

最終結構：
htdocs/
└── python-teaching/
    ├── index.php
    ├── pages/
    ├── static/
    ├── api/
    ├── config/
    └── sql/
```

### 3.3 設置文件權限（Linux/Mac）
```bash
# 如果是Linux或Mac系統
sudo chmod -R 755 /opt/lampp/htdocs/python-teaching/
sudo chown -R www-data:www-data /opt/lampp/htdocs/python-teaching/
```

---

## 🗄️ 步驟4：設置資料庫

### 4.1 訪問phpMyAdmin
- 打開瀏覽器，訪問：http://localhost/phpmyadmin
- 用戶名：root
- 密碼：（默認為空，直接點登錄）

### 4.2 創建資料庫
```sql
1. 點擊左側「新建」
2. 資料庫名稱：python_teaching_gamified
3. 字符集：utf8mb4_unicode_ci
4. 點擊「創建」
```

### 4.3 導入資料庫結構
```
1. 選擇剛創建的資料庫
2. 點擊頂部「導入」標籤
3. 點擊「選擇文件」
4. 選擇：python-teaching/sql/gamification_schema.sql
5. 點擊「執行」
6. 等待導入完成
```

### 4.4 配置資料庫連接
編輯文件：`python-teaching/config/database.php`
```php
<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private $host = 'localhost';
    private $database = 'python_teaching_gamified';
    private $username = 'root';
    private $password = '';  // XAMPP默認密碼為空
    
    // ... 其他代碼保持不變
}
?>
```

---

## ✅ 步驟5：測試運行

### 5.1 訪問網站
打開瀏覽器，訪問：
```
主頁：http://localhost/python-teaching/
遊戲化頁面：http://localhost/python-teaching/pages/gamification.php
API測試：http://localhost/python-teaching/api/gamification_api.php?action=user_stats
```

### 5.2 功能測試清單
```
✅ 主頁正常顯示
✅ 程式碼編輯器可以輸入
✅ 視覺化執行器正常工作
✅ 遊戲化界面顯示正確
✅ 經驗值和等級系統運作
✅ 成就徽章正常顯示
✅ 排行榜數據正確
```

### 5.3 常見問題排除

**問題1：Apache無法啟動**
```
原因：端口80被佔用
解決：
1. 打開XAMPP控制面板
2. 點擊Apache旁的「配置」→「httpd.conf」
3. 找到「Listen 80」改為「Listen 8080」
4. 保存並重啟Apache
5. 訪問：http://localhost:8080/python-teaching/
```

**問題2：MySQL無法啟動**
```
原因：端口3306被佔用
解決：
1. 點擊MySQL旁的「配置」→「my.ini」
2. 找到「port=3306」改為「port=3307」
3. 同時修改database.php中的端口設置
4. 重啟MySQL
```

**問題3：網頁顯示404錯誤**
```
檢查：
1. 文件路徑是否正確
2. Apache是否正常啟動
3. 文件權限是否正確
4. URL拼寫是否正確
```

**問題4：資料庫連接失敗**
```
檢查：
1. MySQL是否正常啟動
2. 資料庫名稱是否正確
3. 用戶名密碼是否正確
4. database.php配置是否正確
```

---

## 🔧 進階配置（可選）

### 虛擬主機設置
如果想使用自定義域名（如：python-learning.local）

1. **編輯hosts文件**
```
Windows：C:\Windows\System32\drivers\etc\hosts
Mac/Linux：/etc/hosts

添加：
127.0.0.1 python-learning.local
```

2. **配置Apache虛擬主機**
編輯：`C:\xampp\apache\conf\extra\httpd-vhosts.conf`
```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/python-teaching"
    ServerName python-learning.local
    <Directory "C:/xampp/htdocs/python-teaching">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. **重啟Apache**
現在可以訪問：http://python-learning.local

---

## 📊 性能優化建議

### PHP配置優化
編輯：`C:\xampp\php\php.ini`
```ini
; 增加記憶體限制
memory_limit = 256M

; 增加上傳文件大小
upload_max_filesize = 50M
post_max_size = 50M

; 增加執行時間
max_execution_time = 300

; 開啟錯誤報告（開發環境）
display_errors = On
error_reporting = E_ALL
```

### MySQL配置優化
編輯：`C:\xampp\mysql\bin\my.ini`
```ini
[mysqld]
# 增加緩存大小
innodb_buffer_pool_size = 256M
query_cache_size = 64M
query_cache_type = 1

# 優化連接數
max_connections = 200
```

---

## 🔒 安全設置（生產環境）

### 1. 設置MySQL密碼
```sql
-- 在phpMyAdmin中執行
ALTER USER 'root'@'localhost' IDENTIFIED BY '你的強密碼';
FLUSH PRIVILEGES;
```

### 2. 禁用不必要的功能
```php
// 在php.ini中
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
```

### 3. 限制目錄訪問
創建：`python-teaching/.htaccess`
```apache
# 禁止訪問敏感文件
<Files "*.sql">
    Deny from all
</Files>

<Files "*.log">
    Deny from all
</Files>

# 禁止目錄瀏覽
Options -Indexes
```

---

## 📞 技術支援

### 遇到問題？

1. **檢查XAMPP日誌**
   - Apache錯誤日誌：`C:\xampp\apache\logs\error.log`
   - MySQL錯誤日誌：`C:\xampp\mysql\data\mysql_error.log`

2. **常用命令**
```bash
# 重啟所有服務
net stop apache2.4 && net start apache2.4
net stop mysql && net start mysql

# 檢查端口佔用
netstat -ano | findstr :80
netstat -ano | findstr :3306
```

3. **在線資源**
   - XAMPP官方文檔：https://www.apachefriends.org/docs/
   - PHP官方文檔：https://www.php.net/docs.php
   - MySQL官方文檔：https://dev.mysql.com/doc/

---

## 🎉 部署完成！

恭喜！您已經成功部署了互動化Python學習環境。

**下一步：**
1. 測試所有功能
2. 自定義內容和樣式
3. 添加更多課程和挑戰
4. 考慮部署到生產環境

**享受您的創新教學平台！** 🚀 