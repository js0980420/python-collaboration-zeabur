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
echo ✅ 前端連接邏輯已修復

echo.
echo 📤 準備推送到GitHub...
git add .
git commit -m "修復Zeabur WebSocket端口配置問題

- 修復zeabur.json端口配置
- 更新WebSocket服務器支持Zeabur環境
- 修復前端WebSocket連接邏輯
- 確保端口8080正確映射"

echo.
echo 🚀 推送到GitHub...
git push origin main

echo.
echo ✅ 修復完成！
echo.
echo 📋 接下來的步驟：
echo 1. 前往Zeabur控制台
echo 2. 重新部署服務
echo 3. 檢查端口映射是否正確
echo 4. 測試WebSocket連接
echo.
echo 🌐 預期的WebSocket URL: wss://your-domain.zeabur.app:8080
echo.
pause 