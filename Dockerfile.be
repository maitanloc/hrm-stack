FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl mbstring zip opcache \
    && { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=0'; \
      echo 'opcache.memory_consumption=192'; \
      echo 'opcache.interned_strings_buffer=16'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.revalidate_freq=0'; \
      echo 'opcache.jit=disable'; \
    } > /usr/local/etc/php/conf.d/99-opcache.ini \
    && { \
      echo '[www]'; \
      echo 'pm = dynamic'; \
      echo 'pm.max_children = 12'; \
      echo 'pm.start_servers = 3'; \
      echo 'pm.min_spare_servers = 2'; \
      echo 'pm.max_spare_servers = 5'; \
      echo 'pm.max_requests = 500'; \
    } > /usr/local/etc/php-fpm.d/zz-perf.conf

WORKDIR /var/www/html

COPY BE/ ./

EXPOSE 9000

CMD ["php-fpm", "-F", "-O"]
