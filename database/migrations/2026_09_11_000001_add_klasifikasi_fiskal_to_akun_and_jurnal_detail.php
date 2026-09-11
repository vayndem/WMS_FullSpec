<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_bagan_akun', function (Blueprint $table) {
            $table->string('klasifikasi_fiskal', 20)->default('NONE')->after('is_cash_bank')
                ->comment('NONE=tidak dikoreksi, BEDA_TETAP, BEDA_WAKTU, PENGHASILAN_FINAL');
        });

        Schema::table('wms_jurnal_detail', function (Blueprint $table) {
            $table->string('klasifikasi_fiskal', 20)->nullable()->after('coa_id')
                ->comment('Override klasifikasi fiskal akun untuk baris ini saja');
        });
    }

    public function down(): void
    {
        Schema::table('wms_bagan_akun', fn (Blueprint $table) => $table->dropColumn('klasifikasi_fiskal'));
        Schema::table('wms_jurnal_detail', fn (Blueprint $table) => $table->dropColumn('klasifikasi_fiskal'));
    }
};
