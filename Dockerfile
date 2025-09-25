 # Use an official PHP image that includes an Apache web server
 FROM php:8.2-apache

 # Install PostgreSQL development libraries, then install and enable the PHP PostgreSQL extension
 RUN apt-get update && apt-get install -y libpq-dev \
 && docker-php-ext-install pdo_pgsql pgsql \
 && docker-php-ext-enable pdo_pgsql pgsql \
 && rm -rf /var/lib/apt/lists/*

 # Copy all your application code into the web server's public directory
 COPY . /var/www/html/
