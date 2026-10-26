# 00 Index — Map of Content SIMPUL

Hub utama dokumentasi SIMPUL (Sistem Informasi Manajemen Pesanan Usaha Lokal — Mom's Cake). Semua catatan saling terhubung; mulai dari sini.

## Peta Catatan

1. Index (kamu di sini)
2. System Architecture — alur data Laravel - PostgreSQL - Groq API → [[System_Architecture]]
3. Database Schema — tabel `admin`, `public."User"`, `pesanan` + relasi → [[Database_Schema]]
4. API Documentation — request/response semua endpoint → [[API_Documentation]]
5. AI Prompt Engineering — prompt system, struktur JSON, sanitasi output → [[AI_Prompt_Engineering]]
6. Development Roadmap — status MVP & panduan integrasi frontend (Radit/Fairuz/Aji) → [[Development_Roadmap]]
7. Postman Testing & Room Tour — room tour kodingan + kertas to-do testing API manual → [[Postman_Testing_and_RoomTour]]

## Ringkasan Cepat

- MVP status: **SUDAH siap dipakai** oleh tim frontend (akhir periode dev). Detail dan kriteria di [[Development_Roadmap]].
- Stack: Laravel + PostgreSQL + Sanctum + Groq API (OpenAI-compatible). Arsitektur di [[System_Architecture]].
- Kredensial admin default hasil seeder: `admin` / `admin123` (wajib diganti sebelum production).
- cheat-sheet endpoint juga tersedia di README.md root repo; versi lengkap di [[API_Documentation]].

## Urutan Baca yang Disarankan

Tim frontend: [[API_Documentation]] → [[Database_Schema]] → [[Development_Roadmap]]
Tim backend/developer baru: [[System_Architecture]] → [[Database_Schema]] → [[AI_Prompt_Engineering]]
