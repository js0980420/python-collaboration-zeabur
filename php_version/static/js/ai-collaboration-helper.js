// 🤖 AI協作助手 - 多人協作中的智能支援
class AICollaborationHelper {
    constructor(roomId, userId) {
        this.roomId = roomId;
        this.userId = userId;
        this.lastCodeCheck = '';
        this.stuckTimer = null;
        this.lastActivity = Date.now();
        this.aiCallCount = 0;
        this.maxAICallsPerHour = 50; // 限制AI調用頻率
        
        this.initAIFeatures();
    }
    
    initAIFeatures() {
        // 綁定各種AI觸發事件
        this.bindCodeAnalysis();
        this.bindStuckDetection();
        this.bindCollaborationHints();
        this.bindSmartSuggestions();
    }
    
    // 1. 即時程式碼分析
    bindCodeAnalysis() {
        const editor = document.getElementById('code-editor');
        if (!editor) return;
        
        // 防抖處理，避免頻繁調用
        const debouncedAnalysis = this.debounce(async (code) => {
            if (this.shouldAnalyzeCode(code)) {
                await this.analyzeCodeWithAI(code);
            }
        }, 3000);
        
        editor.addEventListener('input', (event) => {
            this.lastActivity = Date.now();
            const code = editor.value;
            debouncedAnalysis(code);
        });
    }
    
    // 2. 卡住檢測 - 長時間無進展自動提供幫助
    bindStuckDetection() {
        setInterval(() => {
            const timeSinceLastActivity = Date.now() - this.lastActivity;
            const currentCode = document.getElementById('code-editor').value;
            
            // 5分鐘沒有程式碼變化且不為空
            if (timeSinceLastActivity > 300000 && currentCode.trim().length > 20) {
                this.handleStuckSituation(currentCode);
            }
        }, 60000); // 每分鐘檢查一次
    }
    
    // 3. 協作提示 - 團隊成員互助建議
    bindCollaborationHints() {
        // 監聽其他成員的程式碼變更
        if (window.collaborationWS) {
            window.collaborationWS.onCodeChanged = (data) => {
                this.analyzeTeamProgress(data);
            };
        }
    }
    
    // 4. 智能建議 - 基於上下文的提示
    bindSmartSuggestions() {
        const editor = document.getElementById('code-editor');
        if (!editor) return;
        
        // 游標位置變化時提供上下文建議
        editor.addEventListener('click', async (event) => {
            const cursorPosition = editor.selectionStart;
            const code = editor.value;
            const context = this.getCodeContext(code, cursorPosition);
            
            if (context.needsSuggestion) {
                await this.provideSuggestion(context);
            }
        });
    }
    
    // AI程式碼分析
    async analyzeCodeWithAI(code) {
        if (!this.canMakeAICall()) return;
        
        try {
            const response = await fetch('../api/ai_assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'analyze_code',
                    code: code,
                    room_id: this.roomId,
                    user_id: this.userId,
                    context: 'collaboration'
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.handleAIAnalysis(result.data);
            }
        } catch (error) {
            console.error('AI分析失敗:', error);
        }
    }
    
