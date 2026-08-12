<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event', 30);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('document_operation_keys', function (Blueprint $table) {
            $table->id();
            $table->string('operation_key', 120)->unique();
            $table->string('document_type', 50);
            $table->unsignedBigInteger('document_id');
            $table->string('operation', 50);
            $table->json('result')->nullable();
            $table->timestamps();
        });

        Schema::create('document_reversals', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->string('document_type', 50);
            $table->unsignedBigInteger('document_id');
            $table->text('reason');
            $table->string('status', 20)->default('POSTED');
            $table->foreignId('reversal_journal_id')->nullable()->constrained('jurnals')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at');
            $table->timestamps();
            $table->unique(['document_type', 'document_id']);
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('zone', 80)->nullable();
            $table->string('aisle', 50)->nullable();
            $table->string('rack', 50)->nullable();
            $table->string('bin', 50)->nullable();
            $table->string('type', 30)->default('STORAGE');
            $table->decimal('capacity', 18, 6)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['gudang_id', 'code']);
        });

        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->string('lot_number', 100);
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('quality_status', 30)->default('RELEASED');
            $table->boolean('blocked')->default(false);
            $table->text('block_reason')->nullable();
            $table->timestamps();
            $table->unique(['bahan_id', 'lot_number']);
            $table->index('expires_at');
        });

        Schema::create('inventory_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->cascadeOnDelete();
            $table->string('serial_number', 120)->unique();
            $table->string('status', 30)->default('AVAILABLE');
            $table->timestamps();
        });

        Schema::table('inventory_layers', function (Blueprint $table) {
            $table->foreignId('warehouse_location_id')->nullable()->after('gudang_id')->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->after('warehouse_location_id')->constrained('inventory_lots')->restrictOnDelete();
            $table->string('stock_status', 30)->default('AVAILABLE')->after('inventory_lot_id');
            $table->index(['gudang_id', 'bahan_id', 'stock_status'], 'layers_stock_lookup');
        });

        Schema::table('lpbs', function (Blueprint $table) {
            $table->string('receiving_status', 30)->default('RECEIVED')->after('status');
            $table->foreignId('putaway_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('putaway_at')->nullable();
        });

        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('lpb_id')->constrained('lpbs')->restrictOnDelete();
            $table->string('status', 30)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quality_inspection_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_inspection_id')->constrained('quality_inspections')->cascadeOnDelete();
            $table->foreignId('lpb_detail_id')->constrained('lpb_details')->restrictOnDelete();
            $table->decimal('quantity_received', 18, 6);
            $table->decimal('quantity_accepted', 18, 6)->default(0);
            $table->decimal('quantity_rejected', 18, 6)->default(0);
            $table->string('decision', 30)->default('PENDING');
            $table->text('reason')->nullable();
            $table->unique(['quality_inspection_id', 'lpb_detail_id'], 'quality_inspection_detail_unique');
        });

        Schema::table('invoice_lpbs', function (Blueprint $table) {
            $table->string('match_status', 20)->default('PENDING')->after('status');
            $table->json('match_summary')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
        });

        Schema::create('landed_costs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->date('date');
            $table->string('description');
            $table->string('allocation_basis', 20)->default('VALUE');
            $table->decimal('total_amount', 18, 2);
            $table->foreignId('credit_coa_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('jurnals')->nullOnDelete();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('landed_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landed_cost_id')->constrained('landed_costs')->cascadeOnDelete();
            $table->foreignId('inventory_layer_id')->constrained('inventory_layers')->restrictOnDelete();
            $table->decimal('base_value', 18, 6);
            $table->decimal('allocated_amount', 18, 2);
            $table->decimal('unit_cost_before', 18, 4);
            $table->decimal('unit_cost_after', 18, 4);
            $table->unique(['landed_cost_id', 'inventory_layer_id']);
        });

        Schema::table('transfer_gudangs', function (Blueprint $table) {
            $table->foreignId('dikirim_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikirim_pada')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterima_pada')->nullable();
            $table->string('nomor_surat_jalan', 80)->nullable();
            $table->text('catatan_penerimaan')->nullable();
        });

        Schema::table('detail_transfer_gudangs', function (Blueprint $table) {
            $table->decimal('jumlah_dikirim', 18, 6)->default(0);
            $table->decimal('jumlah_diterima', 18, 6)->default(0);
            $table->decimal('jumlah_selisih', 18, 6)->default(0);
        });

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->decimal('picked_quantity', 18, 6)->default(0);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['gudang_id', 'bahan_id', 'status']);
        });

        Schema::table('npks', function (Blueprint $table) {
            $table->foreignId('inventory_reservation_id')->nullable()->constrained('inventory_reservations')->nullOnDelete();
        });

        Schema::create('picking_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('picking_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('picking_order_id')->constrained('picking_orders')->cascadeOnDelete();
            $table->foreignId('inventory_reservation_id')->constrained('inventory_reservations')->restrictOnDelete();
            $table->foreignId('inventory_layer_id')->nullable()->constrained('inventory_layers')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('quantity_requested', 18, 6);
            $table->decimal('quantity_picked', 18, 6)->default(0);
        });

        Schema::create('replenishment_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->date('calculated_at');
            $table->decimal('average_daily_usage', 18, 6)->default(0);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->decimal('available_quantity', 18, 6)->default(0);
            $table->decimal('suggested_quantity', 18, 6)->default(0);
            $table->string('priority', 20)->default('NORMAL');
            $table->string('status', 20)->default('OPEN');
            $table->timestamps();
            $table->unique(['gudang_id', 'bahan_id', 'calculated_at'], 'replenishment_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_suggestions');
        Schema::dropIfExists('picking_order_lines');
        Schema::dropIfExists('picking_orders');
        Schema::table('npks', fn (Blueprint $table) => $table->dropConstrainedForeignId('inventory_reservation_id'));
        Schema::dropIfExists('inventory_reservations');
        Schema::table('detail_transfer_gudangs', fn (Blueprint $table) => $table->dropColumn(['jumlah_dikirim', 'jumlah_diterima', 'jumlah_selisih']));
        Schema::table('transfer_gudangs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dikirim_oleh');
            $table->dropConstrainedForeignId('diterima_oleh');
            $table->dropColumn(['dikirim_pada', 'diterima_pada', 'nomor_surat_jalan', 'catatan_penerimaan']);
        });
        Schema::dropIfExists('landed_cost_allocations');
        Schema::dropIfExists('landed_costs');
        Schema::table('invoice_lpbs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matched_by');
            $table->dropColumn(['match_status', 'match_summary', 'matched_at']);
        });
        Schema::dropIfExists('quality_inspection_lines');
        Schema::dropIfExists('quality_inspections');
        Schema::table('lpbs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('putaway_by');
            $table->dropColumn(['receiving_status', 'putaway_at']);
        });
        Schema::table('inventory_layers', function (Blueprint $table) {
            $table->dropIndex('layers_stock_lookup');
            $table->dropConstrainedForeignId('warehouse_location_id');
            $table->dropConstrainedForeignId('inventory_lot_id');
            $table->dropColumn('stock_status');
        });
        Schema::dropIfExists('inventory_serials');
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('document_reversals');
        Schema::dropIfExists('document_operation_keys');
        Schema::dropIfExists('audit_logs');
    }
};
