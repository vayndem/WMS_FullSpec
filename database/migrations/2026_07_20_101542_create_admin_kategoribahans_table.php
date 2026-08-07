<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategoribahan', function (Blueprint $table) {
            $table->id();
            $table->string('katnama');
            $table->unsignedBigInteger('tipe_pembebanan_id')->nullable();
            $table->unsignedBigInteger('coa_persediaan_id')->nullable();
            $table->unsignedBigInteger('coa_beban_id')->nullable();
            $table->unsignedBigInteger('coa_clearing_lpb_id')->nullable();
            $table->timestamps();

            $table->foreign('tipe_pembebanan_id')->references('id')->on('tipe_pembebanans')->onDelete('set null');
            $table->foreign('coa_persediaan_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('coa_beban_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('coa_clearing_lpb_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategoribahan');
    }
};
