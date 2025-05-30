@echo off
chcp 65001 >nul
title Python 多人協作教學平台 - 快速部署腳本

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                🐍 Python 多人協作教學平台                   ║
echo ║                     快速部署和測試腳本                       ║
echo ║                                                              ║
echo ║    支援功能：                                                ║
echo ║    • XAMPP 環境檢查和配置                                    ║
echo ║    • MySQL 數據庫自動初始化                                  ║
echo ║    • WebSocket 服務自動啟動                                  ║
echo ║    • 系統功能完整性測試                                      ║
echo ║                                                              ║
echo ║    版本：v1.0.0                                             ║
echo ║    作者：AI Assistant + 客戶協作                             ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

:: 設置變數
set "PROJECT_DIR=%cd%"
set "XAMPP_DIR=C:\xampp"
set "PHP_DIR=%XAMPP_DIR%\php"
set "MYSQL_DIR=%XAMPP_DIR%\mysql\bin"
set "APACHE_DIR=%XAMPP_DIR%\apache\bin"
set "WEBSOCKET_DIR=%PROJECT_DIR%\websocket_server"
set "LOG_FILE=%PROJECT_DIR%\deployment_log.txt"

:: 清空日誌文件
echo [%date% %time%] 開始部署 Python 多人協作教學平台 > "%LOG_FILE%"

echo 🔍 步驟 1: 環境檢查
echo ================================================

:: 檢查 XAMPP 是否安裝
if not exist "%XAMPP_DIR%" (
    echo ❌ 錯誤：未找到 XAMPP 安裝目錄
    echo    請先安裝 XAMPP，下載地址：https://www.apachefriends.org/download.html
    echo [%date% %time%] 錯誤：XAMPP 未安裝 >> "%LOG_FILE%"
    pause
    exit /b 1
)
echo ✅ XAMPP 安裝檢查通過

:: 檢查 PHP 版本
"%PHP_DIR%\php.exe" -v >nul 2>&1
if errorlevel 1 (
    echo ❌ 錯誤：PHP 未正確安裝或配置
    echo [%date% %time%] 錯誤：PHP 配置錯誤 >> "%LOG_FILE%"
    pause
    exit /b 1
)

for /f "tokens=2" %%i in ('"%PHP_DIR%\php.exe" -v 2^>nul ^| find "PHP"') do (
    echo ✅ PHP 版本：%%i
    echo [%date% %time%] PHP 版本：%%i >> "%LOG_FILE%"
    goto :php_found
)
:php_found

:: 檢查 MySQL 服務
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe" >nul
if errorlevel 1 (
    echo ⚠️  MySQL 服務未運行，正在啟動...
    echo [%date% %time%] 啟動 MySQL 服務 >> "%LOG_FILE%"
    net start mysql >nul 2>&1
    timeout /t 3 >nul
) else (
    echo ✅ MySQL 服務正在運行
)

:: 檢查 Apache 服務
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe" >nul
if errorlevel 1 (
    echo ⚠️  Apache 服務未運行，正在啟動...
    echo [%date% %time%] 啟動 Apache 服務 >> "%LOG_FILE%"
    net start apache2.4 >nul 2>&1
    timeout /t 3 >nul
) else (
    echo ✅ Apache 服務正在運行
)

:: 檢查 Node.js
node --version >nul 2>&1
if errorlevel 1 (
    echo ❌ 錯誤：Node.js 未安裝
    echo    請先安裝 Node.js，下載地址：https://nodejs.org/
    echo [%date% %time%] 錯誤：Node.js 未安裝 >> "%LOG_FILE%"
    pause
    exit /b 1
)

for /f %%i in ('node --version 2^>nul') do (
    echo ✅ Node.js 版本：%%i
    echo [%date% %time%] Node.js 版本：%%i >> "%LOG_FILE%"
)

echo.
echo 🗄️  步驟 2: 數據庫初始化
echo ================================================

