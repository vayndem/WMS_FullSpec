<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('posisi_normal')
                ->comment('0=akun nonaktif, 1=akun aktif');
            $table->boolean('is_postable')->default(true)->after('is_active')
                ->comment('0=akun header tidak boleh dijurnal, 1=akun detail boleh dijurnal');
            $table->boolean('is_cash_bank')->default(false)->after('is_postable')
                ->comment('0=bukan akun kas/bank, 1=akun kas/bank yang dapat dipilih saat pembayaran');
        });

        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->unsignedBigInteger('coa_id');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('coa_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
        });

        Schema::table('lpbdetails', function (Blueprint $table) {
            $table->decimal('jumlah_tersisa', 15, 2)->default(0)->after('jumlah_dipakai');
            $table->decimal('nilai_awal', 18, 2)->default(0)->after('harga');
            $table->index(['id_bahan', 'flag_dipakai'], 'lpbdetails_active_lot_index');
        });

        Schema::table('npks', function (Blueprint $table) {
            $table->decimal('harga_satuan', 18, 4)->default(0)->after('jumlah');
            $table->decimal('total_nilai', 18, 2)->default(0)->after('harga_satuan');
            $table->string('status_posting', 20)->default('DRAFT')->after('close')
                ->comment('Status akuntansi NPK: DRAFT=belum posting, POSTED=stok dan jurnal sudah diposting');
        });

        Schema::create('inventory_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bahan_id');
            $table->unsignedBigInteger('gudang_id')->nullable();
            $table->string('source_type', 40)
                ->comment('Jenis sumber layer persediaan, contoh LPB atau STOCK_OPNAME');
            $table->unsignedBigInteger('source_id');
            $table->date('transaction_date');
            $table->decimal('initial_quantity', 15, 2);
            $table->decimal('remaining_quantity', 15, 2);
            $table->decimal('unit_cost', 18, 4);
            $table->timestamps();
            $table->index(['bahan_id', 'gudang_id', 'remaining_quantity'], 'inventory_layers_active_index');
            $table->unique(['source_type', 'source_id'], 'inventory_layers_source_unique');
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('restrict');
            $table->foreign('gudang_id')->references('id')->on('admin_namagudang')->onDelete('restrict');
        });

        Schema::create('npk_stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('npk_id');
            $table->unsignedBigInteger('inventory_layer_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('total_cost', 18, 2);
            $table->timestamps();
            $table->unique(['npk_id', 'inventory_layer_id']);
            $table->foreign('npk_id')->references('id')->on('npks')->onDelete('cascade');
            $table->foreign('inventory_layer_id')->references('id')->on('inventory_layers')->onDelete('restrict');
        });

        DB::table('inventory_layers')->insertUsing(
            ['bahan_id', 'gudang_id', 'source_type', 'source_id', 'transaction_date',
                'initial_quantity', 'remaining_quantity', 'unit_cost', 'created_at', 'updated_at'],
            DB::table('lpbdetails')
                ->join('lpbs', 'lpbs.id_lpb', '=', 'lpbdetails.id_lpb')
                ->join('bahan', 'bahan.id', '=', 'lpbdetails.id_bahan')
                ->select([
                    'lpbdetails.id_bahan',
                    'bahan.tipe_gudang',
                    DB::raw("'LPB_DETAIL'"),
                    'lpbdetails.id',
                    'lpbs.tanggal',
                    'lpbdetails.jumlah_barang_diterima',
                    DB::raw('GREATEST(lpbdetails.jumlah_barang_diterima - lpbdetails.jumlah_dipakai, 0)'),
                    DB::raw('COALESCE(lpbdetails.harga, 0)'),
                    DB::raw('CURRENT_TIMESTAMP'),
                    DB::raw('CURRENT_TIMESTAMP'),
                ])
        );

        Schema::create('invoice_lpb_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_lpb_id');
            $table->unsignedBigInteger('lpb_id');
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['invoice_lpb_id', 'lpb_id']);
            $table->foreign('invoice_lpb_id')->references('id')->on('invoicelpbs')->onDelete('cascade');
            $table->foreign('lpb_id')->references('id')->on('lpbs')->onDelete('restrict');
        });

        Schema::table('invoicelpbs', function (Blueprint $table) {
            $table->boolean('is_void')->default(false)->after('status')
                ->comment('0=invoice aktif, 1=invoice dibatalkan/void');
            $table->unsignedBigInteger('voided_by')->nullable()->after('is_void')
                ->comment('ID user eksternal yang membatalkan invoice');
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_at');
        });

        Schema::table('invoicelpbdetails', function (Blueprint $table) {
            $table->dropForeign(['id_user_finance']);
            $table->boolean('is_void')->default(false)->after('id_user_finance')
                ->comment('0=pembayaran aktif, 1=pembayaran dibatalkan/void');
            $table->unsignedBigInteger('voided_by')->nullable()->after('is_void')
                ->comment('ID user eksternal yang membatalkan pembayaran');
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_at');
        });

        Schema::table('jurnals', function (Blueprint $table) {
            $table->string('status', 20)->default('DRAFT')->after('reff_id')
                ->comment('Status jurnal: DRAFT=belum posting, POSTED=terposting, REVERSED=telah dibalik');
            $table->unsignedBigInteger('created_by')->nullable()->after('status')
                ->comment('ID user eksternal pembuat jurnal');
            $table->unsignedBigInteger('posted_by')->nullable()->after('created_by')
                ->comment('ID user eksternal yang memposting jurnal');
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            $table->unsignedBigInteger('reversal_of_id')->nullable()->after('posted_at')
                ->comment('ID jurnal asal yang dibalik; null untuk jurnal normal');
            $table->foreign('reversal_of_id')->references('id')->on('jurnals')->onDelete('restrict');
            $table->unique(['sumber_transaksi', 'reff_id'], 'jurnals_source_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jurnals', function (Blueprint $table) {
            $table->dropUnique('jurnals_source_reference_unique');
            $table->dropForeign(['reversal_of_id']);
            $table->dropColumn(['status', 'created_by', 'posted_by', 'posted_at', 'reversal_of_id']);
        });
        Schema::dropIfExists('invoice_lpb_receipts');
        Schema::table('invoicelpbs', fn (Blueprint $table) => $table->dropColumn(['is_void', 'voided_by', 'voided_at', 'void_reason']));
        Schema::table('invoicelpbdetails', function (Blueprint $table) {
            $table->dropColumn(['is_void', 'voided_by', 'voided_at', 'void_reason']);
            $table->foreign('id_user_finance')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::dropIfExists('npk_stock_allocations');
        Schema::dropIfExists('inventory_layers');
        Schema::table('npks', fn (Blueprint $table) => $table->dropColumn(['harga_satuan', 'total_nilai', 'status_posting']));
        Schema::table('lpbdetails', function (Blueprint $table) {
            $table->dropIndex('lpbdetails_active_lot_index');
            $table->dropColumn(['jumlah_tersisa', 'nilai_awal']);
        });
        Schema::dropIfExists('accounting_settings');
        Schema::table('chart_of_accounts', fn (Blueprint $table) => $table->dropColumn(['is_active', 'is_postable', 'is_cash_bank']));
    }
};
