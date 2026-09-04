<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_kategori_jasa', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_bahan_id')->nullable()->after('display_code');
            $table->foreign('kategori_bahan_id')->references('id')->on('kategori_bahans')->nullOnDelete();
        });

        Schema::table('wms_pesanan_jasa_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kategori')->nullable()->after('service_category_id');
            $table->foreign('id_kategori')->references('id')->on('kategori_bahans')->nullOnDelete();
        });

        Schema::table('wms_penerimaan_jasa_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kategori')->nullable()->after('service_po_detail_id');
            $table->foreign('id_kategori')->references('id')->on('kategori_bahans')->nullOnDelete();
        });

        $inventoryTypeId = DB::table('tipe_pembebanans')->where('nama_tipe', 'INVENTORY')->value('id');
        $directTypeId = DB::table('tipe_pembebanans')->where('nama_tipe', 'DIRECT_COST')->value('id');
        $grniServiceId = DB::table('wms_bagan_akun')->where('kode_akun', '2104')->value('id');
        $expenseOperationalId = DB::table('wms_bagan_akun')->where('kode_akun', '5202')->value('id');
        $expenseProductionId = DB::table('wms_bagan_akun')->where('kode_akun', '1302')->value('id');
        $opnameLossId = DB::table('wms_bagan_akun')->where('kode_akun', '5106')->value('id');
        $opnameGainId = DB::table('wms_bagan_akun')->where('kode_akun', '4202')->value('id');

        DB::table('kategori_bahans')->updateOrInsert(
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

        DB::table('kategori_bahans')->updateOrInsert(
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

        DB::table('wms_kategori_jasa')
            ->where('display_code', '98')
            ->update([
                'kategori_bahan_id' => DB::table('kategori_bahans')->where('katnama', 'Jasa Operasional')->value('id'),
            ]);

        DB::table('wms_kategori_jasa')
            ->where('display_code', '99')
            ->update([
                'kategori_bahan_id' => DB::table('kategori_bahans')->where('katnama', 'Jasa Produksi')->value('id'),
            ]);

        DB::table('wms_pesanan_jasa_detail')
            ->join('wms_kategori_jasa', 'wms_kategori_jasa.id', '=', 'wms_pesanan_jasa_detail.service_category_id')
            ->update([
                'wms_pesanan_jasa_detail.id_kategori' => DB::raw('wms_kategori_jasa.kategori_bahan_id'),
            ]);

        DB::table('wms_penerimaan_jasa_detail')
            ->join('wms_pesanan_jasa_detail', 'wms_pesanan_jasa_detail.id', '=', 'wms_penerimaan_jasa_detail.service_po_detail_id')
            ->update([
                'wms_penerimaan_jasa_detail.id_kategori' => DB::raw('wms_pesanan_jasa_detail.id_kategori'),
            ]);
    }

    public function down(): void
    {
        Schema::table('wms_penerimaan_jasa_detail', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->dropColumn('id_kategori');
        });

        Schema::table('wms_pesanan_jasa_detail', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
            $table->dropColumn('id_kategori');
        });

        Schema::table('wms_kategori_jasa', function (Blueprint $table) {
            $table->dropForeign(['kategori_bahan_id']);
            $table->dropColumn('kategori_bahan_id');
        });
    }
};
