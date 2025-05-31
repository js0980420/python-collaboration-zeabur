@echo off
chcp 65001 >nul
echo 🔧 修復Zeabur部署端口問題
echo ================================

echo 📋 檢查當前配置...
if not exist "zeabur.json" (
    echo ❌ 找不到 zeabur.json 文件
    pause
    exit /b 1
)

echo ✅ zeabur.json 配置已更新
echo ✅ WebSocket服務器已修復
echo ✅ 前端WebSocket連接URL已修正 (移除Zeabur環境下的:8080端口)

echo.
echo 📤 準備推送到GitHub...
git add .
git commit -m "修復Zeabur WebSocket端口和前端連接URL

- zeabur.json端口配置保持公開80端口
- WebSocket服務器PHP腳本在Zeabur環境監聽0.0.0.0:8080
- 前端HTML在Zeabur環境下連接 wss://<domain> (無端口號)
- 確保端口正確映射和代理"

echo.
echo 🚀 推送到GitHub...
git push origin main

echo.
echo ✅ 修復完成！
echo.
echo 📋 接下來的步驟：
echo 1. 前往Zeabur控制台
echo 2. 觸發新的部署 (最新的commit應該會自動觸發)
echo 3. 檢查部署日誌，確認Apache和WebSocket服務器正常啟動
echo 4. 測試應用程序，WebSocket連接應該正常
echo.
echo 🌐 預期的Zeabur WebSocket URL: wss://your-domain.zeabur.app (無端口號)
echo 🏠 本地測試WebSocket URL: ws://localhost:8080 或 ws://192.168.x.x:8080
echo.
pause 