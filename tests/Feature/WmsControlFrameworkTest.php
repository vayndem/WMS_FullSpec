<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\InvoiceLpb;
use App\Models\InvoicePayment;
use App\Models\InventoryLayer;
use App\Models\ChartOfAccount;
use App\Models\LandedCost;
use App\Models\PenerimaanBarang;
use App\Models\InventoryReservation;
use App\Models\Gudang;
use App\Models\TransferGudang;
use App\Models\Npk;
use App\Models\ReturPembelian;
use App\Models\StokGudang;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DocumentNumberService;
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
        $lpb = PenerimaanBarang::where('document_type', 'GOODS')->whereDoesntHave('invoiceReceipts')->firstOrFail();
        $detail = $lpb->details()->firstOrFail();
        $balance = StokGudang::where('gudang_id', $lpb->gudang_id)->where('bahan_id', $detail->id_bahan)->firstOrFail();
        $before = (float) $balance->stok_tersedia;

        app(InventoryReversalService::class)->reverseLpb($lpb, 'Koreksi integration test LPB');

        $this->assertSame(PenerimaanBarang::REVERSED, $lpb->fresh()->status);
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

    public function test_supplier_advance_can_be_generated_then_consumed_by_a_later_payment(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $supplier = Supplier::create([
            'nama' => 'Supplier Uang Muka Test',
            'alamat' => 'Jl. Test No. 1',
            'telp' => '0800000000',
            'pembayaran' => 'Transfer',
        ]);
        $invoiceA = InvoiceLpb::create([
            'no_invoice' => 'ADV-TEST-INV-A',
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'grand_total' => 1000000,
            'sisa_tagihan' => 1000000,
            'status' => InvoiceLpb::UNPAID,
        ]);
        $invoiceB = InvoiceLpb::create([
            'no_invoice' => 'ADV-TEST-INV-B',
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'grand_total' => 500000,
            'sisa_tagihan' => 500000,
            'status' => InvoiceLpb::UNPAID,
        ]);
        $kasUtama = ChartOfAccount::where('kode_akun', '1101')->firstOrFail();
        $uangMukaAccount = ChartOfAccount::where('kode_akun', '1401')->firstOrFail();
        $numbers = app(DocumentNumberService::class);

        $responseA = $this->actingAs($finance)->postJson(route('invoice-payments.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoiceA->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 1200000,
            'selisih_bayar' => 200000,
            'jenis_selisih' => 'UANG_MUKA_SUPPLIER',
            'coa_selisih_id' => $uangMukaAccount->id,
        ]);
        $responseA->assertCreated();
        $paymentA = InvoicePayment::findOrFail($responseA->json('data.id'));
        $this->assertSame(200000.0, (float) $paymentA->kelebihan_pembayaran);
        $this->assertSame(InvoiceLpb::PAID, $invoiceA->fresh()->status);
        $this->assertSame(200000.0, $paymentA->sisaUangMuka());

        $advances = $this->actingAs($finance)
            ->getJson(route('invoice-payments.available-advances', $supplier->id))
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $advances);
        $this->assertSame($paymentA->id, $advances[0]['id']);
        $this->assertSame(200000.0, (float) $advances[0]['sisa']);

        $responseB = $this->actingAs($finance)->postJson(route('invoice-payments.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoiceB->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 300000,
            'uang_muka_sumber_payment_id' => $paymentA->id,
            'uang_muka_dipakai' => 200000,
        ]);
        $responseB->assertCreated();
        $paymentB = InvoicePayment::findOrFail($responseB->json('data.id'));
        $this->assertSame(500000.0, (float) $paymentB->total_transaksi_pengurang_hutang);
        $this->assertSame(InvoiceLpb::PAID, $invoiceB->fresh()->status);
        $this->assertSame(0.0, $paymentA->sisaUangMuka());

        $journalB = $paymentB->fresh()->load('invoice');
        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'PELUNASAN_HUTANG')->where('reff_id', $paymentB->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $this->assertTrue($jurnal->details->contains(fn ($line) => (int) $line->coa_id === $uangMukaAccount->id && (float) $line->kredit === 200000.0));

        $this->actingAs($finance)->deleteJson(route('invoice-payments.destroy', $paymentA->id))
            ->assertStatus(422);

        $this->actingAs($finance)->deleteJson(route('invoice-payments.destroy', $paymentB->id))
            ->assertOk();
        $this->assertSame(InvoicePayment::VOID, $paymentB->fresh()->status);

        $this->actingAs($finance)->deleteJson(route('invoice-payments.destroy', $paymentA->id))
            ->assertOk();
        $this->assertSame(InvoicePayment::VOID, $paymentA->fresh()->status);
    }

    public function test_supplier_advance_cannot_be_used_across_different_suppliers(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $supplierOne = Supplier::create(['nama' => 'Supplier Satu', 'alamat' => 'Jl. Satu', 'telp' => '0800000001', 'pembayaran' => 'Transfer']);
        $supplierTwo = Supplier::create(['nama' => 'Supplier Dua', 'alamat' => 'Jl. Dua', 'telp' => '0800000002', 'pembayaran' => 'Transfer']);
        $invoiceOne = InvoiceLpb::create(['no_invoice' => 'ADV-TEST-X1', 'kode_supplier' => $supplierOne->id, 'tanggal' => today(), 'grand_total' => 1000000, 'sisa_tagihan' => 1000000, 'status' => InvoiceLpb::UNPAID]);
        $invoiceTwo = InvoiceLpb::create(['no_invoice' => 'ADV-TEST-X2', 'kode_supplier' => $supplierTwo->id, 'tanggal' => today(), 'grand_total' => 500000, 'sisa_tagihan' => 500000, 'status' => InvoiceLpb::UNPAID]);
        $kasUtama = ChartOfAccount::where('kode_akun', '1101')->firstOrFail();
        $uangMukaAccount = ChartOfAccount::where('kode_akun', '1401')->firstOrFail();
        $numbers = app(DocumentNumberService::class);

        $responseOne = $this->actingAs($finance)->postJson(route('invoice-payments.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoiceOne->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 1200000,
            'selisih_bayar' => 200000,
            'jenis_selisih' => 'UANG_MUKA_SUPPLIER',
            'coa_selisih_id' => $uangMukaAccount->id,
        ])->assertCreated();
        $paymentOne = InvoicePayment::findOrFail($responseOne->json('data.id'));

        $this->actingAs($finance)->postJson(route('invoice-payments.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoiceTwo->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 300000,
            'uang_muka_sumber_payment_id' => $paymentOne->id,
            'uang_muka_dipakai' => 200000,
        ])->assertStatus(422);
    }

    public function test_purchase_return_index_and_create_views_render(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);

        $this->actingAs($warehouse)->get(route('retur-pembelian.index'))->assertOk()->assertSee('Retur Pembelian');
        $this->actingAs($warehouse)->get(route('retur-pembelian.create'))->assertOk()->assertSee('Buat Retur Pembelian Baru');
    }

    public function test_lpb_index_and_create_views_render_without_a_stray_detail_row_template(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);

        $index = $this->actingAs($warehouse)->get(route('penerimaan-barang.index'));
        $index->assertOk()->assertSee('Daftar Penerimaan');
        $index->assertSeeInOrder(['x-for="row in rows" :key="row.id"', '<tbody>', 'toggleExpand(row.id)', 'expanded[row.id]', '</tbody>'], false);

        $this->actingAs($warehouse)->get(route('penerimaan-barang.create'))->assertOk()->assertSee('LPB');
    }

    public function test_request_index_and_create_views_render_without_a_stray_detail_row_template(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);

        $index = $this->actingAs($warehouse)->get(route('request.index'));
        $index->assertOk()->assertSee('Daftar Request');
        $index->assertSeeInOrder(['x-for="row in rows" :key="row.id"', '<tbody>', 'toggleExpand(row.id)', 'expanded[row.id]', '</tbody>'], false);

        $this->actingAs($warehouse)->get(route('request.create'))->assertOk();
    }

    public function test_pembelian_index_view_renders_without_a_stray_detail_row_template(): void
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);

        $index = $this->actingAs($purchasing)->get(route('pembelian.index'));
        $index->assertOk()->assertSee('Daftar Transaksi Pembelian');
        $index->assertSeeInOrder(['x-for="row in rows" :key="row.no_po"', '<tbody>', 'toggleExpand(row.no_po)', 'expanded[row.no_po]', '</tbody>'], false);
    }

    public function test_invoice_lpb_create_view_renders_supplier_and_receipt_picker(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);

        $create = $this->actingAs($accounting)->get(route('invoice-lpb.create'));
        $create->assertOk();
        $create->assertSee('Pilih Supplier');
        $create->assertSee('Pilih LPB / BAP Supplier');
        $create->assertSee('function invoiceCreateForm', false);
    }

    public function test_purchase_return_reduces_stock_and_grni_then_can_be_reversed(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $lpb = PenerimaanBarang::where('document_type', 'GOODS')->whereDoesntHave('invoiceReceipts')->where('status', PenerimaanBarang::POSTED)->firstOrFail();
        $detail = $lpb->details()->where('jumlah_tersisa', '>', 0)->firstOrFail();
        $layer = InventoryLayer::where('source_type', 'LPB_DETAIL')->where('source_id', $detail->id)->firstOrFail();
        $balance = StokGudang::where('gudang_id', $lpb->gudang_id)->where('bahan_id', $detail->id_bahan)->firstOrFail();
        $kategori = $detail->kategori;

        $stockBefore = (float) $balance->stok_tersedia;
        $layerBefore = (float) $layer->remaining_quantity;
        $onhandBefore = (float) $detail->bahan->stok_onhand;
        $returQty = min(1.0, $layerBefore);
        $this->assertGreaterThan(0, $returQty);

        $numbers = app(DocumentNumberService::class);
        $response = $this->actingAs($warehouse)->postJson(route('retur-pembelian.store'), [
            'no_retur' => $numbers->external('RTV'),
            'lpb_id' => $lpb->id,
            'tanggal' => today()->toDateString(),
            'alasan' => 'Barang rusak saat pemeriksaan gudang (integration test).',
            'details' => [
                ['lpb_detail_id' => $detail->id, 'jumlah_retur' => $returQty],
            ],
        ]);
        $response->assertCreated();
        $retur = ReturPembelian::findOrFail($response->json('data.id'));

        $this->assertSame($stockBefore - $returQty, (float) $balance->fresh()->stok_tersedia);
        $this->assertSame($layerBefore - $returQty, (float) $layer->fresh()->remaining_quantity);
        $this->assertSame($onhandBefore - $returQty, (float) $detail->bahan->fresh()->stok_onhand);

        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'RETUR_PEMBELIAN')->where('reff_id', $retur->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $expectedAmount = round($returQty * (float) $detail->harga, 2);
        $this->assertTrue($jurnal->details->contains(
            fn($line) => (int) $line->coa_id === (int) $kategori->coa_clearing_lpb_id && abs((float) $line->debit - $expectedAmount) < 0.01
        ));
        $this->assertTrue($jurnal->details->contains(
            fn($line) => (int) $line->coa_id === (int) $kategori->coa_persediaan_id && abs((float) $line->kredit - $expectedAmount) < 0.01
        ));

        $overResponse = $this->actingAs($warehouse)->postJson(route('retur-pembelian.store'), [
            'no_retur' => $numbers->external('RTV'),
            'lpb_id' => $lpb->id,
            'tanggal' => today()->toDateString(),
            'alasan' => 'Percobaan retur melebihi stok tersedia.',
            'details' => [
                ['lpb_detail_id' => $detail->id, 'jumlah_retur' => $layerBefore + 1000],
            ],
        ]);
        $overResponse->assertStatus(422);

        app(InventoryReversalService::class)->reverseReturPembelian($retur, 'Koreksi integration test retur pembelian');

        $this->assertSame(ReturPembelian::REVERSED, $retur->fresh()->status);
        $this->assertSame($stockBefore, (float) $balance->fresh()->stok_tersedia);
        $this->assertSame($layerBefore, (float) $layer->fresh()->remaining_quantity);
        $this->assertDatabaseHas('document_reversals', ['document_type' => 'RETUR_PEMBELIAN', 'document_id' => $retur->id]);
    }
}
