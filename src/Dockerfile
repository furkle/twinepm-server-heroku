FROM nginx:1.13

# Default to master but allow override through --build-arg argument in docker
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
    phing get-repo && \
    cd twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    phing setup-nginx