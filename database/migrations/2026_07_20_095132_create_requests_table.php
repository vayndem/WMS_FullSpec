<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('no_request')->unique();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING')
                ->comment('Lifecycle request: PENDING, APPROVED, atau REJECTED');
            $table->text('catatan_approver')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()
                ->comment('ID user lokal yang menyetujui/menolak request');
            $table->timestamp('approved_at')->nullable()
                ->comment('Waktu keputusan persetujuan atau penolakan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
