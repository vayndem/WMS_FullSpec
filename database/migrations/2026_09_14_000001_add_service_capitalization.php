<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_kategori_jasa', function (Blueprint $table) {
            $table->string('perlakuan', 20)->default('BEBAN')->after('grni_coa_id')->index()
                ->comment('BEBAN=dibebankan langsung, KAPITALISASI=menambah nilai tercatat aset (PSAK 16)');
        });

        Schema::table('wms_pesanan_jasa_detail', function (Blueprint $table) {
            $table->foreignId('aset_id')->nullable()->after('service_category_id')
                ->constrained('wms_asets')->restrictOnDelete();
            $table->unsignedInteger('tambahan_umur_bulan')->nullable()->after('aset_id')
                ->comment('Perpanjangan masa manfaat akibat jasa yang dikapitalisasi');
        });

        Schema::create('wms_kapitalisasi_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('wms_asets')->restrictOnDelete();
            $table->string('sumber_type', 50);
            $table->unsignedBigInteger('sumber_id');
            $table->date('tanggal');
            $table->decimal('nilai', 18, 2);
            $table->unsignedInteger('tambahan_umur_bulan')->nullable();
            $table->decimal('nilai_sebelum', 18, 2);
            $table->decimal('nilai_sesudah', 18, 2);
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('wms_jurnal')->restrictOnDelete();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['sumber_type', 'sumber_id'], 'kapitalisasi_sumber_index');
            $table->unique(['sumber_type', 'sumber_id'], 'kapitalisasi_sumber_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_kapitalisasi_aset');

        Schema::table('wms_pesanan_jasa_detail', function (Blueprint $table) {
            $table->dropForeign(['aset_id']);
            $table->dropColumn(['aset_id', 'tambahan_umur_bulan']);
        });

        Schema::table('wms_kategori_jasa', function (Blueprint $table) {
            $table->dropIndex(['perlakuan']);
            $table->dropColumn('perlakuan');
        });
    }
};
