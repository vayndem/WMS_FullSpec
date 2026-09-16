<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->isiLandedCost();
        $this->isiPembalikan();
    }

    private function isiLandedCost(): void
    {
        $jurnal = DB::table('wms_jurnal')
            ->where('sumber_transaksi', 'LANDED_COST')
            ->select('id', 'reff_id')
            ->get();

        foreach ($jurnal as $baris) {
            $peta = DB::table('wms_biaya_tambahan_alokasi as a')
                ->join('wms_layer_persediaan as l', 'l.id', '=', 'a.inventory_layer_id')
                ->join('bahans as b', 'b.id', '=', 'l.bahan_id')
                ->join('kategori_bahans as k', 'k.id', '=', 'b.tipe_barang')
                ->where('a.landed_cost_id', $baris->reff_id)
                ->whereNotNull('k.coa_persediaan_id')
                ->groupBy('k.coa_persediaan_id', 'l.gudang_id')
                ->select('k.coa_persediaan_id as coa_id', 'l.gudang_id', DB::raw('SUM(a.allocated_amount) as nilai'))
                ->orderByDesc('nilai')
                ->get();

            $terpakai = [];

            foreach ($peta as $item) {
                if (isset($terpakai[$item->coa_id])) {
                    continue;
                }

                $terpakai[$item->coa_id] = true;

                DB::table('wms_jurnal_detail')
                    ->where('jurnal_id', $baris->id)
                    ->where('coa_id', $item->coa_id)
                    ->whereNull('gudang_id')
                    ->update(['gudang_id' => $item->gudang_id]);
            }
        }
    }

    private function isiPembalikan(): void
    {
        DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->join('wms_jurnal_detail as asal', function ($join) {
                $join->on('asal.jurnal_id', '=', 'j.reversal_of_id')
                    ->on('asal.coa_id', '=', 'jd.coa_id');
            })
            ->where('j.sumber_transaksi', 'REVERSAL')
            ->whereNull('jd.gudang_id')
            ->whereNotNull('asal.gudang_id')
            ->update(['jd.gudang_id' => DB::raw('asal.gudang_id')]);
    }

    public function down(): void
    {
        DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->whereIn('j.sumber_transaksi', ['LANDED_COST', 'REVERSAL'])
            ->update(['jd.gudang_id' => null]);
    }
};
