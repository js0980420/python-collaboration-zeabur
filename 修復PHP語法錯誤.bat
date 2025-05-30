@echo off
setlocal enabledelayedexpansion
title 修復PHP語法錯誤 - Python協作教學平台
chcp 65001 >nul

echo.
echo 🔧 Python協作教學平台 - PHP語法錯誤修復腳本
echo ================================================
echo.

REM 檢查 XAMPP 目錄是否存在
if not exist "C:\xampp\htdocs\collaboration" (
    echo ❌ 未找到 XAMPP 協作目錄: C:\xampp\htdocs\collaboration
    echo 請確保您已正確安裝並配置 XAMPP
    pause
    exit /b 1
)

echo 📁 正在備份原始文件...
if exist "C:\xampp\htdocs\collaboration\code_sync_handler.php" (
    copy "C:\xampp\htdocs\collaboration\code_sync_handler.php" "C:\xampp\htdocs\collaboration\code_sync_handler_backup_%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%%time:~6,2%.php" >nul
    echo ✅ 原始文件已備份
) else (
    echo ⚠️  原始文件不存在，將創建新文件
)

echo.
echo 🔄 正在修復 PHP 語法錯誤...

REM 複製修復版文件
copy "code_sync_handler_修復版.php" "C:\xampp\htdocs\collaboration\code_sync_handler.php" >nul

if %errorlevel% equ 0 (
    echo ✅ PHP 語法錯誤修復完成！
    echo.
    echo 📋 修復內容：
    echo    - 修正了 updateUserActivity 函數中的字符串語法錯誤
    echo    - 清理了不正確的轉義字符
    echo    - 優化了 getInitialCode 函數中的字符串處理
    echo    - 確保所有SQL語句正確格式化
    echo.
    
    REM 檢查PHP語法
    echo 🔍 正在檢查PHP語法...
    php -l "C:\xampp\htdocs\collaboration\code_sync_handler.php" >nul 2>&1
    
    if %errorlevel% equ 0 (
        echo ✅ PHP 語法檢查通過！
        echo.
        echo 🌐 現在您可以測試協作功能：
        echo    桌電端: http://localhost/collaboration/
        echo    筆電端: http://192.168.31.32/collaboration/
        echo.
        echo 📊 服務狀態檢查: http://localhost/collaboration/code_sync_handler.php?action=status
        echo 🔧 診斷工具: http://localhost/collaboration/diagnostic.html
    ) else (
        echo ❌ PHP 語法檢查失敗，請檢查文件內容
    )
) else (
    echo ❌ 文件複製失敗，請檢查權限或路徑
)

echo.
echo 📚 下一步：
echo 1. 確保 Apache 和 MySQL 服務正在運行
echo 2. 重新初始化數據庫: http://localhost/collaboration/init_db.php  
echo 3. 分別在桌電和筆電訪問協作頁面
echo 4. 測試代碼同步功能
echo.

pause 