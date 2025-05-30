@echo off
chcp 65001 > nul
color 0A

echo.
echo ================================================================
echo         🚀 Python協作教學平台 - Zeabur雲端部署工具
echo                自動化部署到雲端服務
echo ================================================================
echo.

echo 📋 部署檢查清單：
echo ✅ Dockerfile配置文件
echo ✅ supervisord.conf進程管理
echo ✅ zeabur.json部署配置
echo ✅ MySQL數據庫初始化
echo ✅ 環境變量配置
echo.

echo 🔍 檢查Git狀態...
git status
if %errorlevel% neq 0 (
    echo ❌ 這不是一個Git倉庫，正在初始化...
    git init
    echo ✅ Git倉庫初始化完成
)

echo.
echo 📦 添加所有文件到Git...
git add .

echo.
set /p commit_message="💬 請輸入提交信息（直接回車使用默認）: "
if "%commit_message%"=="" set commit_message=🚀 部署Python協作教學平台到Zeabur

echo.
echo 💾 提交代碼更改...
git commit -m "%commit_message%"

echo.
echo 🔗 檢查遠程倉庫...
git remote -v | findstr origin > nul
if %errorlevel% neq 0 (
    echo.
    echo ⚠️  未配置遠程GitHub倉庫
    echo 📝 請按照以下步驟操作：
    echo.
    echo 1. 在GitHub創建新倉庫：python-collaboration-platform
    echo 2. 複製倉庫URL
    echo 3. 運行以下命令：
    echo    git remote add origin https://github.com/YOUR_USERNAME/python-collaboration-platform.git
    echo    git branch -M main
    echo    git push -u origin main
    echo.
    pause
    exit /b 1
)

echo.
echo 🚀 推送到GitHub...
git push origin main
if %errorlevel% neq 0 (
    echo ❌ 推送失敗，請檢查網絡連接和GitHub認證
    pause
    exit /b 1
)

echo.
echo ================================================================
echo                    🎉 部署準備完成！
echo ================================================================
echo.
echo 📍 下一步操作：
echo.
echo 1. 🌐 登錄Zeabur控制台：https://zeabur.com
echo 2. 🔗 連接您的GitHub倉庫
echo 3. ⚙️  添加MySQL服務
echo 4. 🚀 部署應用服務
echo 5. 🔧 配置環境變量和端口
echo.
echo 📖 詳細步驟請參考：ZEABUR_DEPLOY.md
echo.
echo 🌍 部署完成後，您的平台將在全球可訪問！
echo ⚡ WebSocket延遲 ^< 0.5秒
echo 🔒 自動HTTPS加密
echo 💾 雲端數據庫
echo.

pause
echo.
echo 🌐 正在打開Zeabur控制台...
start https://zeabur.com
echo.
echo 📚 正在打開部署文檔...
start ZEABUR_DEPLOY.md
echo.
echo ✨ 祝您部署順利！
pause 