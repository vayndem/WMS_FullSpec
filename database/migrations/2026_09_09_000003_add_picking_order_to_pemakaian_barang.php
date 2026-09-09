<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_pemakaian_barang', function (Blueprint $table) {
            $table->foreignId('picking_order_id')->nullable()->after('inventory_reservation_id')
                ->constrained('wms_pesanan_pengambilan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wms_pemakaian_barang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('picking_order_id');
        });
    }
};
