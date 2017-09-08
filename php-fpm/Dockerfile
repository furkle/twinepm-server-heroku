FROM php:7.1-fpm

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY php-fpm/ ./php-fpm/

RUN php-fpm/scripts/getPhpFpmDependencies