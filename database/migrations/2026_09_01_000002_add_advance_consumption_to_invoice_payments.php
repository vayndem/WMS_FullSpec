<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('uang_muka_sumber_payment_id')->nullable()->after('kelebihan_pembayaran')
                ->comment('Referensi ke invoice_payments.id yang menjadi sumber uang muka supplier, bila pembayaran ini memakai uang muka');
            $table->decimal('uang_muka_dipakai', 15, 2)->default(0)->after('uang_muka_sumber_payment_id')
                ->comment('Nominal uang muka supplier yang dipakai dari uang_muka_sumber_payment_id untuk mengurangi hutang pada pembayaran ini');
            $table->foreign('uang_muka_sumber_payment_id')->references('id')->on('invoice_payments')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropForeign(['uang_muka_sumber_payment_id']);
            $table->dropColumn(['uang_muka_sumber_payment_id', 'uang_muka_dipakai']);
        });
    }
};
