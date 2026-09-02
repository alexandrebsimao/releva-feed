#!/bin/sh
set -eu

until php artisan migrate --force; do
    echo "Aguardando o PostgreSQL e as migrations..."
    sleep 2
done

exec "$@"
