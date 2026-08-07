<?php

namespace Tests\Unit;

use App\Services\PaymentAllocationService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PaymentAllocationServiceTest extends TestCase
{
    private PaymentAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentAllocationService();
    }

    public function test_normal_payment_and_withholding_reduce_payable(): void
    {
        $this->assertSame(['ap_reduction' => 100.0, 'advance' => 0.0], $this->service->calculate(100, 98, 2, 0, null));
    }

    public function test_income_difference_closes_remaining_payable(): void
    {
        $this->assertSame(['ap_reduction' => 100.0, 'advance' => 0.0], $this->service->calculate(100, 98, 0, 2, 'PENDAPATAN_SELISIH'));
    }

    public function test_expense_difference_accounts_for_cash_paid_above_payable(): void
    {
        $this->assertSame(['ap_reduction' => 100.0, 'advance' => 0.0], $this->service->calculate(100, 102, 0, 2, 'BEBAN_SELISIH'));
    }

    public function test_overpayment_becomes_supplier_advance(): void
    {
        $this->assertSame(['ap_reduction' => 100.0, 'advance' => 20.0], $this->service->calculate(100, 120, 0, 20, 'UANG_MUKA_SUPPLIER'));
    }

    public function test_overpayment_without_advance_type_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->calculate(100, 120, 0, 0, null);
    }
}
