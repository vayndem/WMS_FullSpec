<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoicelpbs', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice', 100)->unique();
            $table->unsignedBigInteger('kode_supplier')->index();
            $table->date('tanggal');
            $table->date('tgl_deadline_pembayaran')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('ongkir', 15, 2)->default(0);
            $table->decimal('pph', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('status_pembayaran', 50)->default('Belum Dibayar')
                ->comment('Status pembayaran: Belum Dibayar, Dibayar Sebagian, Lunas, atau Dibatalkan');
            $table->decimal('total_pembayaran', 15, 2)->default(0);
            $table->decimal('sisa_tagihan', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->tinyInteger('status')->default(0)
                ->comment('Kode pembayaran: 0=belum dibayar, 1=dibayar sebagian, 2=lunas');
            $table->timestamps();

            $table->foreign('kode_supplier')->references('id')->on('suppliers')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoicelpbs');
    }
};
