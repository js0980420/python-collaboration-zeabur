@echo off
chcp 65001 > nul
title 兩人協作實測部署 - XAMPP環境

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                🧪 兩人協作實測部署腳本                        ║
echo ║                                                              ║
echo ║  桌電（光纖網路） + 筆電（手機網路）協作測試                    ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

:: 設置變數
set "XAMPP_ROOT=C:\xampp"
set "PROJECT_DIR=%XAMPP_ROOT%\htdocs"
set "LOG_FILE=collaboration_test_%date:~0,4%%date:~5,2%%date:~8,2%.log"

:: 檢查 XAMPP 是否存在
echo 🔍 檢查 XAMPP 環境...
if not exist "%XAMPP_ROOT%\php\php.exe" (
    echo ❌ 找不到 XAMPP，請確認安裝在 C:\xampp
    echo 💡 下載地址：https://www.apachefriends.org/download.html
    pause
    exit /b 1
)
echo ✅ XAMPP 環境檢查通過

:: 獲取本機 IP 地址
echo.
echo 🌐 獲取本機 IP 地址...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr "IPv4"') do (
    for /f "tokens=1" %%b in ("%%a") do (
        set "LOCAL_IP=%%b"
        goto :ip_found
    )
)
:ip_found
set LOCAL_IP=%LOCAL_IP: =%
echo ✅ 本機 IP 地址: %LOCAL_IP%
echo [%date% %time%] 本機 IP: %LOCAL_IP% >> "%LOG_FILE%"

:: 創建協作測試目錄
echo.
echo 📁 創建測試目錄...
if not exist "%PROJECT_DIR%\collaboration" mkdir "%PROJECT_DIR%\collaboration"
echo ✅ 測試目錄創建完成

:: 複製協作文件
echo.
echo 📋 部署協作文件...

:: 複製主要協作頁面
if exist "雙人協作測試_修復版.html" (
    copy /y "雙人協作測試_修復版.html" "%PROJECT_DIR%\collaboration\index.html" > nul
    echo ✅ 主協作頁面: index.html
) else (
    echo ❌ 找不到 雙人協作測試_修復版.html
    goto :error
)

:: 複製 API 處理器
if exist "code_sync_handler.php" (
    copy /y "code_sync_handler.php" "%PROJECT_DIR%\collaboration\" > nul
    echo ✅ 代碼同步處理器: code_sync_handler.php
)

if exist "chat_api_handler.php" (
    copy /y "chat_api_handler.php" "%PROJECT_DIR%\collaboration\" > nul
    echo ✅ 聊天 API 處理器: chat_api_handler.php
)

if exist "ai_api_handler.php" (
    copy /y "ai_api_handler.php" "%PROJECT_DIR%\collaboration\" > nul
    echo ✅ AI API 處理器: ai_api_handler.php
)

:: 複製數據庫初始化文件
if exist "init_xampp_database.php" (
    copy /y "init_xampp_database.php" "%PROJECT_DIR%\collaboration\init_db.php" > nul
    echo ✅ 數據庫初始化: init_db.php
)

:: 檢查 Apache 服務
echo.
echo 🔧 檢查 Apache 服務...
netstat -an | findstr ":80 " > nul
if %errorlevel% equ 0 (
    echo ✅ Apache 正在運行
) else (
    echo 🚀 啟動 Apache 服務...
    start /min "" "%XAMPP_ROOT%\apache\bin\httpd.exe"
    timeout /t 3 > nul
    
    netstat -an | findstr ":80 " > nul
    if %errorlevel% equ 0 (
        echo ✅ Apache 啟動成功
    ) else (
        echo ❌ Apache 啟動失敗，請手動啟動 XAMPP
        start "" "%XAMPP_ROOT%\xampp-control.exe"
        pause
    )
)

:: 檢查 MySQL 服務
echo.
echo 🗄️ 檢查 MySQL 服務...
netstat -an | findstr ":3306 " > nul
if %errorlevel% equ 0 (
    echo ✅ MySQL 正在運行
) else (
    echo 🚀 啟動 MySQL 服務...
    start /min "" "%XAMPP_ROOT%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP_ROOT%\mysql\bin\my.ini"
    timeout /t 5 > nul
    echo ✅ MySQL 啟動完成
)

