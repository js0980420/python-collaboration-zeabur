@echo off
setlocal enabledelayedexpansion
chcp 65001 > nul
color 0C
title 🚀 多人協作效果實測 - 自動部署

echo.
echo 🚀 ===== Python 多人協作效果實測 =====
echo.
echo 📋 此腳本將為您：
echo    1. 部署多人協作測試頁面到 XAMPP
echo    2. 自動開啟多個瀏覽器窗口測試
echo    3. 提供詳細的測試指導
echo    4. 監控協作效果和延遲性能
echo.
pause

set "XAMPP_ROOT=C:\xampp"
set "PROJECT_DIR=%XAMPP_ROOT%\htdocs\python_collaboration"

:: 檢查 XAMPP 是否存在
if not exist "%XAMPP_ROOT%\php\php.exe" (
    echo ❌ 在 C:\xampp 找不到 XAMPP，請確認安裝路徑
    pause
    exit /b 1
)

:: 創建專案目錄
if not exist "%PROJECT_DIR%" (
    mkdir "%PROJECT_DIR%"
    echo ✅ 創建專案目錄: %PROJECT_DIR%
)

echo.
echo 📂 步驟 1/4: 複製測試檔案
echo ============================

:: 複製多人協作測試頁面
copy /y "multi_user_collaboration_test.html" "%PROJECT_DIR%\collaboration_test.html" > nul && echo ✅ 協作測試頁面: collaboration_test.html

:: 複製其他必要檔案（如果存在）
if exist "xampp_collaboration_platform.html" (
    copy /y "xampp_collaboration_platform.html" "%PROJECT_DIR%\index.html" > nul && echo ✅ 主協作平台: index.html
)

if exist "chat_api_handler.php" (
    copy /y "chat_api_handler.php" "%PROJECT_DIR%\chat_api_handler.php" > nul && echo ✅ 聊天API: chat_api_handler.php
)

if exist "code_sync_handler.php" (
    copy /y "code_sync_handler.php" "%PROJECT_DIR%\code_sync_handler.php" > nul && echo ✅ 代碼同步: code_sync_handler.php
)

echo.
echo 🔄 步驟 2/4: 檢查服務狀態
echo ============================

:: 檢查 Apache 和 MySQL 狀態
echo 🔗 檢查 Apache 服務...
netstat -ano | findstr ":80 " > nul && (
    echo ✅ Apache 運行正常 (端口 80)
    set "APACHE_OK=1"
) || (
    echo ⚠️  Apache 未運行，嘗試啟動...
    "%XAMPP_ROOT%\apache\bin\httpd.exe" -k start > nul 2>&1
    timeout /t 3 > nul
    netstat -ano | findstr ":80 " > nul && (
        echo ✅ Apache 啟動成功
        set "APACHE_OK=1"
    ) || (
        echo ❌ Apache 啟動失敗，請手動啟動 XAMPP
        set "APACHE_OK=0"
    )
)

echo 🔗 檢查 MySQL 服務...
netstat -ano | findstr ":3306" > nul && (
    echo ✅ MySQL 運行正常 (端口 3306)
    set "MYSQL_OK=1"
) || (
    echo ⚠️  MySQL 未運行，請手動啟動
    set "MYSQL_OK=0"
)

echo.
echo 🧪 步驟 3/4: 測試環境準備
echo ============================

echo 📋 協作功能測試清單：
echo    ✓ 多窗口協作測試
echo    ✓ 即時游標同步顯示
echo    ✓ 代碼同步延遲測量
echo    ✓ 協作活動記錄追蹤
echo    ✓ 模擬多用戶協作效果

echo.
echo 🌐 步驟 4/4: 啟動測試環境
echo ============================

if "%APACHE_OK%"=="1" (
    echo 🚀 啟動多人協作測試...
    
    :: 等待一秒後啟動主測試頁面
    timeout /t 1 > nul
    start "" "http://localhost/python_collaboration/collaboration_test.html"
    
    :: 等待2秒後自動開啟第二個窗口
    timeout /t 2 > nul
    start "" "http://localhost/python_collaboration/collaboration_test.html"
    
    :: 等待2秒後自動開啟第三個窗口
    timeout /t 2 > nul
    start "" "http://localhost/python_collaboration/collaboration_test.html"
    
    :: 等待2秒後開啟監控窗口
    timeout /t 2 > nul
    start "" "http://localhost/python_collaboration/collaboration_test.html"
    
    echo ✅ 已開啟 4 個協作測試窗口
    
) else (
    echo ❌ Apache 未運行，無法啟動測試
    echo 💡 請先啟動 XAMPP 控制台中的 Apache 服務
)

echo.
echo 🎉 ===== 多人協作測試部署完成！ =====
echo.
echo 📋 測試說明：
echo    🔀 自動開啟的多個窗口代表不同用戶
echo    👀 在任一窗口編輯代碼，觀察其他窗口同步
echo    🖱️  移動游標，查看其他窗口的游標顯示
echo    ⏱️  監控右側延遲數據，了解同步性能
echo    🤖 點擊「模擬協作」按鈕觀看自動演示
echo.
echo 🧪 實測步驟建議：
echo    1. 【游標測試】- 在不同窗口移動游標，觀察彩色游標同步
echo    2. 【代碼測試】- 在窗口A編輯代碼，檢查窗口B的即時更新
echo    3. 【延遲測試】- 點擊「⏱️ 延遲測試」按鈕測量同步速度
echo    4. 【衝突測試】- 同時在多個窗口編輯同一行代碼
echo    5. 【模擬測試】- 使用「🤖 模擬協作」觀看自動演示
echo.
echo 📊 延遲性能參考：
echo    🟢 優秀: 0-100ms  (幾乎感覺不到延遲)
echo    🟡 良好: 100-300ms (輕微延遲，可接受)
echo    🔴 需改進: 300ms+ (明顯延遲，需優化)
echo.
echo 🔧 如果遇到問題：
echo    • 確保所有窗口都顯示相同的房間號
echo    • 檢查瀏覽器開發者工具的控制台錯誤
echo    • 嘗試重新整理頁面重新連接
echo    • 使用「🔄 重置測試」按鈕清理狀態
echo.
echo ✨ 現在開始體驗真實的多人協作編程！
echo.
pause 