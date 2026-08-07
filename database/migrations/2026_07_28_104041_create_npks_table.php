<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npks', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100);
            $table->string('kode_datapesanan', 100)->nullable();
            $table->date('tanggal');
            $table->unsignedBigInteger('id_barang');
            $table->unsignedBigInteger('id_gudang_asal')->nullable();
            $table->unsignedBigInteger('id_gudang_tujuan')->nullable();
            $table->double('jumlah')->default(0);
            $table->double('jumlah_terkirim')->default(0);
            $table->date('tgl_terkirim')->nullable();
            $table->tinyInteger('close')->default(0)
                ->comment('Status pengeluaran: 0=draft/belum keluar, 1=barang sudah keluar');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('id_user')->default(0);
            $table->string('operator', 100)->nullable();
            $table->timestamps();

            $table->foreign('id_barang')->references('id')->on('bahan')->onDelete('cascade');
            $table->foreign('id_gudang_asal')->references('id')->on('admin_namagudang')->onDelete('set null');
            $table->foreign('id_gudang_tujuan')->references('id')->on('admin_namagudang')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npks');
    }
};
