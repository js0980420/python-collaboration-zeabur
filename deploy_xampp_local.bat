@echo off
chcp 65001 >nul
echo 🏠 XAMPP本地版 - Python協作教學平台部署
echo =============================================

echo 📋 檢查XAMPP環境...

:: 檢查XAMPP是否安裝
if not exist "C:\xampp\apache\bin\httpd.exe" (
    echo ❌ 未找到XAMPP安裝
    echo 請先安裝XAMPP: https://www.apachefriends.org/download.html
    pause
    exit /b 1
)

echo ✅ XAMPP已安裝

:: 檢查Apache和MySQL服務狀態
echo 📋 檢查服務狀態...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✅ Apache服務正在運行
) else (
    echo ⚠️ Apache服務未運行，請啟動XAMPP控制面板並啟動Apache
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✅ MySQL服務正在運行
) else (
    echo ⚠️ MySQL服務未運行，請啟動XAMPP控制面板並啟動MySQL
)

echo.
echo 📁 創建XAMPP部署目錄...
set XAMPP_DIR=C:\xampp\htdocs\python_collaboration
if not exist "%XAMPP_DIR%" mkdir "%XAMPP_DIR%"

echo 📋 複製文件到XAMPP目錄...
copy "xampp_collaboration_platform.html" "%XAMPP_DIR%\index.html" >nul
copy "xampp_websocket_server.php" "%XAMPP_DIR%\websocket_server.php" >nul
copy "code_sync_handler.php" "%XAMPP_DIR%\code_sync_handler.php" >nul
copy "ai_api_handler.php" "%XAMPP_DIR%\ai_api_handler.php" >nul

:: 複製composer文件
if exist "composer.json" copy "composer.json" "%XAMPP_DIR%\composer.json" >nul
if exist "vendor" xcopy "vendor" "%XAMPP_DIR%\vendor" /E /I /Q >nul

echo 📋 創建數據庫初始化腳本...
echo CREATE DATABASE IF NOT EXISTS python_collaboration; > "%XAMPP_DIR%\init_database.sql"
echo USE python_collaboration; >> "%XAMPP_DIR%\init_database.sql"
echo. >> "%XAMPP_DIR%\init_database.sql"
type "mysql\init.sql" >> "%XAMPP_DIR%\init_database.sql" 2>nul

echo 🗄️ 初始化數據庫...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "source %XAMPP_DIR%\init_database.sql" 2>nul
if %ERRORLEVEL% EQU 0 (
    echo ✅ 數據庫初始化成功
) else (
    echo ⚠️ 數據庫初始化可能失敗，請手動執行SQL腳本
)

echo 📋 安裝PHP依賴...
cd /d "%XAMPP_DIR%"
if exist "composer.json" (
    "C:\xampp\php\php.exe" "C:\xampp\composer.phar" install --no-dev 2>nul
    if %ERRORLEVEL% EQU 0 (
        echo ✅ PHP依賴安裝成功
    ) else (
        echo ⚠️ PHP依賴安裝失敗，WebSocket功能可能無法使用
    )
) else (
    echo ⚠️ 未找到composer.json，跳過依賴安裝
)

echo 📋 創建啟動腳本...
echo @echo off > "%XAMPP_DIR%\start_websocket.bat"
echo cd /d "%XAMPP_DIR%" >> "%XAMPP_DIR%\start_websocket.bat"
echo echo 🚀 啟動XAMPP WebSocket服務器... >> "%XAMPP_DIR%\start_websocket.bat"
echo "C:\xampp\php\php.exe" websocket_server.php >> "%XAMPP_DIR%\start_websocket.bat"
echo pause >> "%XAMPP_DIR%\start_websocket.bat"

echo 📋 創建測試腳本...
echo @echo off > "%XAMPP_DIR%\test_connection.bat"
echo echo 🧪 測試XAMPP協作平台連接... >> "%XAMPP_DIR%\test_connection.bat"
echo echo. >> "%XAMPP_DIR%\test_connection.bat"
echo echo 📋 檢查Apache服務... >> "%XAMPP_DIR%\test_connection.bat"
echo curl -s http://localhost/python_collaboration/ ^>nul >> "%XAMPP_DIR%\test_connection.bat"
echo if %%ERRORLEVEL%% EQU 0 ( >> "%XAMPP_DIR%\test_connection.bat"
echo     echo ✅ Apache服務正常 >> "%XAMPP_DIR%\test_connection.bat"
echo ^) else ( >> "%XAMPP_DIR%\test_connection.bat"
echo     echo ❌ Apache服務異常 >> "%XAMPP_DIR%\test_connection.bat"
echo ^) >> "%XAMPP_DIR%\test_connection.bat"
echo echo. >> "%XAMPP_DIR%\test_connection.bat"
echo echo 📋 檢查MySQL連接... >> "%XAMPP_DIR%\test_connection.bat"
echo "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 1;" ^>nul >> "%XAMPP_DIR%\test_connection.bat"
echo if %%ERRORLEVEL%% EQU 0 ( >> "%XAMPP_DIR%\test_connection.bat"
echo     echo ✅ MySQL連接正常 >> "%XAMPP_DIR%\test_connection.bat"
echo ^) else ( >> "%XAMPP_DIR%\test_connection.bat"
echo     echo ❌ MySQL連接異常 >> "%XAMPP_DIR%\test_connection.bat"
echo ^) >> "%XAMPP_DIR%\test_connection.bat"
echo echo. >> "%XAMPP_DIR%\test_connection.bat"
echo echo 🌐 請訪問: http://localhost/python_collaboration/ >> "%XAMPP_DIR%\test_connection.bat"
echo pause >> "%XAMPP_DIR%\test_connection.bat"

echo.
echo ✅ XAMPP本地版部署完成！
echo.
echo 📋 部署信息：
echo    📁 安裝目錄: %XAMPP_DIR%
echo    🌐 訪問地址: http://localhost/python_collaboration/
echo    🔌 WebSocket: ws://127.0.0.1:8080
echo.
echo 📋 使用步驟：
echo    1. 確保XAMPP的Apache和MySQL服務已啟動
echo    2. 運行 start_websocket.bat 啟動WebSocket服務器
echo    3. 訪問 http://localhost/python_collaboration/
echo    4. 開始協作編程！
echo.
echo 🧪 測試連接：運行 test_connection.bat
echo.

:: 詢問是否立即啟動
set /p choice="是否立即啟動WebSocket服務器？(y/n): "
if /i "%choice%"=="y" (
    echo 🚀 啟動WebSocket服務器...
    start "" "%XAMPP_DIR%\start_websocket.bat"
    
    echo 🌐 打開瀏覽器...
    start "" "http://localhost/python_collaboration/"
)

echo.
echo 🎉 部署完成！享受協作編程吧！
pause 