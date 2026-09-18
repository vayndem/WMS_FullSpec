<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_pusat_kerja', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 191);
            $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
            $table->decimal('kapasitas_menit_per_hari', 18, 2)->nullable();
            $table->string('status', 20)->default('AKTIF');
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'gudang_id'], 'pusat_kerja_status_index');
        });

        Schema::create('wms_bom_operasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('wms_bom')->cascadeOnDelete();
            $table->foreignId('pusat_kerja_id')->constrained('wms_pusat_kerja')->restrictOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->string('nama_operasi', 191);
            $table->decimal('waktu_standar_menit', 18, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['bom_id', 'urutan'], 'bom_operasi_urutan_unique');
            $table->index('pusat_kerja_id', 'bom_operasi_pusat_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_bom_operasi');
        Schema::dropIfExists('wms_pusat_kerja');
    }
};
