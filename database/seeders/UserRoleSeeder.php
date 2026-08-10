<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            User::ROLE_SUPER_ADMIN => 'super_admin',
            User::ROLE_PURCHASING => 'purchasing',
            User::ROLE_FINANCE => 'finance',
            User::ROLE_WAREHOUSE => 'warehouse',
            User::ROLE_ACCOUNTING => 'accounting',
            User::ROLE_PRODUCTION => 'production',
        ] as $id => $name) {
            UserRole::updateOrCreate(
                ['id' => $id],
                ['name' => $name]
            );
        }
    }
}
