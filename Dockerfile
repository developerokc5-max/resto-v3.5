FROM php:8.2-apache

# 1️⃣ Enable Apache modules
RUN a2enmod rewrite headers

# 2️⃣ System dependencies (SQLite + PHP extensions)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        zip \
        mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 3️⃣ Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4️⃣ App directory
WORKDIR /var/www/html

# 5️⃣ Copy app source
COPY . .

# 6️⃣ Apache → point to Laravel public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

# 7️⃣ Create database directory and file
RUN mkdir -p database && \
    touch database/database.sqlite && \
    chown -R www-data:www-data database

# 8️⃣ Laravel storage permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# 9️⃣ Install PHP dependencies (production only)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# 🔟 Copy .env.example to .env and generate APP_KEY
RUN cp .env.example .env && \
    php artisan key:generate --force

# 1️⃣1️⃣ Environment defaults (Render overrides via ENV vars)
ENV APP_ENV=production
ENV APP_DEBUG=false

# 1️⃣1️⃣ Expose web port
EXPOSE 80

# 1️⃣2️⃣ Create startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# 1️⃣3️⃣ Use entrypoint to run migrations before Apache starts
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
