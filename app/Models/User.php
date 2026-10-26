<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model ini me-representasikan tabel public."User" di Postgres
 * (data pembeli/pengguna SIMPUL), BUKAN tabel users standar Laravel.
 * Autentikasi admin tetap lewat model Admin, jadi model ini aman.
 * Tabelnya disembunyikan dulu dari relasi auth global —
 * jangan dipakai untuk login pakai email/password karena
 * kolomnya ga sama (nama, no, alamat).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Nama tabel di Postgres harus di-quote karena huruf kapital
    protected $table = 'User';

    public $timestamps = false; // tabel "User" ga punya created_at/updated_at

    protected $fillable = [
        'nama',
        'no',
        'alamat',
    ];

    // Riwayat pesanan pelanggan ini
    public function pesanans()
    {
        return $this->hasMany(Pesanan::class, 'user_id');
    }
}
