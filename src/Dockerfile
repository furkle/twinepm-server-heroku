FROM nginx:1.13

# Set environment variables.
ARG DEBIAN_FRONTEND=noninteractive

# Install the dependencies.
RUN \
    apt-get update && \
    apt-get install -y git && \
    cd /etc && \
    git clone -b ${TWINEPM_BRANCH:-master} https://github.com/furkle/twinepm-server-heroku.git && \
    cd twinepm-server-heroku && \
    ls -la && \
    ./scripts/getPhing && \
    phing get-logic-dependencies