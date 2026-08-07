<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Purchasing Demo', 'email' => 'purchasing@wms.local', 'type' => 5],
            ['name' => 'Finance Demo', 'email' => 'finance@wms.local', 'type' => 13],
            ['name' => 'Warehouse Demo', 'email' => 'warehouse@wms.local', 'type' => 14],
            ['name' => 'Accounting Demo', 'email' => 'accounting@wms.local', 'type' => 33],
        ];

        foreach ($users as $attributes) {
            User::updateOrCreate(
                ['email' => $attributes['email']],
                $attributes + [
                    'password' => 'Wms12345!',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
