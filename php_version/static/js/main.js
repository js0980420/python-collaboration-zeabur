// Python教學網站 - 主要JavaScript功能

// 工具提示初始化
document.addEventListener('DOMContentLoaded', function() {
    // 初始化所有工具提示
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 初始化Prism語法高亮
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }
    
    // 返回頂部按鈕功能
    initBackToTop();
    
    // 導航欄活動狀態
    updateNavbarActive();
});

// 平滑滾動效果
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// 返回頂部功能
function initBackToTop() {
    const backToTopButton = document.getElementById('backToTop');
    if (!backToTopButton) return;
    
    // 監聽滾動事件
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.style.display = 'block';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
    
    // 點擊返回頂部
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// 更新導航欄活動狀態
function updateNavbarActive() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        const href = link.getAttribute('href');
        
        if (href === currentPath || 
            (currentPath === '/' && href === '/') ||
            (currentPath.includes(href) && href !== '/')) {
            link.classList.add('active');
        }
    });
}

// 載入動畫效果
function showLoading(element) {
    if (element) {
        element.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>';
    }
}

function hideLoading() {
    const loadingElements = document.querySelectorAll('.loading');
    loadingElements.forEach(el => {
        el.style.display = 'none';
    });
}

// 錯誤處理
function showError(message, container = null) {
    const errorHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    if (container) {
        container.innerHTML = errorHtml;
    } else {
        // 在頁面頂部顯示錯誤
        const errorContainer = document.createElement('div');
        errorContainer.innerHTML = errorHtml;
        document.body.insertBefore(errorContainer, document.body.firstChild);
    }
}

// 成功提示
function showSuccess(message, container = null) {
    const successHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    if (container) {
        container.innerHTML = successHtml;
    } else {
        const successContainer = document.createElement('div');
        successContainer.innerHTML = successHtml;
        document.body.insertBefore(successContainer, document.body.firstChild);
    }
}

// AI聊天功能
class AIChat {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.messagesContainer = document.querySelector('#chatMessages');
        this.messageInput = document.querySelector('#messageInput');
        this.sendButton = document.querySelector('#sendMessage');
        this.userId = this.generateUserId();
        this.sessionId = null;
        
