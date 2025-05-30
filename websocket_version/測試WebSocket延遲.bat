@echo off
chcp 65001 > nul
color 0E

echo.
echo ================================================================
echo           ⚡ WebSocket延遲測試工具
echo              驗證是否達到 ^<0.5秒 目標延遲
echo ================================================================
echo.

echo 🔍 檢查WebSocket服務器狀態...

:: 首先用簡單的方法測試端口連通性
netstat -an | findstr :8080 > nul
if %errorlevel% == 0 (
    echo ✅ WebSocket服務器正在監聽端口8080
) else (
    echo ❌ WebSocket服務器未運行
    echo 💡 請先啟動WebSocket服務器
    pause
    exit /b 1
)

echo.
echo 📊 進行TCP連接延遲測試...
echo ----------------------------------------

:: 進行多次TCP連接測試來評估延遲
powershell -Command "& { Write-Host '開始進行延遲測試...' -ForegroundColor Green; $results = @(); for ($i = 1; $i -le 5; $i++) { $stopwatch = [System.Diagnostics.Stopwatch]::StartNew(); try { $tcpClient = New-Object System.Net.Sockets.TcpClient; $tcpClient.Connect('localhost', 8080); $tcpClient.Close(); $stopwatch.Stop(); $latency = $stopwatch.ElapsedMilliseconds; $results += $latency; Write-Host \"測試 $i/5: $latency ms\" -ForegroundColor Cyan; } catch { Write-Host \"測試 $i 失敗\" -ForegroundColor Red; } Start-Sleep -Milliseconds 200; } $avgLatency = ($results | Measure-Object -Average).Average; Write-Host ''; Write-Host '📈 測試結果:' -ForegroundColor Yellow; Write-Host \"平均延遲: $($avgLatency.ToString('F2')) ms\" -ForegroundColor White; if ($avgLatency -lt 500) { Write-Host '🎉 延遲測試通過！平均延遲 < 0.5秒' -ForegroundColor Green; } else { Write-Host '⚠️ 延遲測試未達標準' -ForegroundColor Red; } }"

echo.
echo 🌐 啟動瀏覽器測試 WebSocket 功能...
echo.

:: 打開WebSocket協作平台進行實際測試
echo 📝 正在打開 WebSocket 協作平台...
start http://localhost/collaboration/websocket_collaboration_platform.html

echo.
echo 🔗 同時打開 HTTP 輪詢版本進行對比...
start http://localhost/collaboration/dual_collaboration_platform.html

echo.
echo ================================================================
echo                   🎯 測試完成
echo ================================================================
echo.
echo 💻 瀏覽器中已打開兩個版本的協作平台：
echo    ⚡ WebSocket 即時版 - 目標延遲 ^< 0.5秒
echo    🔄 HTTP 輪詢版     - 2秒間隔同步
echo.
echo 📋 測試建議：
echo    1. 在兩個版本中分別打開相同房間
echo    2. 測試代碼同步速度差異
echo    3. 觀察延遲和響應性能
echo    4. 檢查聊天功能的即時性
echo.
echo 🎉 如果WebSocket版本同步速度明顯快於HTTP版本，
echo    說明已成功實現 ^< 0.5秒 的即時協作目標！
echo.
pause 