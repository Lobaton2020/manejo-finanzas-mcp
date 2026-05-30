FROM php:8.2.31-cli

WORKDIR /var/www/html

COPY composer.json ./

RUN apt-get update && apt-get install -y --no-install-recommends \
        zlib1g-dev libzip-dev libicu-dev libxml2-dev curl libonig-dev libsqlite3-dev \
        && docker-php-ext-install -j$(nproc) \
            mysqli pdo pdo_mysql opcache intl pdo_sqlite \
        && pecl install zip \
        && docker-php-ext-enable zip \
        && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
        && apt-get clean \
        && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/*

COPY . .

RUN mkdir -p /tmp/finanzas-mcp-sessions \
    && chmod 777 /tmp/finanzas-mcp-sessions \
    && composer install --no-dev --no-interaction --optimize-autoloader \
    && chmod -R 755 /var/www/html

EXPOSE 8000

CMD ["php", "src/MCP/Server.php"]