<?php

namespace Tests\Unit;

use App\Models\ApiUser;
use App\Models\StockOpname;
use App\Policies\StockOpnamePolicy;
use PHPUnit\Framework\TestCase;

class StockOpnamePolicyTest extends TestCase
{
    public function test_type_fourteen_controls_physical_count_and_submit(): void
    {
        $policy = new StockOpnamePolicy();
        $warehouse = new ApiUser(['type' => 14]);
        $opname = new StockOpname(['status' => StockOpname::DRAFT]);

        $this->assertTrue($policy->create($warehouse));
        $this->assertTrue($policy->update($warehouse, $opname));
        $this->assertTrue($policy->submit($warehouse, $opname));
        $this->assertFalse($policy->approve($warehouse, $opname));

        $opname->status = StockOpname::APPROVED;
        $this->assertFalse($policy->post($warehouse, $opname));
    }

    public function test_type_thirty_three_only_approves_submitted_document(): void
    {
        $policy = new StockOpnamePolicy();
        $accounting = new ApiUser(['type' => 33]);
        $opname = new StockOpname(['status' => StockOpname::SUBMITTED]);

        $this->assertTrue($policy->approve($accounting, $opname));
        $this->assertTrue($policy->reject($accounting, $opname));
        $this->assertFalse($policy->create($accounting));
        $this->assertFalse($policy->post($accounting, $opname));
        $opname->status = StockOpname::APPROVED;
        $this->assertTrue($policy->post($accounting, $opname));
    }
}
