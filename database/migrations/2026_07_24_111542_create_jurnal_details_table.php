<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_jurnal_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id');
            $table->unsignedBigInteger('coa_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('jurnal_id')->references('id')->on('wms_jurnal')->onDelete('cascade');
            $table->foreign('coa_id')->references('id')->on('wms_bagan_akun')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_jurnal_detail');
    }
};
