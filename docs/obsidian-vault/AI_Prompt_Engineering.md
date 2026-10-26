# AI Prompt Engineering

Catatan teknik di balik `AiExtractorController` — endpoint `POST /api/extract-chat`. Konteks arsitektur: [[System_Architecture]]. Struktur tabel: [[Database_Schema]]. Ikhtisar: [[00_Index]].

## Model

- Endpoint: `POST https://api.groq.com/openai/v1/chat/completions` (OpenAI-compatible).
- Nama model di-resolve dari `config('services.groq.model')` → diisi `GROQ_MODEL` di `.env`. Default kode: `llama-3.3-70b-versatile` (arsitektur keluarga Llama-3.3 alias 70B-class reasoning → cepat & murah di Groq).
- Catatan penting: **nama model harus disesuaikan daftar akses akun Groq Anda.** Kalau model tidak tersedia, cek `GET https://api.groq.com/openai/v1/models`. Saat ini value efektif di `.env`: `openai/gpt-oss-120b`.
- Parameter request: `temperature: 0` (deterministik, ekstraksi bukan kreasi), `max_tokens: 500`, `response_format: { "type": "json_object" }`.

## System Prompt

Prinsip yang dijaga (prompt asli lihat di `app/Http/Controllers/AiExtractorController.php`):

1. **Peran ketat**: "kamu adalah ekstraktor data pesanan yang sangat ketat" — mencegah model berkhayal jadi asisten umum.
2. **Output tunggal**: WAJIB hanya satu objek JSON valid dengan kunci tetap:
   ```json
   {
     "nama": "...",
     "no_telepon": "...",
     "alamat": "...",
     "item": "...",
     "deskripsi": "...",
     "total_harga": 0
   }
   ```
3. **Anti-halusinasi**: aturan express "JANGAN pernah mengarang data" — nilai yang tidak ditemukan di chat harus `null` (dan `0` untuk `total_harga`).
4. **Dalam format chat nyata**: teks mentah WhatsApp yang sampai dadakan — singkat, campur bahasa gaul, kadang typo. Model pra-t蒸馏 dengan contoh format relevan dan instruksi ekstensi `null` secara tegas.
5. **Fallback**: chat yang sama sekali bukan pesanan kue → semua kunci `null`/`0`, tidak error.

## Sanitasi & Validasi Output

Meskipun prompt ketat + `response_format=json_object`, backend tetap lapisan-ke-2:

1. `choices[0].message.content` di-`trim` dan dilepas blok kode ```` ```json ... ``` ```` jika menyelip.
2. `json_decode(..., true)` → kalau gagal, return `502` dengan `message` dan `raw` (jangan pernah biarkan PHP notice siap distribusi).
3. `total_harga` di-cast ke `float` kalau numeric (mis. `"350000"` → `350000.0`), else `0`.
4. Semua 6 kunci dipastikan ada (`$data[$key] = $data[$key] ?? null`) supaya frontend ga perlu cek `undefined`.
5. Bungkus koneksi Groq dengan try-catch → HTTP 502 dengan `detail` ori dari Groq (menghindari info leak / trace stack siap distribusi).

## Penggunaan di Alur Bisnis

Hasil ekstraksi tampil ke admin untuk **konfirmasi manual sebelum disimpan** (endpoint POST `/api/pesanan`): AI mempercepat input-data, bukan menggantinya. Detail flow di [[Development_Roadmap]].
