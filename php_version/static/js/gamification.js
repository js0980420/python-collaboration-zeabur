// 🎮 遊戲化系統 JavaScript

class GamificationSystem {
    constructor() {
        this.userLevel = 1;
        this.userExp = 0;
        this.expToNextLevel = 100;
        this.achievements = [];
        this.init();
    }

    init() {
        this.loadUserData();
        this.setupEventListeners();
        this.updateUI();
    }

    // 載入用戶數據
    loadUserData() {
        const userData = localStorage.getItem('pythonLearningData');
        if (userData) {
            const data = JSON.parse(userData);
            this.userLevel = data.level || 1;
            this.userExp = data.exp || 0;
            this.achievements = data.achievements || [];
            this.expToNextLevel = this.calculateExpToNextLevel();
        }
    }

    // 保存用戶數據
    saveUserData() {
        const userData = {
            level: this.userLevel,
            exp: this.userExp,
            achievements: this.achievements,
            lastSaved: new Date().toISOString()
        };
        localStorage.setItem('pythonLearningData', JSON.stringify(userData));
    }

    // 計算升級所需經驗值
    calculateExpToNextLevel() {
        return this.userLevel * 100 + 50;
    }

    // 獲得經驗值
    gainExp(amount, reason = '') {
        this.userExp += amount;
        this.showExpGain(amount, reason);
        
        // 檢查是否升級
        while (this.userExp >= this.expToNextLevel) {
            this.levelUp();
        }
        
        this.updateUI();
        this.saveUserData();
    }

    // 升級
    levelUp() {
        this.userExp -= this.expToNextLevel;
        this.userLevel++;
        this.expToNextLevel = this.calculateExpToNextLevel();
        this.showLevelUpNotification();
        this.checkLevelAchievements();
    }

