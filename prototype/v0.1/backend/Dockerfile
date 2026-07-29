FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
        git curl zip unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Laravel en staging durante el build (no en el volumen)
RUN composer create-project laravel/laravel /laravel-staging --prefer-dist --quiet

WORKDIR /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r//' /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
