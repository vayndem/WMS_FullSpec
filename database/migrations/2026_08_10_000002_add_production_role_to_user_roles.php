<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_roles')->updateOrInsert(
            ['id' => User::ROLE_PRODUCTION],
            ['name' => 'production']
        );
    }

    public function down(): void
    {
        DB::table('user_roles')
            ->where('id', User::ROLE_PRODUCTION)
            ->delete();
    }
};
