# syntax=docker/dockerfile:1

# PHP 8.4+ diperlukan: config/database.php memakai kelas Pdo\Mysql (PHP 8.4+).
ARG PHP_VERSION=8.4

# ---------------------------------------------------------------------------
# Base: PHP + Apache + ekstensi yang dibutuhkan Laravel, Livewire, dan
# PhpSpreadsheet (impor/ekspor Excel) serta MySQL.
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-apache AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip \
        libfreetype-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        zip \
        intl \
        bcmath \
        exif \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Konfigurasi Apache & PHP
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
RUN a2enmod rewrite headers \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Dependensi Composer (di-cache sebagai layer terpisah, produksi tanpa dev)
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

# ---------------------------------------------------------------------------
# Image final
# ---------------------------------------------------------------------------
FROM base AS final

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor

# Autoloader dioptimalkan + package:discover (post-autoload-dump).
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/framework/testing storage/logs \
        storage/app/public storage/app/livewire-tmp bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && cp docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database \
    FILESYSTEM_DISK=local \
    BROADCAST_CONNECTION=log \
    MAIL_MAILER=log \
    PORT=80

EXPOSE 80

# /up adalah health route bawaan Laravel (lihat bootstrap/app.php).
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r '$p = getenv("PORT") ?: "80"; exit(@file_get_contents("http://127.0.0.1:$p/up") === false ? 1 : 0);'

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
