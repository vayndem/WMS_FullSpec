<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_period_locks', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('LOCKED')
                ->comment('Status periode: LOCKED=transaksi pada rentang tanggal ditolak, UNLOCKED=periode dibuka');
            $table->text('reason');
            $table->unsignedBigInteger('locked_by');
            $table->string('locked_by_name')->nullable();
            $table->timestamp('locked_at');
            $table->unsignedBigInteger('unlocked_by')->nullable();
            $table->string('unlocked_by_name')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->text('unlock_reason')->nullable();
            $table->timestamps();
            $table->index(['period_start', 'period_end', 'status'], 'accounting_period_locks_date_status_index');
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('tax_type', 30);
            $table->decimal('rate', 8, 4);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true)
                ->comment('0=tarif pajak tidak berlaku, 1=tarif pajak aktif pada periode efektif');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['tax_type', 'effective_from']);
        });

        Schema::table('invoicelpbs', function (Blueprint $table) {
            $table->string('jenis_pajak', 30)->default('PPN')->after('sub_total')
                ->comment('Snapshot jenis pajak invoice: PPN atau NON_PPN');
            $table->decimal('dpp_ppn', 18, 2)->default(0)->after('jenis_pajak');
            $table->decimal('tarif_ppn', 8, 4)->default(0)->after('dpp_ppn');
            $table->decimal('dasar_pph', 18, 2)->default(0)->after('ppn');
            $table->decimal('tarif_pph', 8, 4)->default(0)->after('dasar_pph');
        });

        Schema::table('invoicelpbdetails', function (Blueprint $table) {
            $table->string('jenis_selisih', 30)->nullable()->after('selisih_bayar')
                ->comment('Jenis selisih pembayaran: PENDAPATAN_SELISIH, BEBAN_SELISIH, atau UANG_MUKA_SUPPLIER');
            $table->unsignedBigInteger('coa_selisih_id')->nullable()->after('jenis_selisih');
            $table->decimal('kelebihan_pembayaran', 15, 2)->default(0)->after('coa_selisih_id');
            $table->foreign('coa_selisih_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('invoicelpbdetails', function (Blueprint $table) {
            $table->dropForeign(['coa_selisih_id']);
            $table->dropColumn(['jenis_selisih', 'coa_selisih_id', 'kelebihan_pembayaran']);
        });
        Schema::table('invoicelpbs', fn (Blueprint $table) => $table->dropColumn([
            'jenis_pajak', 'dpp_ppn', 'tarif_ppn', 'dasar_pph', 'tarif_pph',
        ]));
        Schema::dropIfExists('accounting_period_locks');
        Schema::dropIfExists('tax_rates');
    }
};
