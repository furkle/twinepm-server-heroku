FROM nginx:1.13

# Set the locale
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

RUN \
    apt-get update && \
    apt-get install -y \
        gnupg \
        software-properties-common && \
    add-apt-repository ppa:ondrej/php && \
    apt-get update && \
    apt-get install -y \
        composer \
        git \
        php7.1 \
        php7.1-mbstring \
        php7.1-xml \
        php7.1-zip \
        unzip \
        zip && \
    cd twinepm-server-heroku && \
    composer install

COPY . .