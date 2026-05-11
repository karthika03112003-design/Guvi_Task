FROM php:8.2-cli

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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY . .

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]