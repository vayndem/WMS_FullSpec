<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\PenerimaanBarang;
use App\Models\PemakaianBarang;
use App\Models\MaterialRequest;
use App\Models\PenerimaanJasa;
use App\Models\StockOpname;
use App\Policies\PenerimaanBarangPolicy;
use App\Policies\PemakaianBarangPolicy;
use App\Policies\MaterialRequestPolicy;
use App\Policies\PenerimaanJasaPolicy;
use App\Policies\StockOpnamePolicy;
use PHPUnit\Framework\TestCase;

class WarehouseRolePolicyTest extends TestCase
{
    private function warehouse(): User
    {
        return new User(['id' => User::ROLE_WAREHOUSE, 'name' => 'Gudang', 'type' => User::ROLE_WAREHOUSE]);
    }

    private function production(): User
    {
        return new User(['id' => User::ROLE_PRODUCTION, 'name' => 'Produksi', 'type' => User::ROLE_PRODUCTION]);
    }

    public function test_warehouse_role_can_run_warehouse_documents(): void
    {
        $user = $this->warehouse();

        $this->assertTrue((new MaterialRequestPolicy())->viewAny($user));
        $this->assertTrue((new MaterialRequestPolicy())->create($user));
        $this->assertTrue((new PenerimaanBarangPolicy())->create($user));
        $this->assertTrue((new PemakaianBarangPolicy())->create($user));
        $this->assertTrue((new PenerimaanJasaPolicy())->create($user));
        $this->assertTrue((new StockOpnamePolicy())->create($user));
    }

    public function test_warehouse_role_never_receives_financial_visibility(): void
    {
        $user = $this->warehouse();

        $this->assertFalse((new PemakaianBarangPolicy())->viewFinancials($user));
        $this->assertFalse((new PenerimaanJasaPolicy())->viewFinancials($user));
        $this->assertFalse((new StockOpnamePolicy())->viewFinancials($user));
    }

    public function test_warehouse_role_cannot_approve_request_or_opname(): void
    {
        $user = $this->warehouse();
        $request = new MaterialRequest(['status' => MaterialRequest::PENDING]);
        $opname = new StockOpname(['status' => StockOpname::SUBMITTED]);

        $this->assertFalse((new MaterialRequestPolicy())->approve($user, $request));
        $this->assertFalse((new StockOpnamePolicy())->approve($user, $opname));
        $this->assertFalse((new StockOpnamePolicy())->reject($user, $opname));
    }

    public function test_production_role_can_run_issue_and_opname_without_financials(): void
    {
        $user = $this->production();

        $this->assertTrue((new PemakaianBarangPolicy())->create($user));
        $this->assertTrue((new StockOpnamePolicy())->create($user));
        $this->assertFalse((new PemakaianBarangPolicy())->viewFinancials($user));
        $this->assertFalse((new StockOpnamePolicy())->viewFinancials($user));
        $this->assertFalse((new PenerimaanBarangPolicy())->create($user));
        $this->assertFalse((new PenerimaanJasaPolicy())->create($user));
        $this->assertTrue((new MaterialRequestPolicy())->create($user));
    }
}
