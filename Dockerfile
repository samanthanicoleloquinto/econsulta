# Use an official PHP + Apache image
FROM php:8.2-apache

# Copy project files to container
COPY htdocs/ /var/www/html/

# Enable MySQLi extension for PHP
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Expose port 80 for HTTP traffic
EXPOSE 80

