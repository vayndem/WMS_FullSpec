<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lpbs', function (Blueprint $table) {
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
            $table->decimal('total_pembayaran', 15, 2)->default(0);
            $table->decimal('sisa_tagihan', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->enum('status', ['UNPAID', 'PARTIALLY_PAID', 'PAID', 'VOID'])->default('UNPAID')
                ->comment('Lifecycle pembayaran invoice supplier');
            $table->timestamps();

            $table->foreign('kode_supplier')->references('id')->on('suppliers')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lpbs');
    }
};
