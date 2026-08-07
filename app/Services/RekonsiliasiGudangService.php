<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RekonsiliasiGudangService
{
    public function rows()
    {
        return DB::table('stok_gudangs as sg')->join('gudangs as g', 'g.id', '=', 'sg.gudang_id')->join('bahan as b', 'b.id', '=', 'sg.bahan_id')
            ->leftJoinSub(DB::table('inventory_layers')->select('gudang_id', 'bahan_id', DB::raw('SUM(remaining_quantity) as layer_quantity'), DB::raw('SUM(remaining_quantity * unit_cost) as layer_value'))->groupBy('gudang_id', 'bahan_id'), 'layers', fn($join) => $join->on('layers.gudang_id', '=', 'sg.gudang_id')->on('layers.bahan_id', '=', 'sg.bahan_id'))
            ->select('sg.*', 'g.kode as gudang_kode', 'g.nama as gudang_nama', 'b.nama as bahan_nama', DB::raw('COALESCE(layers.layer_quantity,0) as layer_quantity'), DB::raw('COALESCE(layers.layer_value,0) as layer_value'), DB::raw('sg.stok_tersedia - COALESCE(layers.layer_quantity,0) as selisih'))->orderBy('g.nama')->orderBy('b.nama')->get();
    }
}
