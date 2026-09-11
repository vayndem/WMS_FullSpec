<?php

namespace Tests\Feature;

use App\Models\BaganAkun;
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
        $coa = BaganAkun::firstOrFail();

        $this->actingAs($accounting)->get(route('financial-statements.neraca-saldo'))->assertOk()->assertSee('Neraca Saldo');
        $this->actingAs($accounting)->get(route('financial-statements.neraca-saldo.pdf'))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.buku-besar'))->assertOk()->assertSee('Buku Besar');
        $this->actingAs($accounting)->get(route('financial-statements.buku-besar.pdf', ['coa_id' => $coa->id]))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.laba-rugi'))->assertOk()->assertSee('Laba Rugi');
        $this->actingAs($accounting)->get(route('financial-statements.laba-rugi.pdf'))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.neraca'))->assertOk()->assertSee('Neraca');
        $this->actingAs($accounting)->get(route('financial-statements.neraca.pdf'))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.arus-kas'))->assertOk()->assertSee('Laporan Arus Kas');
        $this->actingAs($accounting)->get(route('financial-statements.arus-kas.pdf'))->assertOk();
        $this->actingAs($accounting)->get(route('financial-statements.arus-kas.excel'))->assertOk();

        $this->actingAs($accounting)->get(route('financial-statements.pajak-penghasilan'))->assertOk()->assertSee('PPh Badan');
        $this->actingAs($accounting)->get(route('financial-statements.rekonsiliasi-fiskal'))->assertOk()->assertSee('Rekonsiliasi Fiskal');
        $this->actingAs($accounting)->get(route('financial-statements.rekonsiliasi-fiskal.pdf'))->assertOk();
        $this->actingAs($accounting)->get(route('financial-statements.rekonsiliasi-fiskal.excel'))->assertOk();
    }

    public function test_non_accounting_role_is_forbidden_from_financial_statements(): void
    {
        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);

        $this->actingAs($purchasing)->get(route('financial-statements.neraca-saldo'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.buku-besar'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.laba-rugi'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.neraca'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.arus-kas'))->assertForbidden();
        $this->actingAs($purchasing)->get(route('financial-statements.pajak-penghasilan'))->assertForbidden();
        $this->actingAs($purchasing)->post(route('financial-statements.pajak-penghasilan.posting'), [
            'tahun_pajak' => today()->year,
            'peredaran_bruto' => 1_000_000_000,
            'posting_date' => today()->toDateString(),
        ])->assertForbidden();
    }

    public function test_trial_balance_debit_and_kredit_totals_match(): void
    {
        $service = app(FinancialStatementService::class);
        $data = $service->trialBalance(today());

        $this->assertEqualsWithDelta($data['total_debit'], $data['total_kredit'], 0.01);
    }

    public function test_cash_flow_reconciles_to_the_general_ledger_cash_balance(): void
    {
        $service = app(FinancialStatementService::class);
        $data = $service->cashFlow(today()->subYears(5), today());

        $this->assertEqualsWithDelta(0.0, $data['selisih'], 0.01);
        $this->assertEqualsWithDelta($data['saldo_akhir_buku'], $data['saldo_akhir'], 0.01);
        $this->assertEqualsWithDelta(
            $data['saldo_akhir'] - $data['saldo_awal'],
            $data['arus_bersih'],
            0.01
        );
        $this->assertSame(
            ['OPERASI', 'INVESTASI', 'PENDANAAN'],
            $data['sections']->pluck('kelompok')->all()
        );
    }

    public function test_fiscal_reconciliation_adds_back_permanent_differences_and_defers_timing_ones(): void
    {
        $service = app(\App\Services\RekonsiliasiFiskalService::class);
        $denda = \App\Models\BaganAkun::where('kode_akun', '5304')->firstOrFail();
        $kasBank = \App\Models\BaganAkun::where('is_cash_bank', true)->firstOrFail();
        $final = \App\Models\BaganAkun::where('kode_akun', '4205')->firstOrFail();

        $sebelum = $service->reconcile(today()->startOfYear(), today());

        $jurnal = \App\Models\Jurnal::create([
            'no_jurnal' => 'TEST-FISKAL-1',
            'tanggal' => today(),
            'keterangan' => 'Uji koreksi fiskal',
            'sumber_transaksi' => 'MANUAL',
            'status' => 'POSTED',
            'total_debit' => 6000000,
            'total_kredit' => 6000000,
        ]);
        $jurnal->details()->createMany([
            ['coa_id' => $denda->id, 'debit' => 5000000, 'kredit' => 0],
            ['coa_id' => $kasBank->id, 'debit' => 1000000, 'kredit' => 6000000],
            ['coa_id' => $final->id, 'debit' => 0, 'kredit' => 1000000],
        ]);

        $sesudah = $service->reconcile(today()->startOfYear(), today());

        $this->assertEqualsWithDelta(
            $sebelum['laba_komersial'] - 5000000 + 1000000,
            $sesudah['laba_komersial'],
            0.01
        );
        $this->assertEqualsWithDelta($sebelum['total_koreksi_positif'] + 5000000, $sesudah['total_koreksi_positif'], 0.01);
        $this->assertEqualsWithDelta($sebelum['total_koreksi_negatif'] + 1000000, $sesudah['total_koreksi_negatif'], 0.01);

        $this->assertEqualsWithDelta(
            $sesudah['laba_komersial'] + $sesudah['total_koreksi_positif'] - $sesudah['total_koreksi_negatif'] + $sesudah['total_koreksi_beda_waktu'],
            $sesudah['laba_fiskal'],
            0.01
        );

        $this->assertTrue(
            $sesudah['beda_tetap_positif']->contains(fn ($row) => $row['kode_akun'] === '5304'),
            'Beban denda pajak harus muncul sebagai koreksi positif.'
        );
        $this->assertTrue(
            $sesudah['beda_waktu']->every(
                fn ($row) => $row['sumber_koreksi'] === 'JADWAL_PENYUSUTAN_FISKAL'
                    || ($row['sumber_koreksi'] === 'MENUNGGU_JADWAL_FISKAL' && $row['koreksi'] === 0.0)
            ),
            'Beda waktu tanpa jadwal fiskal tidak boleh dikoreksi memakai nilai buku.'
        );
    }

    public function test_fiscal_depreciation_schedule_drives_the_timing_difference(): void
    {
        $accounting = \App\Models\User::factory()->create(['type' => \App\Models\User::ROLE_ACCOUNTING]);
        $category = \App\Models\KategoriAset::whereNotNull('depreciation_expense_coa_id')->where('is_active', true)->firstOrFail();

        $asset = \App\Models\Aset::create([
            'nomor_aset' => 'FISKAL-DEP-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Jadwal Fiskal',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => \App\Models\BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 4800000,
            'residual_value' => 0,
            'useful_life_months' => 96,
            'depreciation_method' => \App\Models\Aset::STRAIGHT_LINE,
            'kelompok_fiskal' => 'KELOMPOK_1',
            'metode_penyusutan_fiskal' => \App\Models\Aset::STRAIGHT_LINE,
            'accumulated_depreciation' => 0,
            'akumulasi_penyusutan_fiskal' => 0,
            'book_value' => 4800000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $this->assertEqualsWithDelta(50000.0, $asset->suggestedMonthlyDepreciation(), 0.01);
        $this->assertEqualsWithDelta(100000.0, $asset->suggestedMonthlyFiscalDepreciation(), 0.01);

        $service = app(\App\Services\RekonsiliasiFiskalService::class);
        $sebelum = $service->reconcile(today()->startOfYear(), today());

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Uji Jadwal Fiskal',
        ])->assertOk();

        $penyusutan = $asset->depreciations()->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(50000.0, (float) $penyusutan->amount, 0.01);
        $this->assertEqualsWithDelta(100000.0, (float) $penyusutan->amount_fiskal, 0.01);
        $this->assertEqualsWithDelta(100000.0, (float) $asset->fresh()->akumulasi_penyusutan_fiskal, 0.01);

        $diposting = \App\Models\PenyusutanAset::where('period_label', 'Uji Jadwal Fiskal')->get();
        $selisihDiharapkan = round((float) $diposting->sum('amount') - (float) $diposting->sum('amount_fiskal'), 2);

        $sesudah = $service->reconcile(today()->startOfYear(), today());
        $this->assertEqualsWithDelta(
            $selisihDiharapkan,
            $sesudah['total_koreksi_beda_waktu'] - $sebelum['total_koreksi_beda_waktu'],
            0.01,
            'Koreksi beda waktu harus bergerak persis sebesar (penyusutan komersial - penyusutan fiskal).'
        );

        $barisPenyusutan = $sesudah['beda_waktu']->firstWhere('coa_id', (int) $category->depreciation_expense_coa_id);
        $this->assertNotNull($barisPenyusutan, 'Akun beban penyusutan harus muncul sebagai beda waktu.');
        $this->assertSame('JADWAL_PENYUSUTAN_FISKAL', $barisPenyusutan['sumber_koreksi']);
        $this->assertGreaterThanOrEqual(100000.0, $barisPenyusutan['nilai_fiskal']);
    }

    private function jurnalLabaBesar(float $jumlah): void
    {
        $pendapatan = \App\Models\BaganAkun::where('kode_akun', '4201')->firstOrFail();
        $kasBank = \App\Models\BaganAkun::where('is_cash_bank', true)->firstOrFail();

        $jurnal = \App\Models\Jurnal::create([
            'no_jurnal' => 'TEST-PPH-' . random_int(1000, 9999),
            'tanggal' => today(),
            'keterangan' => 'Uji PPh Badan',
            'sumber_transaksi' => 'MANUAL',
            'status' => 'POSTED',
            'total_debit' => $jumlah,
            'total_kredit' => $jumlah,
        ]);
        $jurnal->details()->createMany([
            ['coa_id' => $kasBank->id, 'debit' => $jumlah, 'kredit' => 0],
            ['coa_id' => $pendapatan->id, 'debit' => 0, 'kredit' => $jumlah],
        ]);
    }

    public function test_pasal_31e_facility_reduces_tax_as_turnover_falls(): void
    {
        $this->jurnalLabaBesar(1_000_000_000);
        $service = app(\App\Services\PajakPenghasilanService::class);

        $kecil = $service->hitung(today()->year, 3_000_000_000);
        $sedang = $service->hitung(today()->year, 10_000_000_000);
        $besar = $service->hitung(today()->year, 60_000_000_000);

        $pkp = $kecil['penghasilan_kena_pajak'];
        $this->assertGreaterThan(0, $pkp, 'Skenario uji harus menghasilkan laba fiskal positif.');
        $this->assertSame($pkp, fmod($pkp, 1000.0) === 0.0 ? $pkp : -1.0, 'PKP wajib dibulatkan ke bawah ribuan penuh.');

        $this->assertEqualsWithDelta($pkp * 0.11, $kecil['pph_terutang'], 0.01);
        $this->assertEqualsWithDelta($pkp * 0.22, $besar['pph_terutang'], 0.01);
        $this->assertEqualsWithDelta(0.0, $besar['pkp_fasilitas'], 0.01);

        $this->assertGreaterThan($kecil['pph_terutang'], $sedang['pph_terutang']);
        $this->assertLessThan($besar['pph_terutang'], $sedang['pph_terutang']);
        $this->assertEqualsWithDelta(
            $sedang['pkp_fasilitas'] * 0.11 + $sedang['pkp_normal'] * 0.22,
            $sedang['pph_terutang'],
            0.01
        );
    }

    public function test_deferred_tax_is_posted_from_the_cumulative_book_versus_tax_base_difference(): void
    {
        $accounting = \App\Models\User::factory()->create(['type' => \App\Models\User::ROLE_ACCOUNTING]);
        $this->actingAs($accounting);
        $category = \App\Models\KategoriAset::where('is_active', true)->firstOrFail();

        \App\Models\Aset::create([
            'nomor_aset' => 'PPH-DT-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Pajak Tangguhan',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => \App\Models\BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 10_000_000,
            'residual_value' => 0,
            'useful_life_months' => 96,
            'depreciation_method' => \App\Models\Aset::STRAIGHT_LINE,
            'kelompok_fiskal' => 'KELOMPOK_1',
            'metode_penyusutan_fiskal' => \App\Models\Aset::STRAIGHT_LINE,
            'accumulated_depreciation' => 0,
            'akumulasi_penyusutan_fiskal' => 2_000_000,
            'book_value' => 10_000_000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $service = app(\App\Services\PajakPenghasilanService::class);
        $data = $service->hitung(today()->year, 10_000_000_000);

        $this->assertEqualsWithDelta(2_000_000.0, $data['beda_waktu_kumulatif'], 0.01);
        $this->assertEqualsWithDelta(440_000.0, $data['pajak_tangguhan_seharusnya'], 0.01);
        $this->assertEqualsWithDelta(440_000.0, $data['gerakan_pajak_tangguhan'], 0.01);

        $perhitungan = $service->posting(today()->year, 10_000_000_000, today()->toDateString());
        $jurnal = $perhitungan->jurnal;

        $this->assertNotNull($jurnal);
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);

        $dtlId = \App\Models\AccountingSetting::accountId(\App\Models\AccountingSetting::LIABILITAS_PAJAK_TANGGUHAN);
        $baris = $jurnal->details()->where('coa_id', $dtlId)->firstOrFail();
        $this->assertEqualsWithDelta(440_000.0, (float) $baris->kredit, 0.01);

        $ulang = $service->hitung(today()->year, 10_000_000_000);
        $this->assertEqualsWithDelta(440_000.0, $ulang['pajak_tangguhan_tercatat'], 0.01);
        $this->assertEqualsWithDelta(0.0, $ulang['gerakan_pajak_tangguhan'], 0.01);

        $this->expectException(\RuntimeException::class);
        $service->posting(today()->year, 10_000_000_000, today()->toDateString());
    }

    public function test_fiscal_loss_is_carried_forward_fifo_and_expires_after_five_years(): void
    {
        $service = app(\App\Services\PajakPenghasilanService::class);
        $tahun = today()->year;

        $rugi = fn (int $th, float $jumlah) => \App\Models\PerhitunganPajakPenghasilan::create([
            'tahun_pajak' => $th,
            'period_start' => \Illuminate\Support\Carbon::create($th, 1, 1),
            'period_end' => \Illuminate\Support\Carbon::create($th, 12, 31),
            'peredaran_bruto' => 1_000_000_000,
            'laba_fiskal' => -$jumlah,
        ]);

        $rugi($tahun - 6, 900_000_000);
        $rugi($tahun - 3, 200_000_000);
        $rugi($tahun - 2, 500_000_000);

        $sisa = collect($service->sisaKerugian($tahun));
        $this->assertSame([$tahun - 3, $tahun - 2], $sisa->pluck('tahun_rugi')->all(), 'Kerugian lebih dari 5 tahun harus kedaluwarsa.');

        $this->jurnalLabaBesar(300_000_000);
        $data = $service->hitung($tahun, 10_000_000_000);

        $this->assertEqualsWithDelta(200_000_000.0, collect($data['rincian_kompensasi'])->firstWhere('tahun_rugi', $tahun - 3)['jumlah'], 0.01, 'Kerugian tertua dipakai lebih dulu.');
        $this->assertEqualsWithDelta($data['laba_fiskal'], $data['kompensasi_kerugian'], 0.01, 'Laba fiskal lebih kecil dari total kerugian, jadi terserap habis.');
        $this->assertEqualsWithDelta(0.0, $data['penghasilan_kena_pajak'], 0.01);
        $this->assertEqualsWithDelta(0.0, $data['pph_terutang'], 0.01);

        $perhitungan = $service->posting($tahun, 10_000_000_000, today()->toDateString());
        $this->assertEqualsWithDelta($data['kompensasi_kerugian'], (float) $perhitungan->kompensasi_kerugian, 0.01);
        $this->assertGreaterThan(0, $perhitungan->kompensasi()->count());

        $sisaSesudah = collect($service->sisaKerugian($tahun))->pluck('sisa', 'tahun_rugi');
        $this->assertEqualsWithDelta(0.0, (float) ($sisaSesudah[$tahun - 3] ?? 0), 0.01, 'Kerugian tertua harus habis terpakai.');
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
        $account = BaganAkun::where('kode_akun', '1102')->firstOrFail();
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
