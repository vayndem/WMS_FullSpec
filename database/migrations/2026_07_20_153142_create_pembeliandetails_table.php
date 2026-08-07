<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian_details', function (Blueprint $table) {
            $table->id();
            $table->string('no_po', 22);
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->double('jumlah')->default(0);
            $table->double('harga')->default(0);
            $table->double('exclude')->default(0);
            $table->double('ppn')->default(0);
            $table->double('include')->default(0);
            $table->double('diterima')->default(0);
            $table->unsignedBigInteger('request_detail_id')->nullable();
            $table->tinyInteger('jenis')->default(0);
            $table->timestamps();

            $table->foreign('no_po')->references('no_po')->on('pembelians')->onDelete('cascade');
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('set null');
            $table->foreign('request_detail_id')->references('id')->on('request_details')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_details');
    }
};
