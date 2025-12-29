# --- Stage 1: Install PHP Dependencies ---
FROM composer:2 as vendor
WORKDIR /app

# Ambil token dari Jenkins agar tidak kena limit/reset koneksi
ARG GITHUB_TOKEN

COPY composer.json composer.lock ./

# Konfigurasi agar Composer lebih tangguh
ENV COMPOSER_PROCESS_TIMEOUT=600
RUN if [ ! -z "$GITHUB_TOKEN" ]; then composer config github-oauth.github.com $GITHUB_TOKEN; fi
RUN composer config preferred-install.dist https

# Gunakan mekanisme retry (coba lagi jika gagal)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction || \
    (sleep 5 && composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction)

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

# Install extension pdo_mysql
RUN docker-php-ext-install pdo_mysql mbstring zip bcmath intl

# Install composer di stage final untuk menjalankan dump-autoload (Opsional tapi aman)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 1. Copy file project dulu
COPY . .

# 2. Copy dependencies dari stage vendor
COPY --from=vendor /app/vendor/ ./vendor/

# 3. Copy assets dari stage frontend
COPY --from=frontend /app/public/build/ ./public/build/

# --- BARIS KRITIKAL YANG DITAMBAHKAN ---
# Ini untuk membuat file vendor/autoload.php yang tadi hilang
RUN composer dump-autoload --optimize --no-dev

# Set permission
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

USER www-data
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
