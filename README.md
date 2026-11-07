# Inventaris Stok — PABWE 2026 Praktikum 7 (Laravel 13 & Livewire)

Aplikasi manajemen persediaan barang dengan arsitektur **Feature-First / Clean Architecture**, dibangun dengan **Laravel 13**, **Livewire 4**, **Tailwind CSS (CDN)**, dan **MySQL**. Mendukung autentikasi username/email, role admin/member, CRUD stok, penyesuaian jumlah, gambar sampul, deskripsi Markdown, impor/ekspor Excel, serta pembuatan data stok dengan AI.

> Semua komponen berjalan **lokal**: database MySQL, penyimpanan gambar di `storage/app/public`, dan AI opsional melalui Ollama. Tidak ada layanan cloud yang diperlukan.

---

## Fitur Utama

1. **Autentikasi & Role**
   - Login memakai **username atau email**.
   - Registrasi publik selalu menghasilkan role `member`.
   - Role `admin` dapat membuka **Manajemen Pengguna** (`/admin/users`) dan melihat stok milik pengguna lain dalam mode **read-only** (`/admin/users/{user}/stocks`).
   - Middleware `role:admin`, `owns.stock`, dan `StockPolicy` menjaga otorisasi; admin tidak bisa mengubah/menghapus stok pengguna lain.
   - Rate limit **1000 request/detik**, force HTTPS opsional lewat `FORCE_HTTPS=true`.

2. **Manajemen Stok**
   - CRUD stok: nama, SKU, deskripsi Markdown, jumlah, satuan, harga, dan minimum stok.
   - **Status otomatis** (`StockStatus`): `out_of_stock` bila jumlah 0, `low_stock` bila jumlah ≤ minimum, selain itu `available`. Status tidak pernah diisi manual, dari file, atau dari AI.
   - Penyesuaian cepat jumlah (+/−) dari daftar maupun halaman detail, dengan proteksi agar stok tidak minus.
   - Pencarian nama/SKU, filter status, pagination, kartu ringkasan total/stok menipis/stok habis.

3. **Gambar Sampul & Markdown**
   - Unggah/ganti/hapus gambar sampul (maks. 5 MB) pada halaman detail stok.
   - Editor deskripsi dengan tab **Tulis / Pratinjau** (marked + DOMPurify), dan render Markdown yang disanitasi di halaman detail.

4. **Excel Import & Export**
   - Ekspor `.xlsx` dengan header: `ID, Nama, SKU, Deskripsi, Qty, Satuan, Harga, Min Stok, Status`.
   - Impor `.xlsx/.xls/.csv`; kolom qty wajib bilangan bulat ≥ 0, satuan default `pcs`, minimum stok default `5`, status dari file diabaikan dan dihitung ulang. Baris invalid dilaporkan tanpa menggagalkan seluruh impor.

5. **Generate Stok dengan AI**
   - Prompt bahasa Indonesia, jumlah item 1–10, lalu pratinjau dan pilih item sebelum disimpan.
   - Kompatibel dengan OpenAI atau endpoint lain yang kompatibel (`OPENAI_ENDPOINT`, `OPENAI_KEY`, `OPENAI_MODEL`).

6. **Profil**
   - Ubah nama, username, email, dan password (verifikasi password lama).
   - Foto profil dengan crop 1:1 (Cropper.js, GD), atau hapus foto.

7. **Pengujian**
   - **131 tes, 483 assertion, 100% line coverage pada `app/`** (PHPUnit 12 + Xdebug).

---

## Struktur Direktori

