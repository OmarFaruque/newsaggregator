# Use the official PHP image as the base
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    build-essential \
    && docker-php-ext-install \
    pdo_mysql \
    bcmath \
    zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*


# Install Composer globally
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer


# Copy project files

COPY . /var/www/html


# Install dependencies
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose the PHP-FPM port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
