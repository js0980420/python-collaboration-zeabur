@echo off
chcp 65001 > nul
color 0A

echo.
echo ===============================================================
echo           🚀 Python協作教學平台 - WebSocket即時版
echo                   啟動和部署腳本
echo ===============================================================
echo.

:: 檢查是否在正確目錄
if not exist "composer.json" (
    echo ❌ 錯誤：請在websocket_version目錄中運行此腳本
    echo 當前目錄應包含 composer.json 文件
    pause
    exit /b 1
)

echo 📍 當前目錄: %CD%
echo.

:: 檢查PHP是否安裝
echo 🔍 檢查PHP環境...
php --version > nul 2>&1
if errorlevel 1 (
    echo ❌ 未找到PHP，請先安裝XAMPP並添加PHP到系統路徑
    echo 💡 建議：
    echo    1. 安裝XAMPP到 C:\xampp
    echo    2. 添加 C:\xampp\php 到系統PATH環境變數
    echo    3. 重新開啟命令提示字元
    pause
    exit /b 1
)

echo ✅ PHP已安裝
php --version
echo.

:: 檢查Composer是否安裝
echo 🔍 檢查Composer...
composer --version > nul 2>&1
if errorlevel 1 (
    echo ❌ 未找到Composer，正在下載安裝...
    
    :: 下載Composer安裝程式
    echo 📥 下載Composer安裝程式...
    powershell -Command "Invoke-WebRequest -Uri https://getcomposer.org/installer -OutFile composer-setup.php"
    
    if not exist "composer-setup.php" (
        echo ❌ 下載失敗，請手動安裝Composer
        echo 🌐 訪問: https://getcomposer.org/download/
        pause
        exit /b 1
    )
    
    :: 安裝Composer
    echo 📦 安裝Composer...
    php composer-setup.php
    del composer-setup.php
    
    if not exist "composer.phar" (
        echo ❌ Composer安裝失敗
        pause
        exit /b 1
    )
    
    echo ✅ Composer安裝成功
    echo.
) else (
    echo ✅ Composer已安裝
    composer --version
    echo.
)

:: 安裝依賴
echo 📦 安裝WebSocket依賴包...
echo 正在安裝 Ratchet + ReactPHP...

:: 使用本地composer.phar或全局composer
if exist "composer.phar" (
    php composer.phar install --no-dev --optimize-autoloader
) else (
    composer install --no-dev --optimize-autoloader
)

if errorlevel 1 (
    echo ❌ 依賴安裝失敗
    echo 💡 嘗試手動安裝:
    echo    composer require ratchet/ratchet
    pause
    exit /b 1
)

echo ✅ 依賴安裝成功
echo.

:: 檢查數據庫連接
echo 🗄️ 檢查MySQL數據庫連接...
php -r "
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;dbname=python_collaboration', 'root', '');
    echo '✅ 數據庫連接成功' . PHP_EOL;
} catch (Exception $e) {
    echo '❌ 數據庫連接失敗: ' . $e->getMessage() . PHP_EOL;
    echo '💡 請確保:' . PHP_EOL;
    echo '   1. XAMPP已啟動' . PHP_EOL;
    echo '   2. MySQL服務正在運行' . PHP_EOL;
    echo '   3. 數據庫 python_collaboration 已創建' . PHP_EOL;
    exit(1);
}
"

if errorlevel 1 (
    echo.
    echo 🔧 建議解決方案:
    echo    1. 啟動XAMPP控制面板
    echo    2. 確保Apache和MySQL都在運行
    echo    3. 運行 http://localhost/phpmyadmin 創建數據庫
    echo.
    pause
    exit /b 1
)

echo.

:: 複製前端文件到XAMPP目錄
echo 📂 部署前端文件到XAMPP...
set XAMPP_HTDOCS=C:\xampp\htdocs\collaboration

if not exist "%XAMPP_HTDOCS%" (
    mkdir "%XAMPP_HTDOCS%"
)

copy "websocket_collaboration_platform.html" "%XAMPP_HTDOCS%\websocket_collaboration_platform.html" > nul 2>&1
if exist "%XAMPP_HTDOCS%\websocket_collaboration_platform.html" (
    echo ✅ 前端文件已部署到: %XAMPP_HTDOCS%
) else (
    echo ⚠️ 前端文件部署失敗，請手動複製文件
)

echo.

:: 顯示啟動信息
echo ===============================================================
echo                   🎯 準備啟動WebSocket服務器
echo ===============================================================
echo.
echo 📡 服務器信息:
echo    地址: ws://localhost:8080
echo    技術: Ratchet + ReactPHP
echo    目標延遲: ^<0.5秒
echo.
echo 🌐 前端訪問:
echo    HTTP輪詢版: http://localhost/collaboration/dual_collaboration_platform.html
echo    WebSocket版: http://localhost/collaboration/websocket_collaboration_platform.html
echo.
echo 💡 使用說明:
echo    1. 服務器啟動後不要關閉此窗口
echo    2. 在瀏覽器中打開前端頁面
echo    3. 輸入房間代碼和用戶名
echo    4. 開始即時協作編程！
echo.
echo ===============================================================

echo.
echo 按任意鍵啟動WebSocket服務器...
pause > nul

echo.
echo 🚀 正在啟動WebSocket服務器...
echo ===============================================================

:: 啟動WebSocket服務器
php websocket_server.php

echo.
echo 🔌 WebSocket服務器已停止
pause 