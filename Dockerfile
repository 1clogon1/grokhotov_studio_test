FROM php:8.3-cli-alpine AS gs_test

RUN apk add --no-cache git zip bash nginx \
    && apk add --no-cache nodejs npm \
    && npm install -g yarn

RUN apk add --no-cache postgresql-dev redis \
    && docker-php-ext-install pdo_pgsql pdo_mysql

ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ARG USER_ID=1000
RUN adduser -u ${USER_ID} -D -H app
USER app

COPY --chown=app . /app
WORKDIR /app

EXPOSE 8337

CMD ["php", "-S", "0.0.0.0:8337", "-t", "public"]
