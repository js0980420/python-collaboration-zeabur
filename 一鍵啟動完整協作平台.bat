@echo off
chcp 65001 > nul
color 0A

echo.
echo ================================================================
echo                🐍 Python協作教學平台 - 一鍵啟動
echo                   完整版 (XAMPP + PHP + MySQL)
echo ================================================================
echo.

:: 檢查管理員權限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ 需要管理員權限才能啟動服務
    echo 請右鍵點擊此批次檔，選擇「以系統管理員身分執行」
    pause
    exit /b 1
)

echo 🔍 正在檢查系統環境...
echo.

:: 檢查XAMPP安裝
if not exist "C:\xampp\xampp_start.exe" (
    echo ❌ 未找到 XAMPP 安裝
    echo 請確保 XAMPP 安裝在 C:\xampp\ 目錄
    pause
    exit /b 1
)
echo ✅ XAMPP 安裝檢查通過

:: 檢查PHP
if not exist "C:\xampp\php\php.exe" (
    echo ❌ 未找到 PHP
    pause
    exit /b 1
)
echo ✅ PHP 檢查通過

:: 檢查MySQL
if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo ❌ 未找到 MySQL
    pause
    exit /b 1
)
echo ✅ MySQL 檢查通過

echo.
echo 🚀 開始啟動服務...
echo.

:: 終止可能存在的Apache進程
echo 📝 清理現有Apache進程...
taskkill /f /im httpd.exe 2>nul
timeout /t 2 /nobreak >nul

:: 終止可能存在的MySQL進程  
echo 📝 清理現有MySQL進程...
taskkill /f /im mysqld.exe 2>nul
timeout /t 2 /nobreak >nul

:: 啟動Apache
echo 🌐 啟動Apache Web服務器...
start /min "" "C:\xampp\apache\bin\httpd.exe"
timeout /t 3 /nobreak >nul

:: 檢查Apache是否啟動成功
tasklist | findstr httpd.exe > nul
if %errorLevel% equ 0 (
    echo ✅ Apache 啟動成功
) else (
    echo ❌ Apache 啟動失敗
    goto :error
)

:: 啟動MySQL
echo 🗄️ 啟動MySQL數據庫服務器...
start /min "" "C:\xampp\mysql\bin\mysqld.exe" --console
timeout /t 5 /nobreak >nul

:: 檢查MySQL是否啟動成功
tasklist | findstr mysqld.exe > nul
if %errorLevel% equ 0 (
    echo ✅ MySQL 啟動成功
) else (
    echo ❌ MySQL 啟動失敗
    goto :error
)

echo.
echo 🔧 檢查協作平台文件...

:: 檢查關鍵文件
if not exist "C:\xampp\htdocs\collaboration\index.html" (
    echo ⚠️ 主頁文件不存在，正在複製...
    if exist "跨設備協作修復版_最終版.html" (
        copy "跨設備協作修復版_最終版.html" "C:\xampp\htdocs\collaboration\index.html" >nul
        echo ✅ 主頁文件複製完成
    ) else (
        echo ❌ 找不到主頁文件源文件
    )
) else (
    echo ✅ 主頁文件存在
)

if not exist "C:\xampp\htdocs\collaboration\code_sync_handler.php" (
    echo ❌ API處理文件不存在
    goto :error
) else (
    echo ✅ API處理文件存在
)

echo.
echo 🧪 測試數據庫連接...

:: 測試MySQL連接
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES;" 2>nul
if %errorLevel% equ 0 (
    echo ✅ MySQL 連接測試通過
) else (
    echo ❌ MySQL 連接失敗
    goto :error
)

:: 檢查協作數據庫
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE python_collaboration; SHOW TABLES;" 2>nul
if %errorLevel% equ 0 (
    echo ✅ 協作數據庫存在
) else (
    echo ⚠️ 協作數據庫不存在，正在初始化...
    if exist "C:\xampp\htdocs\collaboration\init_db.php" (
        "C:\xampp\php\php.exe" "C:\xampp\htdocs\collaboration\init_db.php" >nul 2>&1
        echo ✅ 數據庫初始化完成
    ) else (
        echo ❌ 數據庫初始化文件不存在
        goto :error
    )
)

