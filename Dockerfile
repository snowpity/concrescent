FROM trafex/php-nginx:3.8.0 AS base

# Elevate privileges to root for installation of packages
USER root
RUN apk update
# Install the missing php modules
RUN apk add php84-gd php84-mysqli php84-exif php84-pecl-apcu
# Install a mail transfer agent
RUN apk add msmtp && echo 'sendmail_path = "/usr/bin/msmtp -t"' >> /etc/php84/php.ini;

# Create nginx config directory and increase upload size for files via entity-size.conf and php-upload-size.ini

COPY --chown=root:root ./config/nginx/entity-size.conf /etc/nginx/conf.d/
COPY --chown=root:root ./config/php/90-10-common.ini /etc/php84/conf.d/

RUN ln -s /usr/bin/php84 /usr/bin/php

# Return privileges to unprivileged user after all packages have been installed
USER nobody

FROM base AS dev

# Temporary switch to root
USER root
# Install xdebug
RUN apk add --no-cache php84-pecl-xdebug
# Add configuration
COPY --chown=root:root ./config/php/90-40-xdebug.ini /etc/php84/conf.d/
COPY --chown=root:root ./config/php/90-20-dev.ini /etc/php84/conf.d/

# Switch back to non-root user
USER nobody

VOLUME /var/www/html
VOLUME /var/www/vendor
VOLUME /var/www/templates
VOLUME /srv/host/config.php

EXPOSE 8080

FROM base AS prod

USER root

RUN mkdir /var/www/vendor
RUN chown nobody /var/www/vendor
COPY --chown=nobody composer /usr/bin/
COPY --chown=root:root ./config/php/90-20-prod.ini /etc/php84/conf.d/

USER nobody

COPY --chown=nobody composer.json /var/www/
COPY --chown=nobody composer.lock /var/www/
RUN cd /var/www \
    && composer check-platform-reqs \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy concrescent over to the image so the image is standalone.
COPY --chown=nobody ./templates /var/www/templates
COPY --chown=nobody ./cm2 /var/www/html

VOLUME /var/www/html/config/config.php

EXPOSE 8080
