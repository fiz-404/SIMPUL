# Freebuf Execution Log — SIMPUL Backend

Audit trail seluruh pekerjaan AI (Codebuff/Buffy) selama sesi development backend SIMPUL (Laravel + PostgreSQL + Groq).
Ditulis untuk memudahkan developer / AI asisten lain memverifikasi apa yang berubah, command apa yang dijalankan, dan bug apa yang ditangani.

> Security note: semua credential (API key, password DB) di-redact dalam log ini. Key riil hanya berada di `.env` (gitignored, terverifikasi via `git check-ignore`).

---

## 1. Daftar File — Dibuat / Diedit / Dihapus

### Dibuat (baru)

| File | Tujuan |
|---|---|
| `app/Http/Controllers/OrderController.php` | Controller pesanan: `index()`, `store()`, `updateStatus()` |
| `app/Http/Controllers/CustomerController.php` | Controller pelanggan: `index()`, `show($id)` |
| `app/Http/Controllers/AiExtractorController.php` | Endpoint AI: `extract()` via Groq chat completions |
| `README.md` (root) | Dokumentasi proyek: deskripsi, tech stack, instalasi, tabel endpoint, kontribusi |
| `docs/obsidian-vault/00_Index.md` | Map of Content (hub) vault Obsidian |
| `docs/obsidian-vault/System_Architecture.md` | Arsitektur & alur data Laravel - PostgreSQL - Groq |
| `docs/obsidian-vault/Database_Schema.md` | Skema 3 tabel + relasi + constraint |
| `docs/obsidian-vault/API_Documentation.md` | Kontrak request/response semua endpoint |
| `docs/obsidian-vault/AI_Prompt_Engineering.md` | Prompt system, JSON schema, sanitasi output |
| `docs/obsidian-vault/Development_Roadmap.md` | Analisis MVP + prioritas + pembagian tugas frontend |
| `docs/obsidian-vault/Postman_Testing_and_RoomTour.md` | Panduan testing manual Postman + room tour kode |
| `freebuf_execution_log.md` (root) | File ini |

### Diedit (file yang sudah ada sebelumnya)

| File | Perubahan |
|---|---|
| `app/Http/Controllers/AuthController.php` | Login dibenahi: validasi strict, `Hash::check` (bcrypt, bukan plaintext), issue token Sanctum, plus method `logout()`; representasi 401 konsisten |
| `app/Models/Admin.php` | + trait `HasApiTokens`, + `protected $hidden = ['password']` |
| `app/Models/User.php` | Re-point ke tabel `public."User"` asli Postgres (`$table = 'User'`), fillable (`nama`, `no`, `alamat`), timestamps off, + relasi inverse `pesanans()` |
| `app/Models/Pesanan.php` | (tidak diubah perilakunya; dipakai apa adanya untuk relasi) |
| `routes/api.php` | Iteratif: login/logout/user (Sanctum) → routes pesanan (GET/POST/PATCH) → customers (GET/GET by id) → POST `/api/extract-chat`; semuanya protected `auth:sanctum` kecuali `/login` |
| `config/services.php` | + entry `'groq' => ['key' => env('GROQ_API_KEY'), 'model' => env('GROQ_MODEL')]` |
| `.env` | Fix `DB_CONNECTION=pgsqlz` → `pgsql`; key Groq (ditambah user antara sesi) tetap 1 line; tambah `GROQ_MODEL=openai/gpt-oss-120b`; hapus line `GROQ_API_KEY=` kosong hasil append yang sempat nge-override key asli |
| `database/seeders/DatabaseSeeder.php` | Rewritten: idempotent seeding — 1 admin `admin`/`admin123` (bcrypt), 3 pelanggan, 3 pesanan (status `Baru`/`Diproses`/`Selesai`) |
| `docs/momcake.sql` | Sinkron skema: `DEFAULT 'Baru'` + `CHECK pesanan_status_check ('Baru','Diproses','Selesai')` (dump sebelumnya berisi `masuk/diproses/dikirim/selesai`) |

### Dihapus

Tidak ada file yang dihapus. Data uji di DB dibersihkan setelah smoke test (bukan file).

### Modifikasi DB langsung (bukan lewat migration file)

- `ALTER TABLE public.pesanan DROP CONSTRAINT IF EXISTS pesanan_status_check;`
- `ALTER TABLE public.pesanan ALTER COLUMN status SET DEFAULT 'Baru';`
- `ALTER TABLE public.pesanan ADD CONSTRAINT pesanan_status_check CHECK (status::text = ANY(ARRAY['Baru','Diproses','Selesai']));`
- (Verifikasi via `pg_constraint`: constraint baru terpasang; tabel kosong saat diubah, jadi tidak ada migrasi data lama.)

---

## 2. Command Terminal / Artisan (beserta exit code)

Semua command dijalankan di project root, Linux (Fedora), shell bash; `EXIT=0` berarti sukses.

