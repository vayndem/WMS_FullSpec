<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\Bahan;
use App\Models\DataPesanan;
use App\Models\Gudang;
use App\Models\Jurnal;
use App\Models\Pelanggan;
use App\Models\PemakaianBarang;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\AccountingReconciliationService;
use App\Services\DataPesananService;
use App\Services\DocumentNumberService;
use App\Services\PenjualanService;
use App\Services\StokGudangService;
use App\Services\WmsAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PembatalanPerintahKerjaTest extends TestCase
{
    use DatabaseTransactions;

    private function saldo(string $key): float
    {
        return round((float) DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->whereIn('j.status', ['POSTED', 'REVERSED'])
            ->where('jd.coa_id', AccountingSetting::accountId($key))
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit), 0) s')
            ->value('s'), 2);
    }

    private function stok(float $minimal = 5): StokGudang
    {
        return StokGudang::whereRaw('stok_tersedia - stok_direservasi >= ?', [$minimal])
            ->whereHas('gudang', fn ($q) => $q->where('jenis', Gudang::NORMAL))
            ->firstOrFail();
    }

    private function perintahKerja(User $user, StokGudang $stok, float $jumlah = 4): DataPesanan
    {
        $pelanggan = Pelanggan::create([
            'kode' => 'CUST-BATAL-' . random_int(1000, 9999),
            'nama' => 'PT Uji Pembatalan',
            'termin_hari' => 30,
            'is_active' => true,
        ]);

        $produk = Bahan::whereKeyNot($stok->bahan_id)->firstOrFail();

        $pesanan = app(PenjualanService::class)->buatPesanan([
            'tanggal' => today()->toDateString(),
            'pelanggan_id' => $pelanggan->id,
            'gudang_id' => $stok->gudang_id,
            'is_ppn' => false,
            'details' => [['bahan_id' => $produk->id, 'jumlah' => $jumlah, 'harga_satuan' => 100000]],
        ], $user);

        $service = app(DataPesananService::class);

        return $service->rilis($service->buat($pesanan->details->first(), [
            'tanggal' => today()->toDateString(),
            'gudang_id' => $stok->gudang_id,
            'jumlah_rencana' => $jumlah,
        ], $user));
    }

    private function bebankan(DataPesanan $wo, StokGudang $stok, User $user, float $jumlah = 2): void
    {
        $bahan = Bahan::findOrFail($stok->bahan_id);

        $npk = PemakaianBarang::create([
            'kode' => app(DocumentNumberService::class)->internal('NPK', 'BTL'),
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
    }

    public function test_cancelling_a_work_order_that_absorbed_cost_writes_the_work_in_process_off(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_PRODUCTION, 'is_active' => true]);
        $stok = $this->stok();
        $wo = $this->perintahKerja($user, $stok);
        $this->bebankan($wo, $stok, $user);

        $wo = $wo->fresh();
        $wip = $wo->totalBiaya();
        $this->assertGreaterThan(0, $wip, 'Perintah kerja harus sudah menyerap biaya sebelum diuji.');

        $wipAwal = $this->saldo(AccountingSetting::BARANG_DALAM_PROSES);
        $rugiAwal = $this->saldo(AccountingSetting::RUGI_PEMBATALAN_PRODUKSI);

        app(DataPesananService::class)->batalkan($wo);

        $this->assertSame(DataPesanan::DIBATALKAN, $wo->fresh()->status);

        $this->assertEqualsWithDelta(
            $wipAwal - $wip,
            $this->saldo(AccountingSetting::BARANG_DALAM_PROSES),
            0.01,
            'Barang dalam proses wajib dikosongkan saat perintah kerja dibatalkan.'
        );

        $this->assertEqualsWithDelta(
            $rugiAwal + $wip,
            $this->saldo(AccountingSetting::RUGI_PEMBATALAN_PRODUKSI),
            0.01,
            'Nilai yang dilepas harus mendarat di Kerugian Pembatalan Produksi, bukan tercampur beban produksi biasa.'
        );

        $this->assertNotNull(
            Jurnal::where('sumber_transaksi', 'PEMBATALAN_PERINTAH_KERJA')->where('reff_id', $wo->id)->first(),
            'Pembatalan wajib meninggalkan jurnal yang bisa ditelusuri ke perintah kerjanya.'
        );

        $this->assertEqualsWithDelta(
            0,
            $wo->fresh()->totalBiaya(),
            0.01,
            'Subledger biaya harus ikut nol supaya tidak berbeda dengan buku besar.'
        );
    }

    public function test_the_work_in_process_invariant_still_holds_after_a_cancellation(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_PRODUCTION, 'is_active' => true]);
        $stok = $this->stok();
        $wo = $this->perintahKerja($user, $stok);
        $this->bebankan($wo, $stok, $user);

        app(DataPesananService::class)->batalkan($wo->fresh());

        $cek = collect(app(AccountingReconciliationService::class)->checks())->firstWhere('key', 'wip');

        $this->assertSame(0, $cek['invalid'], 'Setelah pembatalan, saldo WIP buku besar harus tetap sama dengan perintah kerja berjalan.');
    }

    public function test_a_zero_cost_cancellation_still_posts_no_journal(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_PRODUCTION, 'is_active' => true]);
        $stok = $this->stok();
        $wo = $this->perintahKerja($user, $stok);

        $sebelum = Jurnal::where('sumber_transaksi', 'PEMBATALAN_PERINTAH_KERJA')->count();

        app(DataPesananService::class)->batalkan($wo);

        $this->assertSame(DataPesanan::DIBATALKAN, $wo->fresh()->status);
        $this->assertSame(
            $sebelum,
            Jurnal::where('sumber_transaksi', 'PEMBATALAN_PERINTAH_KERJA')->count(),
            'Perintah kerja tanpa biaya tidak perlu menjurnal apa pun.'
        );
    }

    public function test_a_completed_work_order_and_a_cancelled_one_are_both_refused(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_PRODUCTION, 'is_active' => true]);
        $stok = $this->stok();
        $service = app(DataPesananService::class);

        $wo = $this->perintahKerja($user, $stok);
        $service->batalkan($wo);

        try {
            $service->batalkan($wo->fresh());
            $this->fail('Pembatalan ganda seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('sudah dibatalkan', $e->getMessage());
        }

        $lain = $this->perintahKerja($user, $stok);
        $this->bebankan($lain, $stok, $user);
        $service->selesaikan($lain->fresh(), 4, $user);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah selesai');

        $service->batalkan($lain->fresh());
    }
}
