#!/bin/sh
set -eu

cd /var/www/html

run_as_app_user() {
  if [ "$(id -u)" -eq 0 ]; then
    su-exec www-data "$@"
    return $?
  fi

  "$@"
}

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
  echo "vendor/autoload.php is missing. Run 'composer install' on the host before starting Docker Compose." >&2
  exit 1
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --ansi --force
fi

mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
if [ "$(id -u)" -eq 0 ]; then
  chown -R www-data:www-data storage bootstrap/cache || true
fi

app_env="${APP_ENV:-local}"
if [ "$app_env" = "production" ]; then
  echo "[FIX:runtime-hardening] running runtime migrations for production without artisan migrate --force" >&2
  run_as_app_user php artisan idshka:runtime-migrate --no-interaction
else
  echo "[FIX:runtime-hardening] running database migrations for $app_env runtime" >&2
  run_as_app_user php artisan migrate --no-interaction
fi

if [ "$(id -u)" -eq 0 ]; then
  echo "[FIX:runtime-hardening] dropping privileges to www-data" >&2
  exec su-exec www-data "$@"
fi

echo "[FIX:runtime-hardening] running application process without root privileges" >&2
exec "$@"
