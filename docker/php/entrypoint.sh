#!/bin/sh
set -e

cd /var/www/html

if [ ! -d vendor ]; then
    composer install --no-interaction
fi

if [ -f .env ] && ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate
fi

exec "$@"
