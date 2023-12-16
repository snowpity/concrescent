# Use nginx & PHP8.1-FMP image by trafex
FROM trafex/php-nginx:3.4.0 as base

# Elevate privileges to root for installation of packages
USER root
RUN apk update
# Install the missing php modules
RUN apk add php82-gd php82-mysqli php82-exif
# Install a mail transfer agent
RUN apk add msmtp && echo 'sendmail_path = "/usr/bin/msmtp -t"' >> /etc/php82/php.ini;

# Create nginx config directory and increase upload size for files via entity-size.conf and php-upload-size.ini

COPY --chown=root:root ./config/entity-size.conf /etc/nginx/conf.d/
COPY --chown=root:root ./config/php-upload-size.ini /etc/php82/conf.d/

# Return privileges to unprivileged user after all packages have been installed
USER nobody

FROM base as dev

# Temporary switch to root
USER root
# Install xdebug
RUN apk add --no-cache php82-pecl-xdebug
# Add configuration
COPY --chown=root:root ./config/xdebug.ini /etc/php82/conf.d/
COPY --chown=root:root ./config/php-dev.ini /etc/php82/conf.d/

# Switch back to non-root user
USER nobody

VOLUME /var/www/html
VOLUME /srv/host/config.php

FROM base as prod

# Copy concrescent over to the image
COPY --chown=nobody:nobody ./cm2 /var/www/html

VOLUME /var/www/html/config/config.php

EXPOSE 8080
