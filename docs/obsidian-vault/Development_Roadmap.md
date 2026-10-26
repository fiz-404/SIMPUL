# Development Roadmap

Status MVP dan panduan integrasi frontend — buat **Radit**, **Fairuz**, dan **Aji**. Ikhtisar: [[00_Index]]. Referensi teknis: [[API_Documentation]], [[System_Architecture]], [[Database_Schema]], [[AI_Prompt_Engineering]].

## Status MVP — Saat Ini

Backend **SUDAH memenuhi kriteria MVP** untuk digunakan tim frontend (Vue.js). Yang sudah jadi dan teruji:

| Fitur | Status | Bukti |
|---|---|---|
| Auth Sanctum (login/logout/profil admin) | ✅ | HTTP test nyata + smoke test bcrypt, token OK |
| Orders (list + relasi, create, ubah status) | ✅ | GET/POST `/api/pesanan`, PATCH status: `Baru`/`Diproses`/`Selesai` |
| Customers (list + detail + riwayat) | ✅ | GET `/api/customers`, `/api/customers/{id}` |
| AI Chat Extractor (Groq) | ✅ | Panggilan nyata sukses, JSON akurat: nama/no/alamat/item/deskripsi/total_harga |
| Seeder data awal | ✅ | `admin/admin123` + 3 pelanggan + 3 pesanan |
| SQL dump tersinkron | ✅ | `docs/momcake.sql` cocok dengan constraint DB live |

### Bottleneck / yang belum selesai (tidak blocker MVP, tapi penting)

1. **Pesanan tidak bisa diedit selain status** — endpoint PATCH field lain (item, deskripsi, total_harga) belum ada.
2. **Customer masih read-only** — belum ada POST/PUT/DELETE pelanggan.
3. **Ga ada pagination & filter** — GET list mengembalikan semua data; aman untuk volume MVP, rawan saat data besar.
4. **Ekstensi dari extract ke insert belum otomatis** — admin harus konfirmasi lalu POST `/api/pesanan` manual/via UI (ini memang by design, lihat [[AI_Prompt_Engineering]]).
5. **Password admin default masih `admin123`** — wajib di-hash ulang/bikin admin asli sebelum production.
6. **`docs/momcake.sql` schema-only** — data diisi lewat `php artisan db:seed`, dump tidak berisi seed.
7. **Belum ada test suite otomatis** (PHPUnit) untuk endpoint — smoke test masih manual.

### Urutan Prioritas Setelah MVP

1. Untuk pesanan: PATCH `PUT /api/pesanan/{id}` (edit field) + DELETE.
2. Untuk pelanggan: POST/PUT/DELETE.
3. Pagination + filter status di GET list.
4. Rate-limiting `throttle:api` untuk login & extract-chat.
5. PHPUnit feature test (migrasi DB in-memory PostgreSQL / sqlite).

## Panduan Integrasi Frontend

Per tim:

### Radit — Auth & boilerplate
- Mulai dari fetch wrapper (axios) yang selalu pasang header `Authorization: Bearer`.
- Endpoint: POST `/api/login` → simpan token (persistent storage, makanya ini bukan halaman SPA biasa) → redirect dashboard.
- Tangani 401 → logout otomatis dan arahkan ke halaman login.

### Fairuz — Modul Pesanan
- Daftar pesanan dari GET `/api/pesanan` (sudah include `admin` & `user`).
- Form input pesanan: pakai POST `/api/extract-chat` — tampilkan hasil `data` di form, biarkan admin edit/konfirmasi — baru POST `/api/pesanan`.
- Tombol ubah status → PATCH `/api/pesanan/{id}/status` dengan opsi `Baru`, `Diproses`, `Selesai`.

### Aji — Modul Pelanggan
- GET `/api/customers` buat tabel daftar, `pesanans_count` buat badge jumlah order.
- Klik pelanggan → GET `/api/customers/{id}` → halaman detail incl. riwayat pesanan.

### Konvensi Bersama

- Semua response standar: `{ "message": ..., "data": ... }` — parse `data`.
- Error validasi 422 → tampilkan `errors` di dekat input.
- Lihat kontrak lengkap di [[API_Documentation]], arsitektur di [[System_Architecture]].
