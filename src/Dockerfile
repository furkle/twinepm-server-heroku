FROM nginx:1.13

# Set environment variables.
ARG DEBIAN_FRONTEND=noninteractive
ENV TERM xterm-256color

# Set the locale.
RUN \
    apt-get update && \
    apt-get install -y \
        apt-utils \
        locales \
        lsb-release \
        software-properties-common \
        gnupg
ENV \
    LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8
RUN \
    localedef -i en_US -f UTF-8 en_US.UTF-8 && \
    /usr/sbin/update-locale LANG=$LANG

# Install the dependencies.
RUN \
    apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C && \
    add-apt-repository -y ppa:ondrej/php && \
    apt-get update && \
    apt-get install -y \
        composer \
        php7.1 \
        php7.1-mbstring \
        php7.1-xml \
        php7.1-zip \
        unzip \
        zip

COPY . /etc/twinepm-server-src

RUN \
    cd /etc/twinepm-server-heroku && \
    composer install

# Copy the source folder to the container.