<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian_histories', function (Blueprint $table) {
            $table->id('id_history');
            $table->string('no_revisi', 50);
            $table->string('action', 10)->default('REVISION');
            $table->timestamp('archived_at')->useCurrent();
            $table->string('no_po', 22);
            $table->unsignedBigInteger('pembelian_id');
            $table->date('tanggal');
            $table->unsignedBigInteger('supplier_id');
            $table->string('no_order', 250)->nullable();
            $table->string('untuk_perhatian', 250)->nullable();
            $table->string('term', 250)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('ppn', 8, 4)->default(0);
            $table->decimal('total_exclude', 18, 2)->default(0);
            $table->decimal('total_ppn', 18, 2)->default(0);
            $table->decimal('total_include', 18, 2)->default(0);
            $table->decimal('diskon', 18, 2)->default(0);
            $table->decimal('ongkir', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('term_pengiriman', 10)->nullable();
            $table->tinyInteger('jenis')->default(0);
            $table->string('input_label', 100)->nullable();
            $table->tinyInteger('cetak')->default(0);
            $table->boolean('kunci')->default(false)
                ->comment('Snapshot kunci PO: 0=dapat diedit/dicetak, 1=terkunci');
            $table->tinyInteger('counter_asli')->default(1);
            $table->tinyInteger('cetak_ulang')->default(0);
            $table->timestamps();
        });

        Schema::create('pembelian_detail_histories', function (Blueprint $table) {
            $table->id('id_history_detail');
            $table->string('no_revisi', 50);
            $table->unsignedBigInteger('pembelian_detail_id');
            $table->string('no_po', 22);
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->decimal('jumlah', 18, 6)->default(0);
            $table->decimal('harga', 18, 4)->default(0);
            $table->decimal('exclude', 18, 2)->default(0);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('include', 18, 2)->default(0);
            $table->decimal('diterima', 18, 6)->default(0);
            $table->unsignedBigInteger('request_detail_id')->nullable();
            $table->tinyInteger('jenis')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_detail_histories');
        Schema::dropIfExists('pembelian_histories');
    }
};
