<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Ambil semua pesanan beserta relasi admin dan user.
     */
    public function index()
    {
        $pesanans = Pesanan::with(['admin', 'user'])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'message' => 'Data pesanan berhasil diambil!',
            'data'    => $pesanans,
        ], 200);
    }

    /**
     * Simpan pesanan baru. Status otomatis 'Baru'.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'admin_id'    => ['required', 'integer', 'exists:admin,id'],
            'user_id'     => ['required', 'integer', 'exists:"User",id'],
            'item'        => ['required', 'string', 'max:255'],
            'deskripsi'   => ['nullable', 'string'],
            'total_harga' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['status'] = 'Baru';

        $pesanan = Pesanan::create($validated);

        return response()->json([
            'message' => 'Pesanan berhasil dibuat!',
            'data'    => $pesanan->fresh(),
        ], 201);
    }

    /**
     * Ubah status pesanan: 'Baru', 'Diproses', atau 'Selesai'.
     */
    public function updateStatus(Request $request, $id)
    {
        $pesanan = Pesanan::find($id);

        if (!$pesanan) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan!',
            ], 404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Baru', 'Diproses', 'Selesai'])],
        ]);

        $pesanan->forceFill(['status' => $validated['status']])->save();

        return response()->json([
            'message' => 'Status pesanan berhasil diubah!',
            'data'    => $pesanan->fresh(),
        ], 200);
    }
}
