FROM php:8.3-fpm-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev libcurl4-openssl-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_sqlite bcmath curl exif gd intl opcache pcntl zip \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

FROM php-base AS vendor
ARG INSTALL_DEV_DEPENDENCIES=false
COPY composer.json composer.lock ./
RUN if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        composer install --no-interaction --no-progress --prefer-dist --no-scripts; \
    else \
        composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader; \
    fi

FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources ./resources
COPY vite.config.* ./
COPY public ./public
RUN npm run build

FROM php-base AS app
ARG INSTALL_DEV_DEPENDENCIES=false
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        mkdir -p resources/views/scribe public/vendor/scribe .scribe \
        && chown -R www-data:www-data resources/views/scribe public/vendor .scribe \
        && composer dump-autoload; \
    else composer dump-autoload --no-dev --optimize; fi \
    && php artisan package:discover --ansi
USER www-data
CMD ["php-fpm"]
