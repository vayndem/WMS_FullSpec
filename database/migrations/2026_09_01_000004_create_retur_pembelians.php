<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_pembelians', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur', 100)->unique();
            $table->unsignedBigInteger('lpb_id');
            $table->date('tanggal');
            $table->text('alasan');
            $table->string('status', 20)->default('POSTED')
                ->comment('Status retur: POSTED=aktif dan sudah mengurangi stok/GRNI, REVERSED=telah dibalik');
            $table->decimal('total_nilai', 18, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->foreign('lpb_id')->references('id')->on('wms_penerimaan_barang')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('retur_pembelian_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retur_pembelian_id');
            $table->unsignedBigInteger('lpb_detail_id');
            $table->decimal('jumlah_retur', 18, 6);
            $table->decimal('harga', 18, 2);
            $table->decimal('total_harga', 18, 2);
            $table->timestamps();

            $table->foreign('retur_pembelian_id')->references('id')->on('retur_pembelians')->onDelete('cascade');
            $table->foreign('lpb_detail_id')->references('id')->on('wms_penerimaan_barang_detail')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_pembelian_details');
        Schema::dropIfExists('retur_pembelians');
    }
};
