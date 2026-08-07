<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('npks', function (Blueprint $table) {
            $table->decimal('jumlah_stok', 18, 6)->nullable()->after('jumlah')
                ->comment('Kuantitas yang mengurangi stok dalam satuan utama bahan');
            $table->string('satuan_transaksi', 50)->nullable()->after('jumlah_stok')
                ->comment('Snapshot satuan yang dipakai user saat membuat NPK');
        });

        DB::table('npks')->whereNull('jumlah_stok')->update([
            'jumlah_stok' => DB::raw('jumlah'),
            'satuan_transaksi' => DB::raw('(SELECT satuan FROM bahan WHERE bahan.id = npks.id_barang)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('npks', function (Blueprint $table) {
            $table->dropColumn(['jumlah_stok', 'satuan_transaksi']);
        });
    }
};