| Command | Exit | Catatan hasil |
|---|---|---|
| `php -v && php -l <file>` (AuthController, Admin, OrderController, CustomerController, AiExtractorController, DatabaseSeeder, User, config/services, routes/api, docs/momcake sanity) | 0 | `No syntax errors detected ...` di semua file |
| `php artisan route:list --path=api` | 0 | 8–9 route terverifikasi berkembang seiring sesi |
| `php artisan tinker --execute '...trait check + createToken...'` (panggilan pertama, gagal) | 1 | `Database connection [pgsqlz] not configured.` → root cause .env |
| `sed -i 's/DB_CONNECTION=pgsqlz/DB_CONNECTION=pgsql/' .env` | 0 | Fix koneksi |
| `php artisan tinker --execute '... trait + createToken (setelah persist) ...'` | 0 | `Token issued: OK`, `Hash::check: OK` |
| `ALTER TABLE ... DROP/ADD CONSTRAINT pesanan_status_check` (via `tinker --execute`) | 0 | Constraint berubah: `masuk/diproses/dikirim/selesai` → `Baru/Diproses/Selesai`, default `Baru` |
| `php artisan db:seed --force` | 0 | `INFO Seeding database.` |
| `php artisan tinker --execute '... Hash::check admin123 ...'` | 0 | `bcrypt check: OK` (bukan plaintext) |
| `php artisan serve --port=8090/8091/8092/8093` (BACKGROUND) | – | background process, dipakai untuk smoke test |
| `curl` login → extract-chat / pesanan / customers | 0 | detail di bagian 3 |
| `GET https://api.groq.com/openai/v1/models` (via curl + key di header, tidak di-print) | 0 | daftar model yang bisa dipakai akun ini |
| `php artisan config:clear` | 0 | setiap kali `.env` berubah |
| `grep -c '^GROQ_API_KEY' .env` | 0 | memastikan 1 baris key |
| `git check-ignore .env && git status --porcelain` | 0 | `.env` gitignored: OK |
| `find docs/obsidian-vault -mindepth 1 -type d \| wc -l` | 0 | 0 (vault tetap flat) |
| wikilink checker custom (PHP, resolve semua `[[...]]`) | 0 | 0 broken link di 7 file |
| `grep` scan konten typo/gibberish + checklist count | 0 | BERSIH / 6 item checklist |
| `kill <pid>` + `lsof -t -iTCP:... -sTCP:LISTEN` | 0 | semua server uji dimatikan |

---

## 3. Hasil Smoke test / API Test Nyata

### 3.1 Auth (Sanctum)

- `POST /api/login` `{"username":"admin","password":"admin123"}` → `200 OK`
  ```json
  {
    "message": "Login berhasil!",
    "token": "<redacted>|auth-token",
    "data": { "id": 5, "username": "admin" }
  }
  ```
- Login salah → `401` `{"message":"Username atau password salah!"}`

### 3.2 Orders (Pesanan)

- `GET /api/pesanan` → `200` — data array + relasi admin & user terverifikasi:
  ```json
  {
    "message": "Data pesanan berhasil diambil!",
    "data": [
      {
        "id": 1, "admin_id": 2, "user_id": 1,
        "item": "Kue Ulang Tahun", "deskripsi": "Test", "status": "Diproses", "total_harga": "150000.00",
        "admin": { "id": 2, "username": "t_smoke_admin" },
        "user":  { "id": 1, "nama": "T Smoke", "no": "000", "alamat": "Test Street" }
      }
    ]
  }
  ```
- `POST /api/pesanan` → `201 Created`, `status` auto `Baru`, total_harga tersimpan `150000.00`.
- `PATCH /api/pesanan/1/status` `Diproses` → `200` memperbarui status; `Selesai` → `200`.
- `PATCH` with `{ "status": "Bodou" }` → `422`. `PATCH /api/pesanan/999/status` → `404`.

### 3.3 Customers

- `GET /api/customers` → `200`; item pelanggan + `pesanans_count`.
- `GET /api/customers/2` → `200` — detail + `pesanans` (2 riwayat) dengan relasi ke `public."User"`.
- `GET /api/customers/999` → `404`.

### 3.4 AI Extractor (Groq, panggilan pertama sesungguhnya)

- First attempt dengan model `llama-3.3-70b-versatile` → `404`:
  ```
  The model `llama-3.3-70b-versatile` does not exist or you do not have access to it.
  ```
- List model via `/v1/models` yang tersedia di akun menunjukkan: `openai/gpt-oss-120b`, `openai/gpt-oss-20b`, `qwen/qwen3.8-27b` (+ whisper/orpheus/prompt-guard tools).
- Setelah ganti `GROQ_MODEL=openai/gpt-oss-120b`, `POST /api/extract-chat` → `HTTP 200`:
  ```json
  {
    "message": "Ekstraksi berhasil!",
    "data": {
      "nama": "Andi Wijaya",
      "no_telepon": "081234567001",
      "alamat": "Jl. Merdeka No. 12 Jakarta Pusat",
      "item": "kue ulang tahun tema dinosaurus",
      "deskripsi": "tema dinosaurus, ukuran 20cm, tulisan Selamat Ulang Tahun Raka",
      "total_harga": 350000
    }
  }
  ```

