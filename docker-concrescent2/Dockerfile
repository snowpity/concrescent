# Use nginx & PHP8.1-FMP image by trafex
FROM trafex/php-nginx:latest

# Elevate privileges to root for installation of packages
USER root
RUN apk update
# Install the missing php modules
RUN apk add php81-gd php81-mysqli php81-exif
# Install a mail transfer agent
RUN apk add msmtp && \
    echo 'sendmail_path = "/usr/bin/msmtp -t"' >> /etc/php81/php.ini;
# Create nginx config directory and increase upload size for files via entity-size.conf and php-upload-size.ini
RUN mkdir /etc/nginx/conf.d
COPY --chown=root:root ./docker-concrescent2/entity-size.conf /etc/nginx/conf.d/entity-size.conf
COPY --chown=root:root ./docker-concrescent2/php-upload-size.ini /etc/php81/conf.d/php-upload-size.ini

# Return privileges to unprivileged user after all packages have been installed
USER nobody

# Copy concrescent over to the image
COPY --chown=nobody:nobody ./cm2 /var/www/html

EXPOSE 8080
