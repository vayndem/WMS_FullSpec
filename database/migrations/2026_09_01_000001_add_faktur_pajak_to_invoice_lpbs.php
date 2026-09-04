<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->string('no_faktur_pajak', 30)->nullable()->after('ppn')
                ->comment('Nomor Seri Faktur Pajak (NSFP) dari DJP, syarat kredit PPN Masukan. Wajib diisi bila jenis_pajak = PPN.');
        });
    }

    public function down(): void
    {
        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->dropColumn('no_faktur_pajak');
        });
    }
};
