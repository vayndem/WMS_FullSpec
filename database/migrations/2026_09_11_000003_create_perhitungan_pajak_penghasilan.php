<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_perhitungan_pajak_penghasilan', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun_pajak')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('peredaran_bruto', 18, 2)->default(0);
            $table->decimal('laba_komersial', 18, 2)->default(0);
            $table->decimal('koreksi_positif', 18, 2)->default(0);
            $table->decimal('koreksi_negatif', 18, 2)->default(0);
            $table->decimal('koreksi_beda_waktu', 18, 2)->default(0);
            $table->decimal('laba_fiskal', 18, 2)->default(0);
            $table->decimal('penghasilan_kena_pajak', 18, 2)->default(0);
            $table->decimal('pkp_fasilitas', 18, 2)->default(0)
                ->comment('Bagian PKP yang dapat fasilitas Pasal 31E (tarif 50% x 22%)');
            $table->decimal('pph_terutang', 18, 2)->default(0);
            $table->decimal('beda_waktu_kumulatif', 18, 2)->default(0)
                ->comment('Nilai buku komersial dikurangi nilai buku fiskal seluruh aset');
            $table->decimal('pajak_tangguhan_seharusnya', 18, 2)->default(0);
            $table->decimal('pajak_tangguhan_tercatat', 18, 2)->default(0);
            $table->decimal('gerakan_pajak_tangguhan', 18, 2)->default(0);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamps();
            $table->foreign('journal_id')->references('id')->on('wms_jurnal')->restrictOnDelete();
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_perhitungan_pajak_penghasilan');
    }
};
