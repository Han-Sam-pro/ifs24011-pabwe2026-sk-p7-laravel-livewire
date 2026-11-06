# Deployment: Vercel + Aiven MySQL + Cloudflare R2

Catatan integrasi untuk aplikasi Inventaris Stok.

## 1. Vercel

Login (wajib interaktif, OAuth GitHub):

```powershell
vercel login
```

Pilih **GitHub**, selesaikan login di browser, lalu cek:

```powershell
vercel whoami
```

## 2. Aiven MySQL (free tier)

1. Daftar di <https://console.aiven.io/signup> (tanpa kartu kredit).
2. **Create service** → **MySQL** → pilih **Free plan** → region terdekat (Singapore).
3. Tunggu status **Running**.
4. Buka **Overview**, catat:
   - Host
   - Port
   - User (`avnadmin`)
   - Password
   - Database (`defaultdb`)
5. Buat database aplikasi (opsional, disarankan): tab **Databases** → `pabwe_p7_todolist`.
6. Unduh **CA certificate** dari halaman service.

Simpan CA sebagai `certs/aiven-ca.pem` di root proyek. File ini akan dipakai lewat env:

```dotenv
MYSQL_ATTR_SSL_CA=/app/certs/aiven-ca.pem
```

> Free tier Aiven bisa dimatikan otomatis jika tidak ada aktivitas. Nyalakan lagi dari console bila aplikasi gagal konek setelah lama idle.

## 3. Cloudflare R2 (gambar permanen)

1. Buka **R2** di dashboard Cloudflare → **Create bucket** (`pabwe-stock`).
2. **Settings → Public access** → aktifkan **r2.dev**.
3. **Manage R2 API Tokens** → **Create token** → izin **Object Read & Write**, batasi ke bucket tersebut.
4. Catat:
   - Endpoint: `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`
   - Bucket
   - Access Key ID
   - Secret Access Key
   - Public URL: `https://pub-<...>.r2.dev`

## 4. Environment Vercel

```text
APP_NAME="Inventaris Stok"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<dari .env lokal>
APP_URL=https://<project>.vercel.app
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=<aiven host>
DB_PORT=<aiven port>
DB_DATABASE=pabwe_p7_todolist
DB_USERNAME=avnadmin
DB_PASSWORD=<aiven password>
MYSQL_ATTR_SSL_CA=/app/certs/aiven-ca.pem

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr

PUBLIC_DISK_DRIVER=s3
PUBLIC_DISK_URL=https://pub-<...>.r2.dev
AWS_ACCESS_KEY_ID=<r2 key>
AWS_SECRET_ACCESS_KEY=<r2 secret>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=pabwe-stock
AWS_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true

OPENAI_ENDPOINT=<endpoint cloud>
OPENAI_KEY=<key cloud>
OPENAI_MODEL=<model cloud>
OPENAI_TIMEOUT=120
```

## 5. Migrasi database cloud

Dari laptop, dengan `.env` diarahkan ke Aiven:

```powershell
php artisan config:clear
php artisan migrate:fresh --seed --force
```

## 6. Deploy

```powershell
vercel --prod
```

Setelah deploy, uji: login admin/admin, tambah stok, upload cover, ekspor Excel, dan buka gambar cover setelah redeploy.
