# API Documentation

Dokumentasi lengkap endpoint SIMPUL. Base URL lokal: `http://127.0.0.1:8000/api`. Ikhtisar lainnya: [[00_Index]], struktur data di [[Database_Schema]], alur di [[System_Architecture]].

## Konvensi Umum

- Format: JSON (`Content-Type: application/json`).
- Semua endpoint kecuali `/login` butuh header `Authorization: Bearer <token>`.
- Error validasi Laravel → HTTP 422 dengan format `{ "message": ..., "errors": { "field": ["pesan"] } }`.
- Kesalahan umum: 401 (token salah/hilang), 404 (data ga ada), 502 (Groq error), 503 (key belum diset).

---

## Auth

### POST `/api/login` (public)

Request:
```json
{ "username": "admin", "password": "admin123" }
```

Response 200:
```json
{
  "message": "Login berhasil!",
  "token": "5|xxxxxxxxxxxxxxxx...",
  "data": { "id": 5, "username": "admin" }
}
```
(catatan: `password` tidak akan muncul, ada di `$hidden`.)

Gagal 401:
```json
{ "message": "Username atau password salah!" }
```

### POST `/api/logout` (auth)

Response 200: `{ "message": "Logout berhasil!" }` — token yang dipakai langsung dihapus.

### GET `/api/user` (auth)

Response 200: data admin yang login, mis. `{ "id": 5, "username": "admin" }`.

---

## Customers

### GET `/api/customers` (auth)

Response 200:
```json
{
  "message": "Data pelanggan berhasil diambil!",
  "data": [
    { "id": 1, "nama": "Andi Wijaya", "no": "081234567001", "alamat": "Jl. Merdeka No. 12, Jakarta Pusat", "pesanans_count": 1 }
  ]
}
```

### GET `/api/customers/{id}` (auth)

Response 200: satu pelanggan + riwayat pesanan:
```json
{
  "message": "Detail pelanggan berhasil diambil!",
  "data": {
    "id": 1, "nama": "Andi Wijaya", "no": "081234567001", "alamat": "...",
    "pesanans": [
      { "id": 1, "admin_id": 5, "user_id": 1, "item": "Bolu Pandan", "deskripsi": "...", "status": "Baru", "total_harga": "120000.00" }
    ]
  }
}
```
Gagal: 404 `{ "message": "Pelanggan tidak ditemukan!" }`.

---

## Pesanan (Orders)

Lihat struktur tabel di [[Database_Schema]].

### GET `/api/pesanan` (auth)

Response 200 — semua pesanan + relasi admin & user (eager load):
```json
{
  "message": "Data pesanan berhasil diambil!",
  "data": [
    {
      "id": 1, "admin_id": 5, "user_id": 1,
      "item": "Kue Ulang Tahun", "deskripsi": "...", "status": "Diproses", "total_harga": "350000.00",
      "admin": { "id": 5, "username": "admin" },
      "user":  { "id": 1, "nama": "Andi Wijaya", "no": "081234567001", "alamat": "..." }
    }
  ]
}
```

### POST `/api/pesanan` (auth)

Request:
```json
{
  "admin_id": 5,
  "user_id": 1,
  "item": "Kue Ulang Tahun",
  "deskripsi": "Tema dinosaurus, 20cm",
  "total_harga": 350000
}
```
Validasi: `admin_id` & `user_id` harus ada di tabel masing-masing (`exists:admin,id`, `exists:"User",id`), `item` wajib, `deskripsi` nullable, `total_harga` numeric >= 0.  — tidak bisa di-set manual di endpoint ini.

Response 201:
```json
{
  "message": "Pesanan berhasil dibuat!",
  "data": { "id": 4, "admin_id": 5, "user_id": 1, "item": "Kue Ulang Tahun", "deskripsi": "...", "status": "Baru", "total_harga": "350000.00" }
}
```

### PATCH `/api/pesanan/{id}/status` (auth)

Request:
```json
{ "status": "Diproses" }
```
Validasi: status harus salah satu dari `Baru`, `Diproses`, `Selesai` (Rule::in). Rejected → 422. ID ga ada → 404 `{ "message": "Pesanan tidak ditemukan!" }`.

Response 200:
```json
{
  "message": "Status pesanan berhasil diubah!",
  "data": { "id": 1, "status": "Diproses", "...": "..." }
}
```

---

## AI Extract

### POST `/api/extract-chat` (auth)

Request:
```json
{ "raw_text": "Halo momcake, saya mau pesan kue ulang tahun, nama saya Andi, 081234567001, kirim ke Jl. Merdeka 12, tema dinosaurus 20cm, total 350 ribu" }
```
(catatan: `raw_text` = teks mentah chat yang di-copy asli; maksimal 5000 karakter.)

Detail prompt & parsing di [[AI_Prompt_Engineering]].

Response 200:
```json
{
  "message": "Ekstraksi berhasil!",
  "data": {
    "nama": "Andi",
    "no_telepon": "081234567001",
    "alamat": "Jl. Merdeka 12",
    "item": "kue ulang tahun",
    "deskripsi": "tema dinosaurus, 20cm",
    "total_harga": 350000
  }
}
```
- Kunci ga ditemukan → `null` (dan `total_harga` → `0`).
- 503: `GROQ_API_KEY` belum di-set.
- 502: koneksi gagal / Groq error / model balas JSON ga valid (respons memuat `detail`).
- 422: `raw_text` hilang/kosong.

---

## Alur Yang Disarankan Buat Frontend

1. POST `/api/login` → simpan token.
2. POST `/api/extract-chat` (paste chat pelanggan) → tampilkan JSON hasil ke admin untuk konfirmasi/diedit.
3. POST `/api/pesanan` dengan data hasil konfirmasi → pesanan baru status `Baru`.
4. PATCH `/api/pesanan/{id}/status` seiring berjalannya pesanan (`Diproses` → `Selesai`).
5. GET `/api/customers` & `/api/customers/{id}` untuk daftar/detail pelanggan.
