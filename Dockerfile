FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip \
    intl \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
