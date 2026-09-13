<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_gelombang_pengambilan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 40)->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->string('status', 20)->default('DIRENCANAKAN')->index();
            $table->string('strategi', 20)->default('LOKASI');
            $table->string('catatan', 255)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ditugaskan_ke')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dirilis_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();
            $table->index(['gudang_id', 'status']);
        });

        Schema::table('wms_pesanan_pengambilan', function (Blueprint $table) {
            $table->foreignId('gelombang_id')->nullable()->after('id')
                ->constrained('wms_gelombang_pengambilan')->nullOnDelete();
            $table->unsignedInteger('urutan_dalam_gelombang')->nullable()->after('gelombang_id');
        });
    }

    public function down(): void
    {
        Schema::table('wms_pesanan_pengambilan', function (Blueprint $table) {
            $table->dropForeign(['gelombang_id']);
            $table->dropColumn(['gelombang_id', 'urutan_dalam_gelombang']);
        });

        Schema::dropIfExists('wms_gelombang_pengambilan');
    }
};
