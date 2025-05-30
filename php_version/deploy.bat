@echo off
chcp 65001 >nul
title Python教學網站 - 一鍵部署到Railway

echo 🚀 Python教學網站 - 一鍵部署到Railway
echo =====================================

REM 檢查是否在php_version目錄
if not exist "index.php" (
    echo ❌ 錯誤：請在php_version目錄下運行此腳本
    pause
    exit /b 1
)

REM 檢查git是否已初始化
if not exist ".git" (
    echo 📁 初始化Git倉庫...
    git init
    
    REM 創建.gitignore
    if not exist ".gitignore" (
        echo 📝 創建.gitignore文件...
        (
        echo .env
        echo .env.local
        echo .env.production
        echo /vendor/
        echo composer.phar
        echo composer.lock
        echo *.log
        echo /logs/
        echo *.tmp
        echo *.temp
        echo .vscode/
        echo .idea/
        echo *.swp
        echo *.swo
        echo .DS_Store
        echo Thumbs.db
        echo *.sql.backup
        echo *.sql.bak
        echo /cache/
        echo /tmp/
        echo /uploads/
        echo /storage/
        echo .railway/
        ) > .gitignore
    )
)

REM 添加所有文件到git
echo 📦 添加文件到Git...
git add .

REM 提交更改
echo 💾 提交更改...
git commit -m "Deploy: PHP+MySQL Python教學網站 %date% %time%"

REM 檢查是否已設置遠程倉庫
git remote | findstr "origin" >nul
if errorlevel 1 (
    echo.
    echo 🔗 請設置GitHub遠程倉庫：
    echo 1. 在GitHub上創建新倉庫（建議名稱：python-teaching-php）
    echo 2. 複製倉庫URL
    echo.
    set /p repo_url="請輸入GitHub倉庫URL: "
    
    if not "!repo_url!"=="" (
        git remote add origin "!repo_url!"
        echo ✅ 遠程倉庫已設置
    ) else (
        echo ❌ 未設置遠程倉庫，請手動設置後運行：
        echo    git push -u origin main
        pause
        exit /b 1
    )
)

REM 推送到GitHub
echo 🚀 推送到GitHub...
git branch -M main
git push -u origin main

echo.
echo ✅ 代碼已成功推送到GitHub！
echo.
echo 📋 接下來的Railway部署步驟：
echo 1. 訪問 https://railway.app
echo 2. 使用GitHub帳號登錄
echo 3. 點擊 'New Project' → 'Deploy from GitHub repo'
echo 4. 選擇你的倉庫
echo 5. 添加MySQL數據庫服務
echo 6. 設置環境變數：
echo    - XAI_API_KEY=你的XAI API密鑰
echo    - APP_DEBUG=false
echo 7. 在MySQL數據庫中執行 sql/schema.sql
echo.
echo 🔧 詳細部署指南請查看：RAILWAY_DEPLOYMENT.md
echo.
echo 🎉 部署完成後，你將擁有一個完全功能的Python教學網站！

pause 