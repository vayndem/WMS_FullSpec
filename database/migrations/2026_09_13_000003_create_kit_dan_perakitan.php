<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_kit', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('nama', 150);
            $table->foreignId('bahan_hasil_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah_hasil', 18, 6)->default(1);
            $table->boolean('aktif')->default(true)->index();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
            $table->index('bahan_hasil_id');
        });

        Schema::create('wms_kit_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained('wms_kit')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->timestamps();
            $table->unique(['kit_id', 'bahan_id'], 'kit_komponen_unik');
        });

        Schema::create('wms_perakitan_kit', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 40)->unique();
            $table->foreignId('kit_id')->constrained('wms_kit')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->string('jenis', 20)->default('RAKIT')->index();
            $table->date('tanggal');
            $table->decimal('jumlah_kit', 18, 6);
            $table->decimal('nilai_total', 18, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->index();
            $table->string('catatan', 255)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diposting_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diposting_pada')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('wms_jurnal')->restrictOnDelete();
            $table->timestamps();
            $table->index(['gudang_id', 'status']);
        });

        Schema::create('wms_perakitan_kit_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perakitan_id')->constrained('wms_perakitan_kit')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->decimal('nilai', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['perakitan_id', 'bahan_id'], 'perakitan_detail_induk_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_perakitan_kit_detail');
        Schema::dropIfExists('wms_perakitan_kit');
        Schema::dropIfExists('wms_kit_komponen');
        Schema::dropIfExists('wms_kit');
    }
};
