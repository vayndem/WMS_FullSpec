<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kredits', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama', 200);
            $table->timestamps();
        });

        Schema::create('debits', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debits');
        Schema::dropIfExists('kredits');
    }
};
