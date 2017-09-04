FROM nginx:1.13

# Default to master but allow override through --build-arg argument in docker
# build command.
ARG TWINEPM_BRANCH=master
ARG TWINEPM_REPO_SITE=github.com
ARG TWINEPM_REPO_OWNER=furkle
ARG TWINEPM_REPO_NAME=twinepm-server-heroku

# Set environment variables.
ENV \
    DEBIAN_FRONTEND=noninteractive \
    TWINEPM_BRANCH=${TWINEPM_BRANCH} \
    TWINEPM_REPO_SITE=${TWINEPM_REPO_SITE} \
    TWINEPM_REPO_OWNER=${TWINEPM_REPO_OWNER} \
    TWINEPM_REPO_NAME=${TWINEPM_REPO_NAME}

# Install the dependencies.
RUN \
    apt-get update && \
    apt-get install -y \
        apt-utils \
        git && \
    cd /etc && \
    git clone -b ${TWINEPM_BRANCH} https://${TWINEPM_REPO_SITE}/${TWINEPM_REPO_OWNER}/${TWINEPM_REPO_NAME}.git && \
    cd twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    phing install-php-packages && \
    phing setup-nginx