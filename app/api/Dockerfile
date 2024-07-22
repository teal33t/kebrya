# Use Ubuntu 22.04 as the base image
FROM ubuntu:22.04

# Avoid prompts from apt
ENV DEBIAN_FRONTEND=noninteractive

# Allow Composer to run as root
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
    php7.4-opcache

# Install Apache and mod_php
RUN apt-get install -y apache2 libapache2-mod-php7.4

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Clean up
RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure Apache
RUN a2enmod rewrite

# Configure Apache for the API
RUN echo '<VirtualHost *:8080>\n\
    DocumentRoot /var/www/api/public\n\
    <Directory /var/www/api/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Change Apache port to 8080
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf

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

# Expose port
EXPOSE 8080

# Start Apache in foreground
CMD ["apache2ctl", "-D", "FOREGROUND"]