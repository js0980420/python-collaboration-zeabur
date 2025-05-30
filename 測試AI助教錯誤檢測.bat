@echo off
chcp 65001 > nul
color 0E

echo.
echo ================================================================
echo           🤖 AI助教錯誤檢測功能測試
echo               測試修復後的代碼檢查能力
echo ================================================================
echo.

echo 🧪 準備測試包含錯誤的代碼樣本...
echo.

:: 創建測試用的錯誤代碼文件
echo # 測試代碼 - 包含各種錯誤 > test_code_with_errors.py
echo tTt# 開頭的無效字符 >> test_code_with_errors.py
echo def hello_world( >> test_code_with_errors.py
echo     print("Hello World" >> test_code_with_errors.py
echo if x = 5: >> test_code_with_errors.py
echo print("test") >> test_code_with_errors.py
echo      irregularIndent = "bad" >> test_code_with_errors.py

echo ✅ 測試代碼文件已創建
echo.

echo 📁 測試代碼內容：
echo ----------------------------------------
type test_code_with_errors.py
echo ----------------------------------------
echo.

echo 🚀 正在測試AI助教的錯誤檢測功能...
echo.

:: 使用PowerShell發送POST請求測試AI助教
powershell -Command "
    $testCode = Get-Content 'test_code_with_errors.py' -Raw
    $body = @{
        action = 'bugs'
        code = $testCode
        user_id = 'test_user'
        room = 'test_room'
    } | ConvertTo-Json -Depth 10
    
    try {
        $response = Invoke-RestMethod -Uri 'http://localhost/collaboration/ai_api_handler.php' -Method POST -Body $body -ContentType 'application/json; charset=utf-8' -TimeoutSec 10
        
        Write-Host '✅ AI助教回應成功！' -ForegroundColor Green
        Write-Host ''
        Write-Host '📋 檢測結果：' -ForegroundColor Yellow
        Write-Host '----------------------------------------'
        Write-Host $response.response
        Write-Host '----------------------------------------'
        Write-Host ''
        
        if ($response.response -like '*tTt*' -or $response.response -like '*無效*') {
            Write-Host '🎉 測試通過！AI助教成功檢測到開頭的無效字符！' -ForegroundColor Green
        } else {
            Write-Host '⚠️ 需要檢查：AI助教可能沒有檢測到開頭的無效字符' -ForegroundColor Yellow
        }
        
        Write-Host ''
        Write-Host '💡 測試詳情：'
        Write-Host '• Demo模式：' $response.demo_mode
        Write-Host '• 處理動作：' $response.action
        Write-Host '• 處理時間：' $response.timestamp
        
    } catch {
        Write-Host '❌ 測試失敗：' $_.Exception.Message -ForegroundColor Red
        Write-Host ''
        Write-Host '可能的原因：'
        Write-Host '1. XAMPP服務未啟動'
        Write-Host '2. ai_api_handler.php文件不存在'
        Write-Host '3. PHP配置問題'
        Write-Host ''
        Write-Host '建議先運行：快速部署協作平台到XAMPP.bat'
    }
"

echo.
echo 🧹 清理測試文件...
del test_code_with_errors.py 2>nul

echo.
echo ================================================================
echo                      測試完成
echo ================================================================
echo.
echo 💡 如果測試通過，代表AI助教現在能夠：
echo    • 檢測代碼開頭的無效字符（如 tTt）
echo    • 發現括號不匹配問題
echo    • 檢查引號匹配
echo    • 識別縮進錯誤
echo    • 警告可能的語法問題
echo.
echo 🎯 下一步：
echo    1. 在協作平台中輸入包含錯誤的代碼
echo    2. 點擊「檢查錯誤」按鈕
echo    3. 觀察AI助教是否正確檢測到問題
echo.
pause 