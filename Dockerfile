
  # Use an official PHP image that includes an Apache web server
  FROM php:8.2-apache

  # Install the PHP extension for connecting to PostgreSQL.
  # This is required for your PHP code to be able to query your database.
  RUN docker-php-ext-install pdo pdo_pgsql

  # Copy all your application code into the web server's public directory
  COPY . /var/www/html/
