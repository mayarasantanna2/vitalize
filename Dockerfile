FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN apt-get update \
    && apt-get install -y libonig-dev libcurl4-openssl-dev \
    && docker-php-ext-install mbstring curl \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY . /var/www/html/

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 
