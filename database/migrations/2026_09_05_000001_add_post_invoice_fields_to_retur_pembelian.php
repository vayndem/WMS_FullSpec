<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_retur_pembelian', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('lpb_id');
            $table->decimal('hutang_reduction', 18, 2)->default(0)->after('total_nilai');
            $table->unsignedBigInteger('advance_payment_id')->nullable()->after('hutang_reduction');

            $table->foreign('invoice_id')->references('id')->on('wms_faktur_pembelian')->onDelete('restrict');
            $table->foreign('advance_payment_id')->references('id')->on('wms_pembayaran_faktur')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('wms_retur_pembelian', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['advance_payment_id']);
            $table->dropColumn(['invoice_id', 'hutang_reduction', 'advance_payment_id']);
        });
    }
};
