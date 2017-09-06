FROM nginx:1.13

WORKDIR ${PWD}

COPY web /etc/twinepm-server-heroku/web
COPY scripts/getPhing /etc/twinepm-server-heroku/scripts/getPhing
COPY build.xml /etc/twinepm-server-heroku/build.xml