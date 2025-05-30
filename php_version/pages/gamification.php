<?php
$page_title = "🎮 遊戲化學習體驗";
$page_description = "體驗創新的Python學習方式：遊戲化系統、視覺化程式執行、成就徽章等";
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Python教學網站</title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300;400;500;600&family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- 自定義樣式 -->
    <link href="../static/css/gamification.css" rel="stylesheet">
    <link href="../static/css/visualizer.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .hero-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin: 20px 0;
            text-align: center;
            color: white;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .demo-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .user-avatar {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-stats {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(0,0,0,0.1); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-python"></i> Python教學網站
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">首頁</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lessons.php">課程</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="gamification.php">遊戲化學習</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="display-4 mb-4">🎮 遊戲化學習體驗</h1>
            <p class="lead">創新的Python學習方式，讓程式學習變得有趣又有效！</p>
            <div class="row mt-5">
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-trophy fa-3x mb-3" style="color: #FFD700;"></i>
                        <h4>成就系統</h4>
                        <p>解鎖徽章，追蹤學習進度</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-eye fa-3x mb-3" style="color: #4ECDC4;"></i>
                        <h4>視覺化執行</h4>
                        <p>看見程式碼如何運作</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-gamepad fa-3x mb-3" style="color: #FF6B6B;"></i>
                        <h4>互動學習</h4>
                        <p>挑戰任務，提升技能</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 用戶狀態面板 -->
        <div class="demo-section">
            <div class="row">
                <div class="col-md-6">
                    <h3><i class="fas fa-user-circle"></i> 學習狀態</h3>
                    <div class="d-flex align-items-center mb-3">
                        <div class="level-display">Level 1</div>
                        <div class="ms-3 flex-grow-1">
                            <div class="exp-bar-container">
                                <div class="exp-bar" style="width: 0%;">
                                    <div class="exp-text">0/150</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress-circle" style="--progress: 0deg;">
                        <div class="progress-text">0%</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h3><i class="fas fa-medal"></i> 成就徽章</h3>
                    <div class="achievements-container">
                        <!-- 徽章將由JavaScript動態生成 -->
                    </div>
                </div>
            </div>
        </div>

        <!-- 程式碼視覺化執行器 -->
        <div class="demo-section">
            <div class="code-visualizer">
                <!-- 視覺化器將由JavaScript動態生成 -->
            </div>
        </div>

        <!-- 挑戰系統 -->
        <div class="demo-section">
            <h3><i class="fas fa-flag-checkered"></i> 程式挑戰</h3>
            <p class="text-muted">完成挑戰獲得經驗值和成就徽章！</p>
            <div class="challenges-container">
                <!-- 挑戰將由JavaScript動態生成 -->
            </div>
        </div>

        <!-- 排行榜 -->
        <div class="demo-section">
            <div class="leaderboard">
                <div class="leaderboard-header">
                    <i class="fas fa-crown"></i> 學習排行榜
                </div>
                <!-- 排行榜項目將由JavaScript動態生成 -->
            </div>
        </div>

        <!-- 統計數據 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">1,234</div>
                <div>程式碼執行次數</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">567</div>
                <div>完成的練習</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">89</div>
                <div>解鎖的成就</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">12</div>
                <div>學習天數</div>
            </div>
        </div>

        <!-- 功能特色說明 -->
        <div class="row">
            <div class="col-md-6">
                <div class="feature-card">
                    <h4><i class="fas fa-star text-warning"></i> 經驗值系統</h4>
                    <ul>
                        <li>執行程式碼獲得經驗值</li>
                        <li>完成課程獲得額外獎勵</li>
                        <li>升級解鎖新功能</li>
                        <li>個人化學習進度追蹤</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-card">
                    <h4><i class="fas fa-eye text-info"></i> 視覺化執行</h4>
                    <ul>
                        <li>逐行程式碼執行動畫</li>
                        <li>變數變化即時顯示</li>
                        <li>執行步驟詳細記錄</li>
                        <li>錯誤位置精確定位</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="feature-card">
                    <h4><i class="fas fa-trophy text-success"></i> 成就系統</h4>
                    <ul>
                        <li>多種成就徽章收集</li>
                        <li>學習里程碑記錄</li>
                        <li>社交分享功能</li>
                        <li>個人成就展示</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-card">
                    <h4><i class="fas fa-users text-primary"></i> 社群功能</h4>
                    <ul>
                        <li>學習排行榜競賽</li>
                        <li>程式碼分享平台</li>
                        <li>同儕學習交流</li>
                        <li>導師指導系統</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 使用說明 -->
        <div class="demo-section">
            <h3><i class="fas fa-question-circle"></i> 如何使用</h3>
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-play-circle fa-3x text-primary"></i>
                    </div>
                    <h5>1. 執行程式碼</h5>
                    <p>在編輯器中輸入Python程式碼，點擊執行按鈕</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-star fa-3x text-warning"></i>
                    </div>
                    <h5>2. 獲得經驗值</h5>
                    <p>每次執行程式碼都會獲得經驗值，累積升級</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-medal fa-3x text-success"></i>
                    </div>
                    <h5>3. 解鎖成就</h5>
                    <p>完成特定任務解鎖成就徽章</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-chart-line fa-3x text-info"></i>
                    </div>
                    <h5>4. 追蹤進度</h5>
                    <p>查看學習統計和排行榜位置</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 遊戲化系統 -->
    <script src="../static/js/gamification.js"></script>
    <script src="../static/js/visualizer.js"></script>
    
    <script>
        // 頁面載入完成後的初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 模擬一些初始數據
            setTimeout(() => {
                // 觸發一些示例事件來展示功能
                if (typeof gamification !== 'undefined') {
                    // 模擬獲得經驗值
                    gamification.gainExp(25, '訪問遊戲化頁面');
                }
            }, 1000);
            
            // 添加示例程式碼到視覺化器
            setTimeout(() => {
                const codeEditor = document.getElementById('codeEditor');
                if (codeEditor && !codeEditor.value.trim()) {
                    codeEditor.value = `# 歡迎使用視覺化執行器！
print("Hello, Python!")
name = "學習者"
age = 20
print(f"我是{name}，今年{age}歲")

# 簡單的迴圈
for i in range(3):
    print(f"這是第{i+1}次迴圈")

# 條件判斷
if age >= 18:
    print("你已經成年了！")
else:
    print("你還未成年")`;
                    
                    // 觸發更新
                    const event = new Event('input', { bubbles: true });
                    codeEditor.dispatchEvent(event);
                }
            }, 2000);
        });
    </script>
</body>
</html> 