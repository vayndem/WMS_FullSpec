<?php

namespace Tests\Unit;

use App\Models\AccountingPeriodLock;
use App\Models\AccountingReconciliation;
use App\Models\ApiUser;
use App\Models\Jurnal;
use App\Models\TaxRate;
use App\Policies\AccountingPeriodLockPolicy;
use App\Policies\AccountingReconciliationPolicy;
use App\Policies\JurnalPolicy;
use App\Policies\TaxRatePolicy;
use App\Policies\BahanPolicy;
use App\Policies\InvoicelpbPolicy;
use App\Models\Invoicelpb;
use PHPUnit\Framework\TestCase;

class AccountingControlPolicyTest extends TestCase
{
    public function test_only_accounting_can_manage_period_and_tax(): void
    {
        $warehouse = new ApiUser(['type' => 5]);
        $accounting = new ApiUser(['type' => 33]);
        $lock = new AccountingPeriodLock(['status' => 'LOCKED']);
        $rate = new TaxRate();

        $this->assertFalse((new AccountingPeriodLockPolicy())->create($warehouse));
        $this->assertTrue((new AccountingPeriodLockPolicy())->create($accounting));
        $this->assertTrue((new AccountingPeriodLockPolicy())->unlock($accounting, $lock));
        $this->assertFalse((new TaxRatePolicy())->update($warehouse, $rate));
        $this->assertTrue((new TaxRatePolicy())->update($accounting, $rate));
    }

    public function test_reconciliation_financial_values_are_accounting_only(): void
    {
        $policy = new AccountingReconciliationPolicy();
        $warehouse = new ApiUser(['type' => 5]);
        $accounting = new ApiUser(['type' => 33]);

        $this->assertTrue($policy->viewAny($warehouse));
        $this->assertFalse($policy->viewFinancials($warehouse));
        $this->assertTrue($policy->viewFinancials($accounting));
    }

    public function test_automatic_journal_cannot_be_reversed_from_general_journal(): void
    {
        $policy = new JurnalPolicy();
        $accounting = new ApiUser(['type' => 33]);
        $manual = new Jurnal(['sumber_transaksi' => 'MANUAL', 'status' => 'POSTED']);
        $automatic = new Jurnal(['sumber_transaksi' => 'LPB', 'status' => 'POSTED']);

        $this->assertTrue($policy->reverse($accounting, $manual));
        $this->assertFalse($policy->reverse($accounting, $automatic));
    }

    public function test_every_authenticated_user_can_view_material_but_only_accounting_sees_price(): void
    {
        $policy = new BahanPolicy();
        $warehouse = new ApiUser(['type' => 5]);
        $accounting = new ApiUser(['type' => 33]);
        $other = new ApiUser(['type' => 13]);

        $this->assertTrue($policy->viewAny($warehouse));
        $this->assertTrue($policy->viewAny($other));
        $this->assertFalse($policy->viewFinancials($warehouse));
        $this->assertFalse($policy->viewFinancials($other));
        $this->assertTrue($policy->viewFinancials($accounting));
    }

    public function test_type_thirteen_can_pay_but_cannot_edit_invoice_header(): void
    {
        $policy = new InvoicelpbPolicy();
        $paymentUser = new ApiUser(['type' => 13]);
        $invoice = new Invoicelpb(['is_void' => false, 'sisa_tagihan' => 100000]);

        $this->assertTrue($policy->pay($paymentUser, $invoice));
        $this->assertTrue($policy->voidPayment($paymentUser, $invoice));
        $this->assertFalse($policy->update($paymentUser, $invoice));
        $this->assertFalse($policy->delete($paymentUser, $invoice));
    }
}
