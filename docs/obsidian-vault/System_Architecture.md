# System Architecture

Penjelasan alur data dan komponen SIMPUL. Lihat ikhtisar di [[00_Index]].

## Komponen

1. **Laravel Backend** — satu aplikasi monolit berada di repositori ini. Semua endpoint HTTP berada di `routes/api.php`, diproteksi Sanctum kecuali `/api/login`.

2. **PostgreSQL** — database `SIMPUL`. Tabel utama: `admin`, `public."User"`, `pesanan`. Struktur di [[Database_Schema]].

3. **Groq API** — layanan AI eksternal (OpenAI-compatible) di `https://api.groq.com/openai/v1/chat/completions`. Untuk detail prompt & parsing lihat [[AI_Prompt_Engineering]].

## Alur Data Inti

### Alur 1 — Auth
Frontend (Vue.js) → POST `/api/login` (username, password) → Laravel query tabel `admin` (via model `App\Models\Admin`) → verifikasi `Hash::check(bcrypt)` → issue token Sanctum → frontend simpan token, kirim header `Authorization: Bearer <token>` di request berikutnya.

### Alur 2 — Pesanan CRUD
Frontend → `Authorization: Bearer` → `AuthController`/`OrderController` → Eloquent `App\Models\Pesanan` (eager load relasi `admin` & `user`) → PostgreSQL → JSON response.

Contoh chain: GET `/api/pesanan` → `Pesanan::with(['admin','user'])` → SELECT `pesanan` JOIN `admin` JOIN `public."User"` → JSON.

### Alur 3 — AI Extraction
1. Frontend kumpulkan teks mentah chat WhatsApp (paste manual oleh admin).
2. POST `/api/extract-chat` (`raw_text`).
3. `AiExtractorController` → `Http` facade → Groq chat completions (system prompt ketat + user message = raw_text), `response_format: json_object`.
4. Response Groq (JSON string) → di-parse backend → normalisasi kunci (`total_harga` ke float, kunci hilang diisi null) → JSON ke frontend.
5. Frontend menampilkan hasil ekstraksi untuk dikonfirmasi admin, lalu memanggil POST `/api/pesanan` untuk menyimpan pesanan (status awal `Baru`).

Diagram alur:

```
Frontend (Vue)
    │  Bearer token (Sanctum)
    ▼
Laravel API (routes/api.php)
    ├── OrderController  ──▶ PostgreSQL: pesanan/admin/"User"
    ├── CustomerController ─▶ PostgreSQL: "User" (+ pesanan history)
    └── AiExtractorController ─▶ HTTPS ▶ Groq API (llama/gpt-oss) ─▶ JSON kembali
```

## Keputusan Desain Penting

- **Auth pakai Admin, bukan User.** Tabel `admin` khusus login; tabel `public."User"` hanya data pelanggan (nama, no, alamat, tanpa email/password). Jangan sampai tertukar.
- **Status: Tanpa Buat-Timestamps.** Semua tabel legacy tidak punya `created_at`/`updated_at` — di model `$timestamps = false`. Kalau menambah tabel baru, konsultasikan dulu sebelum ikut-ikutan.
- **Kolom password selalu di-hide** lewat `$hidden` di model `Admin` agar tidak pernah bocor di response JSON.
- **Model Groq dapat diganti** via `GROQ_MODEL` di `.env` , karena nama model di-resolve dari `config('services.groq.model')`.
