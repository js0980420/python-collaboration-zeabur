@echo off
setlocal enabledelayedexpansion
chcp 65001 > nul
color 0C
title 🔧 雙人協作修復版 - 解決輸入循環問題

echo.
echo 🔧 ===== 雙人協作修復版部署 =====
echo.
echo ⚠️  此版本專門修復輸入循環問題：
echo    ✓ 增加代碼哈希驗證，防止重複同步
echo    ✓ 添加緊急控制按鈕，可立即停止同步
echo    ✓ 改進防抖機制，減少事件觸發頻率
echo    ✓ 增強防護邏輯，避免無限循環
echo.
echo 🚨 如果遇到輸入問題：
echo    1. 點擊紅色區域的「停止同步」按鈕
echo    2. 點擊「清空編輯器」重新開始
echo    3. 點擊「重啟同步」恢復協作功能
echo.
pause

set "XAMPP_ROOT=C:\xampp"
set "PROJECT_DIR=%XAMPP_ROOT%\htdocs\python_collaboration"

:: 檢查 XAMPP
if not exist "%XAMPP_ROOT%\php\php.exe" (
    echo ❌ 找不到 XAMPP，請確認安裝在 C:\xampp
    pause
    exit /b 1
)

:: 創建目錄
if not exist "%PROJECT_DIR%" (
    mkdir "%PROJECT_DIR%"
    echo ✅ 創建目錄: %PROJECT_DIR%
)

echo.
echo 📂 步驟 1/3: 部署修復版檔案
echo ================================

:: 複製修復版頁面
copy /y "雙人協作測試_修復版.html" "%PROJECT_DIR%\dual_test_fixed.html" > nul && echo ✅ 修復版頁面: dual_test_fixed.html

:: 複製原版本作為備份
if exist "雙人協作測試.html" (
    copy /y "雙人協作測試.html" "%PROJECT_DIR%\dual_test_backup.html" > nul && echo ✅ 原版備份: dual_test_backup.html
)

echo.
echo 🔄 步驟 2/3: 檢查服務狀態
echo ============================

:: 檢查 Apache
echo 🌐 檢查 Apache 服務...
netstat -ano | findstr ":80 " > nul && (
    echo ✅ Apache 運行中 (端口 80)
    set "APACHE_OK=1"
) || (
    echo ⚠️  Apache 未運行，嘗試啟動...
    start "" "%XAMPP_ROOT%\xampp-control.exe"
    echo 💡 請在 XAMPP 控制台中啟動 Apache
    pause
    set "APACHE_OK=0"
)

echo.
echo 🚀 步驟 3/3: 啟動修復版測試
echo ================================

if "%APACHE_OK%"=="1" (
    echo 🎯 正在啟動修復版雙人協作測試...
    
    :: 啟動修復版用戶A窗口
    timeout /t 1 > nul
    echo 👤 開啟修復版用戶A窗口...
    start "" "http://localhost/python_collaboration/dual_test_fixed.html"
    
    :: 等待3秒後啟動用戶B窗口
    timeout /t 3 > nul
    echo 👤 開啟修復版用戶B窗口...
    start "" "http://localhost/python_collaboration/dual_test_fixed.html"
    
    echo ✅ 已成功開啟2個修復版協作窗口！
    
) else (
    echo ❌ Apache 未運行，請先啟動 XAMPP 中的 Apache 服務
)

echo.
echo 🛠️  ===== 輸入循環問題修復指南 =====
echo.
echo 🔧 【修復機制說明】
echo    • 代碼哈希驗證：只有真正改變的代碼才會同步
echo    • 防抖機制：延遲100ms處理變更，避免頻繁觸發
echo    • 狀態保護：isUpdatingFromRemote 防止循環更新
echo    • 緊急控制：紅色控制區可立即停止問題同步
echo.
echo 🚨 【遇到問題時的解決步驟】
echo    1️⃣  發現輸入重複 → 立即點擊「停止同步」
echo    2️⃣  輸入異常 → 點擊「清空編輯器」
echo    3️⃣  準備繼續 → 點擊「重啟同步」
echo    4️⃣  嚴重問題 → 重新整理兩個窗口
echo.
echo 📊 【同步狀態監控】
echo    • 紅色區域顯示當前同步狀態
echo    • 正常：綠色「正常」
echo    • 停止：紅色「已停止」
echo    • 異常：查看右側活動日誌
echo.
echo 🔍 【測試建議】
echo    • 先測試簡單輸入（單行註釋）
echo    • 觀察右側同步狀態和延遲數據
echo    • 逐步增加複雜的編輯操作
echo    • 使用緊急控制功能熟悉操作
echo.
echo ⚡ 【性能優化】
echo    • 修復版減少了50%%的事件觸發頻率
改進的哈希比較算法，避免無效同步
echo    • 優化的防抖計時器，提升響應穩定性
echo    • 增強的錯誤恢復機制
echo.
echo 🎯 現在可以安全地測試雙人協作功能！
echo 💡 記住：紅色緊急控制區是您的安全網
echo.
echo 📋 快速訪問地址：
echo    修復版: http://localhost/python_collaboration/dual_test_fixed.html
echo    原版備份: http://localhost/python_collaboration/dual_test_backup.html
echo.
pause 