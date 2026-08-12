<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\InvoiceLpb;
use App\Models\InvoicePayment;
use App\Models\InventoryLayer;
use App\Models\ChartOfAccount;
use App\Models\LandedCost;
use App\Models\Lpb;
use App\Models\InventoryReservation;
use App\Models\Gudang;
use App\Models\TransferGudang;
use App\Models\Npk;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\InventoryReversalService;
use App\Services\LandedCostService;
use App\Services\RekonsiliasiGudangService;
use App\Services\ThreeWayMatchService;
use App\Services\TransferGudangService;
use App\Services\WarehouseExecutionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WmsControlFrameworkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_rejects_email_header_injection_characters(): void
    {
        $this->post(route('login.attempt'), [
            'email' => "operator@example.com\r\nBcc: attacker@example.com",
            'password' => 'secret',
        ])->assertSessionHasErrors('email');
    }

    public function test_control_center_is_available_to_warehouse_and_accounting(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);

        $this->actingAs($warehouse)->get(route('wms-control.index'))->assertOk();
        $this->actingAs($accounting)->get(route('wms-control.index'))->assertOk();
    }

    public function test_reservation_updates_and_releases_reserved_balance_atomically(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        Auth::login($user);
        $balance = StokGudang::whereRaw('stok_tersedia - stok_direservasi >= 1')->firstOrFail();
        $before = (float) $balance->stok_direservasi;

        $reservation = app(WarehouseExecutionService::class)->reserve($balance->gudang_id, $balance->bahan_id, 1);
        $this->assertSame($before + 1, (float) $balance->fresh()->stok_direservasi);

        $pick = app(WarehouseExecutionService::class)->createPick($reservation);
        app(WarehouseExecutionService::class)->completePick($pick);
        $this->assertSame('PICKED', $reservation->fresh()->status);

        app(WarehouseExecutionService::class)->release($reservation->fresh());
        $this->assertSame($before, (float) $balance->fresh()->stok_direservasi);
        $this->assertSame('RELEASED', $reservation->fresh()->status);
    }

    public function test_transfer_uses_in_transit_then_receipt_without_changing_global_stock(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        Auth::login($user);
        $source = StokGudang::whereRaw('stok_tersedia - stok_direservasi >= 1')->whereHas('gudang', fn ($query) => $query->where('jenis', Gudang::NORMAL))->firstOrFail();
        $target = Gudang::where('jenis', Gudang::NORMAL)->whereKeyNot($source->gudang_id)->firstOrFail();
        $material = Bahan::findOrFail($source->bahan_id);
        $masterBefore = (float) $material->stok_onhand;
        $transfer = TransferGudang::create(['nomor_transfer' => 'TEST-IN-TRANSIT-001', 'tanggal' => today(), 'gudang_asal_id' => $source->gudang_id, 'gudang_tujuan_id' => $target->id, 'status' => TransferGudang::DIAJUKAN, 'dibuat_oleh' => $user->id]);
        $transfer->details()->create(['bahan_id' => $source->bahan_id, 'jumlah' => 1]);

        app(TransferGudangService::class)->konfirmasi($transfer);
        $this->assertSame(TransferGudang::DIKIRIM, $transfer->fresh()->status);

        app(TransferGudangService::class)->terima($transfer->fresh());
        $this->assertSame(TransferGudang::DITERIMA, $transfer->fresh()->status);
        $this->assertSame($masterBefore, (float) $material->fresh()->stok_onhand);
    }

    public function test_seeded_documents_pass_three_way_matching(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($user);
        $invoice = InvoiceLpb::where('status', '!=', InvoiceLpb::VOID)->firstOrFail();

        $result = app(ThreeWayMatchService::class)->evaluate($invoice);

        $this->assertSame('MATCHED', $result['status']);
        $this->assertSame([], $result['issues']);
    }

    public function test_landed_cost_updates_active_layer_and_posts_balanced_journal(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($user);
        $layer = InventoryLayer::where('remaining_quantity', '>', 0)->firstOrFail();
        $credit = ChartOfAccount::where('is_active', true)->where('is_postable', true)->where('kategori_akun', 'LIABILITAS')->where('posisi_normal', 'KREDIT')->firstOrFail();
        $cost = LandedCost::create(['number' => 'TEST-LDC-001', 'date' => today(), 'description' => 'Integration landed cost', 'allocation_basis' => 'VALUE', 'total_amount' => 1000, 'credit_coa_id' => $credit->id, 'created_by' => $user->id]);
        $before = (float) $layer->unit_cost;

        app(LandedCostService::class)->allocate($cost, [$layer->id]);
        $journal = app(LandedCostService::class)->post($cost);

        $this->assertSame('POSTED', $cost->fresh()->status);
        $this->assertGreaterThan($before, (float) $layer->fresh()->unit_cost);
        $this->assertEquals($journal->total_debit, $journal->total_kredit);
    }

    public function test_npk_reversal_restores_fifo_stock_and_creates_reversal_journal(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($user);
        $npk = Npk::where('status', Npk::POSTED)->firstOrFail();
        $material = Bahan::findOrFail($npk->id_barang);
        $before = (float) $material->stok_onhand;
        $quantity = (float) $npk->jumlah_stok > 0 ? (float) $npk->jumlah_stok : (float) $npk->jumlah;

        $reversal = app(InventoryReversalService::class)->reverseNpk($npk, 'Koreksi integration test reversal');

        $this->assertSame(Npk::REVERSED, $npk->fresh()->status);
        $this->assertSame($before + $quantity, (float) $material->fresh()->stok_onhand);
        $this->assertNotNull($reversal->reversal_journal_id);
    }

    public function test_unconsumed_unbilled_lpb_can_be_reversed_without_orphan_stock(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($user);
        $lpb = Lpb::where('document_type', 'GOODS')->whereDoesntHave('invoiceReceipts')->firstOrFail();
        $detail = $lpb->details()->firstOrFail();
        $balance = StokGudang::where('gudang_id', $lpb->gudang_id)->where('bahan_id', $detail->id_bahan)->firstOrFail();
        $before = (float) $balance->stok_tersedia;

        app(InventoryReversalService::class)->reverseLpb($lpb, 'Koreksi integration test LPB');

        $this->assertSame(Lpb::REVERSED, $lpb->fresh()->status);
        $this->assertSame($before - (float) $detail->jumlah_barang_diterima, (float) $balance->fresh()->stok_tersedia);
        $this->assertDatabaseHas('document_reversals', ['document_type' => 'LPB', 'document_id' => $lpb->id]);
    }

    public function test_quantity_and_value_reconciliation_are_balanced(): void
    {
        $summary = app(RekonsiliasiGudangService::class)->summary();

        $this->assertSame(0, $summary['quantity_exceptions']);
        $this->assertEqualsWithDelta(0, $summary['global_quantity_difference'], .000001);
        $this->assertEqualsWithDelta(0, $summary['value_difference'], .01);
    }

    public function test_invoice_reconciliation_detail_uses_canonical_status_column(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);

        $this->actingAs($accounting)
            ->get(route('reconciliation.show', 'invoice'))
            ->assertOk()
            ->assertSee('Detail Rekonsiliasi')
            ->assertSee('Partially Paid');
    }

    public function test_void_payment_is_excluded_from_active_invoice_payments(): void
    {
        $payment = InvoicePayment::where('status', InvoicePayment::POSTED)->firstOrFail();
        $invoice = $payment->invoice;
        $activeBefore = $invoice->payments()->count();

        $payment->update(['status' => InvoicePayment::VOID]);

        $this->assertSame($activeBefore - 1, $invoice->payments()->count());
        $this->assertSame(InvoicePayment::VOID, $payment->fresh()->status);
    }
}