    // 處理卡住情況
    async handleStuckSituation(code) {
        if (!this.canMakeAICall()) return;
        
        try {
            const response = await fetch('../api/ai_assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'help_stuck',
                    code: code,
                    room_id: this.roomId,
                    user_id: this.userId,
                    stuck_duration: Date.now() - this.lastActivity
                })
            });
            
            const result = await response.json();
            if (result.success && result.data.suggestion) {
                this.showStuckHelp(result.data);
                
                // 在聊天中分享AI建議
                if (window.collaborationWS) {
                    window.collaborationWS.sendChatMessage(
                        `🤖 AI助手: ${result.data.suggestion}`
                    );
                }
            }
        } catch (error) {
            console.error('獲取卡住幫助失敗:', error);
        }
    }
    
    // 分析團隊進度
    async analyzeTeamProgress(memberData) {
        // 比較不同成員的程式碼進度
        const currentCode = document.getElementById('code-editor').value;
        const memberCode = memberData.code;
        
        // 如果其他成員的程式碼更先進，提供學習建議
        if (this.isCodeMoreAdvanced(memberCode, currentCode)) {
            const suggestion = await this.getProgressSuggestion(memberCode, currentCode);
            if (suggestion) {
                this.showProgressHint(suggestion, memberData.username);
            }
        }
    }
    
    // 提供上下文建議
    async provideSuggestion(context) {
        if (!this.canMakeAICall()) return;
        
        try {
            const response = await fetch('../api/ai_assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'context_suggestion',
                    code: context.code,
                    cursor_position: context.position,
                    room_id: this.roomId,
                    user_id: this.userId
                })
            });
            
            const result = await response.json();
            if (result.success && result.data.suggestion) {
                this.showContextSuggestion(result.data);
            }
        } catch (error) {
            console.error('獲取上下文建議失敗:', error);
        }
    }
    
    // 處理AI分析結果
    handleAIAnalysis(analysisData) {
        const { errors, suggestions, improvements, score } = analysisData;
        
        // 顯示錯誤提示
        if (errors && errors.length > 0) {
            this.showInlineErrors(errors);
        }
        
        // 顯示改進建議
        if (suggestions && suggestions.length > 0) {
            this.showImprovementSuggestions(suggestions);
        }
        
        // 更新程式碼品質分數
        if (score !== undefined) {
            this.updateCodeQualityScore(score);
        }
    }
    
    // 顯示卡住幫助
    showStuckHelp(helpData) {
        const helpPanel = this.createHelpPanel();
        helpPanel.innerHTML = `
            <div class="ai-help-stuck">
                <h6><i class="fas fa-lightbulb text-warning"></i> AI助手建議</h6>
                <div class="suggestion-content">
                    <p>${helpData.suggestion}</p>
                    ${helpData.code_example ? `
                        <div class="code-example">
                            <small class="text-muted">參考代碼：</small>
                            <pre><code>${helpData.code_example}</code></pre>
                        </div>
                    ` : ''}
                </div>
                <div class="help-actions mt-2">
                    <button class="btn btn-sm btn-primary" onclick="aiHelper.applyAISuggestion('${helpData.suggestion}')">
                        <i class="fas fa-check"></i> 應用建議
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="aiHelper.dismissHelp()">
                        <i class="fas fa-times"></i> 忽略
                    </button>
                </div>
            </div>
        `;
        
        this.showHelpPanel(helpPanel);
    }
    
    // 顯示內聯錯誤
    showInlineErrors(errors) {
        const editor = document.getElementById('code-editor');
        if (!editor) return;
        
        // 清除舊的錯誤標記
        this.clearErrorMarkers();
        
        errors.forEach(error => {
            const errorMarker = document.createElement('div');
            errorMarker.className = 'error-marker';
            errorMarker.style.cssText = `
                position: absolute;
                background-color: rgba(255, 0, 0, 0.2);
                border-left: 3px solid #dc3545;
                z-index: 100;
            `;
            
            // 計算錯誤位置
            const position = this.calculateErrorPosition(error.line, error.column);
            errorMarker.style.left = position.x + 'px';
            errorMarker.style.top = position.y + 'px';
            errorMarker.style.width = '200px';
            errorMarker.style.height = '20px';
            
            // 添加錯誤提示
            errorMarker.title = error.message;
            
            editor.parentElement.appendChild(errorMarker);
        });
    }
    
    // 顯示改進建議
    showImprovementSuggestions(suggestions) {
        const suggestionPanel = document.getElementById('ai-suggestions');
        if (!suggestionPanel) return;
        
        suggestionPanel.innerHTML = `
            <div class="ai-suggestions">
                <h6><i class="fas fa-magic text-info"></i> AI改進建議</h6>
                <ul class="list-unstyled">
                    ${suggestions.map(suggestion => `
                        <li class="suggestion-item mb-2">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-arrow-right text-primary me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="suggestion-text">${suggestion.text}</div>
                                    ${suggestion.example ? `
                                        <div class="suggestion-example mt-1">
                                            <small class="text-muted">範例：</small>
                                            <code class="d-block">${suggestion.example}</code>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
    }
    
    // 輔助方法
    shouldAnalyzeCode(code) {
        // 避免重複分析相同程式碼
        if (code === this.lastCodeCheck) return false;
        
        // 程式碼長度檢查
        if (code.trim().length < 10) return false;
        
        // 更新最後檢查的程式碼
        this.lastCodeCheck = code;
        return true;
    }
    
    canMakeAICall() {
        // 檢查AI調用頻率限制
        const now = Date.now();
        const oneHourAgo = now - 3600000;
        
        // 重置計數器（簡化實現）
        if (this.lastAICallReset < oneHourAgo) {
            this.aiCallCount = 0;
            this.lastAICallReset = now;
        }
        
        if (this.aiCallCount >= this.maxAICallsPerHour) {
            console.warn('AI調用頻率限制');
            return false;
        }
        
        this.aiCallCount++;
        return true;
    }
    
    getCodeContext(code, position) {
        const lines = code.split('\n');
        const currentLineIndex = this.getLineFromPosition(code, position);
        const currentLine = lines[currentLineIndex] || '';
        
        return {
            code: code,
            position: position,
            currentLine: currentLine,
            lineIndex: currentLineIndex,
            needsSuggestion: this.needsContextSuggestion(currentLine)
        };
    }
    
    needsContextSuggestion(line) {
        // 檢查是否需要上下文建議
        const triggers = [
            'def ',      // 函數定義
            'class ',    // 類定義
            'for ',      // 迴圈
            'if ',       // 條件判斷
            'import ',   // 導入模組
            'try:',      // 異常處理
        ];
        
        return triggers.some(trigger => line.trim().startsWith(trigger));
    }
    
    isCodeMoreAdvanced(code1, code2) {
        // 簡單的程式碼複雜度比較
        const complexity1 = this.calculateCodeComplexity(code1);
        const complexity2 = this.calculateCodeComplexity(code2);
        
        return complexity1 > complexity2 + 2; // 顯著更複雜
    }
    
    calculateCodeComplexity(code) {
        const complexityIndicators = [
            /def\s+\w+/g,        // 函數定義
            /class\s+\w+/g,      // 類定義
            /for\s+\w+/g,        // for迴圈
            /while\s+/g,         // while迴圈
            /if\s+/g,            // if條件
            /try:/g,             // 異常處理
            /import\s+\w+/g      // 模組導入
        ];
        
        let complexity = 0;
        complexityIndicators.forEach(pattern => {
            const matches = code.match(pattern);
            if (matches) complexity += matches.length;
        });
        
        return complexity;
    }
    
    createHelpPanel() {
        let panel = document.getElementById('ai-help-panel');
        if (!panel) {
            panel = document.createElement('div');
            panel.id = 'ai-help-panel';
            panel.className = 'ai-help-panel';
            panel.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                width: 350px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                padding: 15px;
                display: none;
            `;
            document.body.appendChild(panel);
        }
        return panel;
    }
    
    showHelpPanel(panel) {
        panel.style.display = 'block';
        
        // 3秒後自動隱藏（除非用戶互動）
        setTimeout(() => {
            if (!panel.matches(':hover')) {
                panel.style.display = 'none';
            }
        }, 10000);
    }
    
    // 防抖函數
    debounce(func, wait) {
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
    
    // 應用AI建議
    applyAISuggestion(suggestion) {
        const editor = document.getElementById('code-editor');
        if (!editor) return;
        
        // 在游標位置插入建議
        const cursorPosition = editor.selectionStart;
        const currentCode = editor.value;
        const newCode = currentCode.slice(0, cursorPosition) + 
                       '\n# AI建議: ' + suggestion + '\n' + 
                       currentCode.slice(cursorPosition);
        
        editor.value = newCode;
        
        // 通知其他協作者
        if (window.collaborationWS) {
            window.collaborationWS.sendCodeChange(newCode, {
                type: 'ai_suggestion_applied',
                suggestion: suggestion
            });
        }
        
        this.dismissHelp();
    }
    
    // 關閉幫助面板
    dismissHelp() {
        const panel = document.getElementById('ai-help-panel');
        if (panel) {
            panel.style.display = 'none';
        }
    }
}

// 全域AI助手實例
let aiHelper = null;

// 初始化AI協作助手
function initAICollaborationHelper(roomId, userId) {
    aiHelper = new AICollaborationHelper(roomId, userId);
    console.log('AI協作助手已啟動');
}

// 手動觸發AI幫助
function requestAIHelp() {
    const editor = document.getElementById('code-editor');
    if (editor && aiHelper) {
        const code = editor.value;
        aiHelper.handleStuckSituation(code);
    }
}

// 導出給全域使用
window.AICollaborationHelper = AICollaborationHelper;
window.initAICollaborationHelper = initAICollaborationHelper;
window.requestAIHelp = requestAIHelp; 