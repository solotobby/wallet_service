FROM php:7.4-fpm-alpine3.13

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www

COPY crontab /etc/crontabs/root

CMD ["crond", "-f"]

