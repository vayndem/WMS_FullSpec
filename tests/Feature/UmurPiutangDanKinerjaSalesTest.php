<?php

namespace Tests\Feature;

use App\Models\FakturPenjualan;
use App\Models\Gudang;
use App\Models\Pelanggan;
use App\Models\PesananPenjualan;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\FakturPenjualanService;
use App\Services\KinerjaSalesService;
use App\Services\PenjualanService;
use App\Services\PiutangAgingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UmurPiutangDanKinerjaSalesTest extends TestCase
{
    use DatabaseTransactions;

    private function stok(float $minimal = 4): StokGudang
    {
        return StokGudang::whereRaw('stok_tersedia - stok_direservasi >= ?', [$minimal])
            ->whereHas('gudang', fn ($query) => $query->where('jenis', Gudang::NORMAL))
            ->firstOrFail();
    }

    private function pelanggan(): Pelanggan
    {
        return Pelanggan::create([
            'kode' => 'CUST-AGE-' . random_int(1000, 9999),
            'nama' => 'PT Uji Umur Piutang',
            'termin_hari' => 14,
            'is_active' => true,
        ]);
    }

    private function faktur(User $user, ?User $sales = null, int $mundurHari = 40, float $jumlah = 2): FakturPenjualan
    {
        $stok = $this->stok($jumlah);
        $penjualan = app(PenjualanService::class);
        $tanggal = today()->subDays($mundurHari);

        $pesanan = $penjualan->buatPesanan([
            'tanggal' => $tanggal->toDateString(),
            'pelanggan_id' => $this->pelanggan()->id,
            'sales_user_id' => $sales?->id,
            'gudang_id' => $stok->gudang_id,
            'is_ppn' => false,
            'details' => [['bahan_id' => $stok->bahan_id, 'jumlah' => $jumlah, 'harga_satuan' => 100000]],
        ], $user);

        $suratJalan = $penjualan->postingSuratJalan(
            $penjualan->buatSuratJalan($pesanan->fresh('details'), [
                'tanggal' => today()->toDateString(),
                'details' => [['pesanan_penjualan_detail_id' => $pesanan->details->first()->id, 'jumlah' => $jumlah]],
            ], $user),
            $user
        );

        $service = app(FakturPenjualanService::class);

        $faktur = $service->buatDariSuratJalan($suratJalan, [
            'tanggal' => $tanggal->toDateString(),
            'jatuh_tempo' => $tanggal->copy()->addDays(5)->toDateString(),
        ], $user);

        return $service->posting($faktur, $user);
    }

    private function akuntansi(): User
    {
        return User::factory()->create(['type' => User::ROLE_ACCOUNTING, 'is_active' => true]);
    }

    public function test_an_overdue_invoice_lands_in_the_bucket_that_matches_its_age(): void
    {
        $user = $this->akuntansi();
        $faktur = $this->faktur($user, mundurHari: 40);

        $laporan = app(PiutangAgingService::class)->laporan(null, $faktur->pelanggan_id);
        $baris = $laporan['baris']->firstWhere('nomor', $faktur->nomor);

        $this->assertNotNull($baris);
        $this->assertSame('31-60 Hari', $baris['ember']);
        $this->assertSame(35, $baris['hari_lewat']);
        $this->assertEqualsWithDelta((float) $faktur->grand_total, $baris['sisa'], 0.01);
    }

    public function test_the_subledger_total_ties_out_to_the_general_ledger(): void
    {
        $this->faktur($this->akuntansi());

        $tieOut = app(PiutangAgingService::class)->laporan()['tie_out'];

        $this->assertTrue($tieOut['tersedia']);
        $this->assertEqualsWithDelta(0, $tieOut['selisih'], 0.01);
    }

    public function test_an_as_of_date_before_a_payment_still_shows_the_full_receivable(): void
    {
        $user = $this->akuntansi();
        $faktur = $this->faktur($user, mundurHari: 30);
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE, 'is_active' => true]);

        app(FakturPenjualanService::class)->terimaPembayaran($faktur, [
            'tanggal' => today()->subDays(3)->toDateString(),
            'jumlah' => 50000,
            'coa_kas_bank_id' => \App\Models\BaganAkun::where('is_cash_bank', true)->value('id'),
        ], $finance);

        $aging = app(PiutangAgingService::class);

        $sebelum = $aging->laporan(today()->subDays(10), $faktur->pelanggan_id)['total_piutang'];
        $sesudah = $aging->laporan(today(), $faktur->pelanggan_id)['total_piutang'];

        $this->assertEqualsWithDelta((float) $faktur->grand_total, $sebelum, 0.01);
        $this->assertEqualsWithDelta($sebelum - 50000, $sesudah, 0.01);
    }

    public function test_draft_and_void_invoices_never_enter_the_aging_report(): void
    {
        $user = $this->akuntansi();
        $faktur = $this->faktur($user);

        $faktur->update(['status' => FakturPenjualan::VOID]);

        $laporan = app(PiutangAgingService::class)->laporan(null, $faktur->pelanggan_id);

        $this->assertNull($laporan['baris']->firstWhere('nomor', $faktur->nomor));
    }

    public function test_the_salesperson_defaults_to_the_creator_and_is_copied_onto_the_invoice(): void
    {
        $user = $this->akuntansi();
        $faktur = $this->faktur($user);

        $pesanan = PesananPenjualan::where('pelanggan_id', $faktur->pelanggan_id)->firstOrFail();

        $this->assertSame($user->id, (int) $pesanan->sales_user_id);
        $this->assertSame($user->id, (int) $faktur->sales_user_id);
    }

    public function test_sales_performance_follows_the_snapshot_not_the_current_order_owner(): void
    {
        $pembuat = $this->akuntansi();
        $sales = User::factory()->create(['type' => User::ROLE_PURCHASING, 'is_active' => true]);

        $faktur = $this->faktur($pembuat, $sales, mundurHari: 5);

        $pesanan = PesananPenjualan::where('pelanggan_id', $faktur->pelanggan_id)->firstOrFail();
        $pesanan->update(['sales_user_id' => $pembuat->id]);

        $laporan = app(KinerjaSalesService::class)->laporan(today()->subDays(10), today());
        $baris = $laporan['baris']->firstWhere('sales_user_id', $sales->id);

        $this->assertNotNull($baris);
        $this->assertEqualsWithDelta((float) $faktur->total_dpp, $baris['penjualan'], 0.01);
        $this->assertSame(0, $baris['jumlah_pesanan']);
    }

    public function test_the_receivable_pages_are_open_to_finance_and_closed_to_the_warehouse(): void
    {
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE, 'is_active' => true]);
        $gudang = User::factory()->create(['type' => User::ROLE_WAREHOUSE, 'is_active' => true]);
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING, 'is_active' => true]);

        $this->actingAs($finance)->get(route('piutang-aging.index'))->assertOk();
        $this->actingAs($gudang)->get(route('piutang-aging.index'))->assertForbidden();

        $this->actingAs($purchasing)->get(route('kinerja-sales.index'))->assertOk();
        $this->actingAs($gudang)->get(route('kinerja-sales.index'))->assertForbidden();
    }
}
