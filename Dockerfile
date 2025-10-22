# Use official PHP image with Apache
FROM php:8.2-apache

# Copy all files from your project to Apache directory
COPY . /var/www/html/

# Install mysqli extension (for MySQL connection)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite (optional but helpful for frameworks)
RUN a2enmod rewrite

# Expose port 80 (default web port)
EXPOSE 80

# Start Apache automatically
CMD ["apache2-foreground"]
