# Menjalankan Aplikasi dengan Docker

Image ini berisi PHP 8.4 + Apache. Aplikasi dan dependensinya sudah ada di dalam image, sedangkan rahasia (APP_KEY, password database, API key) **tidak** ikut di-build. Semuanya diisi lewat environment variable saat container dijalankan.

Container mendengarkan di port `80` secara default, atau di `$PORT` bila platform (misalnya Render) menyuntikkannya.

## 1. Build image

```bash
docker build -t inventaris-stok-p7 .
```

## 2. Jalankan secara lokal

Buat file `.env.docker` (jangan di-commit) berisi nilai produksi, lalu:

```bash
docker run -d --name inventaris-stok \
  --env-file .env.docker \
  --add-host=host.docker.internal:host-gateway \
  -p 4001:80 \
  inventaris-stok-p7
```

Buka `http://localhost:4001`. Port `4001` sesuai dengan konfigurasi proxy nginx di server (`127.0.0.1:4001`).

Bila database MySQL berjalan di mesin host, gunakan `DB_HOST=host.docker.internal` (container tidak mengenali `127.0.0.1` sebagai mesin host).

## 3. Environment variables

| Variable | Wajib | Contoh | Keterangan |
|---|---|---|---|
| `APP_KEY` | Ya | `base64:...` | Hasilkan dengan `php artisan key:generate --show`. Tanpa ini aplikasi error 500. |
| `APP_URL` | Ya | `https://ifs24011-pabwe2026-sk-p7-laravel-livewire.s1if.cloud` | Gunakan `https://` di produksi. |
| `APP_ENV` | Tidak | `production` | Sudah default di image. |
| `APP_DEBUG` | Tidak | `false` | Jangan set `true` di produksi. |
| `DB_CONNECTION` | Ya | `mysql` | |
| `DB_HOST` | Ya | `db.example.com` | Harus bisa dijangkau dari container. |
| `DB_PORT` | Ya | `3306` | |
| `DB_DATABASE` | Ya | `pabwe_p7_todolist` | |
| `DB_USERNAME` | Ya | | |
| `DB_PASSWORD` | Ya | | |
| `MYSQL_ATTR_SSL_CA` | Tidak | `/var/www/html/database/certs/ca.pem` | Hanya bila database mewajibkan TLS. |
| `RUN_MIGRATIONS` | Tidak | `true` | Set `true` untuk deploy pertama (membuat tabel). Setelah berhasil, boleh diubah ke `false`. |
| `TRUSTED_PROXIES` | Tidak | `*` | Default `*`, cocok di belakang nginx/Render. Isi dengan IP proxy (dipisah koma) bila ingin lebih ketat. |
| `OPENAI_ENDPOINT` | Tidak | `https://api.openai.com/v1` | Fitur "Generate AI". `127.0.0.1:11434` (Ollama lokal) tidak bisa dijangkau dari container. |
| `OPENAI_KEY` | Tidak | | |
| `OPENAI_MODEL` | Tidak | `gpt-4o-mini` | |
| `OPENAI_TIMEOUT` | Tidak | `30` | |
| `PORT` | Tidak | `80` | Diisi otomatis oleh Render. |

Default yang sudah ada di image: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=local`.

## 4. Deploy di Render

1. **New +** → **Web Service** → pilih repo.
2. **Runtime**: `Docker`. **Dockerfile Path**: `./Dockerfile`.
3. **Health Check Path**: `/up`.
4. Isi **Environment** sesuai tabel di atas. Untuk deploy pertama, set `RUN_MIGRATIONS=true`.
5. Setelah status **Live** dan migrasi berhasil, ubah `RUN_MIGRATIONS` ke `false`.

Catatan:
- Paket gratis Render tidak punya disk persisten. Gambar sampul dan foto profil yang diunggah akan hilang saat container restart. Gunakan paket berbayar dengan disk, mount ke `/var/www/html/storage`.
- Database MySQL harus bisa diakses dari internet (atau dari IP Render), dan `DB_HOST` harus berupa host publik, bukan `127.0.0.1`.

## 5. Deploy di server s1if.cloud (nginx sebagai reverse proxy)

Container dijalankan dengan `-p 4001:80`, lalu nginx meneruskan request ke `http://127.0.0.1:4001`. Pastikan blok `location /` di nginx meneruskan header berikut agar Laravel mengenali HTTPS:

```nginx
location / {
    proxy_pass http://127.0.0.1:4001;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;
}
```

Perbarui container setelah perubahan kode:

```bash
docker build -t inventaris-stok-p7 . \
  && docker rm -f inventaris-stok \
  && docker run -d --name inventaris-stok --env-file .env.docker \
       --add-host=host.docker.internal:host-gateway -p 4001:80 inventaris-stok-p7
```

## 6. Memeriksa masalah

```bash
docker logs -f inventaris-stok        # log aplikasi dan Apache (LOG_CHANNEL=stderr)
curl -i http://127.0.0.1:4001/up      # harus 200 bila aplikasi sehat
```

| Gejala | Penyebab umum |
|---|---|
| 500 tanpa detail | `APP_KEY` kosong atau tidak valid. Cek `docker logs`. |
| `SQLSTATE[HY000] [2002]` | `DB_HOST` salah, atau database tidak bisa dijangkau dari container. |
| `Base table or view not found` | Migrasi belum dijalankan. Set `RUN_MIGRATIONS=true` lalu restart container. |
| `SSL` / certificate error ke database | `MYSQL_ATTR_SSL_CA` salah path, atau file CA tidak ada di image. |
| Redirect loop atau URL `http://` | Pastikan nginx meneruskan `X-Forwarded-Proto`, dan `APP_URL` memakai `https://`. |
| Upload gambar gagal | Batas unggah 10 MB (`docker/php.ini`). Cek juga izin folder `storage`. |
