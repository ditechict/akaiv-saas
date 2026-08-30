FROM php:7.4.16-fpm-alpine3.13

# install system dependencies and PHP extensions
RUN apk update && apk add \
    curl \
    libpng-dev \
    unzip \
    vim \
    zip \
    && docker-php-ext-install mysqli pdo pdo_mysql

# clear cache
RUN rm /var/cache/apk/*

# get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

