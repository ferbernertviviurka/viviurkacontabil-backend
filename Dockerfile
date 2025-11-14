# Multi-stage build for Laravel application
# Optimized for Render.com deployment
FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    postgresql-client \
    nodejs \
    npm \
    supervisor \
    bash \
    netcat-openbsd

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Configure PHP
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/upload.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/upload.ini

# Configure Opcache for production
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies without scripts (scripts need app files)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copy entrypoint script (needed in production stage)
COPY docker/entrypoint.sh /tmp/entrypoint.sh

# Copy application files
COPY . .

# Now run composer scripts (package:discover, etc.) after files are copied
# First regenerate autoload files
RUN composer dump-autoload --optimize --no-interaction --classmap-authoritative || true

# Then run package discovery (needs app files)
RUN php artisan package:discover --ansi || true

# Create necessary directories
RUN mkdir -p storage/app/public \
    storage/app/private \
    storage/app/documents \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Production stage (optimized for Render.com)
FROM base AS production

# Copy entrypoint script to final location
RUN cp /tmp/entrypoint.sh /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

# Expose port (Render uses PORT env var)
# Default to 8000, but Render will override with PORT
EXPOSE 8000

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Default command for Render.com
# Render will override this with dockerCommand from render.yaml
# The PORT env var is automatically set by Render and will be passed via dockerCommand
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# Development stage
FROM base AS development

# Install development dependencies (no-scripts is already handled in base stage)
RUN composer install --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Regenerate autoload files with dev dependencies
RUN composer dump-autoload --optimize --no-interaction || true

# Run package discovery (needs app files)
RUN php artisan package:discover --ansi || true

# Install Node.js dependencies
RUN npm install

# Copy supervisor config (optional for dev)
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose ports
EXPOSE 9000 8000

# Default command for development (can be overridden in docker-compose)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
