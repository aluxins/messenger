FROM php:8.1.14-cli-alpine

RUN cat /etc/issue

# Add basics first.
RUN apk update && apk upgrade && apk add bash curl ca-certificates openssl openssh git nano libxml2-dev tzdata icu-dev openntpd libedit-dev libzip-dev libjpeg-turbo-dev libpng-dev freetype-dev autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c pcre-dev libffi-dev libressl-dev libevent-dev zlib-dev libtool automake supervisor

COPY extension /tmp/extension
WORKDIR /tmp/extension
RUN chmod +x install.sh \
    && sh install.sh \
    && rm -rf /tmp/extension

RUN php -m

# Add Composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Configure PHP
COPY config/php.ini /usr/local/etc/php/conf.d/20-custom.ini

# Configure supervisord
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /run

# Setup document root
RUN mkdir -p /app

# Make the document root a volume
VOLUME /app

#echo " > /usr/local/etc/php/conf.d/phalcon.ini
# Switch to use a non-root user from here on
USER root

# Add application
WORKDIR /app

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]