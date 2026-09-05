<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wms_faktur_pembelian MODIFY status ENUM('PENDING_APPROVAL', 'UNPAID', 'PARTIALLY_PAID', 'PAID', 'VOID') DEFAULT 'UNPAID'");

        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        DB::table('wms_faktur_pembelian')
            ->whereNotIn('status', ['VOID'])
            ->update(['approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('wms_faktur_pembelian', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at']);
        });

        DB::statement("ALTER TABLE wms_faktur_pembelian MODIFY status ENUM('UNPAID', 'PARTIALLY_PAID', 'PAID', 'VOID') DEFAULT 'UNPAID'");
    }
};
