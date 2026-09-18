<?php

namespace Tests\Feature;

use App\Models\BaganAkun;
use App\Models\Bahan;
use App\Models\BiayaTambahan;
use App\Models\Gudang;
use App\Models\LayerPersediaan;
use App\Models\PemakaianBarang;
use App\Models\PenerimaanBarang;
use App\Models\StokGudang;
use App\Models\Supplier;
use App\Models\TransferGudang;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\InventoryReversalService;
use App\Services\LandedCostService;
use App\Services\RekonsiliasiGudangService;
use App\Services\StokGudangService;
use App\Services\TransferGudangService;
use App\Services\WarehouseExecutionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WmsControlFrameworkTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_receiving_captures_lot_expiry_and_expired_stock_is_rejected_with_a_clear_message(): void
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $warehouse = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $numbers = app(DocumentNumberService::class);
        $gudang = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
        $bahan = Bahan::whereNotNull('kategori')->firstOrFail();
        $supplier = Supplier::create(['nama' => 'Supplier Uji Expiry', 'alamat' => 'Jl. Expiry', 'telp' => '0800000097', 'pembayaran' => 'Transfer']);

        $poNumber = $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 5000, 'jumlah' => 10]],
        ])->assertCreated()->json('data.no_po');

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

        $suggestion = \App\Models\SaranPengisianUlang::updateOrCreate([
            'gudang_id' => $gudang->id,
            'bahan_id' => $bahan->id,
            'calculated_at' => today(),
        ], [
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

        $poNumber = $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 5000, 'jumlah' => 20]],
        ])->assertCreated()->json('data.no_po');

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

        $poNumber = $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => [['bahan_id' => $bahan->id, 'harga' => 5000, 'jumlah' => 10]],
        ])->assertCreated()->json('data.no_po');

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

        $poNumber = $this->actingAs($purchasing)->postJson(route('pembelian.store'), [
            'tanggal' => today()->toDateString(),
            'supplier_id' => $supplier->id,
            'gudang_id' => $gudang->id,
            'is_ppn' => 0,
            'details' => $bahans->map(fn ($b) => ['bahan_id' => $b->id, 'harga' => 5000, 'jumlah' => 4])->all(),
        ])->assertCreated()->json('data.no_po');

        $noLpb = $this->actingAs($warehouse)->postJson(route('penerimaan-barang.store'), [
            'tanggal' => today()->toDateString(),
            'no_po' => $poNumber,
            'no_sj' => "SJ-{$tanda}",
            'details' => $bahans->map(fn ($b) => [
                'id_bahan' => $b->id,
                'id_kategori' => $b->kategori,
                'jumlah_barang_diterima' => 4,
            ])->all(),
        ])->assertCreated()->json('data.id_lpb');

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

}
