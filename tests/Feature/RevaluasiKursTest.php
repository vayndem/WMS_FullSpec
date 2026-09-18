<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\FakturPembelian;
use App\Models\Jurnal;
use App\Models\RevaluasiKurs;
use App\Models\User;
use App\Services\AccountingReconciliationService;
use App\Services\RevaluasiKursService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class RevaluasiKursTest extends TestCase
{
    use DatabaseTransactions;

    private function akuntansi(): User
    {
        return User::factory()->create(['type' => User::ROLE_ACCOUNTING, 'is_active' => true]);
    }

    private function periode(): string
    {
        return now()->format('Y-m');
    }

    private function saldo(string $key): float
    {
        return round((float) DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->whereIn('j.status', ['POSTED', 'REVERSED'])
            ->where('jd.coa_id', AccountingSetting::accountId($key))
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit), 0) s')
            ->value('s'), 2);
    }

    private function fakturValas(float $kurs = 15000): FakturPembelian
    {
        $faktur = FakturPembelian::whereNotIn('status', [FakturPembelian::VOID, FakturPembelian::PENDING_APPROVAL])
            ->where('sisa_tagihan', '>', 0)
            ->firstOrFail();

        $faktur->forceFill([
            'mata_uang_asing' => 'USD',
            'kurs' => $kurs,
            'nilai_asing' => round((float) $faktur->grand_total / $kurs, 2),
        ])->save();

        return $faktur->fresh();
    }

    private function selisihDiharapkan(FakturPembelian $faktur, float $kursBaru): float
    {
        $valas = round((float) $faktur->sisa_tagihan / (float) $faktur->kurs, 2);

        return round($valas * $kursBaru - (float) $faktur->sisa_tagihan, 2);
    }

    public function test_a_weaker_rupiah_turns_an_open_foreign_payable_into_an_exchange_loss(): void
    {
        $user = $this->akuntansi();
        $faktur = $this->fakturValas(15000);
        $service = app(RevaluasiKursService::class);
        $periode = $this->periode();
        $diharapkan = $this->selisihDiharapkan($faktur, 16000);

        $service->simpanKurs(['mata_uang' => 'USD', 'periode' => $periode, 'kurs' => 16000], $user);

        $rugiAwal = $this->saldo(AccountingSetting::RUGI_SELISIH_KURS);
        $hutangAwal = $this->saldo(AccountingSetting::HUTANG_USAHA);

        $hasil = $service->posting($periode, $user);

        $this->assertGreaterThan(0, $diharapkan, 'Rupiah melemah harus menghasilkan selisih positif.');
        $this->assertEqualsWithDelta($diharapkan, $hasil['total_selisih'], 0.01);

        $this->assertEqualsWithDelta(
            $rugiAwal + $diharapkan,
            $this->saldo(AccountingSetting::RUGI_SELISIH_KURS),
            0.01,
            'Rupiah melemah atas hutang valas menimbulkan rugi selisih kurs.'
        );

        $this->assertEqualsWithDelta(
            $hutangAwal - $diharapkan,
            $this->saldo(AccountingSetting::HUTANG_USAHA),
            0.01,
            'Hutang usaha bertambah (saldo kredit membesar) sebesar selisih kurs.'
        );

        $this->assertNotNull(Jurnal::where('sumber_transaksi', 'REVALUASI_KURS')->first());
    }

    public function test_a_stronger_rupiah_produces_a_gain_instead(): void
    {
        $user = $this->akuntansi();
        $faktur = $this->fakturValas(15000);
        $service = app(RevaluasiKursService::class);
        $periode = $this->periode();
        $diharapkan = $this->selisihDiharapkan($faktur, 14500);

        $service->simpanKurs(['mata_uang' => 'USD', 'periode' => $periode, 'kurs' => 14500], $user);

        $labaAwal = $this->saldo(AccountingSetting::LABA_SELISIH_KURS);
        $hasil = $service->posting($periode, $user);

        $this->assertLessThan(0, $diharapkan, 'Rupiah menguat harus menghasilkan selisih negatif.');
        $this->assertEqualsWithDelta($diharapkan, $hasil['total_selisih'], 0.01);

        $this->assertEqualsWithDelta(
            $labaAwal + $diharapkan,
            $this->saldo(AccountingSetting::LABA_SELISIH_KURS),
            0.01,
            'Saldo kredit Laba Selisih Kurs harus membesar.'
        );
    }

    public function test_inventory_is_never_revalued_because_it_is_not_a_monetary_item(): void
    {
        $user = $this->akuntansi();
        $this->fakturValas(15000);
        $service = app(RevaluasiKursService::class);
        $periode = $this->periode();

        $akunPersediaan = DB::table('kategori_bahans')->whereNotNull('coa_persediaan_id')->distinct()->pluck('coa_persediaan_id');

        $nilaiPersediaan = fn () => round((float) DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->whereIn('j.status', ['POSTED', 'REVERSED'])
            ->whereIn('jd.coa_id', $akunPersediaan)
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit), 0) s')
            ->value('s'), 2);

        $sebelum = $nilaiPersediaan();

        $service->simpanKurs(['mata_uang' => 'USD', 'periode' => $periode, 'kurs' => 16000], $user);
        $service->posting($periode, $user);

        $this->assertEqualsWithDelta(
            $sebelum,
            $nilaiPersediaan(),
            0.01,
            'PSAK 10: persediaan adalah pos non-moneter dan tetap pada kurs historis.'
        );
    }

    public function test_the_payables_invariant_stays_green_after_a_revaluation(): void
    {
        $user = $this->akuntansi();
        $this->fakturValas(15000);
        $service = app(RevaluasiKursService::class);
        $periode = $this->periode();

        $service->simpanKurs(['mata_uang' => 'USD', 'periode' => $periode, 'kurs' => 16000], $user);
        $service->posting($periode, $user);

        $cek = collect(app(AccountingReconciliationService::class)->checks())->firstWhere('key', 'ap');

        $this->assertSame(
            0,
            $cek['invalid'],
            'Setelah revaluasi, hutang buku besar harus sama dengan sisa tagihan ditambah akumulasi selisih kurs.'
        );
    }

    public function test_a_period_cannot_be_revalued_twice_and_needs_every_rate_filled(): void
    {
        $user = $this->akuntansi();
        $this->fakturValas(15000);
        $service = app(RevaluasiKursService::class);
        $periode = $this->periode();

        try {
            $service->posting($periode, $user);
            $this->fail('Revaluasi tanpa kurs penutup seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('kurs penutupnya belum diisi', $e->getMessage());
        }

        $service->simpanKurs(['mata_uang' => 'USD', 'periode' => $periode, 'kurs' => 16000], $user);
        $service->posting($periode, $user);

        $this->assertSame(1, RevaluasiKurs::where('periode', $periode)->count());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah pernah diposting');

        $service->posting($periode, $user);
    }

    public function test_the_exchange_journal_is_keyed_by_period_not_by_a_row_id(): void
    {
        $user = $this->akuntansi();
        $this->fakturValas(15000);
        $service = app(RevaluasiKursService::class);
        $periode = $this->periode();

        $service->simpanKurs(['mata_uang' => 'USD', 'periode' => $periode, 'kurs' => 16000], $user);
        $service->posting($periode, $user);

        $rincian = RevaluasiKurs::where('periode', $periode)->firstOrFail();
        $jurnal = Jurnal::findOrFail($rincian->journal_id);

        $this->assertSame(
            (int) str_replace('-', '', $periode),
            (int) $jurnal->reff_id,
            'Revaluasi memposting satu jurnal untuk seluruh periode, sehingga reff_id sengaja berisi kunci periode YYYYMM, bukan id baris wms_revaluasi_kurs.'
        );

        $this->assertGreaterThan(
            0,
            RevaluasiKurs::where('journal_id', $jurnal->id)->count(),
            'Rincian per faktur tetap tersimpan di wms_revaluasi_kurs dan menunjuk ke jurnal periode yang sama.'
        );

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('wms_jurnal')->insert([
            'no_jurnal' => "FX-{$periode}-DUP",
            'tanggal' => $jurnal->tanggal,
            'keterangan' => 'Percobaan revaluasi kedua pada periode yang sama',
            'sumber_transaksi' => 'REVALUASI_KURS',
            'reff_id' => (int) str_replace('-', '', $periode),
            'status' => 'POSTED',
            'created_by' => $user->id,
            'total_debit' => 0,
            'total_kredit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
