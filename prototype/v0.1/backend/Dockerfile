FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
        git curl zip unzip libpq-dev libzip-dev \
        libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Laravel en staging durante el build (no en el volumen)
RUN composer create-project laravel/laravel /laravel-staging --prefer-dist --quiet \
    && cd /laravel-staging \
    && composer require laravel/sanctum barryvdh/laravel-dompdf --quiet \
    && php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --quiet

WORKDIR /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r//' /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
