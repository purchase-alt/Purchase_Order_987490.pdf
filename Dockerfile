# Use official PHP image with Apache
FROM php:8.2-apache

# Set working directory to Apache’s web root
WORKDIR /var/www/html

# Copy your project files into the web root
COPY . /var/www/html/

# Ensure proper permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 for web traffic
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
