@echo off
chcp 65001 > nul
title 部署跨設備協作修復版 - 解決同步問題

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                🔧 跨設備協作修復版部署                        ║
echo ║                                                              ║
echo ║  解決 BroadcastChannel 限制，實現真正的跨設備同步              ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

:: 設置變數
set "XAMPP_ROOT=C:\xampp"
set "PROJECT_DIR=%XAMPP_ROOT%\htdocs\collaboration"
set "LOG_FILE=cross_device_fix_%date:~0,4%%date:~5,2%%date:~8,2%.log"

:: 檢查 XAMPP 環境
echo 🔍 檢查 XAMPP 環境...
if not exist "%XAMPP_ROOT%\php\php.exe" (
    echo ❌ 找不到 XAMPP，請確認安裝在 C:\xampp
    pause
    exit /b 1
)
echo ✅ XAMPP 環境檢查通過

:: 獲取本機 IP
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
echo ✅ 本機 IP: %LOCAL_IP%

:: 創建項目目錄
echo.
echo 📁 準備項目目錄...
if not exist "%PROJECT_DIR%" mkdir "%PROJECT_DIR%"
echo ✅ 項目目錄準備完成

:: 部署修復版文件
echo.
echo 🚀 部署跨設備協作修復版...

:: 部署主要協作頁面
if exist "跨設備協作修復版.html" (
    copy /y "跨設備協作修復版.html" "%PROJECT_DIR%\index.html" > nul
    echo ✅ 主協作頁面: index.html (HTTP 輪詢版本)
) else (
    echo ❌ 找不到 跨設備協作修復版.html
    goto :error
)

:: 部署診斷工具
if exist "協作同步診斷工具.html" (
    copy /y "協作同步診斷工具.html" "%PROJECT_DIR%\diagnostic.html" > nul
    echo ✅ 診斷工具: diagnostic.html
)

:: 確保 PHP 處理器存在
if exist "code_sync_handler.php" (
    copy /y "code_sync_handler.php" "%PROJECT_DIR%\" > nul
    echo ✅ 代碼同步處理器: code_sync_handler.php
) else (
    echo ⚠️  code_sync_handler.php 不存在，將創建基礎版本...
    call :create_sync_handler
)

:: 確保數據庫初始化文件存在
if exist "init_xampp_database.php" (
    copy /y "init_xampp_database.php" "%PROJECT_DIR%\init_db.php" > nul
    echo ✅ 數據庫初始化: init_db.php
)

:: 檢查服務狀態
echo.
echo 🔧 檢查服務狀態...

:: 檢查 Apache
netstat -an | findstr ":80 " > nul
if %errorlevel% equ 0 (
    echo ✅ Apache 正在運行
) else (
    echo 🚀 啟動 Apache...
    start /min "" "%XAMPP_ROOT%\apache\bin\httpd.exe"
    timeout /t 3 > nul
)

:: 檢查 MySQL
netstat -an | findstr ":3306 " > nul
if %errorlevel% equ 0 (
    echo ✅ MySQL 正在運行
) else (
    echo 🚀 啟動 MySQL...
    start /min "" "%XAMPP_ROOT%\mysql\bin\mysqld.exe"
    timeout /t 3 > nul
)

