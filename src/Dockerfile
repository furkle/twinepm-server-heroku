FROM nginx:1.13

# Set environment variables.
ENV DEBIAN_FRONTEND noninteractive
ENV TERM xterm-256color

# Set the locale.
RUN DEBIAN_FRONTEND=noninteractive \
    apt-get update && \
    apt-get install -yqq \
        locales \
        apt-get install -y language-pack-en-base \
        lsb-release \
        software-properties-common \
        gnupg && \
RUN DEBIAN_FRONTEND=noninteractive \
    export LC_ALL=en_US.UTF-8 && \
    export LANG=en_US.UTF-8
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8
RUN localedef -i en_US -f UTF-8 en_US.UTF-8 && \
    /usr/sbin/update-locale LANG=$LANG

# Install the dependencies.
RUN DEBIAN_FRONTEND=noninteractive LC_ALL=en_US.UTF-8 \
    /usr/bin/gpg-agent --daemon && \
    LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php && \
    apt-get update && \
    apt-get install -y \
        composer \
        php7.1 \
        php7.1-mbstring \
        php7.1-xml \
        php7.1-zip \
        unzip \
        zip && \
    cd twinepm-server-heroku && \
    composer install

# Copy the source folder to the container.
COPY . .