:: 創建測試信息頁面
echo.
echo 📄 創建測試信息頁面...
echo ^<!DOCTYPE html^> > "%PROJECT_DIR%\collaboration\info.html"
echo ^<html lang="zh-TW"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo ^<head^> >> "%PROJECT_DIR%\collaboration\info.html"
echo     ^<meta charset="UTF-8"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo     ^<meta name="viewport" content="width=device-width, initial-scale=1.0"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo     ^<title^>兩人協作實測 - 連接信息^</title^> >> "%PROJECT_DIR%\collaboration\info.html"
echo     ^<style^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; } >> "%PROJECT_DIR%\collaboration\info.html"
echo         .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); } >> "%PROJECT_DIR%\collaboration\info.html"
echo         .info-box { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196f3; } >> "%PROJECT_DIR%\collaboration\info.html"
echo         .success { background: #e8f5e8; border-left-color: #4caf50; } >> "%PROJECT_DIR%\collaboration\info.html"
echo         .warning { background: #fff3e0; border-left-color: #ff9800; } >> "%PROJECT_DIR%\collaboration\info.html"
echo         .url { font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 16px; } >> "%PROJECT_DIR%\collaboration\info.html"
echo         h1 { color: #333; text-align: center; } >> "%PROJECT_DIR%\collaboration\info.html"
echo         h2 { color: #666; border-bottom: 2px solid #eee; padding-bottom: 10px; } >> "%PROJECT_DIR%\collaboration\info.html"
echo     ^</style^> >> "%PROJECT_DIR%\collaboration\info.html"
echo ^</head^> >> "%PROJECT_DIR%\collaboration\info.html"
echo ^<body^> >> "%PROJECT_DIR%\collaboration\info.html"
echo     ^<div class="container"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^<h1^>🧪 兩人協作實測環境^</h1^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^<div class="info-box success"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<h2^>✅ 部署成功！^</h2^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<p^>協作測試環境已成功部署到 XAMPP。^</p^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^<div class="info-box"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<h2^>🖥️ 桌電端（主機）- 光纖網路^</h2^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<p^>^<strong^>訪問地址：^</strong^>^</p^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<div class="url"^>http://localhost/collaboration/^</div^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<p^>^<strong^>角色：^</strong^> 用戶A（紅色游標）^</p^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^<div class="info-box warning"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<h2^>💻 筆電端（客戶端）- 手機網路^</h2^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<p^>^<strong^>訪問地址：^</strong^>^</p^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<div class="url"^>http://%LOCAL_IP%/collaboration/^</div^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<p^>^<strong^>角色：^</strong^> 用戶B（藍色游標）^</p^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^<div class="info-box"^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<h2^>🚀 測試步驟^</h2^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^<ol^> >> "%PROJECT_DIR%\collaboration\info.html"
echo                 ^<li^>^<a href="init_db.php"^>初始化數據庫^</a^>^</li^> >> "%PROJECT_DIR%\collaboration\info.html"
echo                 ^<li^>^<a href="index.html"^>桌電端開始協作^</a^>^</li^> >> "%PROJECT_DIR%\collaboration\info.html"
echo                 ^<li^>筆電端使用上方 IP 地址訪問^</li^> >> "%PROJECT_DIR%\collaboration\info.html"
echo             ^</ol^> >> "%PROJECT_DIR%\collaboration\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\collaboration\info.html"
echo     ^</div^> >> "%PROJECT_DIR%\collaboration\info.html"
echo ^</body^> >> "%PROJECT_DIR%\collaboration\info.html"
echo ^</html^> >> "%PROJECT_DIR%\collaboration\info.html"
echo ✅ 測試信息頁面: info.html

:: 創建網路測試頁面
echo.
echo 🌐 創建網路測試工具...
echo ^<?php > "%PROJECT_DIR%\collaboration\network_test.php"
echo header('Content-Type: application/json'); >> "%PROJECT_DIR%\collaboration\network_test.php"
echo $start_time = microtime(true); >> "%PROJECT_DIR%\collaboration\network_test.php"
echo usleep(10000); >> "%PROJECT_DIR%\collaboration\network_test.php"
echo $end_time = microtime(true); >> "%PROJECT_DIR%\collaboration\network_test.php"
echo $response_time = round(($end_time - $start_time) * 1000, 2); >> "%PROJECT_DIR%\collaboration\network_test.php"
echo echo json_encode(['success' =^> true, 'response_time_ms' =^> $response_time, 'client_ip' =^> $_SERVER['REMOTE_ADDR']]); >> "%PROJECT_DIR%\collaboration\network_test.php"
echo ?^> >> "%PROJECT_DIR%\collaboration\network_test.php"
echo ✅ 網路測試工具: network_test.php

:: 顯示完成信息
echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                    🎉 部署完成！                              ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.
echo 📋 測試環境信息：
echo    🖥️  桌電端: http://localhost/collaboration/
echo    💻 筆電端: http://%LOCAL_IP%/collaboration/
echo    📄 測試信息: http://localhost/collaboration/info.html
echo.
echo 🚀 開始測試步驟：
echo    1. 桌電端打開: http://localhost/collaboration/info.html
echo    2. 點擊"初始化數據庫"
echo    3. 桌電端開始協作測試
echo    4. 筆電端使用 IP 地址連接
echo.
echo 📊 測試記錄將保存到: %LOG_FILE%
echo.

:: 記錄部署信息
echo [%date% %time%] 部署完成 - 桌電: localhost, 筆電: %LOCAL_IP% >> "%LOG_FILE%"

:: 詢問是否立即打開測試頁面
echo 是否立即打開測試信息頁面？(Y/N)
set /p choice="請選擇: "
if /i "%choice%"=="Y" (
    start "" "http://localhost/collaboration/info.html"
    echo ✅ 已打開測試信息頁面
)

echo.
echo 💡 提示：請確保筆電與桌電在同一網路環境下
echo 📱 筆電連接地址: http://%LOCAL_IP%/collaboration/
echo.
pause
goto :end

:error
echo.
echo ❌ 部署失敗！請檢查文件是否存在。
echo 📋 需要的文件：
echo    - 雙人協作測試_修復版.html
echo    - code_sync_handler.php
echo    - chat_api_handler.php
echo    - init_xampp_database.php
echo.
pause

:end
echo [%date% %time%] 腳本執行結束 >> "%LOG_FILE%" 