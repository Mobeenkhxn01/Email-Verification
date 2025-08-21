# Use PHP with Apache
FROM php:8.2-apache

# Install required extensions and tools
RUN apt-get update && apt-get install -y unzip git cron \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer (from official composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files into container
COPY . .

# Install PHP dependencies via Composer
RUN composer install --no-dev --optimize-autoloader

# Expose port 80 for web traffic
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]
