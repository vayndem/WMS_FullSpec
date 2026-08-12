<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_lpb_id');
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
            $table->unsignedBigInteger('finance_user_id');
            $table->enum('status', ['POSTED', 'VOID'])->default('POSTED');
            $table->timestamps();

            $table->foreign('invoice_lpb_id')->references('id')->on('invoice_lpbs')->onDelete('cascade');
            $table->foreign('coa_kas_bank_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('finance_user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
