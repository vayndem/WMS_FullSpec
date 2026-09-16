<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_data_pesanan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->foreignId('pesanan_penjualan_id')->constrained('wms_pesanan_penjualan')->restrictOnDelete();
            $table->foreignId('pesanan_penjualan_detail_id')->constrained('wms_pesanan_penjualan_detail')->restrictOnDelete();
            $table->foreignId('bahan_hasil_id')->constrained('bahans')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->decimal('jumlah_rencana', 18, 6);
            $table->decimal('jumlah_selesai', 18, 6)->default(0);
            $table->decimal('jumlah_terkirim', 18, 6)->default(0);
            $table->decimal('biaya_per_unit', 18, 4)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diselesaikan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diselesaikan_pada')->nullable();
            $table->timestamps();
            $table->index(['status', 'tanggal'], 'data_pesanan_status_index');
        });

        Schema::create('wms_data_pesanan_biaya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pesanan_id')->constrained('wms_data_pesanan')->cascadeOnDelete();
            $table->string('sumber', 20);
            $table->unsignedBigInteger('referensi_id');
            $table->date('tanggal');
            $table->decimal('nilai', 18, 2);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
            $table->unique(['data_pesanan_id', 'sumber', 'referensi_id'], 'data_pesanan_biaya_unique');
            $table->index(['sumber', 'referensi_id'], 'data_pesanan_biaya_sumber_index');
        });

        Schema::table('wms_pemakaian_barang', function (Blueprint $table) {
            $table->unsignedBigInteger('data_pesanan_id')->nullable()->after('kode_datapesanan');
            $table->foreign('data_pesanan_id')->references('id')->on('wms_data_pesanan')->nullOnDelete();
        });

        Schema::table('wms_surat_jalan_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('data_pesanan_id')->nullable()->after('bahan_id');
            $table->foreign('data_pesanan_id')->references('id')->on('wms_data_pesanan')->nullOnDelete();
        });

        Schema::table('wms_penerimaan_jasa_alokasi', function (Blueprint $table) {
            $table->unsignedBigInteger('data_pesanan_id')->nullable()->after('datapesanan_code');
            $table->foreign('data_pesanan_id')->references('id')->on('wms_data_pesanan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wms_penerimaan_jasa_alokasi', function (Blueprint $table) {
            $table->dropForeign(['data_pesanan_id']);
            $table->dropColumn('data_pesanan_id');
        });

        Schema::table('wms_surat_jalan_detail', function (Blueprint $table) {
            $table->dropForeign(['data_pesanan_id']);
            $table->dropColumn('data_pesanan_id');
        });

        Schema::table('wms_pemakaian_barang', function (Blueprint $table) {
            $table->dropForeign(['data_pesanan_id']);
            $table->dropColumn('data_pesanan_id');
        });

        Schema::dropIfExists('wms_data_pesanan_biaya');
        Schema::dropIfExists('wms_data_pesanan');
    }
};
