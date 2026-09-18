<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\Gudang;
use App\Models\StokGudang;
use App\Models\TransferGudang;
use App\Models\User;
use App\Services\AccountingReconciliationService;
use App\Services\TransferGudangService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersediaanDalamPerjalananTest extends TestCase
{
    use DatabaseTransactions;

    private function saldoAkun(string $key): float
    {
        $coa = AccountingSetting::accountId($key);

        return round((float) DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->whereIn('j.status', ['POSTED', 'REVERSED'])
            ->where('jd.coa_id', $coa)
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit), 0) s')
            ->value('s'), 2);
    }

    private function persediaanGudang(int $gudangId): float
    {
        $akun = DB::table('kategori_bahans')->whereNotNull('coa_persediaan_id')->distinct()->pluck('coa_persediaan_id');

        return round((float) DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->whereIn('j.status', ['POSTED', 'REVERSED'])
            ->whereIn('jd.coa_id', $akun)
            ->where('jd.gudang_id', $gudangId)
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit), 0) s')
            ->value('s'), 2);
    }

    private function siapkanTransfer(float $jumlah = 2): array
    {
        $operator = User::factory()->create(['type' => User::ROLE_WAREHOUSE, 'is_active' => true]);
        Auth::setUser($operator);

        $stok = StokGudang::whereRaw('stok_tersedia - stok_direservasi >= ?', [$jumlah])
            ->whereHas('gudang', fn ($q) => $q->where('jenis', Gudang::NORMAL))
            ->firstOrFail();

        $asal = Gudang::findOrFail($stok->gudang_id);
        $tujuan = Gudang::where('jenis', Gudang::NORMAL)
            ->where('id', '!=', $asal->id)
            ->where('boleh_transfer', true)
            ->firstOrFail();

        $transfer = TransferGudang::create([
            'nomor_transfer' => 'TRF-TRANSIT-' . random_int(10000, 99999),
            'tanggal' => today(),
            'gudang_asal_id' => $asal->id,
            'gudang_tujuan_id' => $tujuan->id,
            'status' => TransferGudang::DIAJUKAN,
            'dibuat_oleh' => $operator->id,
        ]);

        $transfer->details()->create(['bahan_id' => $stok->bahan_id, 'jumlah' => $jumlah]);

        return [$transfer->fresh('details'), $asal, $tujuan, $jumlah];
    }

    public function test_shipping_moves_value_out_of_the_source_warehouse_into_goods_in_transit(): void
    {
        [$transfer, $asal, $tujuan] = $this->siapkanTransfer();

        $transitAwal = $this->saldoAkun(AccountingSetting::PERSEDIAAN_DALAM_PERJALANAN);
        $asalAwal = $this->persediaanGudang((int) $asal->id);
        $tujuanAwal = $this->persediaanGudang((int) $tujuan->id);

        app(TransferGudangService::class)->konfirmasi($transfer);

        $transitSetelah = $this->saldoAkun(AccountingSetting::PERSEDIAAN_DALAM_PERJALANAN);
        $nilai = round($transitSetelah - $transitAwal, 2);

        $this->assertGreaterThan(0, $nilai, 'Pengiriman wajib mendebit Persediaan Dalam Perjalanan.');

        $this->assertEqualsWithDelta(
            $asalAwal - $nilai,
            $this->persediaanGudang((int) $asal->id),
            0.01,
            'Nilai persediaan gudang asal harus turun begitu barang berangkat, bukan menunggu diterima.'
        );

        $this->assertEqualsWithDelta(
            $tujuanAwal,
            $this->persediaanGudang((int) $tujuan->id),
            0.01,
            'Gudang tujuan belum boleh mengakui persediaan sebelum barang tiba.'
        );
    }

    public function test_receiving_releases_goods_in_transit_into_the_destination_warehouse(): void
    {
        [$transfer, $asal, $tujuan] = $this->siapkanTransfer();
        $service = app(TransferGudangService::class);

        $transitAwal = $this->saldoAkun(AccountingSetting::PERSEDIAAN_DALAM_PERJALANAN);
        $tujuanAwal = $this->persediaanGudang((int) $tujuan->id);

        $transfer = $service->konfirmasi($transfer);
        $nilai = round($this->saldoAkun(AccountingSetting::PERSEDIAAN_DALAM_PERJALANAN) - $transitAwal, 2);

        $service->terima($transfer);

        $this->assertEqualsWithDelta(
            $transitAwal,
            $this->saldoAkun(AccountingSetting::PERSEDIAAN_DALAM_PERJALANAN),
            0.01,
            'Penerimaan penuh wajib mengosongkan kembali Persediaan Dalam Perjalanan.'
        );

        $this->assertEqualsWithDelta(
            $tujuanAwal + $nilai,
            $this->persediaanGudang((int) $tujuan->id),
            0.01,
            'Nilai yang dilepas dari perjalanan harus mendarat di gudang tujuan.'
        );
    }

    public function test_an_under_receipt_leaves_the_missing_value_in_goods_in_transit(): void
    {
        [$transfer, $asal, $tujuan, $jumlah] = $this->siapkanTransfer(2);
        $service = app(TransferGudangService::class);

        $transitAwal = $this->saldoAkun(AccountingSetting::PERSEDIAAN_DALAM_PERJALANAN);
        $transfer = $service->konfirmasi($transfer);

        $detail = $transfer->details->first();
        $service->terima($transfer, [$detail->id => $jumlah - 1]);

        $sisa = round($this->saldoAkun(AccountingSetting::PERSEDIAAN_DALAM_PERJALANAN) - $transitAwal, 2);

        $this->assertGreaterThan(
            0,
            $sisa,
            'Barang yang berangkat tapi tidak tiba harus tertinggal sebagai nilai dalam perjalanan, bukan hilang diam-diam.'
        );
    }

    public function test_the_reconciliation_page_watches_goods_in_transit(): void
    {
        [$transfer] = $this->siapkanTransfer();
        app(TransferGudangService::class)->konfirmasi($transfer);

        $cek = collect(app(AccountingReconciliationService::class)->checks())->firstWhere('key', 'transit');

        $this->assertNotNull($cek, 'Invarian persediaan dalam perjalanan wajib ada di daftar rekonsiliasi.');
        $this->assertSame(0, $cek['invalid'], 'Saldo buku besar dalam perjalanan harus sama dengan nilai layer IN_TRANSIT.');
        $this->assertGreaterThan(0, $cek['amount'], 'Setelah pengiriman, saldo dalam perjalanan tidak boleh nol.');
    }
}
