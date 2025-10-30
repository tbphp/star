# ============================================
# Stage 1: Build Frontend
# ============================================
FROM node:20-alpine AS frontend-builder

WORKDIR /build
COPY ./frontend .
RUN npm install
RUN npm run build

# ============================================
# Stage 2: Build Backend (PHP-FPM + Frontend)
# ============================================
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy backend application files
COPY ./backend .

# Copy frontend build output to Laravel public directory
COPY --from=frontend-builder /build/dist /var/www/html/public/dist

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && mkdir -p public/dist \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache public

# Expose PHP-FPM port
EXPOSE 9000

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
