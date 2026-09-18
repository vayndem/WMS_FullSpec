<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_pesanan_penjualan', function (Blueprint $table) {
            $table->foreignId('sales_user_id')->nullable()->after('pelanggan_id')
                ->constrained('users')->nullOnDelete();
            $table->index(['sales_user_id', 'tanggal'], 'pesanan_penjualan_sales_index');
        });

        Schema::table('wms_faktur_penjualan', function (Blueprint $table) {
            $table->foreignId('sales_user_id')->nullable()->after('pelanggan_id')
                ->constrained('users')->nullOnDelete();
            $table->index(['sales_user_id', 'tanggal'], 'faktur_penjualan_sales_index');
        });

        DB::statement('UPDATE wms_pesanan_penjualan SET sales_user_id = dibuat_oleh WHERE sales_user_id IS NULL');
        DB::statement('UPDATE wms_faktur_penjualan SET sales_user_id = dibuat_oleh WHERE sales_user_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('wms_pesanan_penjualan', function (Blueprint $table) {
            $table->dropIndex('pesanan_penjualan_sales_index');
            $table->dropConstrainedForeignId('sales_user_id');
        });

        Schema::table('wms_faktur_penjualan', function (Blueprint $table) {
            $table->dropIndex('faktur_penjualan_sales_index');
            $table->dropConstrainedForeignId('sales_user_id');
        });
    }
};
