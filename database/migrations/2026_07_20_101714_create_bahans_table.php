<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori');
            $table->string('nama', 200);
            $table->string('keterangan_bahan', 200)->nullable();
            $table->string('satuan', 250);
            $table->decimal('stok_onhand', 18, 6)->default(0);
            $table->decimal('stok_onpurchase', 18, 6)->default(0);
            $table->decimal('planning', 18, 6)->default(0);
            $table->decimal('stokawal', 18, 6)->default(0);
            $table->decimal('pengambilan_stokawal', 18, 6)->default(0);
            $table->decimal('berat_kecil', 18, 6)->nullable()->default(1);
            $table->string('satuan_kecil', 11)->nullable();
            $table->unsignedBigInteger('tipe_gudang');
            $table->unsignedBigInteger('tipe_barang');

            $table->timestamps();

            $table->foreign('tipe_gudang')->references('id')->on('gudangs')->onDelete('cascade');
            $table->foreign('tipe_barang')->references('id')->on('kategori_bahans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahans');
    }
};
