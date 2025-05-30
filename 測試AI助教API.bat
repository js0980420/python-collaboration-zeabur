@echo off
chcp 65001 >nul
title 🤖 測試AI助教API

echo.
echo ========================================
echo 🤖 測試AI助教API連接狀態
echo ========================================
echo.

echo 🔍 檢查Apache服務...
tasklist | findstr "httpd" >nul
if %errorlevel% equ 0 (
    echo ✅ Apache服務正在運行
) else (
    echo ❌ Apache服務未運行，請先啟動XAMPP
    pause
    exit /b 1
)

echo.
echo 🔍 測試AI助教API狀態...
powershell -Command "& {try {$r = Invoke-WebRequest 'http://localhost/collaboration/ai_api_handler.php'; Write-Host '✅ API狀態: HTTP' $r.StatusCode; $r.Content} catch {Write-Host '❌ API錯誤:' $_.Exception.Message}}"

echo.
echo 🔍 測試AI助教功能（解釋代碼）...
powershell -Command "& {$body = '{\"action\":\"explain\",\"code\":\"def hello():\n    print(\\\"Hello World\\\")\"}'; try {$r = Invoke-WebRequest -Uri 'http://localhost/collaboration/ai_api_handler.php' -Method POST -Body $body -ContentType 'application/json'; Write-Host '✅ AI功能測試: HTTP' $r.StatusCode; $data = $r.Content | ConvertFrom-Json; Write-Host '回應內容:'; $data.response} catch {Write-Host '❌ AI功能錯誤:' $_.Exception.Message}}"

echo.
echo 🔍 檢查AI助教日誌...
if exist "C:\xampp\htdocs\collaboration\ai_debug.log" (
    echo ✅ 找到AI日誌檔案
    echo 📋 最近的日誌記錄：
    powershell -Command "Get-Content 'C:\xampp\htdocs\collaboration\ai_debug.log' | Select-Object -Last 5"
) else (
    echo ⚠️  未找到AI日誌檔案
)

echo.
echo ========================================
echo 🎯 測試完成
echo ========================================
echo.
pause 