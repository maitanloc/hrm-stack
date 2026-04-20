FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl mbstring zip opcache

WORKDIR /var/www/html

# Use source mount from compose for faster iteration/deploy parity.
COPY BE/ /var/www/html

EXPOSE 9000

CMD ["php-fpm", "-F"]
