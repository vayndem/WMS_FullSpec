<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_bom', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 191);
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->string('versi', 20)->default('1');
            $table->decimal('jumlah_hasil', 18, 6)->default(1);
            $table->string('status', 20)->default('AKTIF');
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['bahan_id', 'versi'], 'bom_bahan_versi_unique');
            $table->index(['status', 'bahan_id'], 'bom_status_index');
        });

        Schema::create('wms_bom_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('wms_bom')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->string('satuan', 30)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['bom_id', 'bahan_id'], 'bom_detail_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_bom_detail');
        Schema::dropIfExists('wms_bom');
    }
};