    // 顯示經驗值獲得動畫
    showExpGain(amount, reason) {
        const notification = document.createElement('div');
        notification.className = 'exp-notification';
        notification.innerHTML = `
            <div class="exp-gain">
                <i class="fas fa-star"></i>
                +${amount} EXP
                ${reason ? `<br><small>${reason}</small>` : ''}
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // 動畫效果
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-50px)';
        }, 100);
        
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 2000);
    }

    // 顯示升級通知
    showLevelUpNotification() {
        const notification = document.createElement('div');
        notification.className = 'achievement-notification show';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-trophy notification-icon"></i>
                <div>
                    <h4>🎉 恭喜升級！</h4>
                    <p>你現在是 Level ${this.userLevel}！</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // 播放升級音效（如果有）
        this.playSound('levelup');
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 500);
        }, 3000);
    }

    // 解鎖成就
    unlockAchievement(achievementId) {
        if (this.achievements.includes(achievementId)) {
            return; // 已經解鎖
        }
        
        this.achievements.push(achievementId);
        const achievement = this.getAchievementData(achievementId);
        
        if (achievement) {
            this.showAchievementNotification(achievement);
            this.gainExp(achievement.exp, `解鎖成就: ${achievement.name}`);
        }
        
        this.saveUserData();
    }

    // 獲取成就數據
    getAchievementData(achievementId) {
        const achievements = {
            'first_code': {
                id: 'first_code',
                name: '第一行程式碼',
                description: '執行你的第一個Python程式',
                icon: 'fas fa-code',
                exp: 50,
                rarity: 'bronze'
            },
            'hello_world': {
                id: 'hello_world',
                name: 'Hello World',
                description: '成功輸出Hello World',
                icon: 'fas fa-globe',
                exp: 25,
                rarity: 'bronze'
            },
            'loop_master': {
                id: 'loop_master',
                name: '迴圈大師',
                description: '完成10個迴圈練習',
                icon: 'fas fa-sync',
                exp: 100,
                rarity: 'silver'
            },
            'function_expert': {
                id: 'function_expert',
                name: '函數專家',
                description: '創建並使用5個自定義函數',
                icon: 'fas fa-cogs',
                exp: 150,
                rarity: 'gold'
            },
            'level_5': {
                id: 'level_5',
                name: '初學者',
                description: '達到Level 5',
                icon: 'fas fa-seedling',
                exp: 100,
                rarity: 'bronze'
            },
            'level_10': {
                id: 'level_10',
                name: '進階學習者',
                description: '達到Level 10',
                icon: 'fas fa-tree',
                exp: 200,
                rarity: 'silver'
            },
            'streak_7': {
                id: 'streak_7',
                name: '七日連續',
                description: '連續7天學習Python',
                icon: 'fas fa-fire',
                exp: 150,
                rarity: 'gold'
            }
        };
        
        return achievements[achievementId];
    }

    // 顯示成就通知
    showAchievementNotification(achievement) {
        const notification = document.createElement('div');
        notification.className = 'achievement-notification show';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="${achievement.icon} notification-icon"></i>
                <div>
                    <h4>🏆 成就解鎖！</h4>
                    <p><strong>${achievement.name}</strong></p>
                    <small>${achievement.description}</small>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // 播放成就音效
        this.playSound('achievement');
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 500);
        }, 4000);
    }

    // 檢查等級相關成就
    checkLevelAchievements() {
        if (this.userLevel >= 5) this.unlockAchievement('level_5');
        if (this.userLevel >= 10) this.unlockAchievement('level_10');
    }

    // 更新UI顯示
    updateUI() {
        this.updateLevelDisplay();
        this.updateExpBar();
        this.updateAchievementDisplay();
    }

    // 更新等級顯示
    updateLevelDisplay() {
        const levelDisplay = document.querySelector('.level-display');
        if (levelDisplay) {
            levelDisplay.textContent = `Level ${this.userLevel}`;
        }
    }

    // 更新經驗值進度條
    updateExpBar() {
        const expBar = document.querySelector('.exp-bar');
        const expText = document.querySelector('.exp-text');
        
        if (expBar && expText) {
            const percentage = (this.userExp / this.expToNextLevel) * 100;
            expBar.style.width = `${percentage}%`;
            expText.textContent = `${this.userExp}/${this.expToNextLevel}`;
        }
    }

    // 更新成就顯示
    updateAchievementDisplay() {
        const achievementContainer = document.querySelector('.achievements-container');
        if (!achievementContainer) return;
        
        const allAchievements = [
            'first_code', 'hello_world', 'loop_master', 
            'function_expert', 'level_5', 'level_10', 'streak_7'
        ];
        
        achievementContainer.innerHTML = '';
        
        allAchievements.forEach(achievementId => {
            const achievement = this.getAchievementData(achievementId);
            const isUnlocked = this.achievements.includes(achievementId);
            
            const badgeElement = document.createElement('div');
            badgeElement.className = `achievement-badge badge-${achievement.rarity} ${isUnlocked ? '' : 'badge-locked'}`;
            badgeElement.innerHTML = `<i class="${achievement.icon} badge-icon"></i>`;
            badgeElement.title = `${achievement.name}: ${achievement.description}`;
            
            if (isUnlocked) {
                badgeElement.addEventListener('click', () => {
                    this.showAchievementDetails(achievement);
                });
            }
            
            achievementContainer.appendChild(badgeElement);
        });
    }

    // 顯示成就詳情
    showAchievementDetails(achievement) {
        alert(`🏆 ${achievement.name}\n\n${achievement.description}\n\n獲得經驗值: ${achievement.exp}`);
    }

    // 播放音效
    playSound(type) {
        // 這裡可以添加音效播放邏輯
        console.log(`Playing sound: ${type}`);
    }

    // 設置事件監聽器
    setupEventListeners() {
        // 程式碼執行事件
        document.addEventListener('codeExecuted', (event) => {
            this.gainExp(10, '執行程式碼');
            
            // 檢查特定成就
            if (!this.achievements.includes('first_code')) {
                this.unlockAchievement('first_code');
            }
            
            if (event.detail && event.detail.output && event.detail.output.includes('Hello')) {
                this.unlockAchievement('hello_world');
            }
        });
        
        // 課程完成事件
        document.addEventListener('lessonCompleted', (event) => {
            this.gainExp(50, `完成課程: ${event.detail.lessonName}`);
        });
        
        // 練習完成事件
        document.addEventListener('exerciseCompleted', (event) => {
            this.gainExp(25, '完成練習');
        });
    }
}

// 排行榜系統
class LeaderboardSystem {
    constructor() {
        this.leaderboardData = [];
        this.init();
    }

    init() {
        this.loadLeaderboard();
        this.updateLeaderboardDisplay();
    }

    // 載入排行榜數據
    loadLeaderboard() {
        // 模擬排行榜數據
        this.leaderboardData = [
            { name: '小明', level: 15, exp: 2350, avatar: '👨‍💻' },
            { name: '小華', level: 12, exp: 1890, avatar: '👩‍💻' },
            { name: '小李', level: 10, exp: 1456, avatar: '🧑‍💻' },
            { name: '小王', level: 8, exp: 1123, avatar: '👨‍🎓' },
            { name: '小陳', level: 7, exp: 987, avatar: '👩‍🎓' }
        ];
        
        // 添加當前用戶
        const currentUser = {
            name: '你',
            level: gamification.userLevel,
            exp: gamification.userExp,
            avatar: '🌟',
            isCurrentUser: true
        };
        
        this.leaderboardData.push(currentUser);
        this.leaderboardData.sort((a, b) => b.exp - a.exp);
    }

    // 更新排行榜顯示
    updateLeaderboardDisplay() {
        const leaderboard = document.querySelector('.leaderboard');
        if (!leaderboard) return;
        
        const leaderboardItems = leaderboard.querySelector('.leaderboard-items') || 
                                document.createElement('div');
        leaderboardItems.className = 'leaderboard-items';
        leaderboardItems.innerHTML = '';
        
        this.leaderboardData.forEach((user, index) => {
            const rank = index + 1;
            const item = document.createElement('div');
            item.className = `leaderboard-item ${user.isCurrentUser ? 'current-user' : ''}`;
            
            let rankClass = 'rank-other';
            if (rank === 1) rankClass = 'rank-1';
            else if (rank === 2) rankClass = 'rank-2';
            else if (rank === 3) rankClass = 'rank-3';
            
            item.innerHTML = `
                <div class="rank-number ${rankClass}">${rank}</div>
                <div class="user-avatar">${user.avatar}</div>
                <div class="user-info">
                    <div class="user-name">${user.name}</div>
                    <div class="user-stats">Level ${user.level} • ${user.exp} EXP</div>
                </div>
            `;
            
            leaderboardItems.appendChild(item);
        });
        
        if (!leaderboard.contains(leaderboardItems)) {
            leaderboard.appendChild(leaderboardItems);
        }
    }
}

// 挑戰系統
class ChallengeSystem {
    constructor() {
        this.challenges = [];
        this.completedChallenges = [];
        this.init();
    }

    init() {
        this.loadChallenges();
        this.loadCompletedChallenges();
        this.updateChallengeDisplay();
    }

    // 載入挑戰數據
    loadChallenges() {
        this.challenges = [
            {
                id: 'basic_print',
                title: '基礎輸出',
                description: '使用print()函數輸出你的名字',
                difficulty: 'easy',
                exp: 20,
                code: 'print("你的名字")',
                solution: 'print("小明")'
            },
            {
                id: 'simple_math',
                title: '簡單計算',
                description: '計算兩個數字的和並輸出結果',
                difficulty: 'easy',
                exp: 25,
                code: 'a = 5\nb = 3\n# 在這裡計算並輸出a+b',
                solution: 'a = 5\nb = 3\nprint(a + b)'
            },
            {
                id: 'for_loop',
                title: '迴圈練習',
                description: '使用for迴圈輸出1到5的數字',
                difficulty: 'medium',
                exp: 40,
                code: '# 使用for迴圈輸出1到5',
                solution: 'for i in range(1, 6):\n    print(i)'
            },
            {
                id: 'function_def',
                title: '函數定義',
                description: '定義一個函數來計算圓的面積',
                difficulty: 'hard',
                exp: 60,
                code: '# 定義計算圓面積的函數\n# 使用公式: π * r²',
                solution: 'import math\n\ndef circle_area(radius):\n    return math.pi * radius ** 2\n\nprint(circle_area(5))'
            }
        ];
    }

    // 載入已完成的挑戰
    loadCompletedChallenges() {
        const completed = localStorage.getItem('completedChallenges');
        this.completedChallenges = completed ? JSON.parse(completed) : [];
    }

    // 保存已完成的挑戰
    saveCompletedChallenges() {
        localStorage.setItem('completedChallenges', JSON.stringify(this.completedChallenges));
    }

    // 完成挑戰
    completeChallenge(challengeId) {
        if (!this.completedChallenges.includes(challengeId)) {
            this.completedChallenges.push(challengeId);
            const challenge = this.challenges.find(c => c.id === challengeId);
            
            if (challenge) {
                gamification.gainExp(challenge.exp, `完成挑戰: ${challenge.title}`);
                this.saveCompletedChallenges();
                this.updateChallengeDisplay();
            }
        }
    }

    // 更新挑戰顯示
    updateChallengeDisplay() {
        const challengeContainer = document.querySelector('.challenges-container');
        if (!challengeContainer) return;
        
        challengeContainer.innerHTML = '';
        
        this.challenges.forEach(challenge => {
            const isCompleted = this.completedChallenges.includes(challenge.id);
            
            const challengeCard = document.createElement('div');
            challengeCard.className = `challenge-card ${isCompleted ? 'completed' : ''}`;
            challengeCard.innerHTML = `
                <div class="challenge-difficulty difficulty-${challenge.difficulty}">
                    ${challenge.difficulty.toUpperCase()}
                </div>
                <h4>${challenge.title}</h4>
                <p>${challenge.description}</p>
                <div class="challenge-reward">
                    <i class="fas fa-star"></i> ${challenge.exp} EXP
                </div>
                <button class="btn btn-primary challenge-btn" 
                        onclick="startChallenge('${challenge.id}')"
                        ${isCompleted ? 'disabled' : ''}>
                    ${isCompleted ? '✅ 已完成' : '開始挑戰'}
                </button>
            `;
            
            challengeContainer.appendChild(challengeCard);
        });
    }

    // 獲取挑戰數據
    getChallenge(challengeId) {
        return this.challenges.find(c => c.id === challengeId);
    }
}

// 全局函數
function startChallenge(challengeId) {
    const challenge = challengeSystem.getChallenge(challengeId);
    if (challenge) {
        // 在程式碼編輯器中載入挑戰代碼
        const codeEditor = document.querySelector('.code-editor');
        if (codeEditor) {
            codeEditor.value = challenge.code;
        }
        
        // 顯示挑戰說明
        alert(`挑戰: ${challenge.title}\n\n${challenge.description}\n\n完成後可獲得 ${challenge.exp} 經驗值！`);
    }
}

// 初始化系統
let gamification, leaderboard, challengeSystem;

document.addEventListener('DOMContentLoaded', function() {
    gamification = new GamificationSystem();
    leaderboard = new LeaderboardSystem();
    challengeSystem = new ChallengeSystem();
    
    // 添加CSS樣式
    const style = document.createElement('style');
    style.textContent = `
        .exp-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1000;
            opacity: 1;
            transform: translateY(0);
            transition: all 0.5s ease;
        }
        
        .current-user {
            background: #e8f5e8 !important;
            border-left: 4px solid #27ae60 !important;
        }
        
        .challenge-card.completed {
            opacity: 0.7;
            background: #f8f9fa;
        }
        
        .challenge-reward {
            color: #f39c12;
            font-weight: bold;
            margin: 10px 0;
        }
    `;
    document.head.appendChild(style);
}); 