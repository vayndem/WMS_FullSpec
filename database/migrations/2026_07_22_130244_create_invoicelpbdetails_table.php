<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoicelpbdetails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_invoice_lpb');
            $table->date('tanggal_pembayaran');
            $table->string('metode_pembayaran', 150);
            $table->unsignedBigInteger('coa_kas_bank_id')->nullable();
            $table->decimal('jumlah_pembayaran', 15, 2)->default(0);
            $table->decimal('potongan_pph23', 15, 2)->default(0);
            $table->decimal('potongan_materai', 15, 2)->default(0);
            $table->decimal('biaya_transfer_bank', 15, 2)->default(0);
            $table->decimal('selisih_bayar', 15, 2)->default(0);
            $table->decimal('total_transaksi_pengurang_hutang', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('id_user_finance');
            $table->timestamps();

            $table->foreign('id_invoice_lpb')->references('id')->on('invoicelpbs')->onDelete('cascade');
            $table->foreign('coa_kas_bank_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('id_user_finance')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoicelpbdetails');
    }
};
