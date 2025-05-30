@echo off
setlocal enabledelayedexpansion
title Python協作教學平台 - 一鍵修復並啟動
chcp 65001 >nul

echo.
echo 🚀 Python協作教學平台 - 一鍵修復並啟動腳本
echo =================================================
echo.

:: 檢查管理員權限
net session >nul 2>&1
if errorlevel 1 (
    echo ⚠️  需要管理員權限來啟動XAMPP服務
    echo 請右鍵點擊腳本選擇「以系統管理員身分執行」
    pause
    exit /b 1
)

:: 1. 檢查XAMPP安裝
echo 🔍 步驟1：檢查XAMPP安裝...
if not exist "C:\xampp" (
    echo ❌ 未找到XAMPP安裝目錄: C:\xampp
    echo 請先安裝XAMPP並確保安裝在C:\xampp
    pause
    exit /b 1
)
echo ✅ XAMPP安裝確認

:: 2. 創建協作目錄
echo 📁 步驟2：創建協作目錄...
if not exist "C:\xampp\htdocs\collaboration" (
    mkdir "C:\xampp\htdocs\collaboration"
    echo ✅ 協作目錄創建成功
) else (
    echo ✅ 協作目錄已存在
)

:: 3. 停止現有服務
echo 🛑 步驟3：停止現有XAMPP服務...
taskkill /f /im httpd.exe >nul 2>&1
taskkill /f /im mysqld.exe >nul 2>&1
echo ✅ 現有服務已停止

:: 4. 修復PHP文件
echo 🔧 步驟4：修復PHP語法錯誤...
if exist "code_sync_handler_修復版.php" (
    copy "code_sync_handler_修復版.php" "C:\xampp\htdocs\collaboration\code_sync_handler.php" >nul
    echo ✅ PHP語法錯誤修復完成
) else (
    echo ⚠️  未找到修復版PHP文件，請確保文件存在
)

:: 5. 部署完整數據庫初始化腳本
echo 📊 步驟5：部署數據庫初始化腳本...
if exist "完整數據庫初始化.php" (
    copy "完整數據庫初始化.php" "C:\xampp\htdocs\collaboration\init_complete_db.php" >nul
    echo ✅ 數據庫初始化腳本部署完成
) else (
    echo ⚠️  未找到數據庫初始化腳本
)

:: 6. 部署前端文件
echo 🌐 步驟6：部署前端文件...
if exist "跨設備協作修復版.html" (
    copy "跨設備協作修復版.html" "C:\xampp\htdocs\collaboration\index.html" >nul
    echo ✅ 協作編輯器部署完成
)
if exist "協作同步診斷工具.html" (
    copy "協作同步診斷工具.html" "C:\xampp\htdocs\collaboration\diagnostic.html" >nul
    echo ✅ 診斷工具部署完成
)

:: 7. 啟動MySQL服務
echo 🗄️  步驟7：啟動MySQL服務...
"C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone --console >nul 2>&1 &
timeout /t 5 >nul
echo ✅ MySQL服務啟動完成

:: 8. 啟動Apache服務
echo 🌐 步驟8：啟動Apache服務...
"C:\xampp\apache\bin\httpd.exe" -D FOREGROUND >nul 2>&1 &
timeout /t 3 >nul
echo ✅ Apache服務啟動完成

:: 9. 檢查服務狀態
echo 🔍 步驟9：檢查服務狀態...
timeout /t 2 >nul
netstat -an | findstr ":3306" >nul
if %errorlevel% equ 0 (
    echo ✅ MySQL服務運行正常 (端口3306)
) else (
    echo ❌ MySQL服務啟動失敗
)

netstat -an | findstr ":80" >nul
if %errorlevel% equ 0 (
    echo ✅ Apache服務運行正常 (端口80)
) else (
    echo ❌ Apache服務啟動失敗
)

:: 10. 檢查PHP語法
echo 🔍 步驟10：檢查PHP語法...
php -l "C:\xampp\htdocs\collaboration\code_sync_handler.php" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ PHP語法檢查通過
) else (
    echo ❌ PHP語法檢查失敗
)

echo.
echo 🎉 修復和啟動完成！
echo.
echo 📋 下一步操作：
echo 1. 初始化數據庫：
echo    http://localhost/collaboration/init_complete_db.php
echo.
echo 2. 協作編程平台：
echo    桌電端：http://localhost/collaboration/
echo    筆電端：http://192.168.31.32/collaboration/
echo.
echo 3. 診斷工具：
echo    http://localhost/collaboration/diagnostic.html
echo.
echo 4. 系統狀態檢查：
echo    http://localhost/collaboration/code_sync_handler.php?action=status
echo.
echo 💡 提醒：
echo - 確保兩台設備在同一網路環境
echo - 桌電需要開啟防火牆允許HTTP連接
echo - 如果遇到問題，請查看XAMPP Control Panel
echo.

choice /c YN /m "是否現在打開數據庫初始化頁面"
if %errorlevel% equ 1 (
    start http://localhost/collaboration/init_complete_db.php
)

echo.
echo 🔧 如需手動管理XAMPP服務，請執行：
echo C:\xampp\xampp-control.exe
echo.
pause 