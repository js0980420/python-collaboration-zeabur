@echo off
chcp 65001 > nul
title 修復 XAMPP MySQL 問題

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                🔧 修復 XAMPP MySQL 問題                       ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

echo 🔍 檢查 3306 端口使用情況...
netstat -ano | findstr ":3306"

echo.
echo 🔍 檢查佔用端口的進程...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":3306.*LISTENING"') do (
    echo 進程 ID: %%a
    tasklist | findstr "%%a"
)

echo.
echo 🛑 停止可能衝突的 MySQL 進程...
taskkill /f /im mysqld.exe 2>nul
if %errorlevel% equ 0 (
    echo ✅ 已停止 MySQL 進程
) else (
    echo ℹ️  沒有找到運行中的 MySQL 進程
)

echo.
echo ⏳ 等待端口釋放...
timeout /t 3 > nul

echo.
echo 🔍 確認 3306 端口狀態...
netstat -ano | findstr ":3306"
if %errorlevel% equ 0 (
    echo ⚠️  端口 3306 仍被佔用
    echo 💡 可能需要手動停止系統 MySQL 服務
) else (
    echo ✅ 端口 3306 已釋放
)

echo.
echo 🚀 嘗試啟動 XAMPP MySQL...
start /min "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"

echo.
echo ⏳ 等待 MySQL 啟動...
timeout /t 5 > nul

echo.
echo 🔍 檢查 XAMPP MySQL 狀態...
netstat -ano | findstr ":3306"
if %errorlevel% equ 0 (
    echo ✅ MySQL 已啟動
    echo.
    echo 🧪 測試數據庫連接...
    "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 'MySQL 連接成功!' as status;" 2>nul
    if %errorlevel% equ 0 (
        echo ✅ 數據庫連接正常
    ) else (
        echo ⚠️  數據庫連接可能有問題
    )
) else (
    echo ❌ MySQL 啟動失敗
    echo.
    echo 💡 建議解決方案：
    echo    1. 檢查 XAMPP 控制面板
    echo    2. 查看 MySQL 錯誤日誌
    echo    3. 重新安裝 XAMPP
)

echo.
echo 📋 如果問題持續，請嘗試：
echo    1. 重啟電腦
echo    2. 以管理員身份運行 XAMPP
echo    3. 檢查防毒軟體是否阻擋
echo.

pause 