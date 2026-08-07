<?php

namespace Tests\Unit;

use App\Services\InventoryCostCalculator;
use PHPUnit\Framework\TestCase;

class InventoryCostCalculatorTest extends TestCase
{
    public function test_average_uses_only_latest_five_active_layers(): void
    {
        $layers = collect([
            ['transaction_date' => '2026-01-01', 'remaining_quantity' => 100, 'unit_cost' => 1],
            ['transaction_date' => '2026-02-01', 'remaining_quantity' => 10, 'unit_cost' => 10],
            ['transaction_date' => '2026-03-01', 'remaining_quantity' => 10, 'unit_cost' => 20],
            ['transaction_date' => '2026-04-01', 'remaining_quantity' => 10, 'unit_cost' => 30],
            ['transaction_date' => '2026-05-01', 'remaining_quantity' => 10, 'unit_cost' => 40],
            ['transaction_date' => '2026-06-01', 'remaining_quantity' => 10, 'unit_cost' => 50],
            ['transaction_date' => '2026-07-01', 'remaining_quantity' => 10, 'unit_cost' => 999],
        ]);

        $this->assertSame(227.8, (new InventoryCostCalculator())->lastFiveWeightedAverage($layers));
    }
}
