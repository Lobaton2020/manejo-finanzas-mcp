FROM php:8.2.31-cli

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN apt-get update && apt-get install -y \
        unzip libzip-dev libicu-dev libxml2-dev \
        && docker-php-ext-install mysqli pdo pdo_mysql opcache intl \
        && pecl install zip apcu \
        && docker-php-ext-enable zip apcu \
        && docker-php-ext-enable apcu \
        && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
        && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY . ./

RUN composer install --no-dev --optimize-autoloader \
    && chmod -R 755 /var/www/html \
    && chown -R www-data:www-data /var/www/html

EXPOSE 8000

CMD ["php", "src/MCP/Server.php"]