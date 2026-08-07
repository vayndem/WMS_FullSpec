<?php

namespace App\Services;

use Illuminate\Support\Collection;
use RuntimeException;

class InventoryCostCalculator
{
    public function lastFiveWeightedAverage(Collection $layers): float
    {
        $selected = $layers->filter(fn($layer) => (float) data_get($layer, 'remaining_quantity') > 0)
            ->sortByDesc(fn($layer) => (string) data_get($layer, 'transaction_date'))
            ->take(5);
        $quantity = $selected->sum(fn($layer) => (float) data_get($layer, 'remaining_quantity'));
        if ($quantity <= 0) {
            throw new RuntimeException('Tidak ada layer LPB aktif untuk menghitung harga rata-rata.');
        }
        return round($selected->sum(
            fn($layer) => (float) data_get($layer, 'remaining_quantity') * (float) data_get($layer, 'unit_cost')
        ) / $quantity, 4);
    }
}
