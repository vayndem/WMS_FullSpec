<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Gudang;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\PemakaianBarang;
use App\Models\PenerimaanBarang;
use App\Models\StokGudang;
use App\Models\TransferGudang;
use App\Models\User;
use App\Services\RekonsiliasiGudangService;
use App\Services\TransferGudangService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GudangDimensionTest extends TestCase
{
    use DatabaseTransactions;

    private function barisJurnal(string $sumber, int $reffId)
    {
        $jurnal = Jurnal::where('sumber_transaksi', $sumber)->where('reff_id', $reffId)->firstOrFail();

        return JurnalDetail::where('jurnal_id', $jurnal->id)->get();
    }

    public function test_goods_receipt_journal_carries_the_receiving_warehouse(): void
    {
        $lpb = PenerimaanBarang::where('document_type', 'GOODS')
            ->where('status', PenerimaanBarang::POSTED)
            ->whereNotNull('gudang_id')
            ->firstOrFail();

        $baris = $this->barisJurnal('LPB', $lpb->id);

        $this->assertGreaterThan(0, $baris->count());
        $this->assertTrue(
            $baris->every(fn (JurnalDetail $detail) => (int) $detail->gudang_id === (int) $lpb->gudang_id),
            'Setiap baris jurnal LPB harus membawa gudang penerimaannya.'
        );
    }

    public function test_material_issue_journal_carries_the_consuming_warehouse(): void
    {
        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)
            ->whereNotNull('id_gudang_asal')
            ->firstOrFail();

        $baris = $this->barisJurnal('NPK', $npk->id);

        $this->assertGreaterThan(0, $baris->count());
        $this->assertTrue(
            $baris->every(fn (JurnalDetail $detail) => (int) $detail->gudang_id === (int) $npk->id_gudang_asal),
            'Setiap baris jurnal NPK harus membawa gudang asal pemakaian.'
        );
    }

    public function test_the_ledger_can_now_value_inventory_per_warehouse(): void
    {
        $perGudang = app(RekonsiliasiGudangService::class)->nilaiPerGudang();

        $this->assertGreaterThan(0, $perGudang->count());

        foreach ($perGudang as $baris) {
            $this->assertEqualsWithDelta(
                0.0,
                $baris['selisih'],
                0.01,
                "Nilai persediaan gudang {$baris['gudang']} di buku besar tidak cocok dengan nilai layer-nya."
            );
        }
    }

    public function test_per_warehouse_values_add_up_to_the_company_wide_figure(): void
    {
        $service = app(RekonsiliasiGudangService::class);
        $ringkasan = $service->summary();

        $this->assertEqualsWithDelta(
            $ringkasan['inventory_gl_value'],
            $service->nilaiPerGudang()->sum('nilai_buku_besar'),
            0.01,
            'Jumlah nilai per gudang harus sama dengan angka perusahaan yang sudah ada.'
        );
    }

    public function test_internal_transfer_moves_value_between_warehouses_without_touching_the_company_total(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        Auth::login($user);

        $source = StokGudang::whereRaw('stok_tersedia - stok_direservasi >= 1')
            ->whereHas('gudang', fn ($query) => $query->where('jenis', Gudang::NORMAL))
            ->firstOrFail();
        $target = Gudang::where('jenis', Gudang::NORMAL)->whereKeyNot($source->gudang_id)->firstOrFail();

        $service = app(RekonsiliasiGudangService::class);
        $nilaiAwal = fn () => $service->nilaiPerGudang()->keyBy('gudang_id');
        $sebelum = $nilaiAwal();
        $totalSebelum = round($service->nilaiPerGudang()->sum('nilai_buku_besar'), 2);

        $transfer = TransferGudang::create([
            'nomor_transfer' => 'TEST-DIMENSI-001',
            'tanggal' => today(),
            'gudang_asal_id' => $source->gudang_id,
            'gudang_tujuan_id' => $target->id,
            'status' => TransferGudang::DIAJUKAN,
            'dibuat_oleh' => $user->id,
        ]);
        $transfer->details()->create(['bahan_id' => $source->bahan_id, 'jumlah' => 1]);

        app(TransferGudangService::class)->konfirmasi($transfer);
        app(TransferGudangService::class)->terima($transfer->fresh());

        $jurnal = Jurnal::where('sumber_transaksi', 'TRANSFER_GUDANG')->where('reff_id', $transfer->id)->first();
        $this->assertNotNull($jurnal, 'Transfer internal sekarang harus membentuk jurnal berdimensi gudang.');
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);

        $baris = JurnalDetail::where('jurnal_id', $jurnal->id)->get();
        $this->assertTrue($baris->contains(fn ($d) => (int) $d->gudang_id === (int) $target->id && (float) $d->debit > 0));
        $this->assertTrue($baris->contains(fn ($d) => (int) $d->gudang_id === (int) $source->gudang_id && (float) $d->kredit > 0));

        $sesudah = $nilaiAwal();
        $totalSesudah = round($service->nilaiPerGudang()->sum('nilai_buku_besar'), 2);

        $this->assertEqualsWithDelta($totalSebelum, $totalSesudah, 0.01, 'Transfer internal tidak boleh mengubah nilai persediaan perusahaan.');

        $nilaiPindah = round((float) $jurnal->total_debit, 2);
        $this->assertEqualsWithDelta(
            ($sebelum[$source->gudang_id]['nilai_buku_besar'] ?? 0) - $nilaiPindah,
            $sesudah[$source->gudang_id]['nilai_buku_besar'] ?? 0,
            0.01
        );
        $this->assertEqualsWithDelta(
            ($sebelum[$target->id]['nilai_buku_besar'] ?? 0) + $nilaiPindah,
            $sesudah[$target->id]['nilai_buku_besar'] ?? 0,
            0.01
        );
    }

    public function test_the_warehouse_dimension_survives_mass_assignment(): void
    {
        $gudang = Gudang::firstOrFail();
        $jurnal = Jurnal::create([
            'no_jurnal' => '26-09-DM-IX-901',
            'tanggal' => today(),
            'keterangan' => 'Uji dimensi gudang',
            'sumber_transaksi' => 'MANUAL',
            'status' => 'DRAFT',
            'total_debit' => 0,
            'total_kredit' => 0,
        ]);

        $akun = \App\Models\BaganAkun::where('is_postable', true)->firstOrFail();
        $jurnal->details()->createMany([
            ['coa_id' => $akun->id, 'gudang_id' => $gudang->id, 'debit' => 1000, 'kredit' => 0, 'keterangan' => 'Uji'],
        ]);

        $this->assertSame(
            (int) $gudang->id,
            (int) JurnalDetail::where('jurnal_id', $jurnal->id)->value('gudang_id'),
            'gudang_id harus ada di $fillable JurnalDetail, kalau tidak akan hilang diam-diam.'
        );
    }

    public function test_no_posted_inventory_line_is_left_without_a_warehouse(): void
    {
        $akunPersediaan = app(RekonsiliasiGudangService::class)->akunPersediaanIds();

        $tanpaGudang = DB::table('wms_jurnal_detail as jd')
            ->join('wms_jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->where('j.status', 'POSTED')
            ->whereIn('jd.coa_id', $akunPersediaan)
            ->whereNull('jd.gudang_id')
            ->count();

        $this->assertSame(
            0,
            $tanpaGudang,
            'Setiap baris jurnal yang menyentuh akun persediaan harus membawa dimensi gudang.'
        );
    }
}
