<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->unsignedBigInteger('asset_coa_id');
            $table->unsignedBigInteger('accumulated_depreciation_coa_id');
            $table->unsignedBigInteger('depreciation_expense_coa_id');
            $table->unsignedBigInteger('disposal_gain_coa_id');
            $table->unsignedBigInteger('disposal_loss_coa_id');
            $table->boolean('is_active')->default(true)
                ->comment('0=kategori asset nonaktif, 1=kategori asset aktif');
            $table->timestamps();
            $table->foreign('asset_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $table->foreign('accumulated_depreciation_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $table->foreign('depreciation_expense_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $table->foreign('disposal_gain_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $table->foreign('disposal_loss_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number', 40)->unique();
            $table->unsignedBigInteger('asset_category_id');
            $table->string('name', 180);
            $table->string('serial_number', 120)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('responsible_person', 150)->nullable();
            $table->string('condition', 30)->default('BAIK');
            $table->date('acquisition_date');
            $table->string('acquisition_type', 30)
                ->comment('Jenis perolehan: OPENING_BALANCE, CASH, CREDIT, GRANT, atau CORRECTION');
            $table->unsignedBigInteger('acquisition_credit_coa_id');
            $table->decimal('acquisition_cost', 18, 2);
            $table->decimal('residual_value', 18, 2)->default(0);
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->string('depreciation_method', 30)->default('STRAIGHT_LINE');
            $table->date('depreciation_start_date')->nullable();
            $table->date('last_depreciation_date')->nullable();
            $table->decimal('opening_accumulated_depreciation', 18, 2)->default(0);
            $table->decimal('accumulated_depreciation', 18, 2)->default(0);
            $table->decimal('book_value', 18, 2);
            $table->string('status', 30)->default('ACTIVE')
                ->comment('Status asset: ACTIVE=aktif, SOLD=terjual, DISPOSED=dihapuskan');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->restrictOnDelete();
            $table->foreign('acquisition_credit_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $table->index(['status', 'asset_category_id']);
        });

        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->date('posting_date');
            $table->string('period_label', 100);
            $table->decimal('suggested_amount', 18, 2)->default(0);
            $table->decimal('amount', 18, 2);
            $table->decimal('book_value_before', 18, 2);
            $table->decimal('book_value_after', 18, 2);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('posted_by')
                ->comment('ID user lokal Accounting type 33 yang memposting penyusutan');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();
            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('journal_id')->references('id')->on('jurnals')->restrictOnDelete();
        });

        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->unique();
            $table->date('disposal_date');
            $table->string('disposal_type', 20)
                ->comment('Jenis pelepasan asset: SALE=penjualan, WRITE_OFF=penghapusan');
            $table->decimal('proceeds', 18, 2)->default(0);
            $table->unsignedBigInteger('cash_bank_coa_id')->nullable();
            $table->decimal('book_value_at_disposal', 18, 2);
            $table->decimal('gain_amount', 18, 2)->default(0);
            $table->decimal('loss_amount', 18, 2)->default(0);
            $table->text('reason');
            $table->unsignedBigInteger('disposed_by');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();
            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('cash_bank_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $table->foreign('journal_id')->references('id')->on('jurnals')->restrictOnDelete();
        });

        Schema::table('pembelians', function (Blueprint $table) {
            $table->string('document_type', 20)->default('GOODS')->after('no_po')->index()
                ->comment('Jenis PO: GOODS=PO barang, SERVICE=PO jasa');
        });

        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('display_code', 10)->unique();
            $table->string('name', 120);
            $table->unsignedBigInteger('expense_coa_id');
            $table->unsignedBigInteger('grni_coa_id');
            $table->boolean('requires_datapesanan')->default(false);
            $table->boolean('requires_cost_center')->default(false)
                ->comment('0=cost center tidak wajib, 1=cost center wajib diisi');
            $table->boolean('is_active')->default(true)
                ->comment('0=kategori jasa nonaktif, 1=kategori jasa aktif');
            $table->timestamps();
            $table->foreign('expense_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $table->foreign('grni_coa_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
        });

        Schema::create('service_po_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembelian_id');
            $table->unsignedBigInteger('service_category_id');
            $table->string('service_type', 30)
                ->comment('Jenis jasa dari kategori: SERVICE_OPERATIONAL atau SERVICE_PRODUCTION');
            $table->text('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit', 30)->default('JOB');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('subtotal', 18, 2);
            $table->decimal('accepted_amount', 18, 2)->default(0);
            $table->timestamps();
            $table->foreign('pembelian_id')->references('id')->on('pembelians')->cascadeOnDelete();
            $table->foreign('service_category_id')->references('id')->on('service_categories')->restrictOnDelete();
            $table->index(['pembelian_id', 'service_type']);
        });

        Schema::table('lpbs', function (Blueprint $table) {
            $table->string('document_type', 20)->default('GOODS')->after('id_lpb')->index()
                ->comment('Jenis penerimaan: GOODS=LPB barang, SERVICE_BAP=BAP jasa');
            $table->boolean('is_cancelled')->default(false)->after('status')
                ->comment('0=dokumen aktif, 1=dokumen penerimaan dibatalkan');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('is_cancelled')
                ->comment('ID user lokal yang membatalkan penerimaan');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });

        Schema::create('service_bap_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lpb_id');
            $table->unsignedBigInteger('service_po_detail_id');
            $table->decimal('progress_percent', 8, 4);
            $table->decimal('amount', 18, 2);
            $table->string('department_cost_center', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('lpb_id')->references('id')->on('lpbs')->cascadeOnDelete();
            $table->foreign('service_po_detail_id')->references('id')->on('service_po_details')->restrictOnDelete();
        });

        Schema::create('service_bap_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_bap_detail_id');
            $table->string('datapesanan_code', 100);
            $table->decimal('percentage', 8, 4);
            $table->decimal('amount', 18, 2);
            $table->timestamps();
            $table->foreign('service_bap_detail_id')->references('id')->on('service_bap_details')->cascadeOnDelete();
            $table->unique(['service_bap_detail_id', 'datapesanan_code'], 'service_bap_datapesanan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_bap_allocations');
        Schema::dropIfExists('service_bap_details');
        Schema::table('lpbs', fn (Blueprint $table) => $table->dropColumn([
            'document_type', 'is_cancelled', 'cancelled_by', 'cancelled_at', 'cancellation_reason',
        ]));
        Schema::dropIfExists('service_po_details');
        Schema::dropIfExists('service_categories');
        Schema::table('pembelians', fn (Blueprint $table) => $table->dropColumn('document_type'));
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
