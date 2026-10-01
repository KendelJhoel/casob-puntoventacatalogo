FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --no-scripts --no-autoloader
COPY src/ src/
COPY public/ public/
COPY views/ views/
COPY config/config.example.php config/config.php
COPY tests/ tests/
COPY main.php app.php ./
COPY .gitignore ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-dev --optimize \
    && mkdir -p public/uploads tickets \
    && chown -R www-data:www-data public/uploads tickets \
    && printf 'upload_max_filesize=2M\npost_max_size=8M\ndisplay_errors=Off\nlog_errors=On\ndate.timezone=America/El_Salvador\n' > /usr/local/etc/php/conf.d/app.ini
USER www-data
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
