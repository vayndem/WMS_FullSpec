<?php

namespace App\Traits;

use App\Models\Pembelian;

trait CalculatesPembelianTotals
{
    public function recalculatePembelianTotals(Pembelian $pembelian): Pembelian
    {
        $sumExclude = $pembelian->details()->sum('exclude');
        $sumPpn = $pembelian->details()->sum('ppn');
        $sumInclude = $pembelian->details()->sum('include');
        $grandTotal = ($sumInclude - $pembelian->diskon) + $pembelian->ongkir;

        $pembelian->update([
            'total_exclude' => $sumExclude,
            'total_ppn' => $sumPpn,
            'total_include' => $sumInclude,
            'grand_total' => max(0, $grandTotal),
        ]);

        return $pembelian->fresh();
    }
}
