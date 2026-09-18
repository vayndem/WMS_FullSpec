<?php

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\LayerPersediaan;
use App\Models\StokGudang;
use App\Models\TransferGudang;
use App\Models\User;
use App\Services\StokGudangService;
use App\Services\TransferGudangService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

class AturanJenisGudangTest extends TestCase
{
    use DatabaseTransactions;

    private function gudang(string $jenis): Gudang
    {
        return Gudang::where('jenis', $jenis)->where('aktif', true)->firstOrFail();
    }

    private function stokTersedia(float $minimal = 2): StokGudang
    {
        return StokGudang::whereRaw('stok_tersedia - stok_direservasi >= ?', [$minimal])
            ->whereHas('gudang', fn ($q) => $q->where('jenis', Gudang::NORMAL))
            ->firstOrFail();
    }

    private function transfer(Gudang $asal, Gudang $tujuan, int $bahanId, float $jumlah = 1): TransferGudang
    {
        $transfer = TransferGudang::create([
            'nomor_transfer' => 'TRF-UJI-' . random_int(10000, 99999),
            'tanggal' => today(),
            'gudang_asal_id' => $asal->id,
            'gudang_tujuan_id' => $tujuan->id,
            'status' => TransferGudang::DIAJUKAN,
            'dibuat_oleh' => User::factory()->create(['type' => User::ROLE_WAREHOUSE])->id,
        ]);

        $transfer->details()->create(['bahan_id' => $bahanId, 'jumlah' => $jumlah]);

        return $transfer->fresh('details');
    }

    public function test_the_damaged_warehouse_is_terminal_in_both_directions(): void
    {
        $stok = $this->stokTersedia();
        $normal = Gudang::findOrFail($stok->gudang_id);
        $rusak = $this->gudang(Gudang::RUSAK);
        $service = app(TransferGudangService::class);

        try {
            $service->konfirmasi($this->transfer($normal, $rusak, (int) $stok->bahan_id));
            $this->fail('Transfer langsung ke Gudang Rusak seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Consider', $e->getMessage());
        }

        app(StokGudangService::class)->masuk(
            (int) $rusak->id,
            (int) $stok->bahan_id,
            5,
            1000,
            'UJI_MASUK_RUSAK',
            'UJI',
            1,
            'uji'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak dapat ditransfer kembali');

        $service->konfirmasi($this->transfer($rusak, $normal, (int) $stok->bahan_id));
    }

    public function test_quarantined_stock_can_only_leave_through_inspection(): void
    {
        $stok = $this->stokTersedia();
        $normal = Gudang::findOrFail($stok->gudang_id);
        $consider = $this->gudang(Gudang::CONSIDER);

        app(StokGudangService::class)->masuk(
            (int) $consider->id,
            (int) $stok->bahan_id,
            5,
            1000,
            'UJI_MASUK_CONSIDER',
            'UJI',
            2,
            'uji'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pemeriksaan Consider');

        app(TransferGudangService::class)->konfirmasi(
            $this->transfer($consider, $normal, (int) $stok->bahan_id)
        );
    }

    public function test_stock_transferred_into_quarantine_lands_on_hold_not_available(): void
    {
        $stok = $this->stokTersedia(3);
        $normal = Gudang::findOrFail($stok->gudang_id);
        $consider = $this->gudang(Gudang::CONSIDER);
        $service = app(TransferGudangService::class);

        $transfer = $service->konfirmasi($this->transfer($normal, $consider, (int) $stok->bahan_id, 2));
        $service->terima($transfer);

        $tersedia = LayerPersediaan::where('gudang_id', $consider->id)
            ->where('bahan_id', $stok->bahan_id)
            ->where('stock_status', 'AVAILABLE')
            ->where('remaining_quantity', '>', 0)
            ->exists();

        $ditahan = LayerPersediaan::where('gudang_id', $consider->id)
            ->where('bahan_id', $stok->bahan_id)
            ->where('stock_status', 'QC_HOLD')
            ->where('remaining_quantity', '>', 0)
            ->exists();

        $this->assertTrue($ditahan, 'Stok yang masuk gudang Consider wajib berstatus QC_HOLD.');
        $this->assertFalse($tersedia, 'Stok karantina tidak boleh berstatus AVAILABLE.');
    }

    public function test_the_warehouse_flags_match_the_meaning_of_each_warehouse_type(): void
    {
        foreach (Gudang::where('aktif', true)->get() as $gudang) {
            if ($gudang->jenis === Gudang::RUSAK) {
                $this->assertFalse((bool) $gudang->boleh_transfer, "{$gudang->kode}: gudang Rusak bersifat terminal, transfer harus dimatikan.");
                $this->assertFalse((bool) $gudang->boleh_npk, "{$gudang->kode}: barang rusak tidak boleh dipakai produksi.");
                $this->assertFalse((bool) $gudang->boleh_penerimaan, "{$gudang->kode}: barang rusak tidak diterima langsung dari supplier.");
            }

            if ($gudang->jenis === Gudang::CONSIDER) {
                $this->assertFalse((bool) $gudang->boleh_npk, "{$gudang->kode}: stok karantina tidak boleh dipakai sebelum diperiksa.");
                $this->assertFalse((bool) $gudang->boleh_penerimaan, "{$gudang->kode}: karantina diisi lewat transfer/inspeksi, bukan penerimaan langsung.");
            }

            $this->assertTrue((bool) $gudang->boleh_opname, "{$gudang->kode}: setiap gudang aktif wajib bisa diopname, termasuk Consider dan Rusak.");
        }
    }

    public function test_an_operator_cannot_issue_material_out_of_quarantine_or_damage(): void
    {
        $operator = User::factory()->create(['type' => User::ROLE_WAREHOUSE, 'is_active' => true]);

        foreach ([Gudang::CONSIDER, Gudang::RUSAK] as $jenis) {
            $gudang = $this->gudang($jenis);

            $this->assertFalse(
                $operator->canAccessGudang((int) $gudang->id, 'npk'),
                "{$gudang->kode}: pemakaian barang dari gudang {$jenis} harus tertutup."
            );

            $this->assertFalse(
                $operator->canAccessGudang((int) $gudang->id, 'receive'),
                "{$gudang->kode}: penerimaan langsung ke gudang {$jenis} harus tertutup."
            );
        }
    }
}
