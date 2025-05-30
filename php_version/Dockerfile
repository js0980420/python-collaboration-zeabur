FROM php:8.1-cli

# 安裝必要的擴展
RUN docker-php-ext-install pdo pdo_mysql

# 設置工作目錄
WORKDIR /app

# 複製應用文件
COPY . .

# 設置權限
RUN chmod -R 755 .

# 暴露端口
EXPOSE 8080

# 啟動命令
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."] 