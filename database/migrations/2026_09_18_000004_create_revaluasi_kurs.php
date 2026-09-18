<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_kurs_penutup', function (Blueprint $table) {
            $table->id();
            $table->string('mata_uang', 3);
            $table->string('periode', 7);
            $table->decimal('kurs', 18, 4);
            $table->string('sumber', 100)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mata_uang', 'periode'], 'kurs_penutup_unique');
        });

        Schema::create('wms_revaluasi_kurs', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->date('tanggal');
            $table->foreignId('faktur_pembelian_id')->constrained('wms_faktur_pembelian')->restrictOnDelete();
            $table->string('mata_uang', 3);
            $table->decimal('valas_beredar', 18, 2);
            $table->decimal('kurs_lama', 18, 4);
            $table->decimal('kurs_baru', 18, 4);
            $table->decimal('nilai_lama', 18, 2);
            $table->decimal('nilai_baru', 18, 2);
            $table->decimal('selisih', 18, 2);
            $table->foreignId('journal_id')->nullable()->constrained('wms_jurnal')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['periode', 'faktur_pembelian_id'], 'revaluasi_kurs_unique');
            $table->index('periode', 'revaluasi_kurs_periode_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_revaluasi_kurs');
        Schema::dropIfExists('wms_kurs_penutup');
    }
};
