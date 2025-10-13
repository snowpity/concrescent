FROM trafex/php-nginx:3.8.0 AS base

# Elevate privileges to root for installation of packages
USER root
RUN apk update
# Install the missing php modules
RUN apk add \
    php84-ctype \
    php84-mbstring \
    php84-iconv \
    php84-gd \
    php84-exif \
    php84-pecl-apcu \
    php84-pdo \
    php84-mysqli php84-pdo_mysql \
    php84-sqlite3 php84-pdo_sqlite
    #php84-pgsql php84-pdo_pgsql
# Install a mail transfer agent
RUN apk add msmtp && echo 'sendmail_path = "/usr/bin/msmtp -t"' >> /etc/php84/php.ini;

# Create nginx config directory and increase upload size for files via entity-size.conf and php-upload-size.ini

COPY --chown=root:root ./build/config/nginx/entity-size.conf /etc/nginx/conf.d/
COPY --chown=root:root ./build/config/php/90-10-common.ini /etc/php84/conf.d/

RUN ln -s /usr/bin/php84 /usr/bin/php

RUN rm -rf /var/www/html \
  && ln -s /srv/app/cm2 /var/www/html

# Return privileges to unprivileged user after all packages have been installed
USER nobody

FROM base AS dev

# Temporary switch to root
USER root
# Install xdebug
RUN apk add --no-cache php84-pecl-xdebug
# Add configuration
COPY --chown=root:root ./build/config/php/90-40-xdebug.ini /etc/php84/conf.d/
COPY --chown=root:root ./build/config/php/90-20-dev.ini /etc/php84/conf.d/

# Switch back to non-root user
USER nobody

VOLUME /srv/app/cm2
VOLUME /srv/app/src
VOLUME /srv/app/bin
VOLUME /srv/app/vendor
VOLUME /srv/app/templates
VOLUME /srv/app/var/log
VOLUME /srv/app/config/concrescent.php
VOLUME /srv/app/.env
VOLUME /srv/app/.env.dev
VOLUME /srv/app/.env.dev.local

EXPOSE 8080

FROM base AS prod

USER root

RUN mkdir /srv/app/vendor /srv/app/var \
  && chown nobody /srv/app/vendor /srv/app/var
COPY --chown=nobody composer /usr/bin/
COPY --chown=root:root ./build/config/php/90-20-prod.ini /etc/php84/conf.d/

USER nobody

COPY --chown=nobody composer.json /srv/app/
COPY --chown=nobody composer.lock /srv/app/
# Copy concrescent over to the image so the image is standalone.
COPY --chown=nobody ./templates /srv/app/templates
COPY --chown=nobody ./cm2 /srv/app/cm2
COPY --chown=nobody ./src /srv/app/src
COPY --chown=nobody ./bin /srv/app/bin

RUN cd /srv/app \
    && composer check-platform-reqs \
    && composer install --no-dev --classmap-authoritative --no-interaction --no-progress

VOLUME /srv/app/var/log
VOLUME /srv/app/config/concrescent.php
VOLUME /srv/app/.env
VOLUME /srv/app/.env.prod
VOLUME /srv/app/.env.prod.local

EXPOSE 8080
