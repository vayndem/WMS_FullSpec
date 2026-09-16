<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->isi('LPB', 'wms_penerimaan_barang', 'gudang_id');
        $this->isi('NPK', 'wms_pemakaian_barang', 'id_gudang_asal');
        $this->isi('STOCK_OPNAME', 'wms_stock_opname', 'warehouse_id');
        $this->isi('PERAKITAN_KIT', 'wms_perakitan_kit', 'gudang_id');

        DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->join('wms_retur_pembelian as r', 'r.id', '=', 'j.reff_id')
            ->join('wms_penerimaan_barang as lpb', 'lpb.id', '=', 'r.lpb_id')
            ->where('j.sumber_transaksi', 'RETUR_PEMBELIAN')
            ->whereNull('jd.gudang_id')
            ->update(['jd.gudang_id' => DB::raw('lpb.gudang_id')]);
    }

    private function isi(string $sumber, string $tabel, string $kolom): void
    {
        DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->join("{$tabel} as src", 'src.id', '=', 'j.reff_id')
            ->where('j.sumber_transaksi', $sumber)
            ->whereNull('jd.gudang_id')
            ->update(['jd.gudang_id' => DB::raw("src.{$kolom}")]);
    }

    public function down(): void
    {
        DB::table('wms_jurnal_detail')->update(['gudang_id' => null]);
    }
};
