<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_lpb_id')->nullable()->change();
            $table->foreignId('supplier_id')->nullable()->after('invoice_lpb_id')
                ->constrained('suppliers')->restrictOnDelete();
        });

        DB::statement('
            UPDATE wms_pembayaran_faktur p
            JOIN wms_faktur_pembelian f ON f.id = p.invoice_lpb_id
            SET p.supplier_id = f.kode_supplier
            WHERE p.supplier_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('wms_pembayaran_faktur', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
