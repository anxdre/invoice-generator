FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# node + pnpm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && corepack enable \
    && corepack prepare pnpm@latest --activate

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN pnpm install
RUN pnpm run build

RUN chown -R www-data:www-data \
        /var/www/storage \
        /var/www/bootstrap/cache \
        /var/www/database \
    && chmod -R 775 \
        /var/www/storage \
        /var/www/bootstrap/cache \
        /var/www/database

CMD ["php-fpm"]
