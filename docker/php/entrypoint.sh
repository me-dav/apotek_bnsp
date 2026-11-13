#!/bin/sh
# Dijalankan setiap container app start, sebelum php-fpm.
set -e

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY kosong. Buat dengan: docker compose run --rm app php artisan key:generate --show" >&2
    exit 1
fi

# Konfigurasi di-cache dari environment variable container
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    # CLI memakai akun owner (DB_USERNAME); aplikasi web memakai akun per peran
    php artisan migrate --force
    # GRANT/REVOKE wajib dijalankan ulang setiap ada migrasi baru
    php artisan db:roles
fi

if [ "${RUN_SEED:-false}" = "true" ]; then
    # Hanya untuk data contoh/demo. Jangan aktif di produksi berisi data nyata.
    php artisan db:seed --force
fi

exec "$@"