        this.init();
    }
    
    init() {
        if (!this.container) return;
        
        // 綁定發送按鈕事件
        if (this.sendButton) {
            this.sendButton.addEventListener('click', () => this.sendMessage());
        }
        
        // 綁定回車鍵發送
        if (this.messageInput) {
            this.messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
    }
    
    generateUserId() {
        let userId = localStorage.getItem('python_learning_user_id');
        if (!userId) {
            userId = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('python_learning_user_id', userId);
        }
        return userId;
    }
    
    async sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;
        
        // 顯示用戶消息
        this.appendMessage('user', message);
        
        // 清空輸入框並禁用
        this.messageInput.value = '';
        this.messageInput.disabled = true;
        this.sendButton.disabled = true;
        
        // 顯示載入中
        this.appendMessage('assistant', '<i class="fas fa-spinner fa-spin"></i> AI思考中...', 'loading');
        
        try {
            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    user_id: this.userId,
                    session_id: this.sessionId
                })
            });
            
            const data = await response.json();
            
            // 移除載入消息
            const loadingMsg = this.messagesContainer.querySelector('.loading');
            if (loadingMsg) {
                loadingMsg.remove();
            }
            
            if (data.success) {
                this.sessionId = data.session_id;
                this.appendMessage('assistant', data.response);
            } else {
                this.appendMessage('assistant', '抱歉，發生了錯誤：' + data.error, 'error');
            }
            
        } catch (error) {
            // 移除載入消息
            const loadingMsg = this.messagesContainer.querySelector('.loading');
            if (loadingMsg) {
                loadingMsg.remove();
            }
            
            this.appendMessage('assistant', '連接失敗，請檢查網絡連接後重試。', 'error');
        }
        
        // 恢復輸入框
        this.messageInput.disabled = false;
        this.sendButton.disabled = false;
        this.messageInput.focus();
    }
    
    appendMessage(role, content, className = '') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role} ${className}`;
        
        const icon = role === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
        const name = role === 'user' ? '你' : 'AI助教';
        
        messageDiv.innerHTML = `
            <div class="message-header">
                ${icon}
                <strong>${name}</strong>
            </div>
            <div class="message-content">${content}</div>
        `;
        
        this.messagesContainer.appendChild(messageDiv);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        
        // 如果是AI回應，啟用語法高亮
        if (role === 'assistant' && !className) {
            setTimeout(() => {
                if (typeof Prism !== 'undefined') {
                    Prism.highlightAllUnder(messageDiv);
                }
            }, 100);
        }
    }
}

// 程式碼檢查功能
class CodeChecker {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.codeTextarea = document.querySelector('#codeInput');
        this.checkButton = document.querySelector('#checkCode');
        this.resultContainer = document.querySelector('#codeResult');
        this.userId = this.getUserId();
        
        this.init();
    }
    
    init() {
        if (!this.container) return;
        
        if (this.checkButton) {
            this.checkButton.addEventListener('click', () => this.checkCode());
        }
    }
    
    getUserId() {
        let userId = localStorage.getItem('python_learning_user_id');
        if (!userId) {
            userId = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('python_learning_user_id', userId);
        }
        return userId;
    }
    
    async checkCode() {
        const code = this.codeTextarea.value.trim();
        if (!code) {
            showError('請輸入Python程式碼', this.resultContainer);
            return;
        }
        
        // 顯示載入狀態
        this.checkButton.disabled = true;
        this.checkButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 檢查中...';
        
        try {
            const response = await fetch('/api/check-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    code: code,
                    user_id: this.userId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayResult(data);
            } else {
                showError('檢查失敗：' + data.error, this.resultContainer);
            }
            
        } catch (error) {
            showError('網絡錯誤，請稍後重試', this.resultContainer);
        }
        
        // 恢復按鈕狀態
        this.checkButton.disabled = false;
        this.checkButton.innerHTML = '<i class="fas fa-check"></i> 檢查程式碼';
    }
    
    displayResult(data) {
        let resultHtml = '<div class="code-check-result">';
        
        // 顯示分析結果
        if (data.analysis) {
            resultHtml += '<div class="analysis-section">';
            resultHtml += '<h5><i class="fas fa-chart-bar"></i> 程式碼分析</h5>';
            resultHtml += `<p><strong>行數：</strong> ${data.analysis.line_count}</p>`;
            resultHtml += `<p><strong>包含函數：</strong> ${data.analysis.has_functions ? '是' : '否'}</p>`;
            resultHtml += `<p><strong>包含迴圈：</strong> ${data.analysis.has_loops ? '是' : '否'}</p>`;
            resultHtml += `<p><strong>包含條件判斷：</strong> ${data.analysis.has_conditionals ? '是' : '否'}</p>`;
            resultHtml += '</div>';
        }
        
        // 顯示AI反饋
        if (data.ai_feedback) {
            resultHtml += '<div class="ai-feedback-section">';
            resultHtml += '<h5><i class="fas fa-robot"></i> AI反饋</h5>';
            resultHtml += `<div class="ai-feedback">${data.ai_feedback.replace(/\n/g, '<br>')}</div>`;
            resultHtml += '</div>';
        }
        
        // 顯示建議
        if (data.suggestions && data.suggestions.length > 0) {
            resultHtml += '<div class="suggestions-section">';
            resultHtml += '<h5><i class="fas fa-lightbulb"></i> 改進建議</h5>';
            resultHtml += '<ul>';
            data.suggestions.forEach(suggestion => {
                resultHtml += `<li>${suggestion}</li>`;
            });
            resultHtml += '</ul>';
            resultHtml += '</div>';
        }
        
        resultHtml += '</div>';
        
        this.resultContainer.innerHTML = resultHtml;
    }
}

// 初始化功能
document.addEventListener('DOMContentLoaded', function() {
    // 初始化AI聊天（如果在AI助教頁面）
    if (document.getElementById('chatContainer')) {
        new AIChat('chatContainer');
    }
    
    // 初始化程式碼檢查（如果在練習頁面）
    if (document.getElementById('codeChecker')) {
        new CodeChecker('codeChecker');
    }
    
    // 常見問題快捷按鈕
    const quickButtons = document.querySelectorAll('.quick-question');
    quickButtons.forEach(button => {
        button.addEventListener('click', function() {
            const question = this.dataset.question;
            const messageInput = document.querySelector('#messageInput');
            if (messageInput) {
                messageInput.value = question;
                messageInput.focus();
            }
        });
    });
    
    // 程式碼範例按鈕
    const exampleButtons = document.querySelectorAll('.load-example');
    exampleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const example = this.dataset.example;
            const codeInput = document.querySelector('#codeInput');
            if (codeInput && example) {
                codeInput.value = decodeURIComponent(example);
            }
        });
    });
}); 