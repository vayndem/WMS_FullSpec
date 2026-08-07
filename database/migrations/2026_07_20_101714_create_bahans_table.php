<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori');
            $table->string('nama', 200);
            $table->string('keterangan_bahan', 200)->nullable();
            $table->string('satuan', 250);
            $table->double('stok_onhand')->default(0);
            $table->double('stok_onpurchase')->default(0);
            $table->double('planning')->default(0);
            $table->double('stokawal')->default(0);
            $table->double('pengambilan_stokawal')->default(0);
            $table->double('berat_kecil', 11, 2)->nullable()->default(1.00);
            $table->string('satuan_kecil', 11)->nullable();
            $table->unsignedBigInteger('tipe_gudang');
            $table->unsignedBigInteger('tipe_barang');

            $table->timestamps();

            $table->foreign('tipe_gudang')->references('id')->on('admin_namagudang')->onDelete('cascade');
            $table->foreign('tipe_barang')->references('id')->on('kategoribahan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan');
    }
};
