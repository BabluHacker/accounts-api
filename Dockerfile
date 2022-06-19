FROM webdevops/php-nginx:8.0-alpine

# Application root directory
ARG ARG_APPLICATION_PATH=/app
ENV APPLICATION_PATH ${ARG_APPLICATION_PATH}

# Domain name of the server
ARG ARG_WEB_ALIAS_DOMAIN=localhost
ENV WEB_ALIAS_DOMAIN ${ARG_WEB_ALIAS_DOMAIN}

# Add package for pgsql
RUN apk add php8-pdo_pgsql

# Accept composer version (1 or 2) as build arg, default 2
ARG ARG_COMPOSER_VERSION=2
ENV COMPOSER_VERSION ${ARG_COMPOSER_VERSION}

ENV WEB_DOCUMENT_ROOT=${APPLICATION_PATH}/public \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=127.0.0.1:9000
ENV SERVICE_NGINX_CLIENT_MAX_BODY_SIZE="50m"

WORKDIR ${APPLICATION_PATH}
COPY . ${APPLICATION_PATH}
RUN chown -R application:www-data ${APPLICATION_PATH}

# Install crontab for laravel scheduler
RUN echo "* * * * * /usr/local/bin/php ${APPLICATION_PATH}/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/root

# Install composer dependencies
USER application
RUN [ ${COMPOSER_VERSION} = "1" ] && composer1 install --ignore-platform-reqs || composer install --ignore-platform-reqs

EXPOSE 80 443
