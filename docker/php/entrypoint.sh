#!/bin/sh

set -e

cd /var/www/html

# Install PHP deps into the mounted volume (or vendor volume) if missing
if [ ! -f "vendor/autoload.php" ]; then
	echo "[entrypoint] Installing PHP dependencies (composer install)..."
	composer install --no-interaction --prefer-dist
fi

exec "$@"