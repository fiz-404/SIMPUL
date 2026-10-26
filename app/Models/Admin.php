<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasApiTokens, HasFactory;

    protected $table = 'admin'; // tabel admin di Postgre
    public $timestamps = false; // gaada created_at/updated_at di tabel admin

    protected $fillable = [
        'username',
        'password',
    ];

    // Jangan pernah bocorin hash password di response JSON
    protected $hidden = [
        'password',
    ];
}
