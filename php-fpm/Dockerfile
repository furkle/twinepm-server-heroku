FROM php:7.1-fpm

RUN mkdir /etc/twinepm-server-heroku

WORKDIR /etc/twinepm-server-heroku

COPY \
    php-fpm/ \
    build.xml \
    ./

COPY scripts/getPhing scripts/getPhing

RUN cp /etc/twinepm-server-heroku/php-fpm/log.conf /usr/local/etc/php-fpm.d/zz-log.conf
