FROM php:8.2-apache

# copy Composer PHAR from the Composer image into the PHP image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# composer require it to work (necessary to unzip)
# rm -rf /var/lib/apt/lists/* -> clean cache to decrease image size
RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*

# install pcov by pecl and enable
RUN pecl install pcov && docker-php-ext-enable pcov