FROM php:8.3-fpm-bullseye

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev pkg-config default-mysql-client libcurl4-openssl-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) intl zip opcache pdo_mysql curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
RUN mkdir -p var && chown -R www-data:www-data var

USER www-data
