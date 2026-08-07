<?php

namespace App\Services;

use RuntimeException;

class PaymentAllocationService
{
    public function calculate(float $remaining, float $cash, float $withholdingTax, float $difference, ?string $type): array
    {
        $cashAndTax = $cash + $withholdingTax;
        $advance = 0;

        if ($type === 'PENDAPATAN_SELISIH') {
            $apReduction = $cashAndTax + $difference;
        } elseif ($type === 'BEBAN_SELISIH') {
            $apReduction = $cashAndTax - $difference;
        } elseif ($type === 'UANG_MUKA_SUPPLIER') {
            $apReduction = min($remaining, $cashAndTax);
            $advance = max(0, $cashAndTax - $apReduction);
            if ($advance <= 0) {
                throw new RuntimeException('Jenis uang muka hanya digunakan bila pembayaran melebihi sisa tagihan.');
            }
        } else {
            $apReduction = $cashAndTax;
        }

        if ($apReduction <= 0 || $apReduction > $remaining + 0.01) {
            throw new RuntimeException('Nilai yang mengurangi hutang tidak valid atau melebihi sisa tagihan. Gunakan Uang Muka Supplier untuk pembayaran lebih.');
        }

        return ['ap_reduction' => round($apReduction, 2), 'advance' => round($advance, 2)];
    }
}
