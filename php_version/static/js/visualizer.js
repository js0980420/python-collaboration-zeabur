// 🎯 程式碼視覺化執行器

class CodeVisualizer {
    constructor() {
        this.code = '';
        this.lines = [];
        this.currentLine = 0;
        this.variables = {};
        this.executionSteps = [];
        this.isRunning = false;
        this.isPaused = false;
        this.speed = 1000; // 執行速度（毫秒）
        this.output = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.createVisualizerUI();
    }

    // 創建視覺化界面
    createVisualizerUI() {
        const container = document.querySelector('.code-visualizer');
        if (!container) return;

        container.innerHTML = `
            <div class="visualizer-header">
                <h3>🎯 程式碼視覺化執行器</h3>
                <div class="control-buttons">
                    <button class="control-btn btn-run" onclick="visualizer.runCode()">
                        <i class="fas fa-play"></i> 執行
                    </button>
                    <button class="control-btn btn-step" onclick="visualizer.stepCode()">
                        <i class="fas fa-step-forward"></i> 單步
                    </button>
                    <button class="control-btn btn-pause" onclick="visualizer.pauseCode()">
                        <i class="fas fa-pause"></i> 暫停
                    </button>
                    <button class="control-btn btn-reset" onclick="visualizer.resetCode()">
                        <i class="fas fa-stop"></i> 重置
                    </button>
                </div>
            </div>
            
            <div class="speed-control">
                <label>執行速度:</label>
                <input type="range" class="speed-slider" min="100" max="3000" value="1000" 
                       onchange="visualizer.setSpeed(this.value)">
                <span class="speed-label">1.0x</span>
            </div>
            
            <div class="code-execution-area">
                <div class="code-input-section">
                    <div class="line-numbers" id="lineNumbers"></div>
                    <textarea class="code-editor" id="codeEditor" 
                              placeholder="在這裡輸入Python程式碼...">print("Hello, World!")
x = 10
y = 20
result = x + y
print(f"結果是: {result}")

for i in range(3):
    print(f"迴圈 {i+1}")

if result > 25:
    print("結果大於25")
else:
    print("結果小於等於25")</textarea>
                </div>
                
                <div class="visualization-panels">
                    <div class="variable-monitor">
                        <h4><i class="fas fa-database"></i> 變數監視器</h4>
                        <div id="variableList"></div>
                    </div>
                    
                    <div class="execution-steps">
                        <h4><i class="fas fa-list-ol"></i> 執行步驟</h4>
                        <div id="stepsList"></div>
                    </div>
                    
                    <div class="console-output" id="consoleOutput">
                        <div class="console-header">
                            <i class="fas fa-terminal"></i> 輸出結果
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flowchart-container" id="flowchartContainer">
                <h4><i class="fas fa-project-diagram"></i> 程式流程圖</h4>
                <div id="flowchartContent"></div>
            </div>
        `;

        this.updateLineNumbers();
    }

