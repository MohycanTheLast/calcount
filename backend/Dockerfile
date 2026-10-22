ARG PHP_VERSION
FROM registry.gitlab.finforce.cz/finforce/php-containers/php:${PHP_VERSION} AS app

COPY backend /var/www/html/

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_NO_DEV=1
ENV APP_ENV=prod

RUN composer install --no-interaction --no-progress

ENV PUBLIC_DIR="www-root"
# Ensure that cache, log and session directories are writable
RUN chown -R www-data:www-data /var/www/html/var
