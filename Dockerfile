# Use official PHP image with Apache
FROM php:8.2-apache

# Copy all your files into the web root
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80

# Start Apache when the container runs
CMD ["apache2-foreground"]
