// 主要JavaScript功能
document.addEventListener('DOMContentLoaded', function() {
    // 初始化工具提示
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // 平滑滾動
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
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

    // 導航欄活動狀態
    const currentLocation = location.pathname;
    const menuItems = document.querySelectorAll('.navbar-nav .nav-link');
    
    menuItems.forEach(item => {
        if (item.getAttribute('href') === currentLocation) {
            item.classList.add('active');
        }
    });

    // 程式碼高亮
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }

    // 載入動畫
    const cards = document.querySelectorAll('.card-custom');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // 返回頂部按鈕
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="fas fa-chevron-up"></i>';
    backToTop.className = 'btn btn-primary position-fixed';
    backToTop.style.cssText = `
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    `;
    document.body.appendChild(backToTop);

    // 顯示/隱藏返回頂部按鈕
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });

    // 返回頂部功能
    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // 表單驗證
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});

// 通用工具函數
const Utils = {
    // 顯示通知
    showNotification: function(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>${message}
            <button type="button" class="btn-close" aria-label="Close"></button>
        `;

        document.body.appendChild(notification);

        // 關閉按鈕事件
        notification.querySelector('.btn-close').addEventListener('click', () => {
            notification.remove();
        });

        // 自動關閉
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    },

    // 複製到剪貼板
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showNotification('已複製到剪貼板', 'success');
            });
        } else {
            // 備用方法
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showNotification('已複製到剪貼板', 'success');
        }
    },

    // 格式化日期
    formatDate: function(date) {
        return new Intl.DateTimeFormat('zh-TW', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    },

    // 防抖函數
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// 為程式碼區塊添加複製按鈕
document.addEventListener('DOMContentLoaded', function() {
    const codeBlocks = document.querySelectorAll('pre code');
    
    codeBlocks.forEach(block => {
        const wrapper = document.createElement('div');
        wrapper.className = 'position-relative';
        
        const copyBtn = document.createElement('button');
        copyBtn.className = 'btn btn-sm btn-outline-secondary position-absolute';
        copyBtn.style.cssText = 'top: 10px; right: 10px; z-index: 10;';
        copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
        copyBtn.title = '複製程式碼';
        
        copyBtn.addEventListener('click', () => {
            Utils.copyToClipboard(block.textContent);
        });
        
        block.parentNode.parentNode.insertBefore(wrapper, block.parentNode);
        wrapper.appendChild(block.parentNode);
        wrapper.appendChild(copyBtn);
    });
});

// 主題切換（如果需要）
const ThemeToggle = {
    init: function() {
        this.createToggleButton();
        this.loadTheme();
    },

    createToggleButton: function() {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-outline-secondary position-fixed';
        toggleBtn.style.cssText = `
            top: 80px;
            right: 20px;
            z-index: 1000;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: none;
        `;
        toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        toggleBtn.title = '切換深色模式';
        
        toggleBtn.addEventListener('click', () => this.toggleTheme());
        document.body.appendChild(toggleBtn);
    },

    toggleTheme: function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // 更新圖標
        const icon = document.querySelector('.theme-toggle i');
        if (icon) {
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    },

    loadTheme: function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
};

// 性能監控
const Performance = {
    init: function() {
        this.measureLoadTime();
        this.trackUserInteractions();
    },

    measureLoadTime: function() {
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            console.log(`頁面載入時間: ${loadTime.toFixed(2)}ms`);
        });
    },

    trackUserInteractions: function() {
        // 追蹤按鈕點擊
        document.addEventListener('click', (e) => {
            if (e.target.matches('button, .btn, a[href]')) {
                console.log('用戶互動:', e.target.textContent.trim());
            }
        });
    }
};

// 初始化所有功能
document.addEventListener('DOMContentLoaded', function() {
    Performance.init();
    
    // 根據需要啟用主題切換
    // ThemeToggle.init();
});

// 錯誤處理
window.addEventListener('error', function(e) {
    console.error('JavaScript錯誤:', e.error);
    Utils.showNotification('發生了一個錯誤，請重新整理頁面', 'danger');
});

// 網路狀態監控
window.addEventListener('online', () => {
    Utils.showNotification('網路連線已恢復', 'success');
});

window.addEventListener('offline', () => {
    Utils.showNotification('網路連線中斷', 'warning');
}); 