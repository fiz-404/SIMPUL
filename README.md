# SIMPUL — Sistem Informasi Manajemen Pesanan Usaha Lokal (Mom's Cake)

SIMPUL adalah backend API untuk mengelola pesanan toko kue **Mom's Cake**: autentikasi admin, manajemen pesanan dan pelanggan, hingga ekstraksi otomatis data pesanan dari chat WhatsApp melalui AI.

## Tech Stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel (framework `^13.17` di composer.json) + Sanctum untuk API auth |
| PHP | >= 8.3 |
| Database | PostgreSQL (tabel `admin`, `public."User"`, `pesanan`) |
| AI | Groq API (chat completions, OpenAI-compatible) — default `llama-3.3-70b-versatile`, aktif via env `GROQ_MODEL` |
| Frontend | Vue.js (repositori terpisah) |

> Catatan: proyek ini dibangun di atas skeleton Laravel terbaru; nama class tetap `App\Models\...` standar Laravel.

## Instalasi & Setup Lokal

```bash
# 1. Install dependency
composer install

# 2. Siapkan environment
cp .env.example .env
php artisan key:generate

# 3. Atur koneksi PostgreSQL di .env
#    DB_CONNECTION=pgsql
#    DB_HOST=127.0.0.1
#    DB_PORT=5432
#    DB_DATABASE=SIMPUL
#    DB_USERNAME=postgres
#    DB_PASSWORD=...
#    GROQ_API_KEY=gsk_...       (dari console.groq.com)
#    GROQ_MODEL=openai/gpt-oss-120b   # model yang tersedia di akun Anda

# 4. Schema database + data awal
php artisan migrate --seed
#    (utan admin 'admin' / 'admin123' dibuat oleh DatabaseSeeder)
#    Alternatif: import skema manual dari docs/momcake.sql, lalu php artisan db:seed

# 5. Jalankan server
php artisan serve
```

## API Endpoints

Semua endpoint protected wajib mengirim header:
```
Authorization: Bearer <token>
```

| Method | Endpoint | Auth | Fungsi |
|---|---|---|---|
| POST | `/api/login` | – | Login admin, mengembalikan token Sanctum |
| POST | `/api/logout` | ✅ | Menghapus token aktif |
| GET | `/api/user` | ✅ | Profil admin yang sedang login |
| GET | `/api/customers` | ✅ | Daftar seluruh pelanggan (+ jumlah pesanan) |
| GET | `/api/customers/{id}` | ✅ | Detail pelanggan + riwayat pesanan |
| GET | `/api/pesanan` | ✅ | Daftar semua pesanan (+ relasi admin & user) |
| POST | `/api/pesanan` | ✅ | Buat pesanan baru (status awal `Baru`) |
| PATCH | `/api/pesanan/{id}/status` | ✅ | Ubah status: `Baru` / `Diproses` / `Selesai` |
| POST | `/api/extract-chat` | ✅ | Ekstrak data pesanan dari teks chat WhatsApp via AI |

Dokumentasi request/response lengkap untuk setiap endpoint ada di `docs/obsidian-vault/API_Documentation.md`.

## Kontribusi Tim

- **Fairuz** — backend (Laravel, PostgreSQL, API Groq)
- **Radit** — frontend (Vue.js)
- **Aji** — frontend (Vue.js)

Panduan branching:

1. Branch utama: `main` — selalu stabil, hanya menerima merge dari `dev` (atau PR yang lolos review).
2. Branch kerja: `dev` untuk integrasi antar-anggota.
3. Buat branch feature dengan nama `feat/<nama>`, contoh: `feat/extract-chat`, `fix/bcrypt-login`.
4. Sebelum PR:
   ```bash
   php -l <file yang diubah>     # cek syntax
   composer test                 # test suite
   ```
5. Commit message ringkas dan deskriptif: `fix: guard login biar error-nya konsisten 401`.
6. Jangan commit `.env`, `vendor/`, atau file besar — sudah di-maintain di `.gitignore`.
