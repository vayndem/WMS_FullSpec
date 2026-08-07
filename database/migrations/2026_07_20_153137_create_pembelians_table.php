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
            $table->double('ppn')->default(0);
            $table->double('total_exclude')->default(0);
            $table->double('total_ppn')->default(0);
            $table->double('total_include')->default(0);
            $table->double('diskon')->default(0);
            $table->double('ongkir')->default(0);
            $table->double('grand_total')->default(0);
            $table->tinyInteger('status')->default(0)
                ->comment('Status PO: 0=Open, 2=Closed');
            $table->string('term_pengiriman', 10)->default('Tidak Ada');
            $table->tinyInteger('jenis')->default(0);
            $table->string('input_label', 100)->nullable()->default('Freight Handling');
            $table->tinyInteger('cetak')->default(0)
                ->comment('Jumlah/penanda proses cetak dokumen PO');
            $table->tinyInteger('kunci')->default(0)
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
