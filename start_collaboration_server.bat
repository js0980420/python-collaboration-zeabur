@echo off
chcp 65001 >nul
echo 🚀 Python多人協作學習平台 - PHP Ratchet版本
echo ========================================

:: 檢查PHP環境
echo 📋 檢查環境...
where php >nul 2>nul
if errorlevel 1 (
    echo ❌ 錯誤：未找到PHP！請先安裝XAMPP或PHP
    echo 📥 下載地址：https://www.apachefriends.org/
    pause
    exit /b 1
)

:: 顯示PHP版本
for /f "tokens=*" %%i in ('php -v ^| findstr /i "PHP"') do (
    echo ✅ PHP版本：%%i
    goto :php_ok
)
:php_ok

:: 檢查Composer
echo 📦 檢查Composer依賴...
cd /d "%~dp0websocket_server"

if not exist "vendor" (
    echo 📥 正在安裝Composer依賴...
    where composer >nul 2>nul
    if errorlevel 1 (
        echo ❌ 錯誤：未找到Composer！
        echo 📥 請先安裝Composer：https://getcomposer.org/download/
        echo 或者手動運行：php composer.phar install
        pause
        exit /b 1
    )
    
    composer install --no-dev
    if errorlevel 1 (
        echo ❌ Composer安裝失敗！
        pause
        exit /b 1
    )
)

:: 檢查MySQL數據庫
echo 🗄️ 檢查數據庫連接...
php -r "
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    echo '✅ MySQL連接成功\n';
    
    // 創建資料庫
    $pdo->exec('CREATE DATABASE IF NOT EXISTS python_collaborate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo '✅ 資料庫 python_collaborate 已準備就緒\n';
    
} catch (PDOException $e) {
    echo '❌ 資料庫連接失敗: ' . $e->getMessage() . '\n';
    echo '💡 請確認XAMPP的MySQL服務已啟動\n';
    exit(1);
}
"

if errorlevel 1 (
    echo ❌ 資料庫檢查失敗！請檢查XAMPP MySQL服務
    pause
    exit /b 1
)

:: 啟動WebSocket服務器
echo.
echo 🚀 啟動WebSocket協作服務器...
echo 📡 端口：8080
echo 🌐 測試地址：ws://localhost:8080
echo.
echo 💡 提示：
echo    1. 打開 collaboration_test.html 進行測試
echo    2. 開啟多個瀏覽器標籤模擬多人協作
echo    3. 按 Ctrl+C 停止服務器
echo.

:: 啟動服務器
php websocket_server.php

echo.
echo 👋 服務器已停止
pause 