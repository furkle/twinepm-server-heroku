FROM nginx:1.13

# Set environment variables.
ARG DEBIAN_FRONTEND=noninteractive

# Install the dependencies.
RUN \
    cd /etc && \
    git clone -b ${TWINEPM_BRANCH:-master} https://github.com/furkle/twinepm-server-heroku.git && \
    cd twinepm-server-heroku && \
    sh scripts/getPhing.sh && \
    phing get-logic-dependencies