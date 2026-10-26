<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Ambil daftar seluruh pelanggan beserta jumlah pesanannya.
     */
    public function index()
    {
        $customers = User::withCount('pesanans')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'message' => 'Data pelanggan berhasil diambil!',
            'data'    => $customers,
        ], 200);
    }

    /**
     * Lihat detail satu pelanggan beserta riwayat pesanannya.
     */
    public function show($id)
    {
        $customer = User::with('pesanans')->find($id);

        if (!$customer) {
            return response()->json([
                'message' => 'Pelanggan tidak ditemukan!',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pelanggan berhasil diambil!',
            'data'    => $customer,
        ], 200);
    }
}
