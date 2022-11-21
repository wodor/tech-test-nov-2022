FROM composer/composer:latest as composer

FROM php:8.1-apache
RUN apt update && apt install -y libcurl4-openssl-dev pkg-config libssl-dev libzip-dev
RUN pecl install mongodb apcu
RUN echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/docker-php-ext-mongo.ini
RUN echo "extension=apcu.so" > /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini
RUN docker-php-ext-install pdo pdo_mysql zip

ENV APACHE_DOCUMENT_ROOT /app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite
