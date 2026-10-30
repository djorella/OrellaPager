# OrellaPager - PHP + Apache image
FROM php:8.3-apache

# mysqli extension for the MariaDB/MySQL connection in index.php
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy the site into Apache's document root
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# Database connection (overridable at runtime; defaults match the original code)
ENV DB_HOST=localhost \
    DB_USER=root \
    DB_PASS= \
    DB_NAME=orellapager

EXPOSE 80
