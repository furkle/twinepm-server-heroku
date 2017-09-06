FROM redis:3.2

WORKDIR ${PWD}

COPY cache /etc/twinepm-server-heroku/cache
COPY scripts/getPhing /etc/twinepm-server-heroku/scripts/getPhing
COPY build.xml /etc/twinepm-server-heroku/build.xml