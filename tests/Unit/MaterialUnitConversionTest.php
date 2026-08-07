<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Bahan;
use App\Policies\BahanPolicy;
use Tests\TestCase;

class MaterialUnitConversionTest extends TestCase
{
    public function test_small_unit_is_converted_to_base_stock_quantity(): void
    {
        $material = (new Bahan())->forceFill([
            'satuan' => 'barrel',
            'berat_kecil' => 10,
            'satuan_kecil' => 'kaleng',
        ]);

        $this->assertTrue($material->hasSmallUnit());
        $this->assertEqualsWithDelta(0.1, $material->toStockQuantity(1), 0.000001);
        $this->assertEqualsWithDelta(9, $material->smallUnitEquivalent(0.9), 0.000001);
    }

    public function test_material_without_small_unit_uses_base_quantity(): void
    {
        $material = (new Bahan())->forceFill([
            'satuan' => 'kg',
            'berat_kecil' => 1,
            'satuan_kecil' => null,
        ]);

        $this->assertFalse($material->hasSmallUnit());
        $this->assertEqualsWithDelta(2.5, $material->toStockQuantity(2.5), 0.000001);
        $this->assertNull($material->smallUnitEquivalent(2.5));
    }

    public function test_types_five_fourteen_and_thirty_three_can_update_material(): void
    {
        $policy = new BahanPolicy();
        $material = new Bahan();

        foreach ([5, 14, 33] as $type) {
            $user = new User(['type' => $type]);
            $this->assertTrue($policy->create($user));
            $this->assertTrue($policy->update($user, $material));
            $this->assertFalse($policy->delete($user, $material));
        }
    }
}
