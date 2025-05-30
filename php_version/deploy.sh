#!/bin/bash

# Python教學網站 - 一鍵部署到Railway腳本

echo "🚀 Python教學網站 - 一鍵部署到Railway"
echo "====================================="

# 檢查是否在php_version目錄
if [ ! -f "index.php" ]; then
    echo "❌ 錯誤：請在php_version目錄下運行此腳本"
    exit 1
fi

# 檢查git是否已初始化
if [ ! -d ".git" ]; then
    echo "📁 初始化Git倉庫..."
    git init
    
    # 創建.gitignore（如果不存在）
    if [ ! -f ".gitignore" ]; then
        echo "📝 創建.gitignore文件..."
        cat > .gitignore << 'EOF'
.env
.env.local
.env.production
/vendor/
composer.phar
composer.lock
*.log
/logs/
*.tmp
*.temp
.vscode/
.idea/
*.swp
*.swo
.DS_Store
Thumbs.db
*.sql.backup
*.sql.bak
/cache/
/tmp/
/uploads/
/storage/
.railway/
EOF
    fi
fi

# 添加所有文件到git
echo "📦 添加文件到Git..."
git add .

# 提交更改
echo "💾 提交更改..."
git commit -m "Deploy: PHP+MySQL Python教學網站 $(date '+%Y-%m-%d %H:%M:%S')"

# 檢查是否已設置遠程倉庫
if ! git remote | grep -q origin; then
    echo ""
    echo "🔗 請設置GitHub遠程倉庫："
    echo "1. 在GitHub上創建新倉庫（建議名稱：python-teaching-php）"
    echo "2. 複製倉庫URL並執行："
    echo "   git remote add origin https://github.com/YOUR_USERNAME/python-teaching-php.git"
    echo ""
    read -p "請輸入GitHub倉庫URL: " repo_url
    
    if [ ! -z "$repo_url" ]; then
        git remote add origin "$repo_url"
        echo "✅ 遠程倉庫已設置"
    else
        echo "❌ 未設置遠程倉庫，請手動設置後運行："
        echo "   git push -u origin main"
        exit 1
    fi
fi

# 推送到GitHub
echo "🚀 推送到GitHub..."
git branch -M main
git push -u origin main

echo ""
echo "✅ 代碼已成功推送到GitHub！"
echo ""
echo "📋 接下來的Railway部署步驟："
echo "1. 訪問 https://railway.app"
echo "2. 使用GitHub帳號登錄"
echo "3. 點擊 'New Project' → 'Deploy from GitHub repo'"
echo "4. 選擇你的倉庫"
echo "5. 添加MySQL數據庫服務"
echo "6. 設置環境變數："
echo "   - XAI_API_KEY=你的XAI API密鑰"
echo "   - APP_DEBUG=false"
echo "7. 在MySQL數據庫中執行 sql/schema.sql"
echo ""
echo "🔧 詳細部署指南請查看：RAILWAY_DEPLOYMENT.md"
echo ""
echo "🎉 部署完成後，你將擁有一個完全功能的Python教學網站！" 