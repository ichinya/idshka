FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
        bash \
        curl \
        libpq \
        libpq-dev \
        su-exec \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" bcmath pdo_pgsql pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY infra/docker/php/entrypoint.sh /usr/local/bin/idshka-entrypoint

RUN chmod +x /usr/local/bin/idshka-entrypoint \
    && sed -i -E 's/^(user|group) = /; \0/' /usr/local/etc/php-fpm.d/www.conf \
    && mkdir -p /var/www/html

WORKDIR /var/www/html

ENTRYPOINT ["idshka-entrypoint"]
CMD ["php-fpm", "-F"]
