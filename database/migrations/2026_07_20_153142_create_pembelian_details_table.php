<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_pesanan_pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->string('no_po', 22);
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->decimal('jumlah', 18, 6)->default(0);
            $table->decimal('harga', 18, 4)->default(0);
            $table->decimal('exclude', 18, 2)->default(0);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('include', 18, 2)->default(0);
            $table->decimal('diterima', 18, 6)->default(0);
            $table->unsignedBigInteger('request_detail_id')->nullable();
            $table->tinyInteger('jenis')->default(0);
            $table->timestamps();

            $table->foreign('no_po')->references('no_po')->on('wms_pesanan_pembelian')->onDelete('cascade');
            $table->foreign('bahan_id')->references('id')->on('bahans')->onDelete('set null');
            $table->foreign('request_detail_id')->references('id')->on('request_details')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_pesanan_pembelian_detail');
    }
};
