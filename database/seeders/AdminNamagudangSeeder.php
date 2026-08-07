<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminNamagudangSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admin_namagudang')->insert([
            ['id' => 1, 'nama' => 'Gudang Utama', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama' => 'Gudang PPIC', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama' => 'Gudang Produksi', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nama' => 'Gudang Teknisi', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'nama' => 'Gudang Pisau', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