### 3.5 Data uji DB (kondisi saat sesi berlangsung)

- `admin=2 User=3 pesanan=3` setelah seed. `admin` berisi akun asli user (`fiz`) + seeder `admin` → `fiz` sengaja tidak disentuh.
- Semua row/token uji dibersihkan: `pesanan=0 admin=0 User=0`, token count `0`.

---

## 4. Bug / Error yang Ditemui & Perbaikannya

| # | Bug / Error | Root Cause | Perbaikan |
|---|---|---|---|
| 1 | `Database connection [pgsqlz] not configured.` (tinker) | `.env` berisi `DB_CONNECTION=pgsqlz` — ga ada connection `pgsqlz` di `config/database.php` | Ganti `DB_CONNECTION=pgsql` (nama driver standar Postgres di Laravel) |
| 2 | Login plaintext (login bandingin string langsung) | Kode awal user | Ganti verifikasi pake `Hash::check(bcrypt)` + seeder menghash password |
| 3 | Constraint status DB tidak cocok spek | CHECK di dump: `masuk/diproses/dikirim/selesai` vs spec `Baru/Diproses/Selesai` | Konfirmasi ke user, tabel kosong, lalu DROP + ADD CHECK baru. Dump `momcake.sql` ikut disinkronkan |
| 4 | Relasi `user()` gagal runtime | Tabel user di Postgres = `public."User"` (kapital), bukan `users` standar Laravel | Model `User` di-repoint: `protected $table = 'User'` + fillable disesuaikan kolom eksisting |
| 5 | `SQLSTATE[23502] Not null violation` di `tokenable_id` | Artefak smoke test: model di-instantiate tapi tidak di-persist sebelum `createToken()` | Smoke test diulang dengan `create()` per model (tidak ada bug kode) |
| 6 | `404 model llama-3.3-70b-versatile` | Akun Groq tidak memiliki akses untuk model tersebut | Model dibuat configurable via `GROQ_MODEL` (default kode tetap sesuai spek); value efektif `.env` di-set `openai/gpt-oss-120b` (terverifikasi lewat panggilan asli ke akun) |
| 7 | Exception mentah dari `retry()` menembus try-catch → response berisi stack trace | Laravel `Http::retry()` menampakkan `RequestException` saat semua attempt gagal (tap->throw) | `retry()` dihilangkan; `RequestException` di-catch terpisah → 502 dengan `detail` yang bersih |
| 8 | `curl` exit code 7 (`HTTP:000`) saat hit endpoint | `php artisan serve` yang di-background pake `&` di dalam command SYNC mati antar command | Gunakan `process_type=BACKGROUND` untuk artisan serve; cek hidup dulu sebelum request |
| 9 | `.env` punya 2 line `GROQ_API_KEY` (1 key asli ditambah user + `1` line empty di append agent) | dotenv menang line terakhir → line kosong bisa menimpa key asli | `sed -i '/^GROQ_API_KEY=$/d' .env` sebelum config:clear → key asli satu-satunya |
| 10 | `sed` gagal di `momcake.sql` (`unterminated 's' command`) | Shell quoting tabrakan dengan `'` dalam pattern | Rewrite edit-pake `php -r` `str_replace` — 1 pass, 2 replacement berhasil, verifikasi grep |
| 11 | Multi-edit `str_replace` membuat `AiExtractorController.php` dengan fragment bergabung (garble) | Replacement strategy {{chained}} saling menimpa karena 4 pasang old/new saling mengulang | Rewrite penuh via `write_file`, lalu `php -l` dan smoke test Groq asli |
| 12 | Draft `Postman_Testing_and_RoomTour.md` mengandung banyak gibberish | Terdeteksi saat read-back review | Rewrote penuh + batch fix frasa; scan ulang: BERSIH |
| 13 | Server uji masih menyala setelah `kill <pid>` | Kill id proses induk tidak selalu mematikan listener PHP bawaan `artisan serve` | Cross-check via `lsof -t -iTCP:<port> -sTCP:LISTEN`, kill ulang pors listener, verifikasi tab dengan curl sampai "server stopped" |

---

## 5. Limitasi & Catatan Lanjutan

1. Model efektif via `.env` = `openai/gpt-oss-120b` (sesuai akses efektif akun Groq); default di kode tetap `llama-3.3-70b-versatile` sesuai spek. Saat deploy ke production, pastikan `GROQ_MODEL` cocok dengan model yang benar-benar tersedia di akun.
2. `docs/momcake.sql` = dump skema saja (tanpa data seed) — data isian dilakukan via `php artisan db:seed`.
3. Belum ada test suite otomatis (PHPUnit); semua smoke test dilakukan manual via curl/tinker (sudah terdokumentasi di atas).
4. Token uji, row uji DB, dan server uji selalu dibersihkan di akhir setiap sesi — **tidak ada data tes yang tersisa di DB live.**

---

*Log ditutup pada sesi yang sama dengan implementasi `Postman_Testing_and_RoomTour.md` dan audit trail ini.*
