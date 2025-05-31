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

# 配置Apache
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod ssl

# 創建Apache配置文件
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    ServerName localhost\n\
    \n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# 安裝PHP依賴（WebSocket服務器）
WORKDIR /var/www/html/websocket_version
RUN composer install --no-dev --optimize-autoloader

# 返回主目錄
WORKDIR /var/www/html

# 創建必要的目錄
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /etc/supervisor/conf.d \
    && mkdir -p /var/run \
    && mkdir -p /var/run/apache2 \
    && mkdir -p /var/lock/apache2 \
    && mkdir -p /var/log/apache2

# 複製supervisor配置文件
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 設置權限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod +x /var/www/html/websocket_version/websocket_server.php \
    && chmod +x /var/www/html/websocket_version/start_websocket.sh

# 暴露端口（Apache 80, WebSocket 8080）
EXPOSE 80 8080

# 啟動supervisor來管理Apache和WebSocket服務器
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"] 