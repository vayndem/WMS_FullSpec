<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->string('mata_uang_asing', 3)->nullable()->after('note');
            $table->decimal('kurs', 18, 4)->nullable()->after('mata_uang_asing');
            $table->decimal('nilai_asing', 18, 2)->nullable()->after('kurs');
            $table->decimal('ppn_impor', 18, 2)->default(0)->after('ongkir');
        });
    }

    public function down(): void
    {
        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->dropColumn(['mata_uang_asing', 'kurs', 'nilai_asing', 'ppn_impor']);
        });
    }
};
