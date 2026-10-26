# Postman Testing & Room Tour

Buku panduan buat dev SIMPUL yang mau testing manual via Postman, plus tur singkat struktur kodingannya. Pendamping: [[00_Index]], [[API_Documentation]], [[System_Architecture]], [[Development_Roadmap]].

Prasyarat: `php artisan serve` harus sudah jalan sebelum semua tes di bawah.

---

## Bagian 1 — Room Tour Framework SIMPUL

Sebelum nyolak-nyolak kode, kenalan dulu sama rumahnya. Ini file dan folder yang **paling sering diutak-atik** developer:

```
SIMPUL/
├── .env                                  ← API key, kredensial DB (jangan ke-commit!)
├── routes/api.php                        ← daftar semua URL endpoint
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php            ← login/logout admin
│   │   ├── OrderController.php           ← CRUD pesanan
│   │   ├── CustomerController.php        ← list/detail pelanggan
│   │   └── AiExtractorController.php     ← ekstraksi AI Groq (prompt di sini)
│   └── Models/                           ← tabel + relasi
│       ├── Admin.php
│       ├── User.php
│       └── Pesanan.php
├── config/services.php                   ← key & model Groq
└── database/seeders/DatabaseSeeder.php   ← data dummy
```

### Fungsi Spesifik (WAJIB hafal)

- **`routes/api.php`** — tempat nambahin URL endpoint. Mau bikin `GET /api/barang`? Daftarkan di sini: `Route::get('/barang', [BarangController::class, 'index']);`. Yang ga terdaftar di file ini = otomatis 404. Endpoint yang ditulis DI DALAM group `Route::middleware('auth:sanctum')` = butuh token; yang di luar (misal `/login`) = bebas akses.

- **`app/Http/Controllers/AiExtractorController.php`** — tempat otak-atik **System Prompt LLM** ( default `llama-3.3-70b-versatile` di kode; model yang efektif bisa diganti lewat `GROQ_MODEL` di `.env`). Prompt-nya ada di dalam blok heredoc `$systemPrompt = <<<'PROMPT' ... PROMPT;`. Mau ubah gaya ekstraksi chat WhatsApp? Edit di situ. Jangan buang aturan anti-halusinasi (lihat juga [[AI_Prompt_Engineering]]).

- **`app/Models/`** — tempat nambahin relasi tabel. Misal mau relasi admin → pesanan: tambah method `pesanans()` yang return `hasMany(Pesanan::class, 'admin_id')` di `Admin.php`. Aturan penting: tabel-tabel legacy ga punya kolom `created_at`/`updated_at` → `$timestamps = false` di model. Jangan dihapus, error-nya ga jelas.

- **`database/seeders/DatabaseSeeder.php`** — tempat nambahin data dummy. Tambah baris → jalankan `php artisan db:seed`. Seeder gw bikin idempotent (`firstOrCreate`), jadi aman dijalankan berulang tanpa duplikat.

- **`.env`** — tempat ganti API Key dan koneksi DB. `DB_CONNECTION`/`DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` buat DB; `GROQ_API_KEY`/`GROQ_MODEL` buat AI. **Wajib:** setelah edit `.env`, jalankan `php artisan config:clear` lalu restart `php artisan serve` — karena env di-baca saat boot. **Catatan:** `.env` ga akan ke-commit (sudah terdaftar di `.gitignore`); ga bikin dual pemelihara "_example" buat tim.

---

## Bagian 2 — Persiapan Postman (Autentikasi Token)

Hampir semua endpoint dilindungi `auth:sanctum`. Mau panggil endpoint proteksi? **Dapatkan token dulu.**

### Langkah Mendapatkan Token dari Endpoint Login

1. Buat request baru di Postman.
2. Method: `POST`, URL: `http://localhost:8000/api/login` (port sesuai output `php artisan serve`; defaultnya 8000).
3. Tab **Body** → pilih **raw** → ubah jenis di sebelah kanan ke **JSON**.
4. Paste body:
   ```json
   {
     "username": "admin",
     "password": "admin123"
   }
   ```
5. Klik **Send**. Bila kredensial cocok: status **200 OK**, respons memuat `"token": "5|abcdef..."`.
6. **Copy nilai token-nya sampai habis.** Format `angka|chars` adalah format token Sanctum.

### Cara Memasukkan Token ke Authorization Postman

1. Buka request lain yang butuh token (mis. `GET /api/pesanan`).
2. Tab **Authorization**.
3. Di dropdown **Type** pilih **Bearer Token**.
4. Paste token yang tadi di-copy (**nilainya saja** — tanpa kata `Bearer`, itu nanti Postman yang otomatis menambahkan) ke input di sebelah dropdown. Postman akan otomatis menambahkan header `Authorization: Bearer <token>` saat send.
5. **Send.** Bila masih 401 — kemungkinan token sudah tidak valid (mis. token lama sudah terhapus karena logout) — login lagi supaya dapat token baru.
6. **Trik hemat waktu:** simpan token sebagai variabel (misal `{{auth_token}}`) di tab **Environments**, lalu di tab Authorization cukup tulis `{{auth_token}}`.

---

## Bagian 3 — Kertas To-Do List Testing API (Step-by-Step)

