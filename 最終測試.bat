@echo off
chcp 65001 > nul
title 最終測試 - XAMPP 協作環境

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                🧪 最終測試 - XAMPP 協作環境                   ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

echo 🔍 檢查 Apache 狀態...
netstat -ano | findstr ":80.*LISTENING"
if %errorlevel% equ 0 (
    echo ✅ Apache 正在運行
) else (
    echo ❌ Apache 未運行
    goto :apache_failed
)

echo.
echo 🔍 檢查 MySQL 狀態...
netstat -ano | findstr ":3306.*LISTENING"
if %errorlevel% equ 0 (
    echo ✅ MySQL 正在運行
) else (
    echo ❌ MySQL 未運行
    goto :mysql_failed
)

echo.
echo 🔍 檢查協作文件...
if exist "C:\xampp\htdocs\collaboration\index.html" (
    echo ✅ 協作頁面存在
) else (
    echo ❌ 協作頁面不存在
    goto :files_missing
)

if exist "C:\xampp\htdocs\collaboration\init_db.php" (
    echo ✅ 數據庫初始化文件存在
) else (
    echo ❌ 數據庫初始化文件不存在
    goto :files_missing
)

echo.
echo 🧪 測試 MySQL 連接...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 1;" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ MySQL 連接正常
) else (
    echo ❌ MySQL 連接失敗
    goto :mysql_connection_failed
)

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

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                    🎉 測試完成！                              ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.
echo ✅ XAMPP 環境正常運行
echo ✅ 協作文件部署完成
echo ✅ 數據庫連接正常
echo.
echo 🚀 現在可以開始兩人協作測試：
echo.
echo 🖥️  桌電端（光纖網路）:
echo    📍 http://localhost/collaboration/
echo    🎯 角色：用戶A（紅色游標）
echo.
echo 💻 筆電端（手機網路）:
echo    📍 http://%LOCAL_IP%/collaboration/
echo    🎯 角色：用戶B（藍色游標）
echo.
echo 📋 測試步驟：
echo    1. 初始化數據庫: http://localhost/collaboration/init_db.php
echo    2. 桌電端開始協作: http://localhost/collaboration/
echo    3. 筆電端連接: http://%LOCAL_IP%/collaboration/
echo    4. 測試即時同步和游標顯示
echo.
echo 💡 如果遇到問題：
echo    - 確保兩台設備在同一網路
echo    - 檢查防火牆設置
echo    - 重啟瀏覽器
echo.

set /p open_test="是否立即打開測試頁面？(Y/N): "
if /i "%open_test%"=="Y" (
    start "" "http://localhost/collaboration/init_db.php"
    timeout /t 2 > nul
    start "" "http://localhost/collaboration/"
    echo ✅ 已打開測試頁面
)

echo.
pause
goto :end

:apache_failed
echo.
echo ❌ Apache 未運行，請啟動 XAMPP 控制面板
start "" "C:\xampp\xampp-control.exe"
pause
goto :end

:mysql_failed
echo.
echo ❌ MySQL 未運行，請檢查端口衝突
echo 💡 建議重啟電腦後再試
pause
goto :end

:files_missing
echo.
echo ❌ 協作文件缺失，請重新部署
echo 💡 執行: 兩人協作實測部署.bat
pause
goto :end

:mysql_connection_failed
echo.
echo ❌ MySQL 連接失敗
echo 💡 請檢查 MySQL 配置
pause
goto :end

:end 