:: 檢查數據庫是否存在
"%MYSQL_DIR%\mysql.exe" -u root -e "USE python_learning;" >nul 2>&1
if errorlevel 1 (
    echo ⚠️  數據庫不存在，正在創建...
    echo [%date% %time%] 創建數據庫 python_learning >> "%LOG_FILE%"
    
    :: 創建數據庫
    "%MYSQL_DIR%\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS python_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    :: 執行數據庫結構文件
    if exist "%PROJECT_DIR%\mysql\init.sql" (
        echo 📁 導入數據庫結構...
        "%MYSQL_DIR%\mysql.exe" -u root python_learning < "%PROJECT_DIR%\mysql\init.sql"
        echo [%date% %time%] 導入數據庫結構完成 >> "%LOG_FILE%"
    ) else (
        echo ⚠️  未找到數據庫初始化文件，將創建基本表結構...
        echo [%date% %time%] 創建基本表結構 >> "%LOG_FILE%"
        
        :: 創建基本表結構
        "%MYSQL_DIR%\mysql.exe" -u root python_learning -e "
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('teacher', 'student', 'admin') DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS rooms (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_code VARCHAR(10) UNIQUE NOT NULL,
            room_name VARCHAR(100) NOT NULL,
            teacher_id INT NOT NULL,
            max_participants INT DEFAULT 3,
            status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS room_participants (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            left_at TIMESTAMP NULL,
            FOREIGN KEY (room_id) REFERENCES rooms(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS learning_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            user_id INT NOT NULL,
            action_type ENUM('join', 'leave', 'code_edit', 'message', 'execute') NOT NULL,
            content TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS code_snapshots (
            id INT PRIMARY KEY AUTO_INCREMENT,
            room_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            version INT NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        );
        
        INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
        ('teacher', 'teacher@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
        ('student1', 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
        ('student2', 'student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');
        "
    )
    echo ✅ 數據庫初始化完成
) else (
    echo ✅ 數據庫已存在，跳過初始化
)

echo.
echo 🔌 步驟 3: WebSocket 服務配置
echo ================================================

:: 檢查 WebSocket 目錄
if not exist "%WEBSOCKET_DIR%" (
    echo 📁 創建 WebSocket 服務目錄...
    mkdir "%WEBSOCKET_DIR%"
    echo [%date% %time%] 創建 WebSocket 目錄 >> "%LOG_FILE%"
)

cd /d "%WEBSOCKET_DIR%"

:: 檢查 package.json
if not exist "package.json" (
    echo 📦 創建 package.json...
    echo {> package.json
    echo   "name": "python-teaching-websocket",>> package.json
    echo   "version": "1.0.0",>> package.json
    echo   "description": "WebSocket server for Python collaborative learning",>> package.json
    echo   "main": "server.js",>> package.json
    echo   "scripts": {>> package.json
    echo     "start": "node server.js",>> package.json
    echo     "dev": "nodemon server.js">> package.json
    echo   },>> package.json
    echo   "dependencies": {>> package.json
    echo     "ws": "^8.14.2",>> package.json
    echo     "express": "^4.18.2",>> package.json
    echo     "cors": "^2.8.5">> package.json
    echo   }>> package.json
    echo }>> package.json
    echo [%date% %time%] 創建 package.json >> "%LOG_FILE%"
)

:: 安裝 Node.js 依賴
if not exist "node_modules" (
    echo 📦 安裝 Node.js 依賴包...
    npm install
    echo [%date% %time%] 安裝 Node.js 依賴完成 >> "%LOG_FILE%"
) else (
    echo ✅ Node.js 依賴已安裝
)

:: 檢查 server.js
if not exist "server.js" (
    echo 🖥️  創建 WebSocket 服務器...
    echo const WebSocket = require('ws');> server.js
    echo const express = require('express');>> server.js
    echo const cors = require('cors');>> server.js
    echo const http = require('http');>> server.js
    echo.>> server.js
    echo const app = express();>> server.js
    echo app.use(cors());>> server.js
    echo app.use(express.json());>> server.js
    echo.>> server.js
    echo const server = http.createServer(app);>> server.js
    echo const wss = new WebSocket.Server({ server });>> server.js
    echo.>> server.js
    echo const rooms = new Map();>> server.js
    echo.>> server.js
    echo wss.on('connection', function connection(ws) {>> server.js
    echo   console.log('New client connected');>> server.js
    echo   >> server.js
    echo   ws.on('message', function incoming(message) {>> server.js
    echo     try {>> server.js
    echo       const data = JSON.parse(message);>> server.js
    echo       console.log('Received:', data);>> server.js
    echo       >> server.js
    echo       // Echo message back to all clients in the same room>> server.js
    echo       wss.clients.forEach(function each(client) {>> server.js
    echo         if (client !== ws ^&^& client.readyState === WebSocket.OPEN) {>> server.js
    echo           client.send(message);>> server.js
    echo         }>> server.js
    echo       });>> server.js
    echo     } catch (e) {>> server.js
    echo       console.error('Error parsing message:', e);>> server.js
    echo     }>> server.js
    echo   });>> server.js
    echo   >> server.js
    echo   ws.on('close', function close() {>> server.js
    echo     console.log('Client disconnected');>> server.js
    echo   });>> server.js
    echo });>> server.js
    echo.>> server.js
    echo const PORT = process.env.PORT ^|^| 3000;>> server.js
    echo server.listen(PORT, function listening() {>> server.js
    echo   console.log('WebSocket server listening on port ' + PORT);>> server.js
    echo });>> server.js
    echo [%date% %time%] 創建 WebSocket 服務器文件 >> "%LOG_FILE%"
)

echo.
echo 🚀 步驟 4: 啟動服務
echo ================================================

:: 啟動 WebSocket 服務
echo 🔌 啟動 WebSocket 服務器...
start /B node server.js
echo [%date% %time%] 啟動 WebSocket 服務器 >> "%LOG_FILE%"
timeout /t 2 >nul

echo.
echo 🧪 步驟 5: 系統測試
echo ================================================

:: 測試 Apache 服務
echo 🌐 測試 Web 服務器...
powershell -command "try { $response = Invoke-WebRequest -Uri 'http://localhost' -TimeoutSec 5; Write-Host '✅ Apache 服務正常，狀態碼：' $response.StatusCode } catch { Write-Host '❌ Apache 服務測試失敗' }" 2>nul

:: 測試 MySQL 連接
echo 🗄️  測試數據庫連接...
"%MYSQL_DIR%\mysql.exe" -u root -e "SELECT 'MySQL Connection OK' as status;" >nul 2>&1
if errorlevel 1 (
    echo ❌ MySQL 連接測試失敗
    echo [%date% %time%] MySQL 連接測試失敗 >> "%LOG_FILE%"
) else (
    echo ✅ MySQL 連接正常
    echo [%date% %time%] MySQL 連接測試成功 >> "%LOG_FILE%"
)

:: 測試 WebSocket 連接
echo 🔌 測試 WebSocket 服務...
timeout /t 2 >nul
netstat -an | find ":3000" >nul 2>&1
if errorlevel 1 (
    echo ❌ WebSocket 服務測試失敗
    echo [%date% %time%] WebSocket 服務測試失敗 >> "%LOG_FILE%"
) else (
    echo ✅ WebSocket 服務正常運行在端口 3000
    echo [%date% %time%] WebSocket 服務測試成功 >> "%LOG_FILE%"
)

echo.
echo 🎉 部署完成！
echo ================================================
echo.
echo 📋 系統資訊：
echo   • 主網站：http://localhost/python-teaching-web/
echo   • 教師控制台：http://localhost/python-teaching-web/php/dashboard.php
echo   • WebSocket 服務：ws://localhost:3000
echo   • 數據庫：localhost:3306/python_learning
echo.
echo 👤 預設帳號：
echo   • 教師帳號：teacher@example.com (密碼：password123)
echo   • 學生帳號：student1@example.com (密碼：password123)
echo   • 學生帳號：student2@example.com (密碼：password123)
echo.
echo 📁 重要檔案：
echo   • 專案目錄：%PROJECT_DIR%
echo   • 部署日誌：%LOG_FILE%
echo   • WebSocket 目錄：%WEBSOCKET_DIR%
echo.
echo 🛠️  故障排除：
echo   • 如果遇到問題，請查看部署日誌：%LOG_FILE%
echo   • 確保防火牆允許 80 和 3000 端口
echo   • 如需重啟服務，可重新運行此腳本
echo.
echo 📞 技術支援：
echo   • 📧 Email：support@pythonteaching.com
echo   • 📱 電話：+886-123-456-789
echo   • 📚 文檔：docs/ 目錄中的完整文檔
echo.

:: 詢問是否開啟瀏覽器
set /p open_browser="🌐 是否要開啟瀏覽器測試系統？(y/n): "
if /i "%open_browser%"=="y" (
    echo 🚀 正在開啟瀏覽器...
    start http://localhost/python-teaching-web/
    echo [%date% %time%] 開啟瀏覽器測試 >> "%LOG_FILE%"
)

echo.
echo ✨ 感謝使用 Python 多人協作教學平台！
echo    祝您教學愉快！
echo.

cd /d "%PROJECT_DIR%"
echo [%date% %time%] 部署腳本執行完成 >> "%LOG_FILE%"

pause 