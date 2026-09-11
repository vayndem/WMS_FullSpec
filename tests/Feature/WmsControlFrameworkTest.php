<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Bahan;
use App\Models\FakturPembelian;
use App\Models\KategoriBahan;
use App\Models\PembayaranFaktur;
use App\Models\LayerPersediaan;
use App\Models\BaganAkun;
use App\Models\BiayaTambahan;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangDetail;
use App\Models\PesananPembelian;
use App\Models\ReservasiPersediaan;
use App\Models\Gudang;
use App\Models\TransferGudang;
use App\Models\PemakaianBarang;
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
use App\Services\StokGudangService;
use App\Services\WmsAccountingService;
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
        $invoice = FakturPembelian::where('status', '!=', FakturPembelian::VOID)->firstOrFail();

        $result = app(ThreeWayMatchService::class)->evaluate($invoice);

        $this->assertSame('MATCHED', $result['status']);
        $this->assertSame([], $result['issues']);
    }

    public function test_landed_cost_updates_active_layer_and_posts_balanced_journal(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($user);
        $layer = LayerPersediaan::where('remaining_quantity', '>', 0)->firstOrFail();
        $credit = BaganAkun::where('is_active', true)->where('is_postable', true)->where('kategori_akun', 'LIABILITAS')->where('posisi_normal', 'KREDIT')->firstOrFail();
        $cost = BiayaTambahan::create(['number' => 'TEST-LDC-001', 'date' => today(), 'description' => 'Integration landed cost', 'allocation_basis' => 'VALUE', 'total_amount' => 1000, 'credit_coa_id' => $credit->id, 'created_by' => $user->id]);
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
        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)->firstOrFail();
        $material = Bahan::findOrFail($npk->id_barang);
        $before = (float) $material->stok_onhand;
        $quantity = (float) $npk->jumlah_stok > 0 ? (float) $npk->jumlah_stok : (float) $npk->jumlah;

        $reversal = app(InventoryReversalService::class)->reverseNpk($npk, 'Koreksi integration test reversal');

        $this->assertSame(PemakaianBarang::REVERSED, $npk->fresh()->status);
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
        $this->assertDatabaseHas('wms_pembalikan_dokumen', ['document_type' => 'LPB', 'document_id' => $lpb->id]);
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
        $payment = PembayaranFaktur::where('status', PembayaranFaktur::POSTED)->firstOrFail();
        $invoice = $payment->invoice;
        $activeBefore = $invoice->payments()->count();

        $payment->update(['status' => PembayaranFaktur::VOID]);

        $this->assertSame($activeBefore - 1, $invoice->payments()->count());
        $this->assertSame(PembayaranFaktur::VOID, $payment->fresh()->status);
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
        $invoiceA = FakturPembelian::create([
            'no_invoice' => 'ADV-TEST-INV-A',
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'grand_total' => 1000000,
            'sisa_tagihan' => 1000000,
            'status' => FakturPembelian::UNPAID,
        ]);
        $invoiceB = FakturPembelian::create([
            'no_invoice' => 'ADV-TEST-INV-B',
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'grand_total' => 500000,
            'sisa_tagihan' => 500000,
            'status' => FakturPembelian::UNPAID,
        ]);
        $kasUtama = BaganAkun::where('kode_akun', '1101')->firstOrFail();
        $uangMukaAccount = BaganAkun::where('kode_akun', '1401')->firstOrFail();
        $numbers = app(DocumentNumberService::class);

        $responseA = $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
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
        $paymentA = PembayaranFaktur::findOrFail($responseA->json('data.id'));
        $this->assertSame(200000.0, (float) $paymentA->kelebihan_pembayaran);
        $this->assertSame(FakturPembelian::PAID, $invoiceA->fresh()->status);
        $this->assertSame(200000.0, $paymentA->sisaUangMuka());

        $advances = $this->actingAs($finance)
            ->getJson(route('pembayaran-faktur.available-advances', $supplier->id))
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $advances);
        $this->assertSame($paymentA->id, $advances[0]['id']);
        $this->assertSame(200000.0, (float) $advances[0]['sisa']);

        $responseB = $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
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
        $paymentB = PembayaranFaktur::findOrFail($responseB->json('data.id'));
        $this->assertSame(500000.0, (float) $paymentB->total_transaksi_pengurang_hutang);
        $this->assertSame(FakturPembelian::PAID, $invoiceB->fresh()->status);
        $this->assertSame(0.0, $paymentA->sisaUangMuka());

        $journalB = $paymentB->fresh()->load('invoice');
        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'PELUNASAN_HUTANG')->where('reff_id', $paymentB->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $this->assertTrue($jurnal->details->contains(fn ($line) => (int) $line->coa_id === $uangMukaAccount->id && (float) $line->kredit === 200000.0));

        $this->actingAs($finance)->deleteJson(route('pembayaran-faktur.destroy', $paymentA->id))
            ->assertStatus(422);

        $this->actingAs($finance)->deleteJson(route('pembayaran-faktur.destroy', $paymentB->id))
            ->assertOk();
        $this->assertSame(PembayaranFaktur::VOID, $paymentB->fresh()->status);

        $this->actingAs($finance)->deleteJson(route('pembayaran-faktur.destroy', $paymentA->id))
            ->assertOk();
        $this->assertSame(PembayaranFaktur::VOID, $paymentA->fresh()->status);
    }

    public function test_supplier_advance_cannot_be_used_across_different_suppliers(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $supplierOne = Supplier::create(['nama' => 'Supplier Satu', 'alamat' => 'Jl. Satu', 'telp' => '0800000001', 'pembayaran' => 'Transfer']);
        $supplierTwo = Supplier::create(['nama' => 'Supplier Dua', 'alamat' => 'Jl. Dua', 'telp' => '0800000002', 'pembayaran' => 'Transfer']);
        $invoiceOne = FakturPembelian::create(['no_invoice' => 'ADV-TEST-X1', 'kode_supplier' => $supplierOne->id, 'tanggal' => today(), 'grand_total' => 1000000, 'sisa_tagihan' => 1000000, 'status' => FakturPembelian::UNPAID]);
        $invoiceTwo = FakturPembelian::create(['no_invoice' => 'ADV-TEST-X2', 'kode_supplier' => $supplierTwo->id, 'tanggal' => today(), 'grand_total' => 500000, 'sisa_tagihan' => 500000, 'status' => FakturPembelian::UNPAID]);
        $kasUtama = BaganAkun::where('kode_akun', '1101')->firstOrFail();
        $uangMukaAccount = BaganAkun::where('kode_akun', '1401')->firstOrFail();
        $numbers = app(DocumentNumberService::class);

        $responseOne = $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
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
        $paymentOne = PembayaranFaktur::findOrFail($responseOne->json('data.id'));

        $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
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

    public function test_pph22_withholding_is_posted_to_its_own_liability_account_on_payment(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $supplier = Supplier::create(['nama' => 'Supplier PPh22', 'alamat' => 'Jl. PPh22', 'telp' => '0800000010', 'pembayaran' => 'Transfer']);
        $invoice = FakturPembelian::create([
            'no_invoice' => 'PPH22-TEST-1',
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'sub_total' => 10000000,
            'jenis_pph' => 'PPH22',
            'dasar_pph' => 10000000,
            'tarif_pph' => 1.5,
            'grand_total' => 10000000,
            'sisa_tagihan' => 10000000,
            'status' => FakturPembelian::UNPAID,
        ]);
        $kasUtama = BaganAkun::where('kode_akun', '1101')->firstOrFail();
        $hutangPph22 = BaganAkun::where('kode_akun', '2105')->firstOrFail();
        $numbers = app(DocumentNumberService::class);

        $response = $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoice->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 9850000,
            'potongan_pph' => 150000,
        ])->assertCreated();
        $payment = PembayaranFaktur::findOrFail($response->json('data.id'));

        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'PELUNASAN_HUTANG')->where('reff_id', $payment->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $this->assertTrue($jurnal->details->contains(fn ($line) => (int) $line->coa_id === $hutangPph22->id && (float) $line->kredit === 150000.0));
    }

    public function test_pph4a2_final_withholding_is_posted_to_its_own_liability_account_on_payment(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $supplier = Supplier::create(['nama' => 'Supplier PPh4a2', 'alamat' => 'Jl. PPh4a2', 'telp' => '0800000011', 'pembayaran' => 'Transfer']);
        $invoice = FakturPembelian::create([
            'no_invoice' => 'PPH4A2-TEST-1',
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'sub_total' => 5000000,
            'jenis_pph' => 'PPH4A2',
            'dasar_pph' => 5000000,
            'tarif_pph' => 10,
            'grand_total' => 5000000,
            'sisa_tagihan' => 5000000,
            'status' => FakturPembelian::UNPAID,
        ]);
        $kasUtama = BaganAkun::where('kode_akun', '1101')->firstOrFail();
        $hutangPph4a2 = BaganAkun::where('kode_akun', '2106')->firstOrFail();
        $numbers = app(DocumentNumberService::class);

        $response = $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoice->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 4500000,
            'potongan_pph' => 500000,
        ])->assertCreated();
        $payment = PembayaranFaktur::findOrFail($response->json('data.id'));

        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'PELUNASAN_HUTANG')->where('reff_id', $payment->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $this->assertTrue($jurnal->details->contains(fn ($line) => (int) $line->coa_id === $hutangPph4a2->id && (float) $line->kredit === 500000.0));
    }

    public function test_payment_cannot_withhold_pph_when_invoice_has_no_pph_type(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $supplier = Supplier::create(['nama' => 'Supplier Tanpa PPh', 'alamat' => 'Jl. Tanpa PPh', 'telp' => '0800000012', 'pembayaran' => 'Transfer']);
        $invoice = FakturPembelian::create([
            'no_invoice' => 'NOPPH-TEST-1',
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'sub_total' => 2000000,
            'jenis_pph' => null,
            'dasar_pph' => 0,
            'tarif_pph' => 0,
            'grand_total' => 2000000,
            'sisa_tagihan' => 2000000,
            'status' => FakturPembelian::UNPAID,
        ]);
        $kasUtama = BaganAkun::where('kode_akun', '1101')->firstOrFail();
        $numbers = app(DocumentNumberService::class);

        $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoice->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 1900000,
            'potongan_pph' => 100000,
        ])->assertStatus(422)
            ->assertJson(['message' => 'Invoice ini tidak menetapkan jenis PPh, sehingga tidak ada potongan PPh yang dapat dicatat.']);
    }

    public function test_automatic_depreciation_posts_straight_line_amount_and_skips_the_same_period_twice(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $category = \App\Models\KategoriAset::where('code', 'EQUIPMENT')->firstOrFail();
        $asset = Aset::create([
            'nomor_aset' => 'AUTO-DEP-TEST-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Penyusutan Otomatis',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 2400000,
            'residual_value' => 0,
            'useful_life_months' => 24,
            'depreciation_method' => 'STRAIGHT_LINE',
            'accumulated_depreciation' => 0,
            'book_value' => 2400000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Penyusutan Otomatis Test',
        ])->assertOk();

        $asset->refresh();
        $this->assertEqualsWithDelta(100000.0, (float) $asset->accumulated_depreciation, 0.01);
        $this->assertSame(1, $asset->depreciations()->count());

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Penyusutan Otomatis Test',
        ])->assertOk();

        $this->assertSame(1, $asset->depreciations()->count());
        $this->assertEqualsWithDelta(100000.0, (float) $asset->fresh()->accumulated_depreciation, 0.01);
    }

    public function test_manual_depreciation_defaults_to_the_suggested_straight_line_amount_when_amount_is_blank(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $category = \App\Models\KategoriAset::where('code', 'EQUIPMENT')->firstOrFail();
        $asset = Aset::create([
            'nomor_aset' => 'MANUAL-DEP-TEST-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Penyusutan Manual',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 1200000,
            'residual_value' => 0,
            'useful_life_months' => 12,
            'depreciation_method' => 'STRAIGHT_LINE',
            'accumulated_depreciation' => 0,
            'book_value' => 1200000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $this->actingAs($accounting)->postJson(route('aset.depreciate', $asset), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Penyusutan Manual Tanpa Nominal',
        ])->assertRedirect();

        $this->assertEqualsWithDelta(100000.0, (float) $asset->fresh()->accumulated_depreciation, 0.01);
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

        $create = $this->actingAs($accounting)->get(route('faktur-pembelian.create'));
        $create->assertOk();
        $create->assertSee('Pilih Supplier');
        $create->assertSee('Pilih LPB / BAP Supplier');
        $create->assertSee('function invoiceCreateForm', false);
    }

    public function test_every_role_dashboard_renders_its_task_list(): void
    {
        $roles = [
            User::ROLE_PURCHASING => 'Semua Tugas Purchasing',
            User::ROLE_FINANCE => 'Semua Tugas Finance',
            User::ROLE_WAREHOUSE => 'Semua Tugas Gudang',
            User::ROLE_PRODUCTION => 'Semua Tugas Produksi',
            User::ROLE_ACCOUNTING => 'Semua Tugas Accounting',
            User::ROLE_ACCOUNTING_MANAGER => 'Semua Tugas Accounting',
            User::ROLE_SUPER_ADMIN => 'Seluruh Tugas Terbuka',
        ];

        foreach ($roles as $role => $expectedHeading) {
            $user = User::factory()->create(['type' => $role]);
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertSee($expectedHeading)
                ->assertSee('Progres Pekerjaan');
        }
    }

    public function test_dashboard_reminders_surface_upcoming_deadlines_with_a_day_countdown(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $layer = LayerPersediaan::where('remaining_quantity', '>', 0)->where('stock_status', 'AVAILABLE')->firstOrFail();
        $lot = \App\Models\LotPersediaan::create([
            'bahan_id' => $layer->bahan_id,
            'lot_number' => 'DASH-REMINDER-1',
            'quality_status' => 'RELEASED',
            'expires_at' => today()->addDays(5),
        ]);
        $layer->update(['inventory_lot_id' => $lot->id]);

        $this->actingAs($warehouse)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kedaluwarsa &amp; Transfer Menggantung', false)
            ->assertSee('DASH-REMINDER-1')
            ->assertSee('5 hari lagi');

        $lot->update(['expires_at' => today()->subDays(2)]);
        $this->actingAs($warehouse)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lewat 2 hari');
    }

    public function test_finance_dashboard_counts_available_advances_without_an_n_plus_one(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->actingAs($finance)->get(route('dashboard'))->assertOk();
        $jumlahQuery = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThan(
            40,
            $jumlahQuery,
            "Dashboard Finance memakai {$jumlahQuery} query — cek apakah ada N+1 yang kembali (dulu uang muka dihitung satu query per baris)."
        );
    }

    public function test_non_purchasing_role_can_submit_and_track_a_material_request_to_fulfillment(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $numbers = app(DocumentNumberService::class);
        $category = \App\Models\KategoriBahan::where('katnama', 'Bahan Baku Paper')->firstOrFail();
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $supplier = Supplier::create(['nama' => 'Supplier Uji Request', 'alamat' => 'Jl. Request', 'telp' => '0800000098', 'pembayaran' => 'Transfer']);

        $this->actingAs($purchasing)->getJson(route('request.create'))->assertStatus(403);

        $this->actingAs($finance)->postJson(route('request.store'), [
            'no_request' => $numbers->internal('REQ', 'PO'),
            'items' => [[
                'nama_barang' => 'Barang Uji Request Baru',
                'jumlah_minta' => 10,
                'satuan' => 'PCS',
                'kategori' => $category->id,
                'tipe_barang' => $category->id,
                'tipe_gudang' => $gudang->id,
            ]],
        ])->assertOk();

        $materialRequest = \App\Models\MaterialRequest::latest('id')->firstOrFail();
        $this->assertSame($finance->id, $materialRequest->requested_by);
        $this->assertSame(\App\Models\MaterialRequest::PENDING, $materialRequest->status);

        $detail = $materialRequest->details()->firstOrFail();
        $this->assertNull($detail->bahan_id);

        $this->actingAs($finance)->postJson(route('request.processApprove', $materialRequest->id), [
            'items' => [$detail->id => ['jumlah_acc' => 10]],
        ])->assertStatus(403);

        $this->actingAs($purchasing)->postJson(route('request.processApprove', $materialRequest->id), [
            'items' => [$detail->id => ['jumlah_acc' => 10]],
        ])->assertOk();

        $materialRequest->refresh();
        $detail->refresh();
        $this->assertSame(\App\Models\MaterialRequest::APPROVED, $materialRequest->status);
        $this->assertNotNull($detail->bahan_id);
        $bahan = Bahan::findOrFail($detail->bahan_id);
        $this->assertSame('Barang Uji Request Baru', $bahan->nama);

        $ownIndex = $this->actingAs($finance)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('request.index'))
            ->assertOk()->json('data');
        $this->assertTrue(collect($ownIndex)->contains(fn ($row) => (int) $row['id'] === $materialRequest->id));

        $poNumber = $numbers->financial('PO');
        $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'no_po' => $poNumber,
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [[
                'bahan_id' => $bahan->id,
                'harga' => 5000,
                'jumlah' => 10,
                'request_detail_id' => $detail->id,
            ]],
        ])->assertCreated();

        $materialRequest->refresh();
        $detail->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $detail->realisasi, 0.000001);
        $this->assertSame(\App\Models\MaterialRequest::FULFILLED, $materialRequest->status);
    }

    public function test_material_request_pdf_and_excel_reports_are_scoped_to_own_requests_for_non_reviewers(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $production = User::factory()->create(['type' => User::ROLE_PRODUCTION]);
        $numbers = app(DocumentNumberService::class);
        $category = \App\Models\KategoriBahan::where('katnama', 'Bahan Baku Paper')->firstOrFail();
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();

        $this->actingAs($finance)->postJson(route('request.store'), [
            'no_request' => $numbers->internal('REQ', 'PO'),
            'items' => [[
                'nama_barang' => 'Barang Uji Scoping Report',
                'jumlah_minta' => 5,
                'satuan' => 'PCS',
                'kategori' => $category->id,
                'tipe_barang' => $category->id,
                'tipe_gudang' => $gudang->id,
            ]],
        ])->assertOk();
        $materialRequest = \App\Models\MaterialRequest::latest('id')->firstOrFail();

        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->actingAs($production)->get(route('request.report.excel'))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('daftar-request-' . now()->format('Ymd-His') . '.xlsx', function (\App\Exports\GenericTableExport $export) use ($materialRequest) {
            $noRequests = $export->collection()->pluck('no_request');
            return !$noRequests->contains($materialRequest->no_request);
        });

        $this->actingAs($production)->get(route('request.report.pdf'))->assertOk();

        $superAdmin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->actingAs($superAdmin)->get(route('request.report.excel'))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('daftar-request-' . now()->format('Ymd-His') . '.xlsx', function (\App\Exports\GenericTableExport $export) use ($materialRequest) {
            return $export->collection()->pluck('no_request')->contains($materialRequest->no_request);
        });
    }

    public function test_receiving_captures_lot_expiry_and_expired_stock_is_rejected_with_a_clear_message(): void
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $numbers = app(DocumentNumberService::class);
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $bahan = Bahan::whereNotNull('kategori')->firstOrFail();
        $supplier = Supplier::create(['nama' => 'Supplier Uji Expiry', 'alamat' => 'Jl. Expiry', 'telp' => '0800000097', 'pembayaran' => 'Transfer']);

        $poNumber = $numbers->financial('PO');
        $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'no_po' => $poNumber,
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 5000, 'jumlah' => 10]],
        ])->assertCreated();

        $expiry = today()->addDays(15);
        $this->actingAs($warehouse)->postJson(route('penerimaan-barang.store'), [
            'id_lpb' => $numbers->external('LPB'),
            'tanggal' => today()->toDateString(),
            'no_po' => $poNumber,
            'no_sj' => 'SJ-EXPIRY-001',
            'details' => [[
                'id_bahan' => $bahan->id,
                'id_kategori' => $bahan->kategori,
                'jumlah_barang_diterima' => 10,
                'lot_number' => 'LOT-EXPIRY-001',
                'expires_at' => $expiry->toDateString(),
            ]],
        ])->assertCreated();

        $lot = \App\Models\LotPersediaan::where('lot_number', 'LOT-EXPIRY-001')->firstOrFail();
        $this->assertSame($expiry->toDateString(), $lot->expires_at->toDateString());

        $tersediaSebelumExpired = (float) LayerPersediaan::where('gudang_id', $gudang->id)
            ->where('bahan_id', $bahan->id)->where('stock_status', 'AVAILABLE')
            ->where('remaining_quantity', '>', 0)->sum('remaining_quantity');

        $lot->update(['expires_at' => today()->subDay()]);

        try {
            app(StokGudangService::class)->ambilLayer((int) $gudang->id, (int) $bahan->id, $tersediaSebelumExpired, today());
            $this->fail('Stok yang lotnya sudah kedaluwarsa seharusnya tidak bisa diambil.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('kedaluwarsa', $exception->getMessage());
        }
    }

    public function test_replenishment_suggestion_becomes_a_material_request_and_is_not_reopened_by_recalculation(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $bahan = Bahan::whereNotNull('kategori')->firstOrFail();

        $suggestion = \App\Models\SaranPengisianUlang::create([
            'gudang_id' => $gudang->id,
            'bahan_id' => $bahan->id,
            'calculated_at' => today(),
            'average_daily_usage' => 2,
            'lead_time_days' => 5,
            'available_quantity' => 3,
            'suggested_quantity' => 25,
            'priority' => 'CRITICAL',
            'status' => \App\Models\SaranPengisianUlang::OPEN,
        ]);

        $this->actingAs($warehouse)
            ->post(route('wms-control.replenishment.request', $suggestion))
            ->assertRedirect();

        $suggestion->refresh();
        $this->assertSame(\App\Models\SaranPengisianUlang::REQUESTED, $suggestion->status);
        $this->assertNotNull($suggestion->material_request_id);

        $materialRequest = \App\Models\MaterialRequest::findOrFail($suggestion->material_request_id);
        $this->assertSame(\App\Models\MaterialRequest::PENDING, $materialRequest->status);
        $this->assertSame($warehouse->id, $materialRequest->requested_by);

        $detail = $materialRequest->details()->firstOrFail();
        $this->assertSame((int) $bahan->id, (int) $detail->bahan_id);
        $this->assertEqualsWithDelta(25.0, (float) $detail->jumlah_minta, 0.000001);
        $this->assertSame((int) $gudang->id, (int) $detail->tipe_gudang);

        $this->actingAs($warehouse)
            ->post(route('wms-control.replenishment.request', $suggestion))
            ->assertSessionHasErrors();

        \App\Models\PengaturanBahanGudang::updateOrCreate(
            ['gudang_id' => $gudang->id, 'bahan_id' => $bahan->id],
            ['titik_pemesanan' => 100, 'stok_pengaman' => 50, 'stok_maksimum' => 200, 'aktif' => true],
        );
        app(\App\Services\ReplenishmentService::class)->calculate((int) $gudang->id);

        $this->assertSame(\App\Models\SaranPengisianUlang::REQUESTED, $suggestion->fresh()->status);

        $this->travel(1)->days();
        app(\App\Services\ReplenishmentService::class)->calculate((int) $gudang->id);

        $besok = \App\Models\SaranPengisianUlang::where('gudang_id', $gudang->id)->where('bahan_id', $bahan->id)
            ->whereDate('calculated_at', today())->firstOrFail();
        $this->assertSame(\App\Models\SaranPengisianUlang::REQUESTED, $besok->status);
        $this->assertSame((int) $materialRequest->id, (int) $besok->material_request_id);

        $this->actingAs($warehouse)
            ->post(route('wms-control.replenishment.request', $besok))
            ->assertSessionHasErrors();
        $this->assertSame(1, \App\Models\MaterialRequest::whereIn('id', \App\Models\SaranPengisianUlang::where('bahan_id', $bahan->id)->pluck('material_request_id')->filter())->count());
    }

    public function test_receiving_rejects_a_reused_lot_number_with_a_conflicting_expiry(): void
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $numbers = app(DocumentNumberService::class);
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $bahan = Bahan::whereNotNull('kategori')->firstOrFail();
        $supplier = Supplier::create(['nama' => 'Supplier Uji Konflik', 'alamat' => 'Jl. Konflik', 'telp' => '0800000094', 'pembayaran' => 'Transfer']);

        $poNumber = $numbers->financial('PO');
        $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'no_po' => $poNumber,
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 5000, 'jumlah' => 20]],
        ])->assertCreated();

        $kirim = fn (string $expiry) => $this->actingAs($warehouse)->postJson(route('penerimaan-barang.store'), [
            'id_lpb' => $numbers->external('LPB'),
            'tanggal' => today()->toDateString(),
            'no_po' => $poNumber,
            'no_sj' => 'SJ-KONFLIK',
            'details' => [[
                'id_bahan' => $bahan->id,
                'id_kategori' => $bahan->kategori,
                'jumlah_barang_diterima' => 5,
                'lot_number' => 'LOT-KONFLIK-1',
                'expires_at' => $expiry,
            ]],
        ]);

        $kirim(today()->addDays(60)->toDateString())->assertCreated();
        $kirim(today()->addDays(90)->toDateString())->assertStatus(422)->assertJsonValidationErrors(['details.0.expires_at']);
        $kirim(today()->addDays(60)->toDateString())->assertCreated();

        $this->assertSame(
            today()->addDays(60)->toDateString(),
            \App\Models\LotPersediaan::where('lot_number', 'LOT-KONFLIK-1')->firstOrFail()->expires_at->toDateString()
        );
    }

    public function test_lot_controlled_material_cannot_be_received_without_lot_and_expiry(): void
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $numbers = app(DocumentNumberService::class);
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $bahan = Bahan::whereNotNull('kategori')->firstOrFail();
        $bahan->update(['wajib_lot' => true, 'wajib_expiry' => true]);
        $supplier = Supplier::create(['nama' => 'Supplier Uji Lot', 'alamat' => 'Jl. Lot', 'telp' => '0800000096', 'pembayaran' => 'Transfer']);

        $poNumber = $numbers->financial('PO');
        $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'no_po' => $poNumber,
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 5000, 'jumlah' => 10]],
        ])->assertCreated();

        $payload = fn (array $detail) => [
            'id_lpb' => $numbers->external('LPB'),
            'tanggal' => today()->toDateString(),
            'no_po' => $poNumber,
            'no_sj' => 'SJ-LOT-001',
            'details' => [array_merge([
                'id_bahan' => $bahan->id,
                'id_kategori' => $bahan->kategori,
                'jumlah_barang_diterima' => 10,
            ], $detail)],
        ];

        $this->actingAs($warehouse)->postJson(route('penerimaan-barang.store'), $payload([]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['details.0.lot_number', 'details.0.expires_at']);

        $this->actingAs($warehouse)->postJson(route('penerimaan-barang.store'), $payload(['lot_number' => 'LOT-WAJIB-1']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['details.0.expires_at']);

        $this->actingAs($warehouse)->postJson(route('penerimaan-barang.store'), $payload([
            'lot_number' => 'LOT-WAJIB-1',
            'expires_at' => today()->addDays(30)->toDateString(),
        ]))->assertCreated();
    }

    private function buatLpbDuaBaris(User $warehouse, string $tanda): PenerimaanBarang
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $numbers = app(DocumentNumberService::class);
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $bahans = Bahan::whereNotNull('kategori')->take(2)->get();
        $supplier = Supplier::create(['nama' => "Supplier {$tanda}", 'alamat' => 'Jl. Putaway', 'telp' => '08000000' . random_int(10, 99), 'pembayaran' => 'Transfer']);

        $poNumber = $numbers->financial('PO');
        $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'no_po' => $poNumber,
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => $bahans->map(fn ($b) => ['bahan_id' => $b->id, 'harga' => 5000, 'jumlah' => 4])->all(),
        ])->assertCreated();

        $noLpb = $numbers->external('LPB');
        $this->actingAs($warehouse)->postJson(route('penerimaan-barang.store'), [
            'id_lpb' => $noLpb,
            'tanggal' => today()->toDateString(),
            'no_po' => $poNumber,
            'no_sj' => "SJ-{$tanda}",
            'details' => $bahans->map(fn ($b) => [
                'id_bahan' => $b->id,
                'id_kategori' => $b->kategori,
                'jumlah_barang_diterima' => 4,
            ])->all(),
        ])->assertCreated();

        return PenerimaanBarang::with('details')->where('id_lpb', $noLpb)->firstOrFail();
    }

    public function test_two_putaway_lines_sharing_one_bin_are_counted_once_against_capacity(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $lpb = $this->buatLpbDuaBaris($warehouse, 'PAS');
        $pas = \App\Models\LokasiGudang::create(['gudang_id' => $lpb->gudang_id, 'code' => 'BIN-PAS', 'name' => 'Bin Pas', 'type' => 'RACK', 'capacity' => 8, 'active' => true]);

        $this->actingAs($warehouse)
            ->post(route('wms-control.penerimaan-barang.putaway', $lpb), [
                'locations' => $lpb->details->mapWithKeys(fn ($d) => [$d->id => $pas->id])->all(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('PUTAWAY', $lpb->fresh()->receiving_status);
        $this->assertEqualsWithDelta(8.0, (float) LayerPersediaan::where('warehouse_location_id', $pas->id)->sum('remaining_quantity'), 0.000001);
    }

    public function test_putaway_places_each_line_in_its_own_bin_and_respects_capacity(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $lpb = $this->buatLpbDuaBaris($warehouse, 'PUTAWAY');
        $details = $lpb->details->values();
        $this->assertCount(2, $details);

        $sempit = \App\Models\LokasiGudang::create(['gudang_id' => $lpb->gudang_id, 'code' => 'BIN-SEMPIT', 'name' => 'Bin Sempit', 'type' => 'RACK', 'capacity' => 0.5, 'active' => true]);
        $lega = \App\Models\LokasiGudang::create(['gudang_id' => $lpb->gudang_id, 'code' => 'BIN-LEGA', 'name' => 'Bin Lega', 'type' => 'RACK', 'active' => true]);
        $lain = \App\Models\LokasiGudang::create(['gudang_id' => $lpb->gudang_id, 'code' => 'BIN-LAIN', 'name' => 'Bin Lain', 'type' => 'RACK', 'active' => true]);

        $semua = $details->mapWithKeys(fn ($d) => [$d->id => $lega->id])->all();

        $this->actingAs($warehouse)
            ->post(route('wms-control.penerimaan-barang.putaway', $lpb), ['locations' => [$details[0]->id => $sempit->id] + $semua])
            ->assertSessionHasErrors();
        $this->assertNotSame('PUTAWAY', $lpb->fresh()->receiving_status);

        $terpisah = $semua;
        $terpisah[$details[0]->id] = $lain->id;
        $this->actingAs($warehouse)
            ->post(route('wms-control.penerimaan-barang.putaway', $lpb), ['locations' => $terpisah])
            ->assertSessionHasNoErrors();

        $this->assertSame('PUTAWAY', $lpb->fresh()->receiving_status);
        $this->assertSame($lain->id, (int) LayerPersediaan::where('source_type', 'LPB_DETAIL')->where('source_id', $details[0]->id)->value('warehouse_location_id'));
        $this->assertSame($lega->id, (int) LayerPersediaan::where('source_type', 'LPB_DETAIL')->where('source_id', $details[1]->id)->value('warehouse_location_id'));
    }

    public function test_completed_picking_order_becomes_a_draft_npk_exactly_once(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $execution = app(WarehouseExecutionService::class);
        $layer = LayerPersediaan::where('remaining_quantity', '>', 0)->where('stock_status', 'AVAILABLE')->firstOrFail();
        $this->actingAs($warehouse);

        $reservation = $execution->reserve((int) $layer->gudang_id, (int) $layer->bahan_id, 1.0);
        $pick = $execution->createPick($reservation);
        $execution->completePick($pick);

        $this->actingAs($warehouse)
            ->post(route('wms-control.picking-orders.issue', $pick))
            ->assertSessionHasNoErrors();

        $npk = PemakaianBarang::where('picking_order_id', $pick->id)->firstOrFail();
        $this->assertSame(PemakaianBarang::DRAFT, $npk->status);
        $this->assertSame((int) $reservation->id, (int) $npk->inventory_reservation_id);
        $this->assertSame((int) $layer->bahan_id, (int) $npk->id_barang);
        $this->assertEqualsWithDelta(1.0, (float) $npk->jumlah_stok, 0.000001);

        $this->actingAs($warehouse)
            ->post(route('wms-control.picking-orders.issue', $pick))
            ->assertSessionHasErrors();
        $this->assertSame(1, PemakaianBarang::where('picking_order_id', $pick->id)->count());
    }

    public function test_serial_registration_cannot_exceed_lot_quantity_and_status_is_updatable(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $layer = LayerPersediaan::where('remaining_quantity', '>', 0)->firstOrFail();
        $lot = \App\Models\LotPersediaan::create(['bahan_id' => $layer->bahan_id, 'lot_number' => 'LOT-SERIAL-1', 'quality_status' => 'RELEASED']);
        $layer->update(['inventory_lot_id' => $lot->id, 'initial_quantity' => 2]);

        foreach (['SN-1', 'SN-2'] as $serial) {
            $this->actingAs($warehouse)
                ->post(route('wms-control.serials.store'), ['inventory_lot_id' => $lot->id, 'serial_number' => $serial])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($warehouse)
            ->post(route('wms-control.serials.store'), ['inventory_lot_id' => $lot->id, 'serial_number' => 'SN-3'])
            ->assertSessionHasErrors('serial_number');
        $this->assertSame(2, $lot->serials()->count());

        $serial = \App\Models\SerialPersediaan::where('serial_number', 'SN-1')->firstOrFail();
        $this->assertSame('AVAILABLE', $serial->status);

        $this->actingAs($warehouse)
            ->patch(route('wms-control.serials.status', $serial), ['status' => 'ISSUED'])
            ->assertSessionHasNoErrors();
        $this->assertSame('ISSUED', $serial->fresh()->status);
    }

    public function test_declining_balance_depreciation_charges_double_rate_on_the_running_book_value(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $category = \App\Models\KategoriAset::where('is_active', true)->firstOrFail();

        $asset = Aset::create([
            'nomor_aset' => 'AUTO-DEP-SALDO-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Saldo Menurun',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 2400000,
            'residual_value' => 0,
            'useful_life_months' => 48,
            'depreciation_method' => Aset::DECLINING_BALANCE,
            'accumulated_depreciation' => 0,
            'book_value' => 2400000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Saldo Menurun Bulan 1',
        ])->assertOk();

        $asset->refresh();
        $this->assertEqualsWithDelta(100000.0, (float) $asset->accumulated_depreciation, 0.01);
        $this->assertEqualsWithDelta(2300000.0, (float) $asset->book_value, 0.01);

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Saldo Menurun Bulan 2',
        ])->assertOk();

        $asset->refresh();
        $this->assertEqualsWithDelta(95833.33, (float) $asset->depreciations()->latest('id')->value('amount'), 0.01);
        $this->assertEqualsWithDelta(2204166.67, (float) $asset->book_value, 0.01);
    }

    public function test_reconciliation_flags_a_reservation_balance_that_no_longer_matches_live_reservations(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $this->actingAs($warehouse);
        $layer = LayerPersediaan::where('remaining_quantity', '>', 1)->where('stock_status', 'AVAILABLE')->firstOrFail();
        $service = app(RekonsiliasiGudangService::class);

        $this->assertSame(0, $service->summary()['reservation_exceptions'], 'Data awal harus bersih.');

        $reservasi = app(WarehouseExecutionService::class)->reserve((int) $layer->gudang_id, (int) $layer->bahan_id, 1.0);
        $this->assertSame(0, $service->summary()['reservation_exceptions'], 'Reservasi normal tidak boleh jadi temuan.');

        $reservasi->update(['status' => 'RELEASED']);

        $ringkasan = $service->summary();
        $this->assertSame(1, $ringkasan['reservation_exceptions'], 'Reservasi yatim harus terdeteksi.');
        $baris = $ringkasan['rows']->firstWhere(fn ($row) => (int) $row->gudang_id === (int) $layer->gudang_id && (int) $row->bahan_id === (int) $layer->bahan_id);
        $this->assertEqualsWithDelta(1.0, (float) $baris->selisih_reservasi, 0.000001);
    }

    public function test_transfer_receipt_places_stock_into_a_bin_and_respects_capacity(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $this->actingAs($warehouse);
        $service = app(TransferGudangService::class);

        $layer = LayerPersediaan::where('remaining_quantity', '>=', 3)->where('stock_status', 'AVAILABLE')->firstOrFail();
        $asal = Gudang::findOrFail($layer->gudang_id);
        $tujuan = Gudang::where('jenis', Gudang::NORMAL)->where('aktif', true)->where('id', '!=', $asal->id)->firstOrFail();

        $transfer = TransferGudang::create([
            'nomor_transfer' => 'TRF-BIN-' . random_int(1000, 9999),
            'tanggal' => today(),
            'gudang_asal_id' => $asal->id,
            'gudang_tujuan_id' => $tujuan->id,
            'status' => TransferGudang::DIAJUKAN,
            'idempotency_key' => 'bin-' . random_int(100000, 999999),
            'dibuat_oleh' => $warehouse->id,
        ]);
        $transfer->details()->create(['bahan_id' => $layer->bahan_id, 'jumlah' => 2]);

        $service->konfirmasi($transfer);

        $sempit = \App\Models\LokasiGudang::create(['gudang_id' => $tujuan->id, 'code' => 'TRF-SEMPIT', 'name' => 'Bin Sempit', 'type' => 'RACK', 'capacity' => 1, 'active' => true]);
        $lega = \App\Models\LokasiGudang::create(['gudang_id' => $tujuan->id, 'code' => 'TRF-LEGA', 'name' => 'Bin Lega', 'type' => 'RACK', 'capacity' => 100, 'active' => true]);
        $detail = $transfer->fresh('details')->details->first();

        try {
            $service->terima($transfer->fresh(), [], null, [$detail->id => $sempit->id]);
            $this->fail('Kapasitas bin tujuan seharusnya menolak penerimaan transfer.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Kapasitas lokasi', $exception->getMessage());
        }

        $service->terima($transfer->fresh(), [], null, [$detail->id => $lega->id]);

        $this->assertSame(TransferGudang::DITERIMA, $transfer->fresh()->status);
        $this->assertEqualsWithDelta(
            2.0,
            (float) LayerPersediaan::where('warehouse_location_id', $lega->id)->sum('remaining_quantity'),
            0.000001,
            'Barang hasil transfer harus mendarat di bin yang dipilih.'
        );
    }

    public function test_invoice_requires_accounting_manager_approval_before_posting_or_payment(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $manager = User::factory()->create(['type' => User::ROLE_ACCOUNTING_MANAGER]);
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $lpb = PenerimaanBarang::where('document_type', 'GOODS')
            ->whereDoesntHave('invoiceReceipts')
            ->whereNull('no_invoice')
            ->where('status', PenerimaanBarang::POSTED)
            ->with('pembelian')
            ->firstOrFail();

        $storeResponse = $this->actingAs($accounting)->postJson(route('faktur-pembelian.store'), [
            'no_invoice' => 'MAKER-CHECKER-TEST-1',
            'lpb_ids' => [$lpb->id],
            'kode_supplier' => $lpb->pembelian->supplier_id,
            'tanggal' => today()->toDateString(),
            'is_ppn' => 0,
        ])->assertCreated();
        $invoice = FakturPembelian::findOrFail($storeResponse->json('data.id'));

        $this->assertSame(FakturPembelian::PENDING_APPROVAL, $invoice->status);
        $this->assertDatabaseMissing('wms_jurnal', ['sumber_transaksi' => 'INVOICE_SUPPLIER', 'reff_id' => $invoice->id]);

        $this->actingAs($accounting)
            ->get(route('reconciliation.index'))
            ->assertOk()
            ->assertDontSee('TIDAK VALID');

        $kasUtama = BaganAkun::where('kode_akun', '1101')->firstOrFail();
        $numbers = app(DocumentNumberService::class);
        $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
            'payment_number' => $numbers->financial('PY'),
            'invoice_lpb_id' => $invoice->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kasUtama->id,
            'jumlah_pembayaran' => 100,
        ])->assertStatus(403);

        $this->actingAs($accounting)->postJson(route('faktur-pembelian.approve', $invoice->id))->assertStatus(403);

        $this->actingAs($manager)->postJson(route('faktur-pembelian.approve', $invoice->id))->assertOk();

        $invoice->refresh();
        $this->assertSame(FakturPembelian::UNPAID, $invoice->status);
        $this->assertSame($manager->id, $invoice->approved_by);
        $this->assertNotNull($invoice->approved_at);
        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'INVOICE_SUPPLIER')->where('reff_id', $invoice->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);

        $this->actingAs($manager)->postJson(route('faktur-pembelian.approve', $invoice->id))->assertStatus(403);
    }

    public function test_invoice_posts_ppn_impor_as_a_separate_creditable_line(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($accounting);
        $lpb = PenerimaanBarang::where('document_type', 'GOODS')
            ->whereDoesntHave('invoiceReceipts')
            ->whereNull('no_invoice')
            ->where('status', PenerimaanBarang::POSTED)
            ->with(['details', 'pembelian'])
            ->firstOrFail();
        $subTotal = $lpb->details->sum(fn ($d) => (float) $d->jumlah_barang_diterima * (float) $d->harga);

        $invoice = FakturPembelian::create([
            'no_invoice' => 'PPNIMPOR-TEST-1',
            'kode_supplier' => $lpb->pembelian->supplier_id,
            'tanggal' => today(),
            'sub_total' => $subTotal,
            'ppn_impor' => 150000,
            'mata_uang_asing' => 'USD',
            'kurs' => 15800,
            'nilai_asing' => round($subTotal / 15800, 2),
            'grand_total' => $subTotal + 150000,
            'sisa_tagihan' => $subTotal + 150000,
            'status' => FakturPembelian::UNPAID,
        ]);
        $invoice->receipts()->create(['lpb_id' => $lpb->id, 'amount' => $subTotal]);
        $lpb->update(['no_invoice' => $invoice->no_invoice]);

        app(WmsAccountingService::class)->postInvoice($invoice);

        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'INVOICE_SUPPLIER')->where('reff_id', $invoice->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $ppnImporAccount = BaganAkun::where('kode_akun', '1104')->firstOrFail();
        $this->assertTrue($jurnal->details->contains(fn ($line) => (int) $line->coa_id === $ppnImporAccount->id && abs((float) $line->debit - 150000.0) < 0.01));
    }

    public function test_generic_and_financial_statement_excel_exports_download_successfully(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);

        $this->actingAs($finance)->get(route('request.report.excel'))->assertOk();
        $this->actingAs($accounting)->get(route('aset.report.excel'))->assertOk();
        $this->actingAs($accounting)->get(route('financial-statements.neraca-saldo.excel'))->assertOk();
        $this->actingAs($warehouse)->get(route('stock-opname.report.excel'))->assertOk();
    }

    public function test_generic_table_export_actually_generates_a_valid_spreadsheet(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);

        $response = $this->actingAs($finance)->get(route('faktur-pembelian.report.excel'));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
    }

    public function test_executive_dashboard_renders_for_accounting_and_manager_but_not_other_roles(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $manager = User::factory()->create(['type' => User::ROLE_ACCOUNTING_MANAGER]);
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);

        $this->actingAs($accounting)->get(route('executive-dashboard.index'))
            ->assertOk()
            ->assertSee('Dashboard Eksekutif')
            ->assertSee('Tren Nilai Persediaan')
            ->assertSee('Aging Hutang Supplier')
            ->assertSee('Top 5 Supplier')
            ->assertSee('Biaya per Kategori Bahan');

        $this->actingAs($manager)->get(route('executive-dashboard.index'))->assertOk();

        $this->actingAs($purchasing)->get(route('executive-dashboard.index'))->assertForbidden();
    }

    public function test_purchase_return_after_partially_paid_invoice_reduces_invoice_balance(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $lpb = PenerimaanBarang::where('document_type', 'GOODS')
            ->whereNotNull('no_invoice')
            ->where('status', PenerimaanBarang::POSTED)
            ->get()
            ->first(fn ($candidate) => $candidate->details->contains(
                fn ($d) => (float) (LayerPersediaan::where('source_type', 'LPB_DETAIL')->where('source_id', $d->id)->value('remaining_quantity') ?? 0) > 0
            ));
        $this->assertNotNull($lpb, 'Fixture LPB dengan invoice dan layer tersisa tidak ditemukan.');
        $detail = $lpb->details->first(fn ($d) => (float) (LayerPersediaan::where('source_type', 'LPB_DETAIL')->where('source_id', $d->id)->value('remaining_quantity') ?? 0) > 0);
        $layer = LayerPersediaan::where('source_type', 'LPB_DETAIL')->where('source_id', $detail->id)->firstOrFail();
        $invoice = FakturPembelian::where('no_invoice', $lpb->no_invoice)->firstOrFail();

        $returQty = min(1.0, (float) $layer->remaining_quantity);
        $this->assertGreaterThan(0, $returQty);
        $returValue = round($returQty * (float) $detail->harga, 2);
        $sisaBefore = (float) $invoice->sisa_tagihan;
        $grandBefore = (float) $invoice->grand_total;
        $this->assertGreaterThan($returValue, $sisaBefore, 'Fixture invoice tidak memiliki sisa tagihan yang cukup untuk skenario ini.');

        $numbers = app(DocumentNumberService::class);
        $response = $this->actingAs($warehouse)->postJson(route('retur-pembelian.store'), [
            'no_retur' => $numbers->external('RTV'),
            'lpb_id' => $lpb->id,
            'tanggal' => today()->toDateString(),
            'alasan' => 'Retur setelah invoice terbit (integration test).',
            'details' => [['lpb_detail_id' => $detail->id, 'jumlah_retur' => $returQty]],
        ])->assertCreated();
        $retur = ReturPembelian::findOrFail($response->json('data.id'));

        $invoice->refresh();
        $this->assertEqualsWithDelta($grandBefore - $returValue, (float) $invoice->grand_total, 0.01);
        $this->assertEqualsWithDelta($sisaBefore - $returValue, (float) $invoice->sisa_tagihan, 0.01);
        $this->assertEqualsWithDelta($returValue, (float) $retur->hutang_reduction, 0.01);
        $this->assertNull($retur->advance_payment_id);

        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'RETUR_PEMBELIAN')->where('reff_id', $retur->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $hutangAccount = BaganAkun::where('kode_akun', '2101')->firstOrFail();
        $this->assertTrue($jurnal->details->contains(fn ($line) => (int) $line->coa_id === $hutangAccount->id && abs((float) $line->debit - $returValue) < 0.01));
    }

    public function test_purchase_return_after_fully_paid_invoice_creates_supplier_advance(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $accounting = app(WmsAccountingService::class);
        $numbers = app(DocumentNumberService::class);
        $category = KategoriBahan::where('katnama', 'Bahan Baku Paper')->firstOrFail();
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $supplier = Supplier::create(['nama' => 'Supplier Retur Lunas', 'alamat' => 'Jl. Retur', 'telp' => '0800000099', 'pembayaran' => 'Transfer']);

        $material = Bahan::create([
            'nama' => 'Bahan Uji Retur Lunas',
            'kategori' => $category->id,
            'satuan' => 'KG',
            'stok_onhand' => 0,
            'stok_onpurchase' => 0,
            'planning' => 0,
            'stokawal' => 0,
            'pengambilan_stokawal' => 0,
            'tipe_gudang' => $gudang->id,
            'tipe_barang' => $category->id,
        ]);

        $po = PesananPembelian::create([
            'no_po' => $numbers->financial('PO', today()->subDays(10)),
            'tanggal' => today()->subDays(10),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'no_order' => '-',
            'untuk_perhatian' => 'Uji Retur Lunas',
            'term' => '30 hari',
            'notes' => 'PO uji retur setelah invoice lunas',
            'total_exclude' => 100000,
            'total_include' => 100000,
            'grand_total' => 100000,
            'status' => PesananPembelian::OPEN,
            'jenis' => 0,
            'kunci' => 1,
        ]);

        $lpbNumber = $numbers->external('LPB', today()->subDays(5));
        $lpb = PenerimaanBarang::create([
            'id_lpb' => $lpbNumber,
            'tanggal' => today()->subDays(5),
            'no_po' => $po->no_po,
            'gudang_id' => $gudang->id,
            'no_sj' => 'SJ-' . $lpbNumber,
            'id_user' => 5,
            'flag' => 0,
            'status' => PenerimaanBarang::POSTED,
            'jenis_lpb' => 1,
            'kunci' => 1,
        ]);
        $detail = PenerimaanBarangDetail::create([
            'id_lpb' => $lpb->id_lpb,
            'id_bahan' => $material->id,
            'id_kategori' => $category->id,
            'jumlah_barang_diterima' => 10,
            'lot_number' => 'LOT-RETUR-LUNAS',
            'harga' => 10000,
            'nilai_awal' => 100000,
            'jumlah_dipakai' => 0,
            'jumlah_tersisa' => 10,
            'flag_dipakai' => 1,
        ]);
        LayerPersediaan::create([
            'bahan_id' => $material->id,
            'gudang_id' => $gudang->id,
            'source_type' => 'LPB_DETAIL',
            'source_id' => $detail->id,
            'transaction_date' => today()->subDays(5),
            'initial_quantity' => 10,
            'remaining_quantity' => 10,
            'unit_cost' => 10000,
        ]);
        app(StokGudangService::class)->masuk($gudang->id, $material->id, 10, 10000, 'LPB', 'LPB', $lpb->id, 'Fixture uji retur lunas');
        $accounting->postLpb($lpb);

        $invoice = FakturPembelian::create([
            'no_invoice' => 'RETUR-LUNAS-TEST-1',
            'kode_supplier' => $supplier->id,
            'tanggal' => today()->subDays(3),
            'sub_total' => 100000,
            'grand_total' => 100000,
            'total_pembayaran' => 100000,
            'sisa_tagihan' => 0,
            'status' => FakturPembelian::PAID,
        ]);
        $invoice->receipts()->create(['lpb_id' => $lpb->id, 'amount' => 100000]);
        $lpb->update(['no_invoice' => $invoice->no_invoice]);
        $accounting->postInvoice($invoice);

        $response = $this->actingAs($warehouse)->postJson(route('retur-pembelian.store'), [
            'no_retur' => $numbers->external('RTV'),
            'lpb_id' => $lpb->id,
            'tanggal' => today()->toDateString(),
            'alasan' => 'Retur setelah invoice lunas (integration test).',
            'details' => [['lpb_detail_id' => $detail->id, 'jumlah_retur' => 2]],
        ])->assertCreated();
        $retur = ReturPembelian::findOrFail($response->json('data.id'));

        $this->assertEqualsWithDelta(0.0, (float) $retur->hutang_reduction, 0.01);
        $this->assertNotNull($retur->advance_payment_id);

        $advance = PembayaranFaktur::findOrFail($retur->advance_payment_id);
        $this->assertSame('UANG_MUKA_SUPPLIER', $advance->jenis_selisih);
        $this->assertEqualsWithDelta(20000.0, (float) $advance->kelebihan_pembayaran, 0.01);
        $this->assertNull($advance->coa_kas_bank_id);

        $jurnal = \App\Models\Jurnal::with('details')->where('sumber_transaksi', 'RETUR_PEMBELIAN')->where('reff_id', $retur->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
        $uangMukaAccount = BaganAkun::where('kode_akun', '1401')->firstOrFail();
        $this->assertTrue($jurnal->details->contains(fn ($line) => (int) $line->coa_id === $uangMukaAccount->id && abs((float) $line->debit - 20000.0) < 0.01));

        $availableAdvances = $this->actingAs(User::factory()->create(['type' => User::ROLE_FINANCE]))
            ->getJson(route('pembayaran-faktur.available-advances', $supplier->id))
            ->assertOk()
            ->json('data');
        $this->assertTrue(collect($availableAdvances)->contains(fn ($row) => (int) $row['id'] === $advance->id));

        $this->actingAs(User::factory()->create(['type' => User::ROLE_FINANCE]))
            ->deleteJson(route('pembayaran-faktur.destroy', $advance->id))
            ->assertStatus(422);

        app(InventoryReversalService::class)->reverseReturPembelian($retur, 'Koreksi integration test retur pasca invoice lunas');

        $this->assertSame(ReturPembelian::REVERSED, $retur->fresh()->status);
        $this->assertEqualsWithDelta(100000.0, (float) $invoice->fresh()->grand_total, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->fresh()->sisa_tagihan, 0.01);
        $this->assertSame(PembayaranFaktur::VOID, $advance->fresh()->status);
        $this->assertEqualsWithDelta(10.0, (float) LayerPersediaan::where('source_type', 'LPB_DETAIL')->where('source_id', $detail->id)->value('remaining_quantity'), 0.000001);
    }

    public function test_purchase_return_reduces_stock_and_grni_then_can_be_reversed(): void
    {
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $lpb = PenerimaanBarang::where('document_type', 'GOODS')->whereDoesntHave('invoiceReceipts')->where('status', PenerimaanBarang::POSTED)->firstOrFail();
        $detail = $lpb->details()->where('jumlah_tersisa', '>', 0)->firstOrFail();
        $layer = LayerPersediaan::where('source_type', 'LPB_DETAIL')->where('source_id', $detail->id)->firstOrFail();
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
        $this->assertDatabaseHas('wms_pembalikan_dokumen', ['document_type' => 'RETUR_PEMBELIAN', 'document_id' => $retur->id]);
    }
}
