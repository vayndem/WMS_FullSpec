<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_pengeluaran_barang', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 40)->unique();
            $table->date('tanggal');
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('pesanan_jasa_id')->nullable()->constrained('wms_pesanan_pembelian')->nullOnDelete();
            $table->string('keperluan', 30)->default('PERBAIKAN')->index();
            $table->string('status', 25)->default('DRAFT')->index();
            $table->date('estimasi_kembali')->nullable();
            $table->date('tanggal_kembali')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->foreignId('dikeluarkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('wms_pengeluaran_barang_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengeluaran_id')->constrained('wms_pengeluaran_barang')->cascadeOnDelete();
            $table->foreignId('aset_id')->nullable()->constrained('wms_asets')->restrictOnDelete();
            $table->string('deskripsi', 255);
            $table->string('nomor_seri', 100)->nullable();
            $table->decimal('jumlah', 18, 6)->default(1);
            $table->string('satuan', 30)->default('UNIT');
            $table->string('kondisi_keluar', 255)->nullable();
            $table->string('kondisi_kembali', 255)->nullable();
            $table->string('status', 25)->default('DI_VENDOR')->index();
            $table->date('tanggal_kembali')->nullable();
            $table->timestamps();
            $table->index(['pengeluaran_id', 'aset_id'], 'pengeluaran_detail_induk_index');
        });

        Schema::create('wms_barang_titipan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 40)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('pengeluaran_id')->nullable()->constrained('wms_pengeluaran_barang')->nullOnDelete();
            $table->string('deskripsi', 255);
            $table->string('nomor_seri', 100)->nullable();
            $table->decimal('jumlah', 18, 6)->default(1);
            $table->string('satuan', 30)->default('UNIT');
            $table->date('tanggal_terima');
            $table->date('estimasi_kembali')->nullable();
            $table->date('tanggal_kembali')->nullable();
            $table->decimal('nilai_taksiran', 18, 2)->nullable()
                ->comment('Memo saja: barang milik vendor, tidak masuk persediaan maupun aset');
            $table->string('status', 25)->default('DITERIMA')->index();
            $table->string('catatan', 255)->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_barang_titipan');
        Schema::dropIfExists('wms_pengeluaran_barang_detail');
        Schema::dropIfExists('wms_pengeluaran_barang');
    }
};
