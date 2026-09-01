<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lpb_details', function (Blueprint $table) {
            $table->decimal('jumlah_retur', 18, 6)->default(0)->after('jumlah_tersisa')
                ->comment('Akumulasi kuantitas yang sudah diretur ke supplier dari baris LPB ini');
        });
    }

    public function down(): void
    {
        Schema::table('lpb_details', function (Blueprint $table) {
            $table->dropColumn('jumlah_retur');
        });
    }
};
