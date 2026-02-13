# Use official PHP with Apache (includes mod_rewrite, good for Symfony)
FROM php:8.1-apache

# Install system dependencies + common PHP extensions for Symfony
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    pdo_mysql \
    intl \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory (do this before copying project files)
WORKDIR /var/www/html

# Copy custom Apache config (before project files to avoid overwriting)
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Copy project files
COPY . .

# Give Apache ownership for cache / logs (create dir if missing)
RUN mkdir -p /var/www/html/var && \
    chown -R www-data:www-data /var/www/html/var

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]