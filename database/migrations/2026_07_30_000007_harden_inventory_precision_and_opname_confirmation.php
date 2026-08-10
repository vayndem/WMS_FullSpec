<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan', function (Blueprint $table) {
            $table->decimal('stok_onhand', 18, 6)->default(0)->change();
            $table->decimal('stok_onpurchase', 18, 6)->default(0)->change();
            $table->decimal('planning', 18, 6)->default(0)->change();
            $table->decimal('stokawal', 18, 6)->default(0)->change();
            $table->decimal('pengambilan_stokawal', 18, 6)->default(0)->change();
            $table->decimal('berat_kecil', 18, 6)->nullable()->default(1)->change();
        });
        Schema::table('request_details', function (Blueprint $table) {
            $table->decimal('jumlah_minta', 18, 6)->change();
            $table->decimal('jumlah_acc', 18, 6)->nullable()->change();
            $table->decimal('realisasi', 18, 6)->default(0)->change();
            $table->decimal('berat_kecil', 18, 6)->nullable()->default(1)->change();
        });
        Schema::table('pembelian_details', function (Blueprint $table) {
            $table->decimal('jumlah', 18, 6)->default(0)->change();
            $table->decimal('diterima', 18, 6)->default(0)->change();
        });
        Schema::table('lpbdetails', function (Blueprint $table) {
            $table->decimal('jumlah_barang_diterima', 18, 6)->default(0)->change();
            $table->decimal('jumlah_dipakai', 18, 6)->default(0)->change();
            $table->decimal('jumlah_tersisa', 18, 6)->default(0)->change();
        });
        Schema::table('npks', function (Blueprint $table) {
            $table->decimal('jumlah', 18, 6)->default(0)->change();
            $table->decimal('jumlah_terkirim', 18, 6)->default(0)->change();
            $table->decimal('jumlah_stok', 18, 6)->default(0)->change();
        });
        Schema::table('pembelian_detail_histories', function (Blueprint $table) {
            $table->decimal('jumlah', 18, 6)->default(0)->change();
            $table->decimal('diterima', 18, 6)->default(0)->change();
        });
        Schema::table('inventory_layers', function (Blueprint $table) {
            $table->decimal('initial_quantity', 18, 6)->change();
            $table->decimal('remaining_quantity', 18, 6)->change();
        });
        Schema::table('npk_stock_allocations', function (Blueprint $table) {
            $table->decimal('quantity', 18, 6)->change();
        });
        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->decimal('system_quantity', 18, 6)->change();
            $table->decimal('physical_quantity', 18, 6)->change();
            $table->decimal('difference_quantity', 18, 6)->change();
            $table->unsignedBigInteger('physical_confirmed_by')->nullable()->after('notes')
                ->comment('ID user lokal role Warehouse yang mengonfirmasi kuantitas fisik');
            $table->timestamp('physical_confirmed_at')->nullable()->after('physical_confirmed_by')
                ->comment('Waktu konfirmasi kuantitas fisik oleh Gudang');
            $table->unsignedBigInteger('valuation_confirmed_by')->nullable()->after('physical_confirmed_at')
                ->comment('ID user lokal role Accounting yang mengonfirmasi harga/valuasi');
            $table->timestamp('valuation_confirmed_at')->nullable()->after('valuation_confirmed_by')
                ->comment('Waktu konfirmasi harga/valuasi oleh Accounting');
        });
        Schema::table('stock_opname_allocations', function (Blueprint $table) {
            $table->decimal('quantity', 18, 6)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->dropColumn([
                'physical_confirmed_by',
                'physical_confirmed_at',
                'valuation_confirmed_by',
                'valuation_confirmed_at',
            ]);
        });
    }
};
