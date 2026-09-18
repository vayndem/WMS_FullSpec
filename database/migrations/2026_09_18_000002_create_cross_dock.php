<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_cross_dock', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->foreignId('penerimaan_barang_detail_id')
                ->constrained('wms_penerimaan_barang_detail')->restrictOnDelete();
            $table->foreignId('pesanan_penjualan_detail_id')
                ->constrained('wms_pesanan_penjualan_detail')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('jumlah', 18, 6);
            $table->foreignId('reservasi_id')->nullable()
                ->constrained('wms_reservasi_persediaan')->nullOnDelete();
            $table->string('status', 20)->default('DIRESERVASI');
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikirim_pada')->nullable();
            $table->timestamps();
            $table->index(['status', 'gudang_id'], 'cross_dock_status_index');
            $table->index('pesanan_penjualan_detail_id', 'cross_dock_pesanan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_cross_dock');
    }
};
