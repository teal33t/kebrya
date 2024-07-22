# Use Ubuntu 22.04 as the base image
FROM ubuntu:22.04

# Avoid prompts from apt
ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set working directory
WORKDIR /var/www/api

# Install system dependencies
RUN apt-get update -y && \
    apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    curl \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg

# Add PHP repository
RUN add-apt-repository ppa:ondrej/php -y

# Update package list after adding new repository
RUN apt-get update -y

# Install PHP and required extensions
RUN apt-get install -y \
    php7.4 \
    php7.4-cli \
    php7.4-common \
    php7.4-mysql \
    php7.4-xml \
    php7.4-mbstring \
    php7.4-curl \
    php7.4-zip \
    php7.4-gd \
    php7.4-intl \
    php7.4-bcmath \
    php7.4-soap \
    php7.4-opcache \
    php7.4-fpm

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install OpenResty
RUN apt-key adv --fetch-keys https://openresty.org/package/pubkey.gpg && \
    add-apt-repository "deb http://openresty.org/package/ubuntu $(lsb_release -sc) main" && \
    apt-get update -y && \
    apt-get install -y openresty

# Clean up after installation
RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Copy composer.json and composer.lock
COPY composer.json composer.lock ./

# Install composer dependencies
RUN set -eux; \
    composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
    composer clear-cache

# Copy rest of the application
COPY . .

# Run composer dump-autoload optimizing for production
RUN set -eux; \
    composer dump-autoload --optimize; \
    composer run-script post-install-cmd; \
    sync

# Set proper permissions
RUN chown -R www-data:www-data .

# Add OpenResty configuration
RUN mkdir -p /usr/local/openresty/nginx/conf
RUN echo 'worker_processes auto;\n\
worker_rlimit_nofile 65535;\n\
events {\n\
    worker_connections 65535;\n\
    multi_accept on;\n\
    use epoll;\n\
}\n\
http {\n\
    include       mime.types;\n\
    default_type  application/octet-stream;\n\
    sendfile        on;\n\
    tcp_nopush      on;\n\
    tcp_nodelay     on;\n\
    keepalive_timeout  65;\n\
    keepalive_requests 100000;\n\
    types_hash_max_size 2048;\n\
    server_tokens off;\n\
    open_file_cache max=200000 inactive=20s;\n\
    open_file_cache_valid 30s;\n\
    open_file_cache_min_uses 2;\n\
    open_file_cache_errors on;\n\
    access_log off;\n\
    error_log /dev/stderr;\n\
    gzip on;\n\
    gzip_vary on;\n\
    gzip_proxied any;\n\
    gzip_comp_level 6;\n\
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;\n\
    server {\n\
        listen       8080 backlog=65535;\n\
        server_name  localhost;\n\
        root /var/www/api/public;\n\
        index index.php index.html index.htm;\n\
        location / {\n\
            try_files $uri $uri/ /index.php$is_args$args;\n\
        }\n\
        location ~ \.php$ {\n\
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;\n\
            fastcgi_index index.php;\n\
            include fastcgi_params;\n\
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
            fastcgi_buffers 16 16k;\n\
            fastcgi_buffer_size 32k;\n\
        }\n\
        location ~ /\.ht {\n\
            deny all;\n\
        }\n\
    }\n\
}' > /usr/local/openresty/nginx/conf/nginx.conf

# Optimize PHP-FPM configuration
RUN sed -i 's/^pm = dynamic/pm = static/' /etc/php/7.4/fpm/pool.d/www.conf && \
    sed -i 's/^pm.max_children = 5/pm.max_children = 50/' /etc/php/7.4/fpm/pool.d/www.conf && \
    sed -i 's/^pm.start_servers = 2/;pm.start_servers = 2/' /etc/php/7.4/fpm/pool.d/www.conf && \
    sed -i 's/^pm.min_spare_servers = 1/;pm.min_spare_servers = 1/' /etc/php/7.4/fpm/pool.d/www.conf && \
    sed -i 's/^pm.max_spare_servers = 3/;pm.max_spare_servers = 3/' /etc/php/7.4/fpm/pool.d/www.conf

# Optimize PHP configuration
RUN echo "opcache.enable=1" >> /etc/php/7.4/fpm/php.ini && \
    echo "opcache.memory_consumption=128" >> /etc/php/7.4/fpm/php.ini && \
    echo "opcache.interned_strings_buffer=8" >> /etc/php/7.4/fpm/php.ini && \
    echo "opcache.max_accelerated_files=4000" >> /etc/php/7.4/fpm/php.ini && \
    echo "opcache.revalidate_freq=60" >> /etc/php/7.4/fpm/php.ini && \
    echo "opcache.fast_shutdown=1" >> /etc/php/7.4/fpm/php.ini

# Expose port
EXPOSE 8080

# Set ENTRYPOINT to start PHP-FPM and OpenResty
ENTRYPOINT service php7.4-fpm start && openresty -g 'daemon off;'