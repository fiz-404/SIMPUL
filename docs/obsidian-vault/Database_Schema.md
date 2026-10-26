# Database Schema

Database: PostgreSQL, nama DB `SIMPUL`. Dump skema: `docs/momcake.sql`. Lihat ikhtisar di [[00_Index]], alur penggunaannya di [[System_Architecture]].

## Tabel `admin`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | integer (serial) | PK |
| username | varchar(50) | UNIQUE |
| password | varchar(255) | bcrypt hash (Wajib! bukan plaintext) |

- Tanpa `created_at`/`updated_at` → model `App\Models\Admin` punya `$timestamps = false`.
- Model pakai trait `HasApiTokens` (Sanctum).
- Kolom `password` ada di `$hidden` supaya ga bocor di JSON.
- Seed default: `admin` / `admin123`.

## Tabel `public."User"`

Nama tabel huruf kapital dengan quote di Postgres: `public."User"`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | integer (serial) | PK |
| nama | varchar(100) | nama pelanggan |
| no | varchar(20) | nomor telepon |
| alamat | text | boleh null |

- BUKAN tabel `users` standar Laravel; model `App\Models\User` diarahkan dengan `protected $table = 'User'` dan **tidak** dipakai untuk login. Kredensial cuma di tabel `admin`. Lihat [[System_Architecture]].
- Relasi: satu user punya banyak pesanan (`pesanans(): hasMany(Pesanan::class, 'user_id')`).

## Tabel `pesanan`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | integer (serial) | PK |
| admin_id | integer | FK → `admin.id` |
| user_id | integer | FK → `public."User".id` |
| item | varchar(255) | nama produk |
| deskripsi | text | boleh null |
| status | varchar(20) | DEFAULT `'Baru'` |
| total_harga | numeric(12,2) | harga total |

- **CHECK constraint `pesanan_status_check`**: nilai `status` cuma boleh `Baru`, `Diproses`, `Selesai` (case-sensitive; `Baru` ≠ `baru`). Constraint ini yang bikin spek lama `masuk/dikirim` tidak valid.
- Tanpa timestamps juga, model `App\Models\Pesanan` `$timestamps = false`.

## Relasi Antar-Tabel

```
admin (1) ────────< pesanan (n)
"User" (1) ────────< pesanan (n)
```

- `Pesanan::admin()` — `belongsTo(Admin::class, 'admin_id')`
- `Pesanan::user()` — `belongsTo(User::class, 'user_id')`
- `User::pesanans()` — `hasMany(Pesanan::class, 'user_id')`
- 

## Catatan Migrasi Skema

- Skema dari dump lama vs DB sekarang berbeda untuk kolom `status` — dump `docs/momcake.sql` sudah di-update mengikuti constraint live: `DEFAULT 'Baru'` + CHECK `('Baru','Diproses','Selesai')`.
- Kalau rebuild DB dari dump, jalankan `php artisan db:seed` sesudahnya supaya data admin/pelanggan/pesanan awal keisi.
- Tabel Laravel sendiri (cache, jobs, personal_access_tokens) masih pakai migration `database/migrations` — tetap jalankan `php artisan migrate` untuk tabel-tabel itu.