    // 設置事件監聽器
    setupEventListeners() {
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('code-editor')) {
                this.updateLineNumbers();
                this.code = e.target.value;
                this.parseCode();
            }
        });
    }

    // 更新行號
    updateLineNumbers() {
        const codeEditor = document.getElementById('codeEditor');
        const lineNumbers = document.getElementById('lineNumbers');
        
        if (!codeEditor || !lineNumbers) return;
        
        const lines = codeEditor.value.split('\n');
        const lineNumbersHTML = lines.map((line, index) => 
            `<div class="line-number" data-line="${index + 1}">${index + 1}</div>`
        ).join('');
        
        lineNumbers.innerHTML = lineNumbersHTML;
    }

    // 解析程式碼
    parseCode() {
        this.lines = this.code.split('\n').filter(line => line.trim() !== '');
        this.generateFlowchart();
    }

    // 執行程式碼
    async runCode() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.isPaused = false;
        this.resetExecution();
        
        const codeEditor = document.getElementById('codeEditor');
        this.code = codeEditor.value;
        this.parseCode();
        
        // 觸發程式碼執行事件（用於遊戲化系統）
        document.dispatchEvent(new CustomEvent('codeExecuted', {
            detail: { code: this.code, output: this.output.join('\n') }
        }));
        
        try {
            await this.executeStepByStep();
        } catch (error) {
            this.showError(error.message);
        }
        
        this.isRunning = false;
    }

    // 單步執行
    async stepCode() {
        if (this.currentLine >= this.lines.length) {
            this.resetExecution();
            return;
        }
        
        await this.executeCurrentLine();
        this.currentLine++;
        this.updateUI();
    }

    // 暫停執行
    pauseCode() {
        this.isPaused = !this.isPaused;
        const pauseBtn = document.querySelector('.btn-pause');
        if (pauseBtn) {
            pauseBtn.innerHTML = this.isPaused ? 
                '<i class="fas fa-play"></i> 繼續' : 
                '<i class="fas fa-pause"></i> 暫停';
        }
    }

    // 重置執行
    resetCode() {
        this.isRunning = false;
        this.isPaused = false;
        this.resetExecution();
        this.updateUI();
    }

    // 重置執行狀態
    resetExecution() {
        this.currentLine = 0;
        this.variables = {};
        this.executionSteps = [];
        this.output = [];
        this.clearHighlights();
    }

    // 逐步執行
    async executeStepByStep() {
        for (let i = 0; i < this.lines.length; i++) {
            if (!this.isRunning) break;
            
            while (this.isPaused) {
                await this.sleep(100);
            }
            
            this.currentLine = i;
            await this.executeCurrentLine();
            this.updateUI();
            
            await this.sleep(this.speed);
        }
    }

    // 執行當前行
    async executeCurrentLine() {
        const line = this.lines[this.currentLine];
        if (!line || line.trim() === '') return;
        
        this.highlightCurrentLine();
        this.addExecutionStep(line);
        
        try {
            await this.interpretLine(line);
        } catch (error) {
            this.showError(`第 ${this.currentLine + 1} 行錯誤: ${error.message}`);
        }
    }

    // 解釋執行程式碼行
    async interpretLine(line) {
        const trimmedLine = line.trim();
        
        // 處理註解
        if (trimmedLine.startsWith('#')) {
            return;
        }
        
        // 處理print語句
        if (trimmedLine.startsWith('print(')) {
            const content = this.extractPrintContent(trimmedLine);
            this.addOutput(content);
            return;
        }
        
        // 處理變數賦值
        if (trimmedLine.includes('=') && !trimmedLine.includes('==')) {
            this.handleAssignment(trimmedLine);
            return;
        }
        
        // 處理for迴圈
        if (trimmedLine.startsWith('for ')) {
            this.handleForLoop(trimmedLine);
            return;
        }
        
        // 處理if語句
        if (trimmedLine.startsWith('if ')) {
            this.handleIfStatement(trimmedLine);
            return;
        }
        
        // 處理else語句
        if (trimmedLine.startsWith('else:')) {
            this.handleElseStatement();
            return;
        }
    }

    // 提取print內容
    extractPrintContent(line) {
        const match = line.match(/print\((.*)\)/);
        if (!match) return '';
        
        let content = match[1].trim();
        
        // 處理字符串
        if (content.startsWith('"') && content.endsWith('"')) {
            return content.slice(1, -1);
        }
        
        if (content.startsWith("'") && content.endsWith("'")) {
            return content.slice(1, -1);
        }
        
        // 處理f-string
        if (content.startsWith('f"') || content.startsWith("f'")) {
            return this.evaluateFString(content);
        }
        
        // 處理變數
        if (this.variables[content]) {
            return this.variables[content].toString();
        }
        
        // 處理表達式
        try {
            return this.evaluateExpression(content).toString();
        } catch {
            return content;
        }
    }

    // 處理變數賦值
    handleAssignment(line) {
        const parts = line.split('=');
        if (parts.length !== 2) return;
        
        const varName = parts[0].trim();
        const expression = parts[1].trim();
        
        let value;
        
        // 處理數字
        if (!isNaN(expression)) {
            value = parseFloat(expression);
        }
        // 處理字符串
        else if ((expression.startsWith('"') && expression.endsWith('"')) ||
                 (expression.startsWith("'") && expression.endsWith("'"))) {
            value = expression.slice(1, -1);
        }
        // 處理表達式
        else {
            value = this.evaluateExpression(expression);
        }
        
        const oldValue = this.variables[varName];
        this.variables[varName] = value;
        
        // 標記變數變化
        this.markVariableChanged(varName, oldValue !== value);
    }

    // 評估表達式
    evaluateExpression(expression) {
        // 簡單的表達式評估（實際應用中需要更複雜的解析器）
        let result = expression;
        
        // 替換變數
        for (const [varName, value] of Object.entries(this.variables)) {
            const regex = new RegExp(`\\b${varName}\\b`, 'g');
            result = result.replace(regex, value);
        }
        
        // 評估數學表達式
        try {
            return Function(`"use strict"; return (${result})`)();
        } catch {
            return result;
        }
    }

    // 評估f-string
    evaluateFString(fstring) {
        let content = fstring.slice(2, -1); // 移除f" 和 "
        
        // 替換{變數}
        content = content.replace(/\{([^}]+)\}/g, (match, varName) => {
            if (this.variables[varName] !== undefined) {
                return this.variables[varName];
            }
            return match;
        });
        
        return content;
    }

    // 處理for迴圈（簡化版）
    handleForLoop(line) {
        const match = line.match(/for\s+(\w+)\s+in\s+range\((\d+)(?:,\s*(\d+))?\)/);
        if (match) {
            const varName = match[1];
            const start = match[3] ? parseInt(match[2]) : 0;
            const end = match[3] ? parseInt(match[3]) : parseInt(match[2]);
            
            this.addOutput(`開始迴圈: ${varName} 從 ${start} 到 ${end-1}`);
        }
    }

    // 處理if語句（簡化版）
    handleIfStatement(line) {
        const condition = line.replace('if ', '').replace(':', '').trim();
        const result = this.evaluateCondition(condition);
        this.addOutput(`條件判斷: ${condition} = ${result}`);
    }

    // 評估條件
    evaluateCondition(condition) {
        try {
            // 替換變數
            let result = condition;
            for (const [varName, value] of Object.entries(this.variables)) {
                const regex = new RegExp(`\\b${varName}\\b`, 'g');
                result = result.replace(regex, value);
            }
            
            return Function(`"use strict"; return (${result})`)();
        } catch {
            return false;
        }
    }

    // 高亮當前行
    highlightCurrentLine() {
        this.clearHighlights();
        const lineNumbers = document.querySelectorAll('.line-number');
        if (lineNumbers[this.currentLine]) {
            lineNumbers[this.currentLine].classList.add('current-line');
        }
    }

    // 清除高亮
    clearHighlights() {
        document.querySelectorAll('.line-number').forEach(line => {
            line.classList.remove('current-line', 'error-line');
        });
    }

    // 添加執行步驟
    addExecutionStep(line) {
        const step = {
            number: this.executionSteps.length + 1,
            line: this.currentLine + 1,
            code: line.trim(),
            description: this.getStepDescription(line)
        };
        
        this.executionSteps.push(step);
    }

    // 獲取步驟描述
    getStepDescription(line) {
        const trimmedLine = line.trim();
        
        if (trimmedLine.startsWith('print(')) {
            return '輸出內容到控制台';
        }
        if (trimmedLine.includes('=') && !trimmedLine.includes('==')) {
            return '變數賦值操作';
        }
        if (trimmedLine.startsWith('for ')) {
            return '開始迴圈執行';
        }
        if (trimmedLine.startsWith('if ')) {
            return '條件判斷';
        }
        if (trimmedLine.startsWith('#')) {
            return '註解說明';
        }
        
        return '執行程式碼';
    }

    // 添加輸出
    addOutput(content) {
        this.output.push(content);
        const consoleOutput = document.getElementById('consoleOutput');
        if (consoleOutput) {
            const line = document.createElement('div');
            line.className = 'console-line';
            line.textContent = content;
            consoleOutput.appendChild(line);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
    }

    // 標記變數變化
    markVariableChanged(varName, isChanged) {
        setTimeout(() => {
            const variableItems = document.querySelectorAll('.variable-item');
            variableItems.forEach(item => {
                const nameElement = item.querySelector('.variable-name');
                if (nameElement && nameElement.textContent === varName && isChanged) {
                    item.classList.add('changed');
                    setTimeout(() => item.classList.remove('changed'), 1000);
                }
            });
        }, 100);
    }

    // 顯示錯誤
    showError(message) {
        const lineNumbers = document.querySelectorAll('.line-number');
        if (lineNumbers[this.currentLine]) {
            lineNumbers[this.currentLine].classList.add('error-line');
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        const consoleOutput = document.getElementById('consoleOutput');
        if (consoleOutput) {
            consoleOutput.appendChild(errorDiv);
        }
    }

    // 更新UI
    updateUI() {
        this.updateVariableMonitor();
        this.updateExecutionSteps();
        this.updateFlowchart();
    }

    // 更新變數監視器
    updateVariableMonitor() {
        const variableList = document.getElementById('variableList');
        if (!variableList) return;
        
        variableList.innerHTML = '';
        
        for (const [name, value] of Object.entries(this.variables)) {
            const item = document.createElement('div');
            item.className = 'variable-item';
            item.innerHTML = `
                <span class="variable-name">${name}</span>
                <span class="variable-value">${value}</span>
            `;
            variableList.appendChild(item);
        }
    }

    // 更新執行步驟
    updateExecutionSteps() {
        const stepsList = document.getElementById('stepsList');
        if (!stepsList) return;
        
        stepsList.innerHTML = '';
        
        this.executionSteps.forEach((step, index) => {
            const item = document.createElement('div');
            item.className = `step-item ${index === this.executionSteps.length - 1 ? 'active' : ''}`;
            item.innerHTML = `
                <div class="step-number">${step.number}</div>
                <div class="step-description">
                    <strong>第${step.line}行:</strong> ${step.description}
                    <br><code>${step.code}</code>
                </div>
            `;
            stepsList.appendChild(item);
        });
        
        stepsList.scrollTop = stepsList.scrollHeight;
    }

    // 生成流程圖
    generateFlowchart() {
        const flowchartContent = document.getElementById('flowchartContent');
        if (!flowchartContent) return;
        
        flowchartContent.innerHTML = `
            <div class="flowchart-node node-start">開始</div>
            <div class="flowchart-arrow"></div>
            <div class="flowchart-node node-process">執行程式碼</div>
            <div class="flowchart-arrow"></div>
            <div class="flowchart-node node-end">結束</div>
        `;
    }

    // 更新流程圖
    updateFlowchart() {
        const nodes = document.querySelectorAll('.flowchart-node');
        nodes.forEach(node => node.classList.remove('node-active'));
        
        if (this.currentLine === 0) {
            nodes[0]?.classList.add('node-active'); // 開始
        } else if (this.currentLine < this.lines.length) {
            nodes[1]?.classList.add('node-active'); // 執行中
        } else {
            nodes[2]?.classList.add('node-active'); // 結束
        }
    }

    // 設置執行速度
    setSpeed(value) {
        this.speed = parseInt(value);
        const speedLabel = document.querySelector('.speed-label');
        if (speedLabel) {
            const speedMultiplier = (3100 - this.speed) / 1000;
            speedLabel.textContent = `${speedMultiplier.toFixed(1)}x`;
        }
    }

    // 睡眠函數
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// 初始化視覺化器
let visualizer;

document.addEventListener('DOMContentLoaded', function() {
    visualizer = new CodeVisualizer();
}); 