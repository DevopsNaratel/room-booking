# --- Stage 1: Install PHP Dependencies ---
FROM composer:latest as vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# --- Stage 2: Build Frontend Assets ---
FROM node:20-alpine as frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
RUN npm run build

# --- Stage 3: Testing Image (PHP CLI) ---
FROM php:8.4-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev

# Install extension pdo_mysql untuk koneksi ke MySQL
RUN docker-php-ext-install pdo_mysql mbstring zip bcmath intl

WORKDIR /var/www

# Copy source code dan hasil build dari stage sebelumnya
COPY . .
COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=frontend /app/public/build/ ./public/build/

# Set permission agar Laravel bisa menulis log/cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Gunakan user non-root untuk keamanan
USER www-data

# Port 8000 adalah port default php artisan serve
EXPOSE 8000

# Jalankan server internal Laravel
# --host=0.0.0.0 wajib agar bisa diakses dari luar container (oleh Service K8s)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]