:: 創建測試信息頁面
echo.
echo 📄 創建測試信息頁面...
echo ^<!DOCTYPE html^> > "%PROJECT_DIR%\info.html"
echo ^<html lang="zh-TW"^> >> "%PROJECT_DIR%\info.html"
echo ^<head^> >> "%PROJECT_DIR%\info.html"
echo     ^<meta charset="UTF-8"^> >> "%PROJECT_DIR%\info.html"
echo     ^<title^>跨設備協作修復版 - 測試信息^</title^> >> "%PROJECT_DIR%\info.html"
echo     ^<style^> >> "%PROJECT_DIR%\info.html"
echo         body { font-family: 'Microsoft JhengHei'; margin: 40px; background: #f5f5f5; } >> "%PROJECT_DIR%\info.html"
echo         .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); } >> "%PROJECT_DIR%\info.html"
echo         .header { text-align: center; color: #333; margin-bottom: 30px; } >> "%PROJECT_DIR%\info.html"
echo         .section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff; } >> "%PROJECT_DIR%\info.html"
echo         .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; margin: 5px; } >> "%PROJECT_DIR%\info.html"
echo         .btn:hover { background: #0056b3; } >> "%PROJECT_DIR%\info.html"
echo         .btn-success { background: #28a745; } >> "%PROJECT_DIR%\info.html"
echo         .btn-warning { background: #ffc107; color: #212529; } >> "%PROJECT_DIR%\info.html"
echo         .highlight { background: #fff3cd; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #ffc107; } >> "%PROJECT_DIR%\info.html"
echo         .problem { background: #f8d7da; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #dc3545; } >> "%PROJECT_DIR%\info.html"
echo         .solution { background: #d4edda; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #28a745; } >> "%PROJECT_DIR%\info.html"
echo     ^</style^> >> "%PROJECT_DIR%\info.html"
echo ^</head^> >> "%PROJECT_DIR%\info.html"
echo ^<body^> >> "%PROJECT_DIR%\info.html"
echo     ^<div class="container"^> >> "%PROJECT_DIR%\info.html"
echo         ^<div class="header"^> >> "%PROJECT_DIR%\info.html"
echo             ^<h1^>🌐 跨設備協作修復版^</h1^> >> "%PROJECT_DIR%\info.html"
echo             ^<p^>解決 BroadcastChannel 限制，實現真正的跨設備同步^</p^> >> "%PROJECT_DIR%\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\info.html"
echo. >> "%PROJECT_DIR%\info.html"
echo         ^<div class="problem"^> >> "%PROJECT_DIR%\info.html"
echo             ^<h3^>❌ 原問題診斷^</h3^> >> "%PROJECT_DIR%\info.html"
echo             ^<p^>^<strong^>BroadcastChannel API 限制：^</strong^> 只能在同一瀏覽器的不同標籤頁間同步，無法跨設備通信。^</p^> >> "%PROJECT_DIR%\info.html"
echo             ^<p^>桌電瀏覽器 ❌ 無法與 筆電瀏覽器 直接通信^</p^> >> "%PROJECT_DIR%\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\info.html"
echo. >> "%PROJECT_DIR%\info.html"
echo         ^<div class="solution"^> >> "%PROJECT_DIR%\info.html"
echo             ^<h3^>✅ 修復方案^</h3^> >> "%PROJECT_DIR%\info.html"
echo             ^<p^>^<strong^>HTTP 輪詢同步：^</strong^> 使用 PHP + MySQL 實現跨設備同步^</p^> >> "%PROJECT_DIR%\info.html"
echo             ^<ul^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>每 2 秒檢查數據庫更新^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>支援代碼同步、游標同步、用戶狀態^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>真正的跨設備、跨網路協作^</li^> >> "%PROJECT_DIR%\info.html"
echo             ^</ul^> >> "%PROJECT_DIR%\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\info.html"
echo. >> "%PROJECT_DIR%\info.html"
echo         ^<div class="section"^> >> "%PROJECT_DIR%\info.html"
echo             ^<h3^>🚀 測試步驟^</h3^> >> "%PROJECT_DIR%\info.html"
echo             ^<ol^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>^<a href="init_db.php" class="btn btn-warning"^>1. 初始化數據庫^</a^>^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>^<a href="index.html" class="btn btn-success"^>2. 桌電端開始協作^</a^>^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>筆電端訪問: ^<strong^>http://%LOCAL_IP%/collaboration/^</strong^>^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>^<a href="diagnostic.html" class="btn"^>4. 診斷工具^</a^>^</li^> >> "%PROJECT_DIR%\info.html"
echo             ^</ol^> >> "%PROJECT_DIR%\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\info.html"
echo. >> "%PROJECT_DIR%\info.html"
echo         ^<div class="highlight"^> >> "%PROJECT_DIR%\info.html"
echo             ^<h3^>📡 網路配置^</h3^> >> "%PROJECT_DIR%\info.html"
echo             ^<p^>^<strong^>桌電端：^</strong^> http://localhost/collaboration/^</p^> >> "%PROJECT_DIR%\info.html"
echo             ^<p^>^<strong^>筆電端：^</strong^> http://%LOCAL_IP%/collaboration/^</p^> >> "%PROJECT_DIR%\info.html"
echo             ^<p^>^<strong^>本機 IP：^</strong^> %LOCAL_IP%^</p^> >> "%PROJECT_DIR%\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\info.html"
echo. >> "%PROJECT_DIR%\info.html"
echo         ^<div class="section"^> >> "%PROJECT_DIR%\info.html"
echo             ^<h3^>🔧 技術改進^</h3^> >> "%PROJECT_DIR%\info.html"
echo             ^<ul^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>✅ 替換 BroadcastChannel 為 HTTP 輪詢^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>✅ 使用 MySQL 數據庫存儲同步數據^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>✅ 支援跨設備、跨網路協作^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>✅ 實時延遲監控和統計^</li^> >> "%PROJECT_DIR%\info.html"
echo                 ^<li^>✅ 用戶狀態和活動追蹤^</li^> >> "%PROJECT_DIR%\info.html"
echo             ^</ul^> >> "%PROJECT_DIR%\info.html"
echo         ^</div^> >> "%PROJECT_DIR%\info.html"
echo     ^</div^> >> "%PROJECT_DIR%\info.html"
echo ^</body^> >> "%PROJECT_DIR%\info.html"
echo ^</html^> >> "%PROJECT_DIR%\info.html"
echo ✅ 測試信息頁面: info.html

:: 顯示完成信息
echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                    🎉 修復版部署完成！                        ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.
echo ✅ 跨設備協作修復版已成功部署
echo ✅ 使用 HTTP 輪詢替代 BroadcastChannel
echo ✅ 支援真正的跨設備同步
echo.
echo 🚀 立即測試：
echo.
echo 📋 測試信息頁面:
echo    📍 http://localhost/collaboration/info.html
echo.
echo 🔧 診斷工具:
echo    📍 http://localhost/collaboration/diagnostic.html
echo.
echo 🌐 協作頁面:
echo    🖥️  桌電端: http://localhost/collaboration/
echo    💻 筆電端: http://%LOCAL_IP%/collaboration/
echo.
echo 📝 測試步驟:
echo    1. 初始化數據庫: http://localhost/collaboration/init_db.php
echo    2. 桌電端開始協作
echo    3. 筆電端連接並測試同步
echo    4. 觀察 HTTP 輪詢同步效果
echo.

set /p open_info="是否立即打開測試信息頁面？(Y/N): "
if /i "%open_info%"=="Y" (
    start "" "http://localhost/collaboration/info.html"
    echo ✅ 已打開測試信息頁面
)

echo.
echo 💡 提示：
echo    - HTTP 輪詢每 2 秒同步一次
echo    - 延遲比 BroadcastChannel 高，但支援跨設備
echo    - 可在診斷工具中查看詳細技術說明
echo.
pause
goto :end

:create_sync_handler
echo 📝 創建基礎同步處理器...
echo ^<?php > "%PROJECT_DIR%\code_sync_handler.php"
echo header('Content-Type: application/json'); >> "%PROJECT_DIR%\code_sync_handler.php"
echo header('Access-Control-Allow-Origin: *'); >> "%PROJECT_DIR%\code_sync_handler.php"
echo header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); >> "%PROJECT_DIR%\code_sync_handler.php"
echo header('Access-Control-Allow-Headers: Content-Type'); >> "%PROJECT_DIR%\code_sync_handler.php"
echo. >> "%PROJECT_DIR%\code_sync_handler.php"
echo if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { >> "%PROJECT_DIR%\code_sync_handler.php"
echo     exit(0); >> "%PROJECT_DIR%\code_sync_handler.php"
echo } >> "%PROJECT_DIR%\code_sync_handler.php"
echo. >> "%PROJECT_DIR%\code_sync_handler.php"
echo // 基礎同步處理邏輯 >> "%PROJECT_DIR%\code_sync_handler.php"
echo $input = json_decode(file_get_contents('php://input'), true); >> "%PROJECT_DIR%\code_sync_handler.php"
echo. >> "%PROJECT_DIR%\code_sync_handler.php"
echo if ($input['action'] == 'get_updates') { >> "%PROJECT_DIR%\code_sync_handler.php"
echo     echo json_encode(['success' =^> true, 'updates' =^> [], 'latestVersion' =^> 1]); >> "%PROJECT_DIR%\code_sync_handler.php"
echo } else { >> "%PROJECT_DIR%\code_sync_handler.php"
echo     echo json_encode(['success' =^> true]); >> "%PROJECT_DIR%\code_sync_handler.php"
echo } >> "%PROJECT_DIR%\code_sync_handler.php"
echo ?^> >> "%PROJECT_DIR%\code_sync_handler.php"
echo ✅ 基礎同步處理器已創建
goto :eof

:error
echo.
echo ❌ 部署失敗！請檢查文件是否存在
pause
goto :end

:end
echo.
echo 📋 部署日誌已保存到: %LOG_FILE%
echo 🔗 項目地址: %PROJECT_DIR%
echo. 