<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpbdetails', function (Blueprint $table) {
            $table->id();
            $table->string('id_lpb', 50);
            $table->unsignedBigInteger('id_bahan');
            $table->unsignedBigInteger('id_kategori')->nullable();
            $table->decimal('jumlah_barang_diterima', 15, 2)->default(0);
            $table->string('lot_number', 80)->nullable();
            $table->integer('harga')->nullable();
            $table->decimal('jumlah_dipakai', 15, 2)->default(0);
            $table->tinyInteger('flag_dipakai')->default(1)
                ->comment('Ketersediaan lot FIFO: 1=masih memiliki sisa dan dapat dipakai, 0=lot habis');
            $table->timestamps();

            $table->index('id_lpb');
            $table->foreign('id_bahan')->references('id')->on('bahan')->onDelete('cascade');
            $table->foreign('id_kategori')->references('id')->on('kategoribahan')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpbdetails');
    }
};
