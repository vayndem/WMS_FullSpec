<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RekonsiliasiGudangService
{
    public function rows()
    {
        return DB::table('stok_gudangs as sg')->join('gudangs as g', 'g.id', '=', 'sg.gudang_id')->join('bahans as b', 'b.id', '=', 'sg.bahan_id')
            ->leftJoinSub(DB::table('inventory_layers')->whereIn('stock_status', ['AVAILABLE', 'QC_HOLD', 'DAMAGED'])->select('gudang_id', 'bahan_id', DB::raw('SUM(remaining_quantity) as layer_quantity'), DB::raw('SUM(remaining_quantity * unit_cost) as layer_value'))->groupBy('gudang_id', 'bahan_id'), 'layers', fn($join) => $join->on('layers.gudang_id', '=', 'sg.gudang_id')->on('layers.bahan_id', '=', 'sg.bahan_id'))
            ->select('sg.*', 'g.kode as gudang_kode', 'g.nama as gudang_nama', 'b.nama as bahan_nama', DB::raw('COALESCE(layers.layer_quantity,0) as layer_quantity'), DB::raw('COALESCE(layers.layer_value,0) as layer_value'), DB::raw('sg.stok_tersedia - COALESCE(layers.layer_quantity,0) as selisih'))->orderBy('g.nama')->orderBy('b.nama')->get();
    }

    public function summary(): array
    {
        $rows = $this->rows();
        $warehouseQuantity = (float) DB::table('stok_gudangs')->sum('stok_tersedia');
        $masterQuantity = (float) DB::table('bahans')->sum('stok_onhand');
        $transitQuantity = (float) DB::table('inventory_layers')->where('stock_status', 'IN_TRANSIT')->sum('remaining_quantity');
        $layerValue = (float) DB::table('inventory_layers')->whereNotIn('stock_status', ['REVERSED', 'TRANSFER_SHORTAGE'])->sum(DB::raw('remaining_quantity * unit_cost'));
        $inventoryGl = (float) DB::table('jurnal_details as jd')->join('jurnals as j', 'j.id', '=', 'jd.jurnal_id')->join('chart_of_accounts as coa', 'coa.id', '=', 'jd.coa_id')->where('j.status', 'POSTED')->where('coa.kategori_akun', 'ASET')->where(function ($query) {
            $stockAccountIds = DB::table('kategori_bahans as k')->join('bahans as b', 'b.tipe_barang', '=', 'k.id')->whereNotNull('k.coa_persediaan_id')->distinct()->pluck('k.coa_persediaan_id');
            $query->whereIn('coa.id', $stockAccountIds);
        })->sum(DB::raw('jd.debit - jd.kredit'));

        return [
            'rows' => $rows,
            'quantity_exceptions' => $rows->filter(fn ($row) => abs((float) $row->selisih) > .000001)->count(),
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
