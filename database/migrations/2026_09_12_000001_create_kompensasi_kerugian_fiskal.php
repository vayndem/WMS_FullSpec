<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_perhitungan_pajak_penghasilan', function (Blueprint $table) {
            $table->decimal('kompensasi_kerugian', 18, 2)->default(0)->after('laba_fiskal')
                ->comment('Total kerugian fiskal tahun sebelumnya yang dikompensasikan pada tahun ini');
        });

        Schema::create('wms_kompensasi_kerugian_fiskal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perhitungan_id')->constrained('wms_perhitungan_pajak_penghasilan')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun_rugi')->comment('Tahun pajak asal kerugian yang dipakai');
            $table->decimal('jumlah', 18, 2);
            $table->timestamps();
            $table->unique(['perhitungan_id', 'tahun_rugi'], 'kompensasi_perhitungan_tahun_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_kompensasi_kerugian_fiskal');
        Schema::table('wms_perhitungan_pajak_penghasilan', fn (Blueprint $table) => $table->dropColumn('kompensasi_kerugian'));
    }
};
