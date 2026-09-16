<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_jurnal_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('gudang_id')->nullable()->after('coa_id');
            $table->foreign('gudang_id')->references('id')->on('gudangs')->nullOnDelete();
            $table->index(['gudang_id', 'coa_id'], 'jurnal_detail_gudang_akun_index');
        });
    }

    public function down(): void
    {
        Schema::table('wms_jurnal_detail', function (Blueprint $table) {
            $table->dropForeign(['gudang_id']);
            $table->dropIndex('jurnal_detail_gudang_akun_index');
            $table->dropColumn('gudang_id');
        });
    }
};
