@echo off
chcp 65001 >nul
echo.
echo ==========================================
echo 🗨️  Python協作平台 - 聊天功能測試
echo ==========================================
echo.

echo 📋 測試步驟：
echo 1. 檢查XAMPP服務狀態
echo 2. 測試聊天API端點
echo 3. 啟動雙人協作平台
echo 4. 驗證聊天功能
echo.

echo 🔍 檢查Apache服務...
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if %ERRORLEVEL% EQU 0 (
    echo ✅ Apache 服務正在運行
) else (
    echo ❌ Apache 服務未運行，請先啟動XAMPP
    pause
    exit /b 1
)

echo 🔍 檢查MySQL服務...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if %ERRORLEVEL% EQU 0 (
    echo ✅ MySQL 服務正在運行
) else (
    echo ❌ MySQL 服務未運行，請先啟動XAMPP
    pause
    exit /b 1
)

echo.
echo 🧪 測試聊天API端點...
curl -s -X POST "http://localhost/collaboration/code_sync_handler.php?action=send_update" ^
     -H "Content-Type: application/json" ^
     -d "{\"action\":\"send_update\",\"room\":\"test_chat\",\"userId\":\"test_user\",\"userName\":\"測試用戶\",\"type\":\"chat_message\",\"data\":{\"message\":\"測試聊天消息\",\"timestamp\":%date:~0,4%%date:~5,2%%date:~8,2%}}" > temp_response.json

if exist temp_response.json (
    echo ✅ 聊天API測試完成
    type temp_response.json
    del temp_response.json
) else (
    echo ❌ 聊天API測試失敗
)

echo.
echo 🧪 測試獲取聊天消息...
curl -s -X POST "http://localhost/collaboration/code_sync_handler.php?action=get_updates" ^
     -H "Content-Type: application/json" ^
     -d "{\"action\":\"get_updates\",\"room\":\"test_chat\",\"userId\":\"test_user2\",\"userName\":\"測試用戶2\",\"lastVersion\":0,\"lastChatId\":0}" > temp_get_response.json

if exist temp_get_response.json (
    echo ✅ 獲取聊天消息測試完成
    type temp_get_response.json
    del temp_get_response.json
) else (
    echo ❌ 獲取聊天消息測試失敗
)

echo.
echo 🚀 啟動雙人協作平台...
echo 📱 平台地址: http://localhost/collaboration/dual_collaboration_platform.html
echo.
echo 💡 測試指南：
echo 1. 在兩個瀏覽器窗口中打開平台
echo 2. 使用不同的用戶名連接到同一房間
echo 3. 在聊天區域發送消息
echo 4. 驗證消息是否在兩個窗口中同步顯示
echo.

start "" "http://localhost/collaboration/dual_collaboration_platform.html"

echo ✅ 聊天功能測試腳本執行完成！
echo.
pause 