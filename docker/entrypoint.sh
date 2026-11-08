#!/bin/sh
# Entrypoint container: disiapkan untuk Docker lokal, VPS (nginx proxy), dan Render.
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

# 1. Port: Render dan platform lain menyuntikkan $PORT; default 80.
PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 2. Folder runtime harus ada dan bisa ditulis (penting bila storage di-mount sebagai volume).
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/framework/testing storage/logs storage/app/public storage/app/livewire-tmp bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 3. Symlink public/storage untuk gambar sampul dan foto profil.
if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction || echo "[entrypoint] storage:link gagal, lanjut."
fi

# 4. Cache konfigurasi dibuat saat start (bukan saat build) agar membaca env dari platform.
runuser -u www-data -- php artisan config:cache --no-interaction
runuser -u www-data -- php artisan view:cache --no-interaction

# 5. Migrasi opsional. Set RUN_MIGRATIONS=true untuk deploy pertama atau saat ada perubahan skema.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] Menjalankan migrasi database..."
    runuser -u www-data -- php artisan migrate --force --no-interaction
fi

echo "[entrypoint] Siap. Mendengarkan di port ${PORT}."
exec "$@"
