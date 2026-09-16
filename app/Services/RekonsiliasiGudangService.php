<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RekonsiliasiGudangService
{
    public const RESERVASI_HIDUP = ['ACTIVE', 'PICKING', 'PICKED'];

    public function rows()
    {
        return DB::table('stok_gudangs as sg')->join('gudangs as g', 'g.id', '=', 'sg.gudang_id')->join('bahans as b', 'b.id', '=', 'sg.bahan_id')
            ->leftJoinSub(DB::table('wms_layer_persediaan')->whereIn('stock_status', ['AVAILABLE', 'QC_HOLD', 'DAMAGED'])->select('gudang_id', 'bahan_id', DB::raw('SUM(remaining_quantity) as layer_quantity'), DB::raw('SUM(remaining_quantity * unit_cost) as layer_value'))->groupBy('gudang_id', 'bahan_id'), 'layers', fn($join) => $join->on('layers.gudang_id', '=', 'sg.gudang_id')->on('layers.bahan_id', '=', 'sg.bahan_id'))
            ->leftJoinSub(DB::table('wms_reservasi_persediaan')->whereIn('status', self::RESERVASI_HIDUP)->select('gudang_id', 'bahan_id', DB::raw('SUM(quantity) as reservasi_hidup'))->groupBy('gudang_id', 'bahan_id'), 'rsv', fn($join) => $join->on('rsv.gudang_id', '=', 'sg.gudang_id')->on('rsv.bahan_id', '=', 'sg.bahan_id'))
            ->select('sg.*', 'g.kode as gudang_kode', 'g.nama as gudang_nama', 'b.nama as bahan_nama', DB::raw('COALESCE(layers.layer_quantity,0) as layer_quantity'), DB::raw('COALESCE(layers.layer_value,0) as layer_value'), DB::raw('sg.stok_tersedia - COALESCE(layers.layer_quantity,0) as selisih'), DB::raw('COALESCE(rsv.reservasi_hidup,0) as reservasi_hidup'), DB::raw('sg.stok_direservasi - COALESCE(rsv.reservasi_hidup,0) as selisih_reservasi'))->orderBy('g.nama')->orderBy('b.nama')->get();
    }

    public function akunPersediaanIds()
    {
        return DB::table('kategori_bahans as k')
            ->join('bahans as b', 'b.tipe_barang', '=', 'k.id')
            ->whereNotNull('k.coa_persediaan_id')
            ->distinct()
            ->pluck('k.coa_persediaan_id');
    }

    public function nilaiPerGudang()
    {
        $akunIds = $this->akunPersediaanIds();

        $ledger = DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->where('j.status', 'POSTED')
            ->whereIn('jd.coa_id', $akunIds)
            ->groupBy('jd.gudang_id')
            ->select('jd.gudang_id', DB::raw('SUM(jd.debit - jd.kredit) as nilai'))
            ->pluck('nilai', 'gudang_id');

        $layer = DB::table('wms_layer_persediaan')
            ->whereNotIn('stock_status', ['REVERSED', 'TRANSFER_SHORTAGE', 'IN_TRANSIT'])
            ->groupBy('gudang_id')
            ->select('gudang_id', DB::raw('SUM(remaining_quantity * unit_cost) as nilai'))
            ->pluck('nilai', 'gudang_id')
            ->map(fn ($nilai) => (float) $nilai);

        $transit = DB::table('wms_layer_persediaan as l')
            ->join('alokasi_transfer_gudangs as a', 'a.inventory_layer_tujuan_id', '=', 'l.id')
            ->join('wms_layer_persediaan as asal', 'asal.id', '=', 'a.inventory_layer_asal_id')
            ->where('l.stock_status', 'IN_TRANSIT')
            ->groupBy('asal.gudang_id')
            ->select('asal.gudang_id', DB::raw('SUM(l.remaining_quantity * l.unit_cost) as nilai'))
            ->pluck('nilai', 'gudang_id');

        foreach ($transit as $gudangId => $nilai) {
            $layer[$gudangId] = round(($layer[$gudangId] ?? 0) + (float) $nilai, 2);
        }

        $gudang = DB::table('gudangs')->pluck('nama', 'id');

        return collect($gudang->keys())
            ->merge($ledger->keys())
            ->merge($layer->keys())
            ->unique()
            ->map(function ($gudangId) use ($gudang, $ledger, $layer) {
                $nilaiBuku = round((float) ($ledger[$gudangId] ?? 0), 2);
                $nilaiLayer = round((float) ($layer[$gudangId] ?? 0), 2);

                return [
                    'gudang_id' => $gudangId,
                    'gudang' => ($gudangId === null || $gudangId === '') ? 'Tanpa dimensi gudang' : ($gudang[$gudangId] ?? 'Gudang #' . $gudangId),
                    'nilai_buku_besar' => $nilaiBuku,
                    'nilai_layer' => $nilaiLayer,
                    'selisih' => round($nilaiBuku - $nilaiLayer, 2),
                ];
            })
            ->filter(fn ($row) => abs($row['nilai_buku_besar']) >= 0.01 || abs($row['nilai_layer']) >= 0.01)
            ->sortByDesc('nilai_layer')
            ->values();
    }

    public function summary(): array
    {
        $rows = $this->rows();
        $warehouseQuantity = (float) DB::table('stok_gudangs')->sum('stok_tersedia');
        $masterQuantity = (float) DB::table('bahans')->sum('stok_onhand');
        $transitQuantity = (float) DB::table('wms_layer_persediaan')->where('stock_status', 'IN_TRANSIT')->sum('remaining_quantity');
        $layerValue = (float) DB::table('wms_layer_persediaan')->whereNotIn('stock_status', ['REVERSED', 'TRANSFER_SHORTAGE'])->sum(DB::raw('remaining_quantity * unit_cost'));
        $inventoryGl = (float) DB::table('wms_jurnal_detail as jd')->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')->join('wms_bagan_akun as coa', 'coa.id', '=', 'jd.coa_id')->where('j.status', 'POSTED')->where('coa.kategori_akun', 'ASET')->where(function ($query) {
            $stockAccountIds = DB::table('kategori_bahans as k')->join('bahans as b', 'b.tipe_barang', '=', 'k.id')->whereNotNull('k.coa_persediaan_id')->distinct()->pluck('k.coa_persediaan_id');
            $query->whereIn('coa.id', $stockAccountIds);
        })->sum(DB::raw('jd.debit - jd.kredit'));

        $perGudang = $this->nilaiPerGudang();

        return [
            'rows' => $rows,
            'per_gudang' => $perGudang,
            'value_exceptions_per_gudang' => $perGudang->filter(fn ($row) => abs((float) $row['selisih']) > 0.01)->count(),
            'quantity_exceptions' => $rows->filter(fn ($row) => abs((float) $row->selisih) > .000001)->count(),
            'reservation_exceptions' => $rows->filter(fn ($row) => abs((float) $row->selisih_reservasi) > .000001)->count(),
            'master_quantity' => $masterQuantity,
            'warehouse_quantity' => $warehouseQuantity,
            'transit_quantity' => $transitQuantity,
            'global_quantity_difference' => $masterQuantity - $warehouseQuantity - $transitQuantity,
            'layer_value' => $layerValue,
            'inventory_gl_value' => $inventoryGl,
            'value_difference' => $layerValue - $inventoryGl,
        ];
    }
}
