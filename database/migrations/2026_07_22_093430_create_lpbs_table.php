<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpbs', function (Blueprint $table) {
            $table->id();
            $table->char('id_lpb', 50)->unique();
            $table->date('tanggal');
            $table->string('no_po', 30)->index();
            $table->string('no_sj', 250);
            $table->integer('id_user');
            $table->tinyInteger('flag')->default(0)
                ->comment('Flag proses LPB; 0=aktif/belum diproses lanjutan');
            $table->string('no_invoice', 50)->nullable();
            $table->integer('status')->default(0)
                ->comment('Status proses LPB; gunakan document_type dan is_cancelled untuk klasifikasi baru');
            $table->tinyInteger('jenis_lpb')->nullable()
                ->comment('Kode jenis penerimaan: 1=LPB barang, 3=BAP jasa; document_type tetap klasifikasi utama WMS');
            $table->tinyInteger('ulang')->default(0);
            $table->tinyInteger('kunci')->default(0)
                ->comment('Kunci dokumen: 0=dapat diedit/dicetak, 1=terkunci setelah cetak/posting');
            $table->tinyInteger('cetakan')->default(0);
            $table->tinyInteger('cetak_ulang')->default(0);
            $table->timestamps();

            $table->foreign('no_po')->references('no_po')->on('pembelians')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpbs');
    }
};
