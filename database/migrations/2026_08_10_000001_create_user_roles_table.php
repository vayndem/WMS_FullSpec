<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('name')->unique();
        });

        DB::table('user_roles')->insert([
            ['id' => User::ROLE_SUPER_ADMIN, 'name' => 'super_admin'],
            ['id' => User::ROLE_PURCHASING, 'name' => 'purchasing'],
            ['id' => User::ROLE_FINANCE, 'name' => 'finance'],
            ['id' => User::ROLE_WAREHOUSE, 'name' => 'warehouse'],
            ['id' => User::ROLE_ACCOUNTING, 'name' => 'accounting'],
        ]);

        DB::table('users')
            ->where('type', 5)
            ->update(['type' => User::ROLE_PURCHASING]);
        DB::table('users')
            ->where('type', 13)
            ->update(['type' => User::ROLE_FINANCE]);
        DB::table('users')
            ->where('type', 14)
            ->update(['type' => User::ROLE_WAREHOUSE]);
        DB::table('users')
            ->where('type', 33)
            ->update(['type' => User::ROLE_ACCOUNTING]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('type')->default(User::ROLE_SUPER_ADMIN)->change();
            $table->foreign('type')->references('id')->on('user_roles');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['type']);
        });

        Schema::dropIfExists('user_roles');
    }
};
