<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiExtractorController extends Controller
{
    // Endpoint OpenAI-compatible milik Groq
    private const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    // Default sesuai spesifikasi. Model sebenarnya diambil dari
    // config('services.groq.model') (GROQ_MODEL di .env) supaya gampang
    // diganti tanpa deploy ulang. Cek daftar model yang tersedia buat
    // key Anda: GET https://api.groq.com/openai/v1/models
    private const GROQ_MODEL_DEFAULT = 'llama-3.3-70b-versatile';

    // Daftar kunci hasil ekstraksi yang wajib ada
    private const KEYS = ['nama', 'no_telepon', 'alamat', 'item', 'deskripsi', 'total_harga'];

    /**
     * Ekstrak data pesanan dari teks mentah chat WhatsApp
     * via Groq chat completions.
     */
    public function extract(Request $request)
    {
        $request->validate([
            'raw_text' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $apiKey = config('services.groq.key');
        $model  = config('services.groq.model') ?: self::GROQ_MODEL_DEFAULT;

        if (empty($apiKey)) {
            return response()->json([
                'message' => 'GROQ_API_KEY belum di-set di .env. Tambahkan GROQ_API_KEY=<key_anda> lalu restart server.',
            ], 503);
        }

        $systemPrompt = <<<'PROMPT'
Kamu adalah ekstraktor data pesanan yang sangat ketat. Pengguna akan mengirimkan teks mentah percakapan WhatsApp pelanggan ke toko kue "Momcake".

TUGAS: Baca percakapan itu dan ekstrak informasi pesanan.

ATURAN (WAJIB):
1. Output kamu HARUS HANYA satu objek JSON valid persis dengan kunci berikut, tanpa penjelasan, tanpa markdown, tanpa blok kode:
   {"nama": "...", "no_telepon": "...", "alamat": "...", "item": "...", "deskripsi": "...", "total_harga": 0}
2. "nama": nama pelanggan yang disebut di chat; null jika tidak ditemukan.
3. "no_telepon": nomor telepon yang disebut di chat; null jika tidak ditemukan.
4. "alamat": alamat yang disebut di chat; null jika tidak ditemukan.
5. "item": nama produk/kue yang dipesan; null jika tidak ditemukan.
6. "deskripsi": detail kue (ukuran, rasa, topping, catatan desain, dll); null jika tidak ada.
7. "total_harga": angka total harga yang disebut di chat (tanpa titik/koma ribuan); 0 jika tidak jelas.
8. JANGAN pernah mengarang data. Jika tidak ada di chat, kembalikan null (atau 0 untuk total_harga).
9. Jika chat bukan pesanan kue sama sekali, kembalikan semua kunci bernilai null/0.
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(60)
                ->post(self::GROQ_ENDPOINT, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $request->input('raw_text')],
                    ],
                    'temperature' => 0,
                    'max_tokens'  => 500,
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (ConnectionException $e) {
            return response()->json([
                'message' => 'Gagal terhubung ke Groq API: ' . $e->getMessage(),
            ], 502);
        } catch (RequestException $e) {
            $body = $e->response->json();

            return response()->json([
                'message' => 'Groq API error.',
                'status'  => $e->response->status(),
                'detail'  => $body['error']['message'] ?? $e->response->body(),
            ], 502);
        }

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Groq API error.',
                'status'  => $response->status(),
                'detail'  => $response->json('error.message') ?? $response->body(),
            ], 502);
        }

        $content = trim((string) $response->json('choices.0.message.content'));

        // Lindungi dari ```json fence yang kadang menyelip
        // meski dengan response_format=json_object
        if (Str::startsWith($content, '```')) {
            $content = trim(str_replace(['```json', '```'], '', $content));
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return response()->json([
                'message' => 'Model tidak mengembalikan JSON valid, coba lagi atau ubah raw_text.',
                'raw'     => $content,
            ], 502);
        }

        if (isset($data['total_harga'])) {
            $data['total_harga'] = is_numeric($data['total_harga'])
                ? (float) $data['total_harga']
                : 0;
        }

        // Pastikan semua kunci ada
        foreach (self::KEYS as $key) {
            $data[$key] = $data[$key] ?? null;
        }

        return response()->json([
            'message' => 'Ekstraksi berhasil!',
            'data'    => $data,
        ], 200);
    }
}
