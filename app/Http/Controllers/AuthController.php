<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login admin: verifikasi username + password (hash bcrypt),
     * lalu kembalikan data admin beserta Sanctum API token.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('username', $validated['username'])->first();

        // Cari user by username saja supaya errornya selalu sama
        // (gak bocorin info kalo username itu ada/gak di sistem).
        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'message' => 'Username atau password salah!',
            ], 401);
        }

        // Issue token Sanctum buat admin ini.
        // Token berlaku selamanya; abal-abalkan di abilities sesuai kebutuhan.
        $token = $admin->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil!',
            'token'   => $token,
            'data'    => $admin,
        ], 200);
    }

    /**
     * Logout admin: hapus token yang sedang dipakai
     * (butuh header Authorization: Bearer <token>).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil!',
        ], 200);
    }
}
