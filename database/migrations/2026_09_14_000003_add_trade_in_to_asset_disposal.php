<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_pelepasan_asets', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('disposal_type')
                ->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('pesanan_pembelian_id')->nullable()->after('supplier_id')
                ->constrained('wms_pesanan_pembelian')->nullOnDelete();
            $table->decimal('dpp_ppn_keluaran', 18, 2)->default(0)->after('proceeds');
            $table->decimal('ppn_keluaran', 18, 2)->default(0)->after('dpp_ppn_keluaran');
            $table->unsignedBigInteger('advance_payment_id')->nullable()->after('ppn_keluaran');
            $table->foreign('advance_payment_id')->references('id')->on('wms_pembayaran_faktur')->nullOnDelete();
        });

        $akun = DB::table('wms_bagan_akun')->where('kode_akun', '2109')->first();

        if (!$akun) {
            DB::table('wms_bagan_akun')->insert([
                'kode_akun' => '2109',
                'nama_akun' => 'Hutang PPN Keluaran',
                'kategori_akun' => 'LIABILITAS',
                'posisi_normal' => 'KREDIT',
                'is_active' => true,
                'is_postable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $akun = DB::table('wms_bagan_akun')->where('kode_akun', '2109')->first();
        }

        if ($akun && !DB::table('accounting_settings')->where('key', 'PPN_KELUARAN')->exists()) {
            DB::table('accounting_settings')->insert([
                'key' => 'PPN_KELUARAN',
                'coa_id' => $akun->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('accounting_settings')->where('key', 'PPN_KELUARAN')->delete();

        Schema::table('wms_pelepasan_asets', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['pesanan_pembelian_id']);
            $table->dropForeign(['advance_payment_id']);
            $table->dropColumn(['supplier_id', 'pesanan_pembelian_id', 'dpp_ppn_keluaran', 'ppn_keluaran', 'advance_payment_id']);
        });
    }
};
