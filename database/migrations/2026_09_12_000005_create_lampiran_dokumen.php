<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_lampiran_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('lampiran_type');
            $table->unsignedBigInteger('lampiran_id');
            $table->string('kategori', 30)->default('LAIN');
            $table->string('nama_asli');
            $table->string('path');
            $table->string('disk', 30);
            $table->string('mime', 120);
            $table->unsignedBigInteger('ukuran');
            $table->string('checksum', 64)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['lampiran_type', 'lampiran_id'], 'lampiran_dokumen_induk_index');
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_lampiran_dokumen');
    }
};
