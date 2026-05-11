FROM php:8.2-apache

RUN a2dismod mpm_event mpm_worker || true && a2enmod mpm_prefork

RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN pecl install mongodb-1.21.0 redis \
    && docker-php-ext-enable mongodb redis

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]