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
RUN mkdir -p /etc/nginx/conf.d

COPY --chown=root:root ./docker-concrescent2/entity-size.conf /etc/nginx/conf.d/entity-size.conf
COPY --chown=root:root ./docker-concrescent2/php-upload-size.ini /etc/php82/conf.d/php-upload-size.ini

# Return privileges to unprivileged user after all packages have been installed
USER nobody
WORKDIR /var/www/html

FROM base as dev

VOLUME /var/www/html
VOLUME /srv/host/config.php

FROM base as prod

# Copy concrescent over to the image
COPY --chown=nobody:nobody ./cm2 /var/www/html

VOLUME /var/www/html/config/config.php

EXPOSE 8080
