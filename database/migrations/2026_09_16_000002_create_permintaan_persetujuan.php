<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_permintaan_persetujuan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 32)->unique();
            $table->string('jenis', 32);
            $table->string('sub_jenis', 32);
            $table->string('referensi_type', 191)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('ringkasan', 255);
            $table->json('payload');
            $table->string('status', 20)->default('PENDING');
            $table->text('alasan');
            $table->text('catatan_checker')->nullable();
            $table->foreignId('diminta_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->timestamps();
            $table->index(['jenis', 'status'], 'permintaan_persetujuan_antrean_index');
            $table->index(['referensi_type', 'referensi_id'], 'permintaan_persetujuan_referensi_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_permintaan_persetujuan');
    }
};