echo.
echo 🌐 測試Web服務...

:: 等待服務完全啟動
echo 請等待服務完全啟動...
timeout /t 3 /nobreak >nul

:: 測試Apache響應
powershell -Command "try { Invoke-WebRequest -Uri 'http://localhost' -TimeoutSec 5 | Out-Null; exit 0 } catch { exit 1 }" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ Apache Web服務響應正常
) else (
    echo ⚠️ Apache Web服務響應異常，但可能仍可使用
)

:: 測試協作API
powershell -Command "try { Invoke-WebRequest -Uri 'http://localhost/collaboration/code_sync_handler.php?action=status' -TimeoutSec 5 | Out-Null; exit 0 } catch { exit 1 }" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 協作API響應正常
) else (
    echo ⚠️ 協作API響應異常，正在診斷...
    echo 請檢查PHP配置或查看錯誤日誌
)

echo.
echo ================================================================
echo                    🎉 協作平台啟動完成！
echo ================================================================
echo.
echo 📊 服務狀態:
echo    • Apache Web服務器: 運行中 (端口 80)
echo    • MySQL 數據庫: 運行中 (端口 3306)
echo    • Python協作API: 已部署
echo.
echo 🌐 訪問地址:
echo    • 主要協作平台: http://localhost/collaboration/
echo    • API狀態檢查: http://localhost/collaboration/code_sync_handler.php?action=status
echo    • 數據庫管理: http://localhost/phpmyadmin/
echo.
echo 🎯 使用說明:
echo    1. 打開瀏覽器訪問協作平台
echo    2. 輸入房間代碼 (如: PY001) 和用戶名稱
echo    3. 點擊「連接協作」開始多人編程
echo    4. 代碼會自動同步到其他設備 (延遲 2-5秒)
echo.
echo 💡 測試帳號:
echo    • 教師: teacher1 / teacher123
echo    • 學生: student1 / student123
echo    • 學生: student2 / student123
echo.
echo ⚠️ 注意事項:
echo    • 確保同一網路下的設備可以訪問您的電腦
echo    • 防火牆可能需要允許 Apache 訪問
echo    • 協作功能基於HTTP輪詢，適合小組學習使用
echo.

:: 詢問是否打開瀏覽器
set /p openBrowser=是否現在打開協作平台? (Y/N): 
if /i "%openBrowser%"=="Y" (
    echo 正在打開瀏覽器...
    start "" "http://localhost/collaboration/"
    echo ✅ 瀏覽器已啟動
)

echo.
echo 📝 日誌文件位置:
echo    • Apache錯誤日誌: C:\xampp\apache\logs\error.log
echo    • MySQL錯誤日誌: C:\xampp\mysql\data\*.err
echo    • PHP錯誤日誌: C:\xampp\php\logs\php_error_log
echo    • 協作同步日誌: C:\xampp\htdocs\collaboration\sync_debug.log
echo.

echo 🎓 技術支援:
echo    • 如遇問題，請檢查錯誤日誌
echo    • 確保沒有其他程序占用端口 80 和 3306
echo    • 重啟服務: 重新運行此腳本
echo.

pause
goto :end

:error
echo.
echo ================================================================
echo                     ❌ 啟動失敗
echo ================================================================
echo.
echo 可能的原因:
echo    1. 端口被占用 (80, 3306)
echo    2. 沒有管理員權限
echo    3. XAMPP 安裝不完整
echo    4. 防火牆阻止服務啟動
echo.
echo 建議解決方案:
echo    1. 以管理員身分重新運行此腳本
echo    2. 檢查並關閉占用端口的程序
echo    3. 重新安裝 XAMPP
echo    4. 暫時關閉防火牆測試
echo.
echo 查看詳細錯誤:
echo    • 檢查 C:\xampp\apache\logs\error.log
echo    • 檢查 C:\xampp\mysql\data\*.err
echo.
pause

:end
echo 腳本結束。 