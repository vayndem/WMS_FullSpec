<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelians', function (Blueprint $table) {
            $table->id();
            $table->string('no_po', 22)->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('supplier_id');
            $table->string('no_order', 250)->nullable()->default('-');
            $table->string('untuk_perhatian', 250)->nullable()->default('-');
            $table->string('term', 250)->nullable()->default('-');
            $table->text('notes')->nullable();
            $table->decimal('ppn', 8, 4)->default(0);
            $table->decimal('total_exclude', 18, 2)->default(0);
            $table->decimal('total_ppn', 18, 2)->default(0);
            $table->decimal('total_include', 18, 2)->default(0);
            $table->decimal('diskon', 18, 2)->default(0);
            $table->decimal('ongkir', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN')
                ->comment('Lifecycle PO: OPEN atau CLOSED');
            $table->string('term_pengiriman', 10)->default('Tidak Ada');
            $table->tinyInteger('jenis')->default(0);
            $table->string('input_label', 100)->nullable()->default('Freight Handling');
            $table->tinyInteger('cetak')->default(0)
                ->comment('Jumlah/penanda proses cetak dokumen PO');
            $table->boolean('kunci')->default(false)
                ->comment('Kunci dokumen: 0=dapat diedit/dicetak, 1=terkunci setelah cetak');
            $table->tinyInteger('counter_asli')->default(1);
            $table->tinyInteger('cetak_ulang')->default(0);
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelians');
    }
};
