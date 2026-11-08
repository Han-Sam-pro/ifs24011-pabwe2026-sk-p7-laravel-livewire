# Deploy ke server (ifs24011-pabwe2026-sk-p7-laravel-livewire.s1if.cloud)

Pipeline Jenkins mengecualikan `.env` dari paket deploy, jadi `.env` harus dibuat manual di server.

Syarat server:
- PHP **>= 8.4.1** (dependensi Laravel 13 / Livewire 4 tidak bisa jalan di PHP 8.3)
- Ekstensi: pdo_mysql, mbstring, xml, curl, zip, gd, fileinfo, bcmath, exif, intl, openssl, tokenizer
- Composer 2, MySQL/MariaDB

Langkah:

```bash
cd /home/delcom/app/ifs24011-pabwe2026-sk-p7-laravel-livewire.s1if.cloud

composer install --no-dev --optimize-autoloader --no-interaction

cp .env.production.example .env        # lalu isi nilai <...>
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

chmod -R ug+rwX storage bootstrap/cache
```

Aplikasi harus berjalan di `127.0.0.1:4001` karena nginx meneruskan request ke sana. Contoh:

```bash
php artisan serve --host=127.0.0.1 --port=4001
```
(Untuk produksi lebih baik pakai PHP-FPM atau Supervisor agar proses tidak mati.)

Setiap kali `.env` diubah: `php artisan config:clear && php artisan config:cache`.

Jika masih 500: cek `storage/logs/laravel.log` (log Laravel). File `logs/error.log` dari nginx hanya mencatat error proxy.
