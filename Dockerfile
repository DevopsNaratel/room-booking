# --- Stage 1: Install PHP Dependencies ---
FROM composer:2 as vendor
WORKDIR /app

# Ambil token dari Jenkins
ARG GITHUB_TOKEN

COPY composer.json composer.lock ./

# KONFIGURASI BENAR:
# 1. Naikkan timeout
ENV COMPOSER_PROCESS_TIMEOUT=600
# 2. Gunakan Token GitHub untuk otentikasi
RUN if [ ! -z "$GITHUB_TOKEN" ]; then composer config github-oauth.github.com $GITHUB_TOKEN; fi
# 3. Paksa menggunakan 'dist' (ZIP) bukan 'source' (Git)
RUN composer config preferred-install dist

# Jalankan install dengan retry
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction || \
    (sleep 5 && composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction)

# --- Stage 2: Build Frontend Assets ---
FROM node:20-alpine as frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
RUN npm run build

# --- Stage 3: Final Image ---
FROM php:8.4-cli-alpine

RUN apk add --no-cache libpng-dev libzip-dev zip unzip oniguruma-dev icu-dev git
RUN docker-php-ext-install pdo_mysql mbstring zip bcmath intl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .
COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=frontend /app/public/build/ ./public/build/

RUN composer dump-autoload --optimize --no-dev
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

USER www-data
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
