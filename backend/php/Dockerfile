FROM php:8.2-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    libpng-dev zip unzip wget zlib1g-dev libicu-dev libzip-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev libxpm-dev \
    libfreetype6-dev \
    git \
    python3 \
    nodejs npm \
    && docker-php-ext-install mysqli pdo_mysql intl gd zip

RUN npm install --global yarn

# Change Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/www-root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
# Enable Apache mod rewrite and restart Apache
RUN a2enmod rewrite headers && service apache2 restart

# Change system timezone
RUN cp /usr/share/zoneinfo/Europe/Prague /etc/localtime && \
    echo "Europe/Prague" > /etc/timezone

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker-php.ini /usr/local/etc/php/conf.d/
