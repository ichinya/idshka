#!/bin/sh
set -eu

cd /var/www/html

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

mkdir -p storage/logs bootstrap/cache
if [ "$(id -u)" -eq 0 ]; then
  chown -R www-data:www-data storage bootstrap/cache || true
  command_string="$*"
  echo "[FIX:runtime-hardening] dropping privileges to www-data" >&2
  exec su -p -s /bin/sh www-data -c "exec $command_string"
fi

echo "[FIX:runtime-hardening] running application process without root privileges" >&2
exec "$@"
