FROM php:8.2-apache

# copy Composer PHAR from the Composer image into the PHP image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# composer require it to work (necessary to unzip)
RUN apt-get update && apt-get install -y unzip