FROM 7.1-fpm

# Install the dependencies.
RUN \
    cd /etc/twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    phing install-php-packages