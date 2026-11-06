# Deploy ke Render (Docker)

## 1. Siapkan repo GitHub

```bash
git init
git add .
git commit -m "Inventaris Stok: konfigurasi Aiven PostgreSQL + Docker"
git branch -M main
git remote add origin https://github.com/<user>/<repo>.git
git push -u origin main
```

Pastikan `.env` **tidak** ikut ter-commit (sudah di `.gitignore`). `database/certs/aiven-ca.pem` harus ikut.

## 2. Buat Web Service di Render

1. Render Dashboard → **New +** → **Web Service** → pilih repo.
2. **Runtime**: `Docker`. **Dockerfile Path**: `./Dockerfile`.
3. **Instance type**: Free (tanpa disk, upload hilang saat restart) atau paket berbayar (dengan disk, lihat langkah 4).
4. **Health Check Path**: `/up`.

## 3. Isi environment variables

Isi di tab **Environment**. Ganti yang berada dalam `<...>`.

| Key | Value |
|---|---|
| `APP_NAME` | `Inventaris Stok` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:...` (lihat catatan di bawah) |
| `APP_URL` | `https://<nama-service>.onrender.com` |
| `APP_LOCALE` | `id` |
| `APP_FALLBACK_LOCALE` | `en` |
| `BCRYPT_ROUNDS` | `12` |
| `FORCE_HTTPS` | `true` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `SESSION_DRIVER` | `database` |
| `SESSION_LIFETIME` | `120` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_ENCRYPT` | `false` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `local` |
| `BROADCAST_CONNECTION` | `log` |
| `MAIL_MAILER` | `log` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `pabwe-ifs24011-pabwe2026-sk-p7-laravel-livewire.j.aivencloud.com` |
| `DB_PORT` | `13167` |
| `DB_DATABASE` | `defaultdb` |
| `DB_USERNAME` | `avnadmin` |
| `DB_PASSWORD` | `<password dari Aiven>` |
| `DB_SSLMODE` | `verify-full` |
| `DB_SSLROOTCERT` | `/var/www/html/database/certs/aiven-ca.pem` |
| `RUN_MIGRATIONS` | `true` (hanya untuk deploy pertama, lalu ubah ke `false`) |
| `OPENAI_ENDPOINT` | `https://api.openai.com/v1` (atau kosongkan) |
| `OPENAI_KEY` | `<API key OpenAI>` (kosongkan jika tidak dipakai) |
| `OPENAI_MODEL` | `gpt-4o-mini` |
| `OPENAI_TIMEOUT` | `30` |

**Catatan `APP_KEY`:** Anda bisa memakai nilai dari `.env` lokal (`base64:3AudoIDT/...`). Untuk produksi, lebih baik buat yang baru dengan `php artisan key:generate --show` lalu simpan nilainya.

**Catatan Ollama:** `OPENAI_ENDPOINT` lokal (`127.0.0.1:11434`) tidak bisa dijangkau dari Render. Fitur Generate AI perlu endpoint publik seperti OpenAI.

## 4. Penyimpanan upload (opsional, paket berbayar)

Foto profil, gambar sampul, dan impor Excel disimpan di `storage/`. Di paket gratis, file ini hilang setiap restart.

Untuk menyimpannya permanen: **Disks** → **Add Disk**, **Mount Path** `/var/www/html/storage`, ukuran 1 GB. Entrypoint akan membuat ulang folder dan symlink `public/storage` setiap boot.

## 5. Deploy dan migrasi

1. Klik **Create Web Service**. Build pertama memakan beberapa menit.
2. Setelah status **Live**, cek `https://<nama-service>.onrender.com/up`. Harus menampilkan halaman sehat.
3. Akun contoh (opsional): buka **Shell** di Render (tersedia di paket berbayar) lalu jalankan `php artisan db:seed`.
4. Setelah migrasi berhasil, ubah `RUN_MIGRATIONS` ke `false` agar tidak dijalankan setiap boot. Migrasi bersifat idempotent, jadi membiarkannya `true` juga aman, hanya sedikit lebih lambat.

## 6. Cek cepat

- Halaman login terbuka dengan HTTPS dan tanpa redirect loop.
- Koneksi DB: login berhasil dan daftar stok muncul.
- Log: tab **Logs** di Render (karena `LOG_CHANNEL=stderr`).

## Troubleshooting

| Gejala | Penyebab umum |
|---|---|
| `SSL error: certificate verify failed` | `DB_SSLROOTCERT` salah path, atau file `aiven-ca.pem` tidak ter-commit |
| `no pg_hba.conf entry` / connection refused | Host atau port Aiven salah, atau IP Render belum diizinkan di Aiven (cek Allowed IP addresses) |
| Redirect loop | Pastikan `FORCE_HTTPS=true` dan `APP_URL` memakai `https://` |
| Halaman error 500 tanpa detail | Set `APP_DEBUG=true` sementara untuk melihat pesan error, lalu kembalikan ke `false` |
| Upload gagal setelah restart | Belum memasang disk (langkah 4) |
