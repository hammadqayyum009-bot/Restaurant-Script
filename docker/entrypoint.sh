#!/bin/sh
set -e

PORT="${PORT:-80}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# Logos, favicons and dish photos uploaded from the admin panel land here.
mkdir -p public/uploads/branding public/uploads/menu public/uploads/content
touch database/database.sqlite
chown -R www-data:www-data storage bootstrap/cache database public/uploads 2>/dev/null || true

exec "$@"
