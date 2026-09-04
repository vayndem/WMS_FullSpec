<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_bagan_akun', function (Blueprint $table) {
            $table->id();
            $table->string('kode_akun', 50)->unique();
            $table->string('nama_akun', 150);
            $table->enum('kategori_akun', ['ASET', 'LIABILITAS', 'EKUITAS', 'PENDAPATAN', 'BEBAN']);
            $table->enum('posisi_normal', ['DEBIT', 'KREDIT']);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_bagan_akun');
    }
};
