FROM nginx:1.13

# Default to master but allowe override through --build-arg argument in docker
# build command.
ARG TWINEPM_BRANCH=master

# Set environment variables.
ENV \
    DEBIAN_FRONTEND=noninteractive \
    TWINEPM_BRANCH=$TWINEPM_BRANCH

# Install the dependencies.
RUN \
    apt-get update && \
    apt-get install -y git && \
    cd /etc && \
    git clone -b $TWINEPM_BRANCH https://github.com/furkle/twinepm-server-heroku.git && \
    cd twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    rm /var/www/html/index.html && \
    echo "<?php require_once '/etc/twinepm-server-heroku/index.php'>" > /var/www/html/index.php