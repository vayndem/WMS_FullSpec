<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 191);
            $table->string('npwp', 30)->nullable();
            $table->string('telp', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('alamat')->nullable();
            $table->string('up', 100)->nullable();
            $table->unsignedSmallInteger('termin_hari')->default(30);
            $table->decimal('plafon_kredit', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pesanan_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->string('nomor_po_pelanggan', 50)->nullable();
            $table->boolean('is_ppn')->default(true);
            $table->decimal('tarif_ppn', 8, 4)->default(11);
            $table->decimal('total_dpp', 18, 2)->default(0);
            $table->decimal('total_ppn', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->string('status', 20)->default('OPEN');
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'tanggal'], 'pesanan_penjualan_status_index');
        });

        Schema::create('wms_pesanan_penjualan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_penjualan_id')->constrained('wms_pesanan_penjualan')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->decimal('jumlah_terkirim', 18, 6)->default(0);
            $table->decimal('harga_satuan', 18, 2);
            $table->decimal('total_harga', 18, 2);
            $table->string('satuan', 30)->nullable();
            $table->timestamps();
            $table->index(['pesanan_penjualan_id', 'bahan_id'], 'pesanan_penjualan_detail_index');
        });

        Schema::create('wms_surat_jalan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->foreignId('pesanan_penjualan_id')->constrained('wms_pesanan_penjualan')->restrictOnDelete();
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->string('nomor_kendaraan', 30)->nullable();
            $table->string('pengirim', 100)->nullable();
            $table->decimal('total_hpp', 18, 2)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->text('keterangan')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('wms_jurnal')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diposting_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diposting_pada')->nullable();
            $table->timestamps();
            $table->index(['status', 'tanggal'], 'surat_jalan_status_index');
        });

        Schema::create('wms_surat_jalan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_jalan_id')->constrained('wms_surat_jalan')->cascadeOnDelete();
            $table->foreignId('pesanan_penjualan_detail_id')->constrained('wms_pesanan_penjualan_detail')->restrictOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->decimal('jumlah_terfaktur', 18, 6)->default(0);
            $table->decimal('harga_satuan', 18, 2);
            $table->decimal('hpp', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['surat_jalan_id', 'bahan_id'], 'surat_jalan_detail_index');
        });

        Schema::create('wms_surat_jalan_alokasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_jalan_detail_id')->constrained('wms_surat_jalan_detail')->cascadeOnDelete();
            $table->foreignId('inventory_layer_id')->constrained('wms_layer_persediaan')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->decimal('harga_satuan', 18, 4);
            $table->decimal('total_hpp', 18, 2);
            $table->timestamps();
            $table->unique(['surat_jalan_detail_id', 'inventory_layer_id'], 'surat_jalan_alokasi_unique');
        });

        Schema::create('wms_faktur_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->date('jatuh_tempo');
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnDelete();
            $table->string('no_faktur_pajak', 30)->nullable();
            $table->boolean('is_ppn')->default(true);
            $table->decimal('tarif_ppn', 8, 4)->default(11);
            $table->decimal('total_dpp', 18, 2)->default(0);
            $table->decimal('total_ppn', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('sisa_tagihan', 18, 2)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->text('keterangan')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('wms_jurnal')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diposting_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diposting_pada')->nullable();
            $table->timestamps();
            $table->index(['status', 'jatuh_tempo'], 'faktur_penjualan_status_index');
        });

        Schema::create('wms_faktur_penjualan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faktur_penjualan_id')->constrained('wms_faktur_penjualan')->cascadeOnDelete();
            $table->foreignId('surat_jalan_detail_id')->constrained('wms_surat_jalan_detail')->restrictOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->decimal('harga_satuan', 18, 2);
            $table->decimal('total_harga', 18, 2);
            $table->timestamps();
        });

        Schema::create('wms_penerimaan_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->foreignId('faktur_penjualan_id')->constrained('wms_faktur_penjualan')->restrictOnDelete();
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnDelete();
            $table->foreignId('coa_kas_bank_id')->constrained('wms_bagan_akun')->restrictOnDelete();
            $table->decimal('jumlah', 18, 2);
            $table->string('referensi', 50)->nullable();
            $table->string('status', 20)->default('POSTED');
            $table->foreignId('journal_id')->nullable()->constrained('wms_jurnal')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['faktur_penjualan_id', 'status'], 'penerimaan_pembayaran_index');
        });

        Schema::create('wms_retur_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->foreignId('surat_jalan_id')->constrained('wms_surat_jalan')->restrictOnDelete();
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnDelete();
            $table->foreignId('faktur_penjualan_id')->nullable()->constrained('wms_faktur_penjualan')->nullOnDelete();
            $table->decimal('total_dpp', 18, 2)->default(0);
            $table->decimal('total_ppn', 18, 2)->default(0);
            $table->decimal('total_hpp', 18, 2)->default(0);
            $table->string('status', 20)->default('POSTED');
            $table->text('alasan');
            $table->foreignId('journal_id')->nullable()->constrained('wms_jurnal')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('wms_retur_penjualan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_penjualan_id')->constrained('wms_retur_penjualan')->cascadeOnDelete();
            $table->foreignId('surat_jalan_detail_id')->constrained('wms_surat_jalan_detail')->restrictOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->decimal('harga_satuan', 18, 2);
            $table->decimal('total_harga', 18, 2);
            $table->decimal('hpp', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_retur_penjualan_detail');
        Schema::dropIfExists('wms_retur_penjualan');
        Schema::dropIfExists('wms_penerimaan_pembayaran');
        Schema::dropIfExists('wms_faktur_penjualan_detail');
        Schema::dropIfExists('wms_faktur_penjualan');
        Schema::dropIfExists('wms_surat_jalan_alokasi');
        Schema::dropIfExists('wms_surat_jalan_detail');
        Schema::dropIfExists('wms_surat_jalan');
        Schema::dropIfExists('wms_pesanan_penjualan_detail');
        Schema::dropIfExists('wms_pesanan_penjualan');
        Schema::dropIfExists('pelanggans');
    }
};
