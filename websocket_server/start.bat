@echo off
echo 🚀 啟動WebSocket多人協作服務器
echo =====================================
echo.

REM 檢查Node.js是否安裝
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 錯誤：未找到Node.js
    echo 請先安裝Node.js: https://nodejs.org/
    pause
    exit /b 1
)

echo ✅ Node.js版本：
node --version

echo.
echo 📦 檢查依賴...
if not exist "node_modules" (
    echo 🔄 首次運行，正在安裝依賴...
    npm install
    if %errorlevel% neq 0 (
        echo ❌ 依賴安裝失敗
        pause
        exit /b 1
    )
    echo ✅ 依賴安裝完成
)

echo.
echo 🚀 啟動服務器...
echo 📡 WebSocket地址: ws://localhost:3000/ws
echo 🌐 Web界面: http://localhost:3000
echo.
echo 💡 提示：
echo    - 按 Ctrl+C 停止服務器
echo    - 在瀏覽器中打開 http://localhost:3000
echo    - 邀請朋友一起協作編程！
echo.

REM 啟動服務器
npm start

echo.
echo 👋 服務器已停止
pause 