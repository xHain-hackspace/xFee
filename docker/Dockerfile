# Install PHP dependencies
FROM composer:2 AS dependencies

WORKDIR /app

COPY authentik-integration/composer.json authentik-integration/composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader


# Build production image
FROM wordpress:php8.3-apache

# Copy the complete theme into the WordPress installation
COPY --chown=www-data:www-data \
    xfee_theme/ \
    /var/www/html/wp-content/themes/xfee_theme/

# Copy the custom Authentik MU plugin
COPY --chown=www-data:www-data \
    authentik-integration/mu-plugins/ \
    /var/www/html/wp-content/mu-plugins/

# Copy OpenID Connect dependencies
COPY --from=dependencies --chown=www-data:www-data \
    /app/mu-plugins/xfee-authentik/vendor/ \
    /var/www/html/wp-content/mu-plugins/xfee-authentik/vendor/