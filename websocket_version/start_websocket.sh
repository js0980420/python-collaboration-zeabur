#!/bin/bash

# WebSocket服務器啟動腳本
echo "🚀 啟動WebSocket服務器..."
echo "工作目錄: $(pwd)"
echo "PHP版本: $(php --version | head -n 1)"
echo "用戶: $(whoami)"

# 檢查必要文件
if [ ! -f "websocket_server.php" ]; then
    echo "❌ 錯誤: websocket_server.php 文件不存在"
    exit 1
fi

if [ ! -d "vendor" ]; then
    echo "❌ 錯誤: vendor 目錄不存在，請運行 composer install"
    exit 1
fi

# 檢查PHP擴展
echo "🔍 檢查PHP擴展..."
php -m | grep -E "(pdo|sockets|json)" || {
    echo "❌ 錯誤: 缺少必要的PHP擴展"
    exit 1
}

# 設置環境變量（如果未設置）
export HOME=${HOME:-/var/www/html}
export USER=${USER:-root}

echo "🌍 環境變量:"
echo "  HOME: $HOME"
echo "  USER: $USER"
echo "  DB_HOST: ${DB_HOST:-未設置}"
echo "  MYSQL_HOST: ${MYSQL_HOST:-未設置}"

# 啟動WebSocket服務器
echo "🔥 啟動WebSocket服務器..."
exec php websocket_server.php 