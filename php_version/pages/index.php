<?php
$pageTitle = 'Python教學平台 - 專業程式設計學習';
$currentPage = 'home';

ob_start();
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    學會 <span class="text-primary">Python</span><br>
                    從這裡開始
                </h1>
                <p class="lead mb-4">
                    專業的Python程式設計學習平台，結合AI助教提供個人化教學體驗。
                    無論你是初學者還是想提升技能，我們都能幫助你達成目標。
                </p>
                <div class="d-flex gap-3 mb-4">
                    <a href="/lessons" class="btn btn-primary btn-lg">
                        <i class="fas fa-play me-2"></i>開始學習
                    </a>
                    <a href="/ai-tutor" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-robot me-2"></i>AI助教
                    </a>
                </div>
                <div class="stats-row">
                    <div class="row g-4">
                        <div class="col-4">
                            <div class="stat-item">
                                <h3 class="mb-0">1000+</h3>
                                <small class="text-muted">學習者</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <h3 class="mb-0">7</h3>
                                <small class="text-muted">系統課程</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <h3 class="mb-0">24/7</h3>
                                <small class="text-muted">AI支援</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-code-demo">
                    <div class="code-window">
                        <div class="code-header">
                            <div class="code-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <span class="code-title">hello_world.py</span>
                        </div>
                        <div class="code-content">
                            <pre><code class="language-python"># 歡迎來到Python世界！
def greet(name):
    """友善的問候函數"""
    return f"你好, {name}! 歡迎學習Python!"

# 主程式
if __name__ == "__main__":
    student_name = "學習者"
    message = greet(student_name)
    print(message)
    
    # 開始你的程式設計之旅
    print("讓我們一起探索Python的無限可能！")</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">為什麼選擇我們？</h2>
            <p class="lead text-muted">專業的教學方法，讓學習變得更有效率</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h4>系統化課程</h4>
                    <p>從基礎語法到進階應用，循序漸進的課程設計讓你扎實掌握Python。</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h4>AI智能助教</h4>
                    <p>24小時在線的AI助教，隨時解答疑問，提供個人化學習建議。</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100">
                    <div class="feature-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h4>實作練習</h4>
                    <p>豐富的程式練習題目，即時程式碼檢查和反饋，邊學邊練。</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4>行動學習</h4>
                    <p>響應式設計，支援手機、平板，隨時隨地都能學習程式設計。</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Learning Path Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">學習路徑</h2>
            <p class="lead text-muted">跟著我們的步驟，輕鬆掌握Python</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="learning-step">
                    <div class="step-number">1</div>
                    <h4>基礎語法</h4>
                    <p>學習Python的基本語法、變數、資料型別等基礎概念。</p>
                    <a href="/lessons" class="btn btn-outline-primary">開始學習</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="learning-step">
                    <div class="step-number">2</div>
                    <h4>邏輯控制</h4>
                    <p>掌握條件判斷、迴圈等程式邏輯控制結構。</p>
                    <a href="/ai-tutor" class="btn btn-outline-primary">AI協助</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="learning-step">
                    <div class="step-number">3</div>
                    <h4>實作應用</h4>
                    <p>透過實際專案練習，將所學知識應用到真實場景。</p>
                    <a href="/practice" class="btn btn-outline-primary">開始練習</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Code Example Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">直觀的程式碼學習</h2>
                <p class="lead mb-4">
                    每個概念都配有清楚的程式碼範例，讓你能夠立即理解和應用。
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>語法高亮顯示</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>即時執行結果</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>錯誤提示與修正建議</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>AI智能代碼審查</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="code-demo-card">
                    <div class="code-header">
                        <span class="code-title">計算機範例</span>
                    </div>
                    <pre><code class="language-python">def calculator(operation, x, y):
    """簡單的計算機函數"""
    if operation == "add":
        return x + y
    elif operation == "subtract":
        return x - y
    elif operation == "multiply":
        return x * y
    elif operation == "divide":
        if y != 0:
            return x / y
        else:
            return "錯誤：除數不能為零"
    else:
        return "不支援的運算"

# 測試計算機
result = calculator("add", 10, 5)
print(f"10 + 5 = {result}")  # 輸出：10 + 5 = 15</code></pre>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">準備開始你的Python之旅了嗎？</h2>
        <p class="lead mb-4">加入千名學習者的行列，從零開始掌握程式設計技能</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="/lessons" class="btn btn-light btn-lg">
                <i class="fas fa-rocket me-2"></i>立即開始
            </a>
            <a href="/ai-tutor" class="btn btn-outline-light btn-lg">
                <i class="fas fa-comments me-2"></i>詢問AI助教
            </a>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include 'templates/base.php';
?> 