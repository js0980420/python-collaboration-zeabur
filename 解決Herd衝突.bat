@echo off
chcp 65001 > nul
title 解決 Laravel Herd 與 XAMPP 衝突

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║              🔧 解決 Laravel Herd 與 XAMPP 衝突               ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

echo 🔍 檢查當前 80 端口使用情況...
netstat -ano | findstr ":80.*LISTENING"

echo.
echo 🛑 停止 Laravel Herd 服務...
herd stop

echo.
echo 🛑 強制停止 nginx 進程...
taskkill /f /im nginx.exe 2>nul

echo.
echo 🛑 停止可能的 PHP 進程...
taskkill /f /im php.exe 2>nul

echo.
echo ⏳ 等待端口釋放...
timeout /t 3 > nul

echo.
echo 🔍 確認 80 端口已釋放...
netstat -ano | findstr ":80.*LISTENING"
if %errorlevel% equ 0 (
    echo ⚠️  端口仍被佔用，請手動檢查
) else (
    echo ✅ 端口 80 已釋放
)

echo.
echo 🚀 啟動 XAMPP Apache...
start /min "" "C:\xampp\apache\bin\httpd.exe"

echo.
echo ⏳ 等待 Apache 啟動...
timeout /t 5 > nul

echo.
echo 🔍 檢查 Apache 狀態...
netstat -ano | findstr ":80.*LISTENING"
if %errorlevel% equ 0 (
    echo ✅ Apache 已成功啟動
    echo.
    echo 🌐 現在可以訪問：
    echo    📍 本地: http://localhost/collaboration/
    echo    📍 遠程: http://192.168.31.32/collaboration/
) else (
    echo ❌ Apache 啟動失敗
    echo 💡 請手動打開 XAMPP 控制面板啟動 Apache
    start "" "C:\xampp\xampp-control.exe"
)

echo.
echo 📋 測試步驟：
echo    1. 初始化數據庫: http://localhost/collaboration/init_db.php
echo    2. 開始協作測試: http://localhost/collaboration/
echo.

pause 