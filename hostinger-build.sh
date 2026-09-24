#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/web"

composer install --no-dev --prefer-dist --optimize-autoloader

php artisan optimize:clear
php artisan storage:link || true
php artisan migrate --force
php artisan optimize

chmod -R 775 storage bootstrap/cache
