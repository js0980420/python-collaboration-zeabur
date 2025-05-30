@echo off
chcp 65001 > nul
color 0B

echo.
echo ================================================================
echo           🚀 Python協作教學平台 - 快速部署到XAMPP
echo                    完整功能版 (含聊天+AI助教)
echo ================================================================
echo.

:: 檢查管理員權限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ 需要管理員權限才能操作
    echo 請右鍵點擊此批次檔，選擇「以系統管理員身分執行」
    pause
    exit /b 1
)

:: 設定路徑變數
set XAMPP_ROOT=C:\xampp
set HTDOCS_PATH=%XAMPP_ROOT%\htdocs\collaboration
set PROJECT_ROOT=%~dp0

echo 🔍 檢查環境和文件...
echo.

:: 檢查XAMPP安裝
if not exist "%XAMPP_ROOT%\xampp_start.exe" (
    echo ❌ 未找到 XAMPP 安裝 (預期路徑: %XAMPP_ROOT%)
    echo 請確保 XAMPP 安裝在正確位置
    pause
    exit /b 1
)
echo ✅ XAMPP 安裝檢查通過

:: 檢查必要文件
echo 📁 檢查項目文件...
set "FILES_TO_CHECK=dual_collaboration_platform.html code_sync_handler.php ai_api_handler.php 完整數據庫初始化.php ai_config.json create_chat_table.sql"

for %%f in (%FILES_TO_CHECK%) do (
    if exist "%%f" (
        echo ✅ 找到: %%f
    ) else (
        echo ❌ 缺少: %%f
        set HAS_MISSING=1
    )
)

if defined HAS_MISSING (
    echo.
    echo ❌ 有必要文件缺失，請確保在正確的項目目錄運行此腳本
    pause
    exit /b 1
)

echo.
echo 🛠️ 準備部署環境...

:: 創建目標目錄
if not exist "%HTDOCS_PATH%" (
    mkdir "%HTDOCS_PATH%"
    echo ✅ 創建協作目錄: %HTDOCS_PATH%
) else (
    echo ✅ 協作目錄已存在: %HTDOCS_PATH%
)

:: 停止現有服務
echo 🛑 停止現有服務...
taskkill /f /im httpd.exe 2>nul
taskkill /f /im mysqld.exe 2>nul
timeout /t 2 /nobreak >nul

echo.
echo 📋 複製項目文件...

:: 複製核心文件
copy "dual_collaboration_platform.html" "%HTDOCS_PATH%\index.html" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 複製主頁: index.html
) else (
    echo ❌ 複製主頁失敗
)

copy "dual_collaboration_platform.html" "%HTDOCS_PATH%\dual_collaboration_platform.html" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 複製協作頁面: dual_collaboration_platform.html
) else (
    echo ❌ 複製協作頁面失敗
)

copy "code_sync_handler.php" "%HTDOCS_PATH%\" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 複製API處理器: code_sync_handler.php
) else (
    echo ❌ 複製API處理器失敗
)

copy "ai_api_handler.php" "%HTDOCS_PATH%\" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 複製AI助教API: ai_api_handler.php
) else (
    echo ❌ 複製AI助教API失敗
)

copy "完整數據庫初始化.php" "%HTDOCS_PATH%\init_db.php" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 複製數據庫初始化: init_db.php
) else (
    echo ❌ 複製數據庫初始化失敗
)

copy "ai_config.json" "%HTDOCS_PATH%\" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 複製AI配置: ai_config.json
) else (
    echo ❌ 複製AI配置失敗
)

copy "create_chat_table.sql" "%HTDOCS_PATH%\" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 複製聊天表結構: create_chat_table.sql
) else (
    echo ❌ 複製聊天表結構失敗
)

:: 創建簡化的index.php重定向
echo ^<?php header('Location: dual_collaboration_platform.html'); ?^> > "%HTDOCS_PATH%\index.php"
echo ✅ 創建PHP重定向文件

echo.
echo 🚀 啟動XAMPP服務...

:: 啟動Apache
echo 🌐 啟動Apache Web服務器...
start /min "" "%XAMPP_ROOT%\apache\bin\httpd.exe"
timeout /t 3 /nobreak >nul

