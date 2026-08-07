<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminKategoribahanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kategoribahan')->insert([
            ['id' => 1, 'katnama' => 'Kertas', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'katnama' => 'Plate', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'katnama' => 'DLL', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