Checklist berurutan dari ujung ke ujung. **Kerjakan secara berurutan** — id `admin_id` / `user_id` di langkah 3 tergantung output seeder & response sebelumnya.

### 1. Login Admin

- [ ] `POST` `http://localhost:8000/api/login` *(tanpa token)*
  - Body:
    ```json
    {
      "username": "admin",
      "password": "admin123"
    }
    ```
  - **Expected:** `200 OK`. Response: `"message": "Login berhasil!"` serta field `token`. **Copy langsung token** → taruh di tab Authorization → Bearer Token untuk langkah berikutnya. Kalau 401: cek data seeder atau bcrypt.

### 2. Ekstraksi AI (Groq / Llama-3)

- [ ] `POST` `http://localhost:8000/api/extract-chat` *(Bearer Token)*
  - Body:
    ```json
    {
      "raw_text": "Halo momcake, saya mau pesan kue ulang tahun ya. Nama saya Andi Wijaya, no telp 081234567001. Dikirim ke Jl. Merdeka No. 12 Jakarta Pusat. Tolong buatin kue ulang tahun tema dinosaurus 20cm, tulisannya Selamat Ulang Tahun Raka. Harga total 350 ribu, makasih!"
    }
    ```
  - **Expected:** `200 OK`. `data` memuat 6 kunci: `nama`, `no_telepon`, `alamat`, `item`, `deskripsi`, `total_harga` — nilai harus benar sesuai isi chat (`total_harga` tanpa titik ribuan: `350000`, bukan `"350.000"`). Kalau `GROQ_API_KEY` belum diset → `503`. Kalau koneksi Groq gagal / model tidak valid → `502` beserta `detail` ("Keterangan kesalahan"). Kalau `raw_text` hilang → `422`.

### 3. Create Pesanan Baru

- [ ] `POST` `http://localhost:8000/api/pesanan` *(Bearer Token)*
  - **id `admin_id` / `user_id`**: ambil dari response sebelumnya (user hasil seed umumnya id 1–3; `admin_id` = id admin username `admin`). Sesuaikan.
  - Body:
    ```json
    {
      "admin_id": 5,
      "user_id": 1,
      "item": "Kue Ulang Tahun",
      "deskripsi": "Tema dinosaurus, ukuran 20cm, tulisan Selamat Ulang Tahun Raka",
      "total_harga": 350000
    }
    ```
  - **Expected:** `201 Created`. Response menampilkan `"status": "Baru"` (status di-set otomatis oleh backend — jangan dikirim di request body). Bila `admin_id`/`user_id` tidak ada di tabel → `422` dengan `errors` per field.

### 4. Get All Pesanan

- [ ] `GET` `http://localhost:8000/api/pesanan` *(Bearer Token)*
  - Body: **tidak ada** — GET tidak perlu body.
  - **Expected:** `200 OK`. `data` berupa **array**, tiap item pesanan berisi relasi: `admin: {...}` dan `user: {...}`. Pesanan dari langkah 3 harus muncul di daftar ini.

### 5. Update Status Pesanan

- [ ] `PATCH` `http://localhost:8000/api/pesanan/{id}/status` *(Bearer Token)*
  - **Ganti `{id}`** dengan id pesanan dari langkah 3/4.
  - Body:
    ```json
    {
      "status": "Diproses"
    }
    ```
  - **Expected:** `200 OK`. `data` berisi `status: "Diproses"`. Uji juga negatif:
    - `{ "status": "Bulat" }` → `422` (nilai di luar `Baru`/`Diproses`/`Selesai` menolak).
    - `PATCH /api/pesanan/9999/status` → `404` `{ "message": "Pesanan tidak ditemukan!" }`.
  - Catatan: status **case-sensitive** — `diproses` (lowercase) akan ditolak.

### 6. Get Detail Customer

- [ ] `GET` `http://localhost:8000/api/customers/1` *(Bearer Token)*
  - Body: **tidak ada**.
  - **Expected:** `200 OK`. Data pelanggan + array `pesanans` (riwayat pesanan dari langkah 3–5, id=1 user merupakan user hasil pertama). Uji juga:
    - `GET /api/customers` → daftar semua pelanggan, setiap item punya `pesanans_count`.
    - `GET /api/customers/9999` → `404`.

---

## Troubleshooting Kilat

| Simptom | Diagnosis |
|---|---|
| 401 padahal sudah dipasang | Pakai token dari login terbaru (token lama bisa jadi dihapus karena logout); atau lupa pilih Bearer Token di tab Authorization |
| 422 "unexpected" | Cek huruf besar/kecil field di Body, pastikan JSON valid (tanpa koma tambahan di akhir), nilai `admin_id`/`user_id` benar-benar ada di DB |
| 500 / response aneh | `php artisan config:clear`, restart server, baca lognya di `storage/logs/laravel.log` |
| Model AI 404/402 | Nama model `GROQ_MODEL` ga ada di daftar akses akun Groq; cek `GET https://api.groq.com/openai/v1/models` |
| DB ga nyambung | Cek PostgreSQL hidup dan kredensial `.env`; ingat restart server setelah edit `.env` |

Referensi lebih dalam: [[API_Documentation]] (kontrak tiap endpoint), [[Database_Schema]] (id & relasi), [[AI_Prompt_Engineering]] (cara ekstraksi kerja), [[Development_Roadmap]] (prioritas berikutnya).
