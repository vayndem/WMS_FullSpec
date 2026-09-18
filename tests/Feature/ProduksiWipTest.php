<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\Bahan;
use App\Models\DataPesanan;
use App\Models\DataPesananBiaya;
use App\Models\Gudang;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\Pelanggan;
use App\Models\PemakaianBarang;
use App\Models\PesananPenjualan;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\AccountingReconciliationService;
use App\Services\DataPesananService;
use App\Services\InventoryReversalService;
use App\Services\PenjualanService;
use App\Services\StokGudangService;
use App\Services\WmsAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ProduksiWipTest extends TestCase
{
    use DatabaseTransactions;

    private function saldoWipGl(): float
    {
        $akun = AccountingSetting::accountId(AccountingSetting::BARANG_DALAM_PROSES);

        return round((float) DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->where('j.status', 'POSTED')
            ->where('jd.coa_id', $akun)
            ->sum(DB::raw('jd.debit - jd.kredit')), 2);
    }

    private function bahanBaku(float $minimal = 5): StokGudang
    {
        return StokGudang::whereRaw('stok_tersedia - stok_direservasi >= ?', [$minimal])
            ->whereHas('gudang', fn ($query) => $query->where('jenis', Gudang::NORMAL))
            ->firstOrFail();
    }

    private function pesananPenjualan(User $user, StokGudang $stok, float $jumlah = 10): PesananPenjualan
    {
        $pelanggan = Pelanggan::create([
            'kode' => 'CUST-WIP-' . random_int(1000, 9999),
            'nama' => 'PT Pemesan Produksi',
            'termin_hari' => 30,
            'is_active' => true,
        ]);

        $produk = Bahan::whereKeyNot($stok->bahan_id)->firstOrFail();

        return app(PenjualanService::class)->buatPesanan([
            'tanggal' => today()->toDateString(),
            'pelanggan_id' => $pelanggan->id,
            'gudang_id' => $stok->gudang_id,
            'is_ppn' => true,
            'tarif_ppn' => 11,
            'details' => [['bahan_id' => $produk->id, 'jumlah' => $jumlah, 'harga_satuan' => 90000]],
        ], $user);
    }

    private function npkKeWorkOrder(DataPesanan $wo, StokGudang $stok, User $user, float $jumlah = 4): PemakaianBarang
    {
        $bahan = Bahan::findOrFail($stok->bahan_id);

        $npk = PemakaianBarang::create([
            'kode' => 'NPK-WIP-' . random_int(10000, 99999),
            'kode_datapesanan' => $wo->nomor,
            'data_pesanan_id' => $wo->id,
            'tanggal' => today()->toDateString(),
            'id_barang' => $bahan->id,
            'id_gudang_asal' => $stok->gudang_id,
            'jumlah' => $jumlah,
            'jumlah_stok' => $bahan->toStockQuantity($jumlah),
            'satuan_transaksi' => $bahan->satuan,
            'id_user' => $user->id,
            'status' => PemakaianBarang::POSTED,
        ]);

        app(WmsAccountingService::class)->consumeStock($npk);
        app(StokGudangService::class)->keluar(
            (int) $stok->gudang_id,
            (int) $bahan->id,
            (float) $npk->jumlah_stok,
            (float) $npk->fresh()->harga_satuan,
            'PENGELUARAN',
            'NPK',
            $npk->id,
            $npk->kode
        );
        app(WmsAccountingService::class)->postNpk($npk->fresh());

        return $npk->fresh();
    }

    private function workOrderDirilis(User $user, StokGudang $stok, float $jumlah = 10): DataPesanan
    {
        $so = $this->pesananPenjualan($user, $stok, $jumlah);
        $service = app(DataPesananService::class);

        $wo = $service->buat($so->details->first(), [
            'tanggal' => today()->toDateString(),
            'gudang_id' => $stok->gudang_id,
            'jumlah_rencana' => $jumlah,
        ], $user);

        return $service->rilis($wo);
    }

    public function test_a_work_order_is_always_born_from_a_sales_order_line(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $so = $this->pesananPenjualan($user, $stok);
        $detail = $so->details->first();

        $wo = app(DataPesananService::class)->buat($detail, [
            'tanggal' => today()->toDateString(),
            'gudang_id' => $stok->gudang_id,
            'jumlah_rencana' => 10,
        ], $user);

        $this->assertSame((int) $so->id, (int) $wo->pesanan_penjualan_id);
        $this->assertSame((int) $detail->id, (int) $wo->pesanan_penjualan_detail_id);
        $this->assertSame(
            (int) $detail->bahan_id,
            (int) $wo->bahan_hasil_id,
            'Produk work order harus mengikuti baris pesanan, bukan diketik ulang.'
        );
        $this->assertSame(DataPesanan::DRAFT, $wo->status);
    }

    public function test_material_issued_to_a_work_order_lands_in_work_in_process_not_expense(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);

        $wipSebelum = $this->saldoWipGl();
        $npk = $this->npkKeWorkOrder($wo, $stok, $user);

        $nilai = round((float) $npk->total_nilai, 2);
        $this->assertGreaterThan(0, $nilai);
        $this->assertEqualsWithDelta($wipSebelum + $nilai, $this->saldoWipGl(), 0.01);

        $jurnal = Jurnal::where('sumber_transaksi', 'NPK')->where('reff_id', $npk->id)->firstOrFail();
        $wipAkun = AccountingSetting::accountId(AccountingSetting::BARANG_DALAM_PROSES);
        $baris = JurnalDetail::where('jurnal_id', $jurnal->id)->get();

        $this->assertTrue(
            $baris->contains(fn ($d) => (int) $d->coa_id === $wipAkun && (float) $d->debit > 0),
            'NPK bertanda perintah kerja harus mendebit Barang Dalam Proses.'
        );
        $this->assertEqualsWithDelta($nilai, $wo->fresh()->totalBiaya(), 0.01);
    }

    public function test_material_issued_without_a_work_order_still_goes_straight_to_expense(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)->whereNull('data_pesanan_id')->firstOrFail();
        $jurnal = Jurnal::where('sumber_transaksi', 'NPK')->where('reff_id', $npk->id)->firstOrFail();
        $wipAkun = AccountingSetting::accountId(AccountingSetting::BARANG_DALAM_PROSES);

        $this->assertFalse(
            JurnalDetail::where('jurnal_id', $jurnal->id)->where('coa_id', $wipAkun)->exists(),
            'NPK tanpa perintah kerja tidak boleh berubah perilakunya.'
        );
    }

    public function test_completing_a_work_order_freezes_the_unit_cost_and_seals_it_against_new_charges(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);
        $npk = $this->npkKeWorkOrder($wo, $stok, $user);

        $service = app(DataPesananService::class);
        $wo = $service->selesaikan($wo->fresh(), 10, $user);

        $this->assertSame(DataPesanan::SELESAI, $wo->status);
        $this->assertEqualsWithDelta(
            round((float) $npk->total_nilai / 10, 4),
            (float) $wo->biaya_per_unit,
            0.0001
        );

        $this->expectException(RuntimeException::class);
        $service->catatBiaya($wo, DataPesananBiaya::NPK, 999999, 50000, today());
    }

    public function test_a_work_order_without_any_cost_cannot_be_completed(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $wo = $this->workOrderDirilis($user, $this->bahanBaku());

        $this->expectException(RuntimeException::class);
        app(DataPesananService::class)->selesaikan($wo, 10, $user);
    }

    public function test_shipping_releases_work_in_process_proportionally_and_closes_the_order(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);
        $this->npkKeWorkOrder($wo, $stok, $user);

        $service = app(DataPesananService::class);
        $wo = $service->selesaikan($wo->fresh(), 10, $user);
        $biaya = $wo->totalBiaya();

        $so = $wo->pesananPenjualan;
        $penjualan = app(PenjualanService::class);
        $wipSebelum = $this->saldoWipGl();

        $sj = $penjualan->buatSuratJalan($so->fresh('details'), [
            'tanggal' => today()->toDateString(),
            'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 6]],
        ], $user);

        $this->assertSame(
            (int) $wo->id,
            (int) $sj->details->first()->data_pesanan_id,
            'Baris surat jalan harus otomatis terkait ke perintah kerja yang sudah selesai.'
        );

        $sj = $penjualan->postingSuratJalan($sj, $user);

        $this->assertEqualsWithDelta(round($biaya * 0.6, 2), (float) $sj->total_hpp, 0.05);
        $this->assertEqualsWithDelta($wipSebelum - (float) $sj->total_hpp, $this->saldoWipGl(), 0.05);

        $wo = $wo->fresh();
        $this->assertSame(DataPesanan::SELESAI, $wo->status);
        $this->assertEqualsWithDelta(round($biaya * 0.4, 2), $wo->saldoWip(), 0.05);

        $sj2 = $penjualan->buatSuratJalan($so->fresh('details'), [
            'tanggal' => today()->toDateString(),
            'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 4]],
        ], $user);
        $penjualan->postingSuratJalan($sj2, $user);

        $wo = $wo->fresh();
        $this->assertSame(DataPesanan::DITUTUP, $wo->status);
        $this->assertEqualsWithDelta(0.0, $wo->saldoWip(), 0.01);
        $this->assertEqualsWithDelta($wipSebelum - $biaya, $this->saldoWipGl(), 0.05);
    }

    public function test_a_production_delivery_never_touches_inventory_layers(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);
        $this->npkKeWorkOrder($wo, $stok, $user);

        $service = app(DataPesananService::class);
        $wo = $service->selesaikan($wo->fresh(), 10, $user);
        $so = $wo->pesananPenjualan;

        $layerSebelum = DB::table('wms_layer_persediaan')->count();

        $penjualan = app(PenjualanService::class);
        $sj = $penjualan->buatSuratJalan($so->fresh('details'), [
            'tanggal' => today()->toDateString(),
            'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 10]],
        ], $user);
        $penjualan->postingSuratJalan($sj, $user);

        $this->assertSame(
            $layerSebelum,
            DB::table('wms_layer_persediaan')->count(),
            'Produksi make-to-order tidak boleh membuat layer barang jadi.'
        );
    }

    public function test_shipping_more_than_was_produced_is_refused(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok, 10);
        $this->npkKeWorkOrder($wo, $stok, $user);

        $service = app(DataPesananService::class);
        $wo = $service->selesaikan($wo->fresh(), 6, $user);

        $this->expectException(RuntimeException::class);
        $service->lepaskanUntukPengiriman($wo, 8);
    }

    public function test_reversing_an_npk_also_pulls_its_cost_back_out_of_work_in_process(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);
        $npk = $this->npkKeWorkOrder($wo, $stok, $user);

        $this->assertEqualsWithDelta((float) $npk->total_nilai, $wo->fresh()->totalBiaya(), 0.01);

        app(InventoryReversalService::class)->reverseNpk($npk, 'Koreksi pemakaian produksi.');

        $this->assertEqualsWithDelta(
            0.0,
            $wo->fresh()->totalBiaya(),
            0.01,
            'Pembalikan NPK harus ikut menarik biayanya keluar dari barang dalam proses.'
        );
        $this->assertSame(0, DataPesananBiaya::where('data_pesanan_id', $wo->id)->count());
    }

    public function test_an_npk_cannot_be_reversed_once_its_work_order_is_sealed(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);
        $npk = $this->npkKeWorkOrder($wo, $stok, $user);

        app(DataPesananService::class)->selesaikan($wo->fresh(), 10, $user);

        $this->expectException(RuntimeException::class);
        app(InventoryReversalService::class)->reverseNpk($npk, 'Koreksi setelah perintah kerja selesai.');
    }

    public function test_cancelling_a_work_order_with_cost_writes_that_cost_off_instead_of_refusing(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);

        $kosong = app(DataPesananService::class)->batalkan($wo);
        $this->assertSame(DataPesanan::DIBATALKAN, $kosong->status);

        $wo2 = $this->workOrderDirilis($user, $stok);
        $this->npkKeWorkOrder($wo2, $stok, $user);

        $wipSebelum = $this->saldoWipGl();
        $biaya = $wo2->fresh()->totalBiaya();
        $this->assertGreaterThan(0, $biaya);

        $dibatalkan = app(DataPesananService::class)->batalkan($wo2->fresh());

        $this->assertSame(DataPesanan::DIBATALKAN, $dibatalkan->status);
        $this->assertEqualsWithDelta($wipSebelum - $biaya, $this->saldoWipGl(), 0.01);
        $this->assertEqualsWithDelta(0, $dibatalkan->totalBiaya(), 0.01);
    }

    public function test_the_ledger_and_the_work_orders_agree_on_how_much_is_in_process(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($user);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($user, $stok);
        $this->npkKeWorkOrder($wo, $stok, $user);

        $cek = app(AccountingReconciliationService::class)->checks()->firstWhere('key', 'wip');

        $this->assertNotNull($cek, 'Rekonsiliasi harus punya pemeriksaan barang dalam proses.');
        $this->assertSame(0, $cek['invalid'], 'Saldo GL barang dalam proses harus sama dengan jumlah perintah kerja berjalan.');
        $this->assertEqualsWithDelta($cek['expected'], $cek['amount'], 0.01);
    }

    public function test_the_work_order_screens_render_and_are_closed_to_outsiders(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($admin);

        $stok = $this->bahanBaku();
        $wo = $this->workOrderDirilis($admin, $stok);

        $this->actingAs($admin)->get(route('data-pesanan.index'))->assertOk()->assertSee('Perintah Kerja');
        $this->actingAs($admin)->get(route('data-pesanan.create'))->assertOk();
        $this->actingAs($admin)->get(route('data-pesanan.show', $wo))->assertOk()->assertSee($wo->nomor);

        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $this->actingAs($finance)->get(route('data-pesanan.index'))->assertForbidden();
    }
}
