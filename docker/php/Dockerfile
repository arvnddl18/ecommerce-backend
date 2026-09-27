FROM php:8.4-fpm-alpine

# Install system dependencies, build libraries and development packages
# Note: libpq-dev is used instead of postgresql-dev to avoid heavy LLVM/Clang bloat
# Note: pdo, pdo_sqlite, mbstring, and xml are already pre-compiled in php:8.4-fpm-alpine
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libpq-dev \
    linux-headers \
    bash \
    icu-dev \
    sqlite-dev

# Configure and install PHP extensions needed for Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    intl

# Copy Composer binary from official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy and setup entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
