# Python 程式設計教學網站

一個現代化的Python學習平台，結合AI助教提供個人化的程式設計教學體驗。

## 功能特色

- 🎓 **系統化課程** - 從基礎到進階的完整Python教學內容
- 🤖 **AI智能助教** - 24/7在線AI助教，隨時解答疑問
- 💻 **在線編輯器** - 內建程式碼編輯器，即學即用
- 📱 **響應式設計** - 支援各種設備，隨時隨地學習
- 🎯 **練習題目** - 豐富的程式練習，鞏固學習成果
- 🌏 **繁體中文** - 完整的繁體中文界面和教學內容

## 技術架構

### 後端
- **Flask** - Python Web框架
- **OpenAI API** - AI助教功能
- **Python-dotenv** - 環境變數管理

### 前端
- **Bootstrap 5** - UI框架
- **Font Awesome** - 圖標庫
- **Prism.js** - 程式碼語法高亮
- **原生JavaScript** - 互動功能

## 快速開始

### 1. 環境要求

- Python 3.8+
- pip（Python套件管理器）
- OpenAI API金鑰

### 2. 安裝步驟

```bash
# 複製專案
git clone <your-repository-url>
cd python-teaching-web

# 安裝依賴
pip install -r requirements.txt

# 設定環境變數
cp env.example .env
# 編輯 .env 文件，填入您的OpenAI API金鑰

# 啟動應用
python app.py
```

### 3. 訪問網站

打開瀏覽器，訪問 `http://localhost:5000`

## 環境設定

創建 `.env` 文件並設定以下變數：

```env
SECRET_KEY=your-secret-key-here
OPENAI_API_KEY=your-openai-api-key-here
FLASK_ENV=development
FLASK_DEBUG=True
```

### 獲取OpenAI API金鑰

1. 訪問 [OpenAI官網](https://openai.com/)
2. 註冊並登入帳戶
3. 前往API設定頁面
4. 創建新的API金鑰
5. 將金鑰複製到 `.env` 文件中

## 項目結構

```
python-teaching-web/
├── app.py                 # 主應用程式
├── requirements.txt       # Python依賴
├── env.example           # 環境變數範例
├── README.md             # 項目說明
├── templates/            # HTML模板
│   ├── base.html        # 基礎模板
│   ├── index.html       # 首頁
│   ├── lessons.html     # 課程頁面
│   ├── ai_tutor.html    # AI助教頁面
│   └── practice.html    # 程式練習頁面
└── static/              # 靜態資源
    ├── css/
    │   └── style.css    # 自定義樣式
    ├── js/
    │   └── main.js      # 主要JavaScript
    └── images/          # 圖片資源
```

## 主要功能說明

### 🎓 課程內容
- Python基礎語法
- 變數與資料型別
- 條件判斷與迴圈
- 函數定義與使用
- 列表與字典操作
- 錯誤處理

### 🤖 AI助教
- 即時問答服務
- 程式碼檢查與建議
- 學習指導
- 繁體中文對話

### 💻 程式練習
- 在線程式編輯器
- 10個練習題目
- 程式碼範例
- AI智能檢查

## 部署說明

### 本地開發
```bash
python app.py
```

### 生產環境部署

#### 使用Gunicorn
```bash
pip install gunicorn
gunicorn -w 4 -b 0.0.0.0:5000 app:app
```

#### 使用Docker（可選）
```dockerfile
FROM python:3.9-slim

WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .
EXPOSE 5000

CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:5000", "app:app"]
```

## 貢獻指南

1. Fork 此專案
2. 創建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 開啟 Pull Request

## 授權

此專案採用 MIT 授權 - 查看 [LICENSE](LICENSE) 文件了解詳情

## 支援

如果您有任何問題或建議：

- 開啟 [Issue](https://github.com/your-username/python-teaching-web/issues)
- 發送郵件到 your-email@example.com

## 更新日誌

### v1.0.0 (2024-05-28)
- ✨ 初始版本發布
- 🎓 完整的Python基礎課程
- 🤖 AI助教功能
- 💻 在線程式編輯器
- 📱 響應式設計

## 致謝

- [OpenAI](https://openai.com/) - 提供AI助教功能
- [Bootstrap](https://getbootstrap.com/) - UI框架
- [Flask](https://flask.palletsprojects.com/) - Web框架
- [Font Awesome](https://fontawesome.com/) - 圖標庫

---

**Made with ❤️ for Python learners** 