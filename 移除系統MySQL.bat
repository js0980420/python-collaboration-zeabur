@echo off
chcp 65001 > nul
title 移除系統 MySQL - 只保留 XAMPP

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║              🗑️ 移除系統 MySQL - 只保留 XAMPP                 ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

echo ⚠️  警告：此操作將移除系統安裝的 MySQL
echo    只保留 XAMPP 內建的 MySQL
echo.
set /p confirm="確定要繼續嗎？(Y/N): "
if /i not "%confirm%"=="Y" (
    echo 操作已取消
    pause
    exit /b
)

echo.
echo 🔍 檢查當前 MySQL 進程和服務...
echo.

echo 📋 當前 3306 端口使用情況：
netstat -ano | findstr ":3306"

echo.
echo 📋 當前 MySQL 進程：
tasklist | findstr -i mysql

echo.
echo 📋 當前 MySQL 服務：
sc query | findstr -i mysql

echo.
echo 🛑 步驟 1: 停止所有 MySQL 進程...
taskkill /f /im mysqld.exe 2>nul
taskkill /f /im mysql.exe 2>nul
if %errorlevel% equ 0 (
    echo ✅ MySQL 進程已停止
) else (
    echo ℹ️  沒有找到運行中的 MySQL 進程
)

echo.
echo 🛑 步驟 2: 停止 MySQL 服務...
net stop mysql 2>nul
net stop mysql80 2>nul
net stop mysql57 2>nul
net stop "MySQL Server" 2>nul
echo ✅ MySQL 服務停止完成

echo.
echo 🗑️ 步驟 3: 移除 MySQL 服務...
sc delete mysql 2>nul
sc delete mysql80 2>nul
sc delete mysql57 2>nul
sc delete "MySQL Server" 2>nul
echo ✅ MySQL 服務移除完成

echo.
echo ⏳ 等待端口釋放...
timeout /t 3 > nul

echo.
echo 🔍 確認 3306 端口已釋放...
netstat -ano | findstr ":3306"
if %errorlevel% equ 0 (
    echo ⚠️  端口 3306 仍被佔用，可能需要重啟電腦
) else (
    echo ✅ 端口 3306 已釋放
)

echo.
echo 🚀 步驟 4: 啟動 XAMPP MySQL...
echo 正在啟動 XAMPP MySQL 服務...

start /min "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone

echo.
echo ⏳ 等待 XAMPP MySQL 啟動...
timeout /t 8 > nul

echo.
echo 🔍 檢查 XAMPP MySQL 狀態...
netstat -ano | findstr ":3306"
if %errorlevel% equ 0 (
    echo ✅ XAMPP MySQL 已成功啟動
    
    echo.
    echo 🧪 測試數據庫連接...
    "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 'XAMPP MySQL 連接成功!' as status;" 2>nul
    if %errorlevel% equ 0 (
        echo ✅ XAMPP MySQL 連接正常
        echo.
        echo 🎉 成功！現在只使用 XAMPP 的 MySQL
        echo.
        echo 📋 連接信息：
        echo    主機: localhost
        echo    端口: 3306
        echo    用戶: root
        echo    密碼: (空白)
    ) else (
        echo ⚠️  數據庫連接測試失敗，但服務已啟動
    )
) else (
    echo ❌ XAMPP MySQL 啟動失敗
    echo.
    echo 💡 請嘗試：
    echo    1. 重啟電腦後再試
    echo    2. 手動打開 XAMPP 控制面板
    echo    3. 檢查 XAMPP 安裝是否完整
)

echo.
echo 📋 後續步驟：
echo    1. 測試協作功能: http://localhost/collaboration/init_db.php
echo    2. 如果有問題，重啟電腦通常能解決
echo    3. 確保只通過 XAMPP 控制面板管理 MySQL
echo.

echo 🔧 XAMPP 控制面板位置: C:\xampp\xampp-control.exe
echo.

pause 