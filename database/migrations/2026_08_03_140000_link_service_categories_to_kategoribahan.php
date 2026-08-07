<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_bahan_id')->nullable()->after('display_code');
            $table->foreign('kategori_bahan_id')->references('id')->on('kategoribahan')->nullOnDelete();
        });

        Schema::table('service_po_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kategori')->nullable()->after('service_category_id');
            $table->foreign('id_kategori')->references('id')->on('kategoribahan')->nullOnDelete();
        });

        Schema::table('service_bap_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kategori')->nullable()->after('service_po_detail_id');
            $table->foreign('id_kategori')->references('id')->on('kategoribahan')->nullOnDelete();
        });

        $inventoryTypeId = DB::table('tipe_pembebanans')->where('nama_tipe', 'INVENTORY')->value('id');
        $directTypeId = DB::table('tipe_pembebanans')->where('nama_tipe', 'DIRECT_COST')->value('id');
        $grniServiceId = DB::table('chart_of_accounts')->where('kode_akun', '2104')->value('id');
        $expenseOperationalId = DB::table('chart_of_accounts')->where('kode_akun', '5202')->value('id');
        $expenseProductionId = DB::table('chart_of_accounts')->where('kode_akun', '1302')->value('id');
        $opnameLossId = DB::table('chart_of_accounts')->where('kode_akun', '5106')->value('id');
        $opnameGainId = DB::table('chart_of_accounts')->where('kode_akun', '4202')->value('id');

        DB::table('kategoribahan')->updateOrInsert(
            ['katnama' => 'Jasa Operasional'],
            [
                'tipe_pembebanan_id' => $directTypeId,
                'coa_persediaan_id' => $expenseOperationalId,
                'coa_beban_id' => $expenseOperationalId,
                'coa_clearing_lpb_id' => $grniServiceId,
                'coa_beban_selisih_opname_id' => $opnameLossId,
                'coa_koreksi_opname_id' => $opnameGainId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('kategoribahan')->updateOrInsert(
            ['katnama' => 'Jasa Produksi'],
            [
                'tipe_pembebanan_id' => $inventoryTypeId,
                'coa_persediaan_id' => $expenseProductionId,
                'coa_beban_id' => $expenseProductionId,
                'coa_clearing_lpb_id' => $grniServiceId,
                'coa_beban_selisih_opname_id' => $opnameLossId,
                'coa_koreksi_opname_id' => $opnameGainId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('service_categories')
            ->where('display_code', '98')
            ->update([
                'kategori_bahan_id' => DB::table('kategoribahan')->where('katnama', 'Jasa Operasional')->value('id'),
            ]);

        DB::table('service_categories')
            ->where('display_code', '99')
            ->update([
                'kategori_bahan_id' => DB::table('kategoribahan')->where('katnama', 'Jasa Produksi')->value('id'),
            ]);

        DB::table('service_po_details')
            ->join('service_categories', 'service_categories.id', '=', 'service_po_details.service_category_id')
            ->update([
                'service_po_details.id_kategori' => DB::raw('service_categories.kategori_bahan_id'),
            ]);

        DB::table('service_bap_details')
            ->join('service_po_details', 'service_po_details.id', '=', 'service_bap_details.service_po_detail_id')
            ->update([
                'service_bap_details.id_kategori' => DB::raw('service_po_details.id_kategori'),
            ]);
    }

    public function down(): void
    {
        Schema::table('service_bap_details', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->dropColumn('id_kategori');
        });

        Schema::table('service_po_details', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->dropColumn('id_kategori');
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropForeign(['kategori_bahan_id']);
            $table->dropColumn('kategori_bahan_id');
        });
    }
};
