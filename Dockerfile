# 使用官方PHP 8.1 Apache鏡像
FROM php:8.1-apache

# 安裝系統依賴
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

# 安裝Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設置工作目錄
WORKDIR /var/www/html

# 複製應用程式文件
COPY . /var/www/html/

# 設置Apache配置
RUN a2enmod rewrite
COPY .htaccess /var/www/html/.htaccess

# 安裝PHP依賴（WebSocket服務器）
WORKDIR /var/www/html/websocket_version
RUN composer install --no-dev --optimize-autoloader

# 返回主目錄
WORKDIR /var/www/html

# 創建supervisor配置文件
RUN mkdir -p /etc/supervisor/conf.d
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 設置權限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 暴露端口（Apache 80, WebSocket 8080）
EXPOSE 80 8080

# 啟動supervisor來管理Apache和WebSocket服務器
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"] 