```
app/
├── Features/
│   ├── Admin/
│   │   ├── Livewire/{AdminUserStockList.php, UserList.php}
│   │   └── Services/AdminUserService.php
│   ├── Ai/
│   │   ├── Contracts/AiClientInterface.php
│   │   ├── DTO/GeneratedStockItem.php
│   │   └── Services/OpenAiStockGeneratorService.php
│   ├── Auth/
│   │   ├── Actions/{LoginUserAction.php, RegisterUserAction.php}
│   │   └── Livewire/{Login.php, Register.php}
│   ├── Profile/
│   │   ├── Livewire/Profile.php
│   │   └── Services/ProfileService.php
│   └── Stock/
│       ├── Contracts/StockRepositoryInterface.php
│       ├── Enums/StockStatus.php
│       ├── Livewire/{StockDetail.php, StockList.php}
│       ├── Models/Stock.php
│       ├── Policies/StockPolicy.php
│       ├── Repositories/StockRepository.php
│       └── Services/{StockExcelService.php, StockService.php}
├── Http/Middleware/{EnsureRole.php, EnsureStockOwnership.php}
├── Models/User.php
└── Providers/AppServiceProvider.php
```

---

## Konfigurasi `.env`

```ini
APP_NAME="Inventaris Stok"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=id

FORCE_HTTPS=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pabwe_p7_todolist
DB_USERNAME=root
DB_PASSWORD=1234567

OPENAI_ENDPOINT=https://api.openai.com/v1
OPENAI_KEY=
OPENAI_MODEL=gpt-4o-mini
OPENAI_TIMEOUT=30
```

> `OPENAI_TIMEOUT` adalah batas waktu request dalam detik. Untuk model lokal yang lambat, naikkan misalnya menjadi `120`.

### Model lokal (Ollama)

Aplikasi memakai API chat-completions yang kompatibel dengan OpenAI, sehingga Ollama bisa dipakai tanpa perubahan kode:

```ini
OPENAI_ENDPOINT=http://127.0.0.1:11434/v1
OPENAI_KEY=ollama
OPENAI_MODEL=gemma4:e4b
OPENAI_TIMEOUT=120
```

```bash
ollama pull gemma4:e4b
php artisan config:clear
```

Model `gemma4:e4b` (6,1 GB) pada pengujian membutuhkan sekitar 25 detik untuk satu item dan 45 detik untuk dua item, karena itu timeout bawaan 30 detik perlu dinaikkan.

> Fitur AI memerlukan endpoint/key yang valid. Tanpa konfigurasi, aplikasi tetap berjalan normal; hanya tombol Generate AI yang menampilkan pesan error.

---

## Instalasi

> ZIP proyek tidak menyertakan folder `vendor/` agar ukurannya kecil. Jalankan `composer install` lebih dulu.

```bash
composer install
cp .env.example .env
php artisan key:generate
# buat database MySQL:
# CREATE DATABASE pabwe_p7_todolist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

> Di Windows, `public/storage` dibuat sebagai junction. Router `server.php` di root proyek diperlukan agar `php artisan serve` tetap dapat menyajikan gambar sampul melalui route `storage.public`; jangan hapus file tersebut.

Buka `http://localhost:8000`.

**Akun bawaan seeder:**

| Role   | Username | Email               | Password |
|--------|----------|---------------------|----------|
| Admin  | `admin`  | `admin@example.com` | `admin`  |
| Member | `member` | `member@example.com`| `member` |

Seeder juga membuat empat contoh stok (tersedia, menipis, habis) untuk akun member.

---

## Pengujian & Coverage

```bash
# Windows PowerShell
$env:XDEBUG_MODE='coverage'; php artisan test --coverage --min=100

# Linux/macOS
XDEBUG_MODE=coverage php artisan test --coverage --min=100
```

Hasil saat ini:

```text
Tests: 131 passed (483 assertions)
Total: 100.0 % (26/26 classes)
```

Laporan coverage dibuat ulang otomatis setiap perintah di atas dijalankan (`coverage.txt` dan `coverage-html/`).

---

## Catatan Teknis

- `stock.status` disimpan sebagai string enum dan selalu dihitung ulang oleh `StockService`/`StockExcelService` melalui `StockStatus::calculate()`.
- Semua rute berada di bawah middleware `throttle:1000-per-second`.
- Tidak ada pipeline CI/CD atau Jenkinsfile pada proyek ini.
