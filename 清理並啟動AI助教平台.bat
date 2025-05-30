@echo off
chcp 65001 >nul
title 🧹 清理並啟動AI助教協作平台

echo.
echo ========================================
echo 🧹 清理背景程序並啟動AI助教平台
echo ========================================
echo.

echo 🔍 檢查當前運行的服務...
echo.

:: 檢查並關閉可能的重複MySQL進程
echo 📋 檢查MySQL進程...
tasklist | findstr "mysqld" >nul
if %errorlevel% equ 0 (
    echo ⚠️  發現MySQL進程正在運行
    echo 🛑 正在關閉MySQL進程...
    taskkill /f /im mysqld.exe >nul 2>&1
    timeout /t 2 >nul
    echo ✅ MySQL進程已關閉
) else (
    echo ✅ 沒有發現MySQL進程
)

:: 檢查並關閉可能的Apache進程
echo 📋 檢查Apache進程...
tasklist | findstr "httpd" >nul
if %errorlevel% equ 0 (
    echo ⚠️  發現Apache進程正在運行
    echo 🛑 正在關閉Apache進程...
    taskkill /f /im httpd.exe >nul 2>&1
    timeout /t 2 >nul
    echo ✅ Apache進程已關閉
) else (
    echo ✅ 沒有發現Apache進程
)

:: 檢查並關閉XAMPP控制面板
echo 📋 檢查XAMPP控制面板...
tasklist | findstr "xampp-control" >nul
if %errorlevel% equ 0 (
    echo ⚠️  發現XAMPP控制面板正在運行
    echo 🛑 正在關閉XAMPP控制面板...
    taskkill /f /im xampp-control.exe >nul 2>&1
    timeout /t 1 >nul
    echo ✅ XAMPP控制面板已關閉
) else (
    echo ✅ 沒有發現XAMPP控制面板
)

:: 關閉其他可能的PowerShell窗口（保留當前窗口）
echo 📋 清理多餘的PowerShell窗口...
for /f "tokens=2" %%i in ('tasklist /fi "imagename eq powershell.exe" /fo table /nh ^| findstr /v /c:"%~dp0"') do (
    if not "%%i"=="%~dp0" (
        taskkill /pid %%i >nul 2>&1
    )
)

echo.
echo ✅ 背景程序清理完成！
echo.

:: 等待一下確保進程完全關閉
echo ⏳ 等待系統穩定...
timeout /t 3 >nul

:: 檢查XAMPP是否存在
if not exist "C:\xampp\xampp-control.exe" (
    echo ❌ 錯誤：找不到XAMPP安裝
    echo 請確保XAMPP已安裝在 C:\xampp\ 目錄
    pause
    exit /b 1
)

:: 檢查協作文件是否存在
if not exist "C:\xampp\htdocs\collaboration\collaboration_with_ai_assistant.html" (
    echo ❌ 錯誤：找不到AI助教協作頁面
    echo 請確保文件已正確部署到XAMPP目錄
    pause
    exit /b 1
)

echo 📋 系統檢查：
echo ✅ XAMPP 安裝檢查通過
echo ✅ 協作文件檢查通過
echo.

:: 啟動XAMPP控制面板
echo 🔧 啟動XAMPP控制面板...
start "" "C:\xampp\xampp-control.exe"
echo ✅ XAMPP控制面板已啟動
echo.

:: 等待用戶啟動服務
echo 📝 請在XAMPP控制面板中：
echo    1. 點擊 Apache 的 "Start" 按鈕
echo    2. 點擊 MySQL 的 "Start" 按鈕
echo    3. 確保兩個服務都顯示為綠色 "Running" 狀態
echo.
echo ⚠️  注意：只需要啟動 Apache 和 MySQL，其他服務不需要！
echo.
echo 按任意鍵繼續（確保Apache和MySQL已啟動）...
pause >nul

:: 測試服務狀態
echo.
echo 🔍 測試服務狀態...

:: 測試Apache
echo 測試Apache服務...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost' -TimeoutSec 5; if($response.StatusCode -eq 200) { Write-Host '✅ Apache服務正常' -ForegroundColor Green } else { Write-Host '❌ Apache服務異常' -ForegroundColor Red } } catch { Write-Host '❌ Apache服務無法連接' -ForegroundColor Red }"

:: 測試協作同步API
echo 測試協作同步API...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost/collaboration/code_sync_handler.php?action=status' -TimeoutSec 5; $data = $response.Content | ConvertFrom-Json; if($data.success) { Write-Host '✅ 協作同步API正常' -ForegroundColor Green } else { Write-Host '❌ 協作同步API異常' -ForegroundColor Red } } catch { Write-Host '❌ 協作同步API無法連接' -ForegroundColor Red }"

:: 測試AI助教API
echo 測試AI助教API...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost/collaboration/ai_api_handler.php' -TimeoutSec 5; $data = $response.Content | ConvertFrom-Json; if($data.success) { if($data.demo_mode) { Write-Host '✅ AI助教API正常 (演示模式)' -ForegroundColor Yellow } else { Write-Host '✅ AI助教API正常 (OpenAI模式)' -ForegroundColor Green } } else { Write-Host '❌ AI助教API異常' -ForegroundColor Red } } catch { Write-Host '❌ AI助教API無法連接' -ForegroundColor Red }"

echo.
echo 🌐 準備開啟瀏覽器...
timeout /t 2 >nul

:: 開啟主要協作頁面
echo 🚀 開啟AI助教協作頁面...
start "" "http://localhost/collaboration/collaboration_with_ai_assistant.html"

:: 等待一下再開啟第二個窗口
timeout /t 3 >nul

:: 開啟第二個協作窗口（模擬多人協作）
echo 👥 開啟第二個協作窗口（模擬多人協作）...
start "" "http://localhost/collaboration/collaboration_with_ai_assistant.html"

echo.
echo ========================================
echo 🎉 AI助教協作平台啟動完成！
echo ========================================
echo.
echo 📖 使用指南：
echo.
echo 🔹 協作功能：
echo    • 在兩個瀏覽器窗口中輸入不同的用戶名
echo    • 點擊"連接協作"開始多人編程
echo    • 代碼會在3秒內自動同步
echo.
echo 🤖 AI助教功能：
echo    • 解釋程式碼：分析代碼功能和邏輯
echo    • 檢查錯誤：找出潛在問題和改進點
echo    • 改進建議：提供優化和最佳實踐建議
echo    • 協作指導：多人編程的學習建議
echo    • 自定義提問：輸入任何Python相關問題
echo.
echo 💡 測試建議：
echo    1. 在編輯器中輸入一些Python代碼
echo    2. 點擊AI助教的各種功能按鈕
echo    3. 嘗試在兩個窗口間進行協作編程
echo    4. 使用聊天功能進行交流
echo.
echo 🔧 故障排除：
echo    • 如果同步失敗，檢查MySQL是否正常運行
echo    • 如果AI助教無回應，檢查網路連接
echo    • 查看瀏覽器開發者工具的控制台錯誤
echo.
echo 🧹 清理完成：
echo    • 已關閉重複的MySQL和Apache進程
echo    • 已關閉多餘的PowerShell窗口
echo    • 只保留必要的XAMPP服務
echo.
echo 按任意鍵關閉此窗口...
pause >nul 