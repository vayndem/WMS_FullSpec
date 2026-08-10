<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Lpb;
use App\Models\Npk;
use App\Models\Request as RequestModel;
use App\Models\ServiceBap;
use App\Models\StockOpname;
use App\Policies\LpbPolicy;
use App\Policies\NpkPolicy;
use App\Policies\RequestPolicy;
use App\Policies\ServiceBapPolicy;
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

        $this->assertTrue((new RequestPolicy())->viewAny($user));
        $this->assertTrue((new RequestPolicy())->create($user));
        $this->assertTrue((new LpbPolicy())->create($user));
        $this->assertTrue((new NpkPolicy())->create($user));
        $this->assertTrue((new ServiceBapPolicy())->create($user));
        $this->assertTrue((new StockOpnamePolicy())->create($user));
    }

    public function test_warehouse_role_never_receives_financial_visibility(): void
    {
        $user = $this->warehouse();

        $this->assertFalse((new NpkPolicy())->viewFinancials($user));
        $this->assertFalse((new ServiceBapPolicy())->viewFinancials($user));
        $this->assertFalse((new StockOpnamePolicy())->viewFinancials($user));
    }

    public function test_warehouse_role_cannot_approve_request_or_opname(): void
    {
        $user = $this->warehouse();
        $request = new RequestModel(['status' => 'pending']);
        $opname = new StockOpname(['status' => StockOpname::SUBMITTED]);

        $this->assertFalse((new RequestPolicy())->approve($user, $request));
        $this->assertFalse((new StockOpnamePolicy())->approve($user, $opname));
        $this->assertFalse((new StockOpnamePolicy())->reject($user, $opname));
    }

    public function test_production_role_can_run_issue_and_opname_without_financials(): void
    {
        $user = $this->production();

        $this->assertTrue((new NpkPolicy())->create($user));
        $this->assertTrue((new StockOpnamePolicy())->create($user));
        $this->assertFalse((new NpkPolicy())->viewFinancials($user));
        $this->assertFalse((new StockOpnamePolicy())->viewFinancials($user));
        $this->assertFalse((new LpbPolicy())->create($user));
        $this->assertFalse((new ServiceBapPolicy())->create($user));
        $this->assertFalse((new RequestPolicy())->create($user));
    }
}
