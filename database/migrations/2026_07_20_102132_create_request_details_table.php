<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->string('nama_barang');
            $table->decimal('jumlah_minta', 18, 6);
            $table->text('keterangan')->nullable();
            $table->decimal('jumlah_acc', 18, 6)->nullable();
            $table->decimal('realisasi', 18, 6)->default(0);
            $table->unsignedBigInteger('kategori')->nullable();
            $table->string('satuan', 250)->nullable();
            $table->decimal('berat_kecil', 18, 6)->nullable()->default(1);
            $table->string('satuan_kecil', 11)->nullable();
            $table->unsignedBigInteger('tipe_gudang')->nullable();
            $table->unsignedBigInteger('tipe_barang')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');
            $table->foreign('bahan_id')->references('id')->on('bahans')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_details');
    }
};
