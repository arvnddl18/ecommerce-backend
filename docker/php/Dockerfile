FROM php:8.3-fpm-alpine

# Install system dependencies and build libraries
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    postgresql-dev \
    oniguruma-dev \
    linux-headers \
    bash

# Install required PHP extensions: pdo_pgsql, mbstring, zip, exif, pcntl, bcmath, xml
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    xml

# Copy Composer binary from official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code into container
COPY . .

# Install dependencies via composer install
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Adjust permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
