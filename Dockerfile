FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y git unzip libzip-dev zip zlib1g-dev libonig-dev libxml2-dev libsqlite3-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader

COPY . ./
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
