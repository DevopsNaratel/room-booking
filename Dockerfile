# --- Stage 1: PHP Dependencies ---
FROM composer:latest as vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# --- Stage 2: Frontend Assets ---
FROM node:20-alpine as frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
RUN npm run build

# --- Stage 3: Production Image ---
FROM php:8.4-fpm-alpine

# Install system dependencies & PostgreSQL dev libraries
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    icu-dev \
    oniguruma-dev \
    postgresql-dev  # <--- WAJIB untuk PostgreSQL

# Install PHP extensions (pdo_pgsql)
RUN docker-php-ext-install pdo_pgsql zip opcache intl bcmath

WORKDIR /var/www

# Copy application code & assets
COPY . .
COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=frontend /app/public/build/ ./public/build/

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Optimasi Laravel
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]