#!/usr/bin/env sh
set -e

if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is required (set LARAVEL_APP_KEY in deploy/.env)." >&2
  exit 1
fi

mkdir -p storage bootstrap/cache

php artisan config:cache --no-interaction >/dev/null 2>&1 || true
php artisan route:cache --no-interaction >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

exec "$@"

