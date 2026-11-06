FROM php:8.4-apache

# PHP extensions required by Laravel, PostgreSQL (Aiven), GD (profile crop), Excel (phpspreadsheet)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev libicu-dev libonig-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql gd zip intl bcmath exif mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Dependencies first (cached when only app code changes)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-autoloader --no-scripts

COPY . .

RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader \
    && cp docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf \
    && cp docker/php.ini /usr/local/etc/php/conf.d/app.ini \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
