<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'SuperAdmin', 'email' => 'superadmin@wms.local', 'type' => User::ROLE_SUPER_ADMIN],
            ['name' => 'Purchasing Demo', 'email' => 'purchasing@wms.local', 'type' => User::ROLE_PURCHASING],
            ['name' => 'Finance Demo', 'email' => 'finance@wms.local', 'type' => User::ROLE_FINANCE],
            ['name' => 'Warehouse Demo', 'email' => 'warehouse@wms.local', 'type' => User::ROLE_WAREHOUSE],
            ['name' => 'Accounting Demo', 'email' => 'accounting@wms.local', 'type' => User::ROLE_ACCOUNTING],
            ['name' => 'Produksi Demo', 'email' => 'produksi@wms.local', 'type' => User::ROLE_PRODUCTION],
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
