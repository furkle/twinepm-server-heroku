FROM nginx:1.13

# Set environment variables.
ARG DEBIAN_FRONTEND=noninteractive

# Set the locale.
RUN \
    apt-get update && \
    apt-get install -y \
        gnupg \
        locales \
        lsb-release \
        php \
        php-xml \
        software-properties-common
ENV \
    LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8 \
RUN \
    localedef -i en_US -f UTF-8 en_US.UTF-8 && \
    /usr/sbin/update-locale LANG=$LANG

# Install the dependencies.
RUN \
    cd /etc && \
    git clone -b ${TWINEPM_BRANCH:-${'master'}} https://github.com/furkle/twinepm-server-heroku.git && \
    cd twinepm-server-heroku && \
    sh scripts/getPhing.sh && \
    phing get-logic-dependencies