FROM nginx:1.13

# Set the locale.
RUN \
    apt-get update && \
    apt-get install -y locales && \
    locale-gen en_US.UTF-8
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

# Install the dependencies.
RUN \
    apt-get update && \
    apt-get install -y \
        gnupg \
    add-apt-repository ppa:ondrej/php && \
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