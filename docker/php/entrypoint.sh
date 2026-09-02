#!/bin/sh
set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

until php artisan migrate --force; do
    echo "Aguardando o PostgreSQL e as migrations..."
    sleep 2
done

exec "$@"
