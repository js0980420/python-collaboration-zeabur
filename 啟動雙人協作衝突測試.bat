@echo off
chcp 65001 > nul
title Python雙人協作教學平台 - 衝突測試版啟動器

echo.
echo ==========================================
echo Python雙人協作教學平台 - 衝突測試版
echo ==========================================
echo.
echo 專為雙人代碼同步衝突測試設計
echo 新UI界面：左側代碼編輯器 + 右側AI助教 + 底部聊天
echo 即時同步與衝突檢測功能
echo.

:: 檢查XAMPP服務狀態
echo 檢查XAMPP服務狀態...
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /i "httpd.exe" >nul
if "%ERRORLEVEL%"=="0" (
    echo Apache服務器正在運行
) else (
    echo Apache服務器未運行，正在啟動...
    start "" "C:\xampp\xampp-control.exe"
    echo 請在XAMPP控制面板中手動啟動Apache和MySQL服務
    echo 等待服務啟動中...
    timeout /t 5 > nul
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /i "mysqld.exe" >nul
if "%ERRORLEVEL%"=="0" (
    echo MySQL數據庫正在運行
) else (
    echo MySQL數據庫未運行
    echo 請在XAMPP控制面板中啟動MySQL服務
)

echo.
echo 部署檢查...

:: 檢查協作處理器是否存在
if exist "C:\xampp\htdocs\collaboration\code_sync_handler.php" (
    echo 代碼同步處理器已部署
) else (
    echo 代碼同步處理器缺失，正在複製...
    copy "code_sync_handler.php" "C:\xampp\htdocs\collaboration\" > nul
    if exist "C:\xampp\htdocs\collaboration\code_sync_handler.php" (
        echo 代碼同步處理器複製成功
    ) else (
        echo 代碼同步處理器複製失敗
    )
)

:: 檢查AI助教處理器是否存在
if exist "C:\xampp\htdocs\collaboration\ai_api_handler.php" (
    echo AI助教處理器已部署
) else (
    echo AI助教處理器缺失，正在複製...
    copy "ai_api_handler.php" "C:\xampp\htdocs\collaboration\" > nul
    if exist "C:\xampp\htdocs\collaboration\ai_api_handler.php" (
        echo AI助教處理器複製成功
    ) else (
        echo AI助教處理器複製失敗
    )
)

:: 檢查AI配置文件是否存在
if exist "C:\xampp\htdocs\collaboration\ai_config.json" (
    echo AI配置文件已部署
) else (
    echo AI配置文件缺失，正在複製...
    copy "ai_config.json" "C:\xampp\htdocs\collaboration\" > nul
    if exist "C:\xampp\htdocs\collaboration\ai_config.json" (
        echo AI配置文件複製成功
    ) else (
        echo AI配置文件複製失敗
    )
)

:: 檢查新的協作平台頁面是否存在
if exist "C:\xampp\htdocs\collaboration\dual_collaboration_platform.html" (
    echo 雙人協作平台頁面已部署
) else (
    echo 雙人協作平台頁面缺失，正在複製...
    copy "dual_collaboration_platform.html" "C:\xampp\htdocs\collaboration\" > nul
    if exist "C:\xampp\htdocs\collaboration\dual_collaboration_platform.html" (
        echo 雙人協作平台頁面複製成功
    ) else (
        echo 雙人協作平台頁面複製失敗
    )
)

echo.
echo 測試連接...

:: 測試API狀態
powershell -command "try { $r = Invoke-WebRequest -Uri 'http://localhost/collaboration/code_sync_handler.php?action=status' -UseBasicParsing; Write-Host '代碼同步API正常 (HTTP' $r.StatusCode ')' } catch { Write-Host '代碼同步API異常' }"

powershell -command "try { $r = Invoke-WebRequest -Uri 'http://localhost/collaboration/ai_api_handler.php' -UseBasicParsing; Write-Host 'AI助教API正常 (HTTP' $r.StatusCode ')' } catch { Write-Host 'AI助教API異常' }"

echo.
echo ==========================================
echo 雙人協作衝突測試指南
echo ==========================================
echo.
echo 測試步驟：
echo 1. 在兩台設備或兩個瀏覽器窗口中打開平台
echo 2. 使用相同房間號（如：test_room）
echo 3. 使用不同用戶名（如：用戶A、用戶B）
echo 4. 點擊"連接協作"按鈕
echo 5. 一起編輯代碼，觀察即時同步效果
echo 6. 故意在同一行同時修改，測試衝突處理
echo.
echo UI功能介紹：
echo - 左側：大型代碼編輯器（主要編輯區域）
echo - 右側：AI助教面板（獨立功能區域）
echo - 底部：協作聊天室（討論和溝通）
echo - 頂部：連接狀態和用戶指示器
echo.
echo AI助教功能：
echo - 解釋代碼：詳細說明程式邏輯
echo - 檢查錯誤：找出程式問題
echo - 改進建議：程式碼優化建議
echo - 協作指導：多人編程技巧
echo.
echo 衝突測試要點：
echo - 兩人同時編輯相同行會觸發衝突警告
echo - 觀察右下角同步狀態指示器
echo - 注意頂部用戶在線狀態顯示
echo - 使用聊天功能協調編輯策略
echo.

echo 正在啟動雙人協作平台...

:: 啟動默認瀏覽器打開協作頁面
start "" "http://localhost/collaboration/dual_collaboration_platform.html"

echo.
echo 雙人協作平台已啟動！
echo URL: http://localhost/collaboration/dual_collaboration_platform.html
echo.
echo 如需在其他設備訪問，請使用：
echo    http://[您的IP地址]/collaboration/dual_collaboration_platform.html
echo.

:: 獲取本機IP地址
echo 本機IP地址：
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr /C:"IPv4"') do (
    set IP=%%i
    setlocal enabledelayedexpansion
    set IP=!IP: =!
    echo    http://!IP!/collaboration/dual_collaboration_platform.html
    endlocal
)

echo.
echo 系統將保持運行，按任意鍵關閉此窗口...
pause > nul

echo.
echo 感謝使用雙人協作教學平台！
timeout /t 2 > nul 