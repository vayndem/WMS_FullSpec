<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\FinancialStatementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinancialStatementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_accounting_can_view_all_four_reports_and_pdf_exports(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $coa = ChartOfAccount::firstOrFail();

        $this->actingAs($accounting)->get(route('financial-statements.neraca-saldo'))->assertOk()->assertSee('Neraca Saldo');
        $this->actingAs($accounting)->get(route('financial-statements.neraca-saldo.pdf'))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.buku-besar'))->assertOk()->assertSee('Buku Besar');
        $this->actingAs($accounting)->get(route('financial-statements.buku-besar.pdf', ['coa_id' => $coa->id]))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.laba-rugi'))->assertOk()->assertSee('Laba Rugi');
        $this->actingAs($accounting)->get(route('financial-statements.laba-rugi.pdf'))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.neraca'))->assertOk()->assertSee('Neraca');
        $this->actingAs($accounting)->get(route('financial-statements.neraca.pdf'))->assertOk();
    }

    public function test_non_accounting_role_is_forbidden_from_financial_statements(): void
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);

        $this->actingAs($purchasing)->get(route('financial-statements.neraca-saldo'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.buku-besar'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.laba-rugi'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.neraca'))->assertForbidden();
    }

    public function test_trial_balance_debit_and_kredit_totals_match(): void
    {
        $service = app(FinancialStatementService::class);
        $data = $service->trialBalance(today());

        $this->assertEqualsWithDelta($data['total_debit'], $data['total_kredit'], 0.01);
    }

    public function test_balance_sheet_balances_with_computed_retained_earnings(): void
    {
        $service = app(FinancialStatementService::class);
        $data = $service->balanceSheet(today());

        $this->assertEqualsWithDelta(
            $data['total_aset'],
            $data['total_liabilitas'] + $data['total_ekuitas'],
            0.01
        );
    }

    public function test_general_ledger_closing_balance_matches_opening_plus_movements(): void
    {
        $service = app(FinancialStatementService::class);
        $account = ChartOfAccount::where('kode_akun', '1102')->firstOrFail();
        $data = $service->generalLedger($account, today()->subYear(), today());

        $sign = $account->posisi_normal === 'DEBIT' ? 1 : -1;
        $movement = $data['rows']->sum(fn ($row) => $sign * ($row['debit'] - $row['kredit']));

        $this->assertEqualsWithDelta(
            $data['opening_balance'] + $movement,
            $data['closing_balance'],
            0.01
        );
    }
}
