#!/bin/sh
set -eu

umask 027
if [ ! -f .env ]; then
    cp .env.example .env
fi
chown www-data:www-data .env
chmod 600 .env

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
fi
chown -R www-data:www-data storage bootstrap/cache
chown www-data:www-data "$(dirname "$DB_DATABASE")" "$DB_DATABASE"
su -s /bin/sh www-data -c 'php artisan migrate --force --no-interaction'

exec apache2-foreground
