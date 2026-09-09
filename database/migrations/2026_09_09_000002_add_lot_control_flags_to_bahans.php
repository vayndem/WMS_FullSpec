<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahans', function (Blueprint $table) {
            $table->boolean('wajib_lot')->default(false)->after('satuan_kecil');
            $table->boolean('wajib_expiry')->default(false)->after('wajib_lot');
        });
    }

    public function down(): void
    {
        Schema::table('bahans', function (Blueprint $table) {
            $table->dropColumn(['wajib_lot', 'wajib_expiry']);
        });
    }
};
