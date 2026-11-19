FROM composer:2 AS composer_binary

FROM php:8.5-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ffmpeg inotify-tools python3-venv unzip \
    && rm -r /var/lib/apt/lists/* \
    && a2enmod rewrite \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Options -Indexes +FollowSymLinks' '</Directory>' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

RUN python3 -m venv /opt/graphify \
    && /opt/graphify/bin/pip install --no-cache-dir graphifyy==0.9.109

RUN printf 'upload_max_filesize=6M\npost_max_size=8M\n' > /usr/local/etc/php/conf.d/sebra-audio.ini

COPY --from=composer_binary /usr/bin/composer /usr/local/bin/composer

ARG APP_UID=1000
ARG APP_GID=1000
RUN groupmod -g "$APP_GID" www-data \
    && usermod -u "$APP_UID" -g "$APP_GID" www-data \
    && php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);'

COPY --chmod=0755 start-laravel.sh /usr/local/bin/start-laravel

WORKDIR /var/www/html
CMD ["start-laravel"]
