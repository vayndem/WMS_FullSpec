<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->dateTime('cutoff_at');
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'REJECTED'])->default('DRAFT')
                ->comment('Alur opname: DRAFT=input fisik, SUBMITTED=menunggu valuasi Accounting, APPROVED=siap posting, POSTED=stok/jurnal final, REJECTED=ditolak');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()
                ->comment('ID user lokal Accounting type 33 yang menyetujui valuasi');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable()
                ->comment('ID user lokal yang memposting hasil opname');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->foreign('warehouse_id')->references('id')->on('admin_namagudang')->onDelete('restrict');
            $table->index(['warehouse_id', 'status']);
        });

        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_opname_id');
            $table->unsignedBigInteger('bahan_id');
            $table->decimal('system_quantity', 15, 2);
            $table->decimal('physical_quantity', 15, 2);
            $table->decimal('difference_quantity', 15, 2);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('difference_value', 18, 2)->default(0);
            $table->string('reason', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['stock_opname_id', 'bahan_id']);
            $table->foreign('stock_opname_id')->references('id')->on('stock_opnames')->onDelete('cascade');
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('restrict');
        });

        Schema::create('stock_opname_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_opname_detail_id');
            $table->unsignedBigInteger('inventory_layer_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('total_cost', 18, 2);
            $table->timestamps();
            $table->foreign('stock_opname_detail_id')->references('id')->on('stock_opname_details')->onDelete('cascade');
            $table->foreign('inventory_layer_id')->references('id')->on('inventory_layers')->onDelete('restrict');
        });

        Schema::table('kategoribahan', function (Blueprint $table) {
            $table->unsignedBigInteger('coa_beban_selisih_opname_id')->nullable()->after('coa_clearing_lpb_id');
            $table->unsignedBigInteger('coa_koreksi_opname_id')->nullable()->after('coa_beban_selisih_opname_id');
            $table->foreign('coa_beban_selisih_opname_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
            $table->foreign('coa_koreksi_opname_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('kategoribahan', function (Blueprint $table) {
            $table->dropForeign(['coa_beban_selisih_opname_id']);
            $table->dropForeign(['coa_koreksi_opname_id']);
            $table->dropColumn(['coa_beban_selisih_opname_id', 'coa_koreksi_opname_id']);
        });
        Schema::dropIfExists('stock_opname_allocations');
        Schema::dropIfExists('stock_opname_details');
        Schema::dropIfExists('stock_opnames');
    }
};
