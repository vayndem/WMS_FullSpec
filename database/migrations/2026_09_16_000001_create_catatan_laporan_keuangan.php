<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_catatan_laporan_keuangan', function (Blueprint $table) {
            $table->id();
            $table->date('periode_dari');
            $table->date('periode_sampai');
            $table->text('gambaran_umum')->nullable();
            $table->text('kebijakan_akuntansi')->nullable();
            $table->text('peristiwa_setelah_periode')->nullable();
            $table->text('komitmen_kontinjensi')->nullable();
            $table->foreignId('disusun_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['periode_dari', 'periode_sampai'], 'calk_periode_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_catatan_laporan_keuangan');
    }
};
