#!/bin/sh

set -e

cd /var/www/html

# Install PHP deps into the mounted volume (or vendor volume) if missing or if phpunit is missing
if [ ! -f "vendor/autoload.php" ] || [ ! -f "vendor/bin/phpunit" ]; then
	echo "[entrypoint] Installing PHP dependencies (composer install)..."
	composer install --no-interaction --prefer-dist
fi

echo "[entrypoint] Running unit and integration tests..."
./vendor/bin/phpunit

exec "$@"