:: 檢查Apache狀態
tasklist | findstr httpd.exe > nul
if %errorLevel% equ 0 (
    echo ✅ Apache 啟動成功 (端口 80)
) else (
    echo ❌ Apache 啟動失敗
    goto :error
)

:: 啟動MySQL
echo 🗄️ 啟動MySQL數據庫服務器...
start /min "" "%XAMPP_ROOT%\mysql\bin\mysqld.exe" --console
timeout /t 5 /nobreak >nul

:: 檢查MySQL狀態
tasklist | findstr mysqld.exe > nul
if %errorLevel% equ 0 (
    echo ✅ MySQL 啟動成功 (端口 3306)
) else (
    echo ❌ MySQL 啟動失敗
    goto :error
)

echo.
echo 🧪 測試服務連接...

:: 測試Apache響應
timeout /t 2 /nobreak >nul
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost' -TimeoutSec 5 -ErrorAction Stop; if($response.StatusCode -eq 200) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ Apache Web服務響應正常
) else (
    echo ⚠️ Apache Web服務可能未完全啟動，稍後再試
)

:: 測試MySQL連接
"%XAMPP_ROOT%\mysql\bin\mysql.exe" -u root -e "SELECT 1;" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ MySQL 數據庫連接正常
) else (
    echo ⚠️ MySQL 連接測試失敗，可能需要手動初始化
)

echo.
echo 📊 檢查數據庫...
"%XAMPP_ROOT%\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS python_collaboration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
if %errorLevel% equ 0 (
    echo ✅ 協作數據庫創建成功
) else (
    echo ⚠️ 數據庫創建可能需要手動執行
)

echo.
echo ================================================================
echo                    🎉 部署完成！
echo ================================================================
echo.
echo 📊 服務狀態:
echo    • Apache Web服務器: ✅ 運行中 (localhost:80)
echo    • MySQL 數據庫: ✅ 運行中 (localhost:3306)
echo    • PHP 引擎: ✅ 已配置
echo.
echo 🌐 訪問地址:
echo    • 主協作平台: http://localhost/collaboration/
echo    • 直接協作頁面: http://localhost/collaboration/dual_collaboration_platform.html
echo    • 數據庫初始化: http://localhost/collaboration/init_db.php
echo    • phpMyAdmin: http://localhost/phpmyadmin/
echo.
echo 🎯 下一步操作:
echo    1. 打開瀏覽器訪問: http://localhost/collaboration/
echo    2. 如果是首次使用，先訪問: http://localhost/collaboration/init_db.php
echo    3. 輸入房間代碼和用戶名開始協作
echo    4. 在其他瀏覽器標籤測試多人協作
echo.
echo 💡 測試建議:
echo    • 同一台電腦: 使用 Chrome + Firefox 測試
echo    • 區域網路: 使用 http://[您的IP]/collaboration/ 讓其他設備加入
echo    • 功能測試: 代碼同步、聊天消息、AI助教(需配置API Key)
echo.
echo ⚠️ 重要提醒:
echo    • 防火牆可能需要允許Apache訪問
echo    • AI助教功能需要在 ai_config.json 中配置API Key
echo    • 數據會保存在MySQL數據庫中，重啟後依然存在
echo.

:: 詢問是否打開瀏覽器
set /p openBrowser=是否現在打開協作平台測試? (Y/N): 
if /i "%openBrowser%"=="Y" (
    echo 正在打開瀏覽器...
    start "" "http://localhost/collaboration/dual_collaboration_platform.html"
    timeout /t 2 /nobreak >nul
    echo.
    echo 🔧 如果頁面無法正常載入，請先訪問:
    echo    http://localhost/collaboration/init_db.php
    echo 進行數據庫初始化。
)

echo.
echo 🎓 部署完成！準備開始協作學習吧！
pause
goto :end

:error
echo.
echo ================================================================
echo                    ❌ 部署失敗
echo ================================================================
echo.
echo 可能的解決方案:
echo 1. 確保以管理員身分運行此腳本
echo 2. 檢查 XAMPP 是否正確安裝在 C:\xampp\
echo 3. 檢查防火牆和殺毒軟體設置
echo 4. 手動啟動 XAMPP Control Panel
echo 5. 查看 Apache 和 MySQL 錯誤日誌
echo.
pause

:end 