<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed data awal SIMPUL:
     * - 1 akun Admin default ('admin' / 'admin123')
     * - 3 sampel pelanggan (tabel public."User")
     * - 3 sampel pesanan dengan status 'Baru', 'Diproses', 'Selesai'
     */
    public function run(): void
    {
        // Admin default
        Admin::firstOrCreate(
            ['username' => 'admin'],
            ['password' => Hash::make('admin123')]
        );

        // 3 sampel pelanggan
        $andi = User::firstOrCreate(
            ['nama' => 'Andi Wijaya', 'no' => '081234567001'],
            ['alamat' => 'Jl. Merdeka No. 12, Jakarta Pusat']
        );
        $budi = User::firstOrCreate(
            ['nama' => 'Budi Santoso', 'no' => '081234567002'],
            ['alamat' => 'Jl. Kenanga No. 8, Bandung']
        );
        $citra = User::firstOrCreate(
            ['nama' => 'Citra Lestari', 'no' => '081234567003'],
            ['alamat' => 'Jl. Mawar No. 21, Surabaya']
        );

        $admin = Admin::where('username', 'admin')->first();

        // 3 sampel pesanan (satu per status)
        $samples = [
            ['user_id' => $andi->id,  'item' => 'Bolu Pandan',      'deskripsi' => 'Ukuran 20 cm, tanpa topping',            'status' => 'Baru',     'total_harga' => 120000],
            ['user_id' => $budi->id,  'item' => 'Kue Ulang Tahun', 'deskripsi' => 'Tema space, tulisan "Selamat Ulang Tahun Raka", ukuran 18 cm', 'status' => 'Diproses', 'total_harga' => 350000],
            ['user_id' => $citra->id, 'item' => 'Bingke Box',      'deskripsi' => 'Box 12 pcs, mixed rasa pandan & gula aren', 'status' => 'Selesai',  'total_harga' => 180000],
        ];

        foreach ($samples as $sample) {
            Pesanan::firstOrCreate([
                'admin_id'    => $admin->id,
                'user_id'     => $sample['user_id'],
                'item'        => $sample['item'],
                'deskripsi'   => $sample['deskripsi'],
                'status'      => $sample['status'],
                'total_harga' => $sample['total_harga'],
            ]);
        }
    }
}
