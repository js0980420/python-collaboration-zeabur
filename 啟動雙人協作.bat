@echo off
setlocal enabledelayedexpansion
chcp 65001 > nul
color 0A
title 🤝 雙人協作效果實測 - 快速部署

echo.
echo 🤝 ===== 雙人協作效果實測 =====
echo.
echo 📋 此腳本將：
echo    ✓ 部署優化的雙人協作測試頁面
echo    ✓ 自動開啟2個瀏覽器窗口（用戶A + 用戶B）
echo    ✓ 提供詳細的測試步驟指導
echo    ✓ 監控協作延遲和同步效果
echo.
echo 🎯 測試目標：
echo    • 游標同步效果測試（彩色游標顯示）
echo    • 代碼即時同步測試（延遲 < 100ms）
echo    • 多窗口協作穩定性測試
echo    • 模擬協作演示功能
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
echo 📂 步驟 1/3: 複製協作測試檔案
echo ================================

:: 複製雙人協作測試頁面
copy /y "雙人協作測試.html" "%PROJECT_DIR%\dual_test.html" > nul && echo ✅ 雙人協作測試頁面: dual_test.html

:: 複製其他檔案（如果存在）
if exist "chat_api_handler.php" (
    copy /y "chat_api_handler.php" "%PROJECT_DIR%\" > nul && echo ✅ 聊天API處理器
)

if exist "code_sync_handler.php" (
    copy /y "code_sync_handler.php" "%PROJECT_DIR%\" > nul && echo ✅ 代碼同步處理器
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
echo 🚀 步驟 3/3: 啟動雙人協作測試
echo ================================

if "%APACHE_OK%"=="1" (
    echo 🎯 正在啟動雙人協作測試環境...
    
    :: 啟動用戶A窗口
    timeout /t 1 > nul
    echo 👤 開啟用戶A窗口...
    start "" "http://localhost/python_collaboration/dual_test.html"
    
    :: 等待3秒後啟動用戶B窗口
    timeout /t 3 > nul
    echo 👤 開啟用戶B窗口...
    start "" "http://localhost/python_collaboration/dual_test.html"
    
    echo ✅ 已成功開啟2個協作窗口！
    
) else (
    echo ❌ Apache 未運行，請先啟動 XAMPP 中的 Apache 服務
)

echo.
echo 🎉 ===== 雙人協作測試部署完成！ =====
echo.
echo 📋 實測指南：
echo.
echo 🔍 【第一步：確認窗口身份】
echo    • 左邊窗口：用戶A (紅色頭像)
echo    • 右邊窗口：用戶B (青色頭像)
echo    • 查看右上角的用戶資訊確認身份
echo.
echo 🖱️  【第二步：游標同步測試】
echo    • 在用戶A窗口移動游標到不同行
echo    • 觀察用戶B窗口是否出現紅色閃爍游標
echo    • 在用戶B窗口移動游標
echo    • 觀察用戶A窗口是否出現青色閃爍游標
echo.
echo ⌨️  【第三步：代碼同步測試】
echo    • 在用戶A窗口編輯代碼（例如添加註釋）
echo    • 觀察用戶B窗口是否即時同步顯示
echo    • 在用戶B窗口編輯代碼
echo    • 觀察用戶A窗口的同步效果
echo.
echo ⏱️  【第四步：延遲測量】
echo    • 點擊任一窗口的「⏱️ 延遲測試」按鈕
echo    • 觀察右側面板的延遲數據
echo    • 正常延遲應該在 50-150ms 之間
echo.
echo 🤖 【第五步：自動演示】
echo    • 點擊「🤖 模擬協作」按鈕
echo    • 觀看自動化的協作演示效果
echo.
echo 📊 【延遲標準參考】
echo    🟢 優秀: 0-100ms   (幾乎無感知延遲)
echo    🟡 良好: 100-300ms (輕微延遲，可接受)
echo    🔴 需優化: 300ms+  (明顯延遲)
echo.
echo 🔧 【故障排除】
echo    • 如果游標不同步：重新整理兩個窗口
echo    • 如果代碼不同步：檢查瀏覽器控制台錯誤
echo    • 如果延遲過高：關閉其他占用資源的程式
echo    • 如果頁面空白：確認 Apache 正常運行
echo.
echo ✨ 現在開始體驗真實的雙人協作編程效果！
echo 💡 建議同時觀察兩個窗口，感受即時協作的魅力
echo.
pause 