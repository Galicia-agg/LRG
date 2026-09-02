#!/bin/sh
set -e

echo "Esperando la base de datos..."
attempts=0
until php artisan db:show > /dev/null 2>&1; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge 30 ]; then
        echo "No se pudo conectar a la base de datos tras 30 intentos." >&2
        exit 1
    fi
    sleep 2
done

php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
