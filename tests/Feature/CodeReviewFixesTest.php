<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\Bahan;
use App\Models\BaganAkun;
use App\Models\BiayaTambahan;
use App\Models\DataPesanan;
use App\Models\FakturPenjualan;
use App\Models\Gudang;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\LayerPersediaan;
use App\Models\Pelanggan;
use App\Models\PemakaianBarang;
use App\Models\PenerimaanBarang;
use App\Models\PesananPenjualan;
use App\Models\StokGudang;
use App\Models\SuratJalan;
use App\Models\TransferGudang;
use App\Models\User;
use App\Services\DataPesananService;
use App\Services\FakturPenjualanService;
use App\Services\LandedCostService;
use App\Services\PenjualanService;
use App\Services\RekonsiliasiGudangService;
use App\Services\SupplierScorecardService;
use App\Services\TransferGudangService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CodeReviewFixesTest extends TestCase
{
    use DatabaseTransactions;

    private function stok(float $minimal = 5): StokGudang
    {
        return StokGudang::whereRaw('stok_tersedia - stok_direservasi >= ?', [$minimal])
            ->whereHas('gudang', fn ($q) => $q->where('jenis', Gudang::NORMAL))
            ->firstOrFail();
    }

    private function pelanggan(): Pelanggan
    {
        return Pelanggan::create([
            'kode' => 'CUST-RV-' . random_int(1000, 9999),
            'nama' => 'PT Uji Review',
            'termin_hari' => 30,
            'is_active' => true,
        ]);
    }

    private function pesanan(User $user, StokGudang $stok, float $jumlah = 10, float $harga = 90000, bool $dariStok = false): PesananPenjualan
    {
        $bahanId = $dariStok
            ? $stok->bahan_id
            : Bahan::whereKeyNot($stok->bahan_id)->firstOrFail()->id;

        return app(PenjualanService::class)->buatPesanan([
            'tanggal' => today()->toDateString(),
            'pelanggan_id' => $this->pelanggan()->id,
            'gudang_id' => $stok->gudang_id,
            'is_ppn' => true,
            'tarif_ppn' => 11,
            'details' => [['bahan_id' => $bahanId, 'jumlah' => $jumlah, 'harga_satuan' => $harga]],
        ], $user);
    }

    public function test_an_npk_can_be_tagged_to_a_work_order_through_http_and_the_cost_lands_in_wip(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        $stok = $this->stok();
        $bahan = Bahan::findOrFail($stok->bahan_id);

        $so = $this->pesanan($admin, $stok);
        $wo = app(DataPesananService::class)->rilis(
            app(DataPesananService::class)->buat($so->details->first(), [
                'tanggal' => today()->toDateString(),
                'gudang_id' => $stok->gudang_id,
                'jumlah_rencana' => 10,
            ], $admin)
        );

        $this->actingAs($admin)->post(route('pemakaian-barang.store'), [
            'kode' => 'NPK' . str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'kode_datapesanan' => $wo->nomor,
            'data_pesanan_id' => $wo->id,
            'tanggal' => today()->toDateString(),
            'id_barang' => $bahan->id,
            'id_gudang_asal' => $stok->gudang_id,
            'jumlah' => 2,
            'status' => 'POSTED',
        ])->assertSuccessful();

        $wo = $wo->fresh();
        $this->assertGreaterThan(
            0,
            $wo->totalBiaya(),
            'NPK yang dikirim lewat HTTP dengan data_pesanan_id harus menyerap biaya ke perintah kerja.'
        );

        $npk = PemakaianBarang::latest('id')->firstOrFail();
        $this->assertSame((int) $wo->id, (int) $npk->data_pesanan_id);

        $wipAkun = AccountingSetting::accountId(AccountingSetting::BARANG_DALAM_PROSES);
        $jurnal = Jurnal::where('sumber_transaksi', 'NPK')->where('reff_id', $npk->id)->firstOrFail();
        $this->assertTrue(
            JurnalDetail::where('jurnal_id', $jurnal->id)->where('coa_id', $wipAkun)->where('debit', '>', 0)->exists()
        );

        $selesai = app(DataPesananService::class)->selesaikan($wo, 10, $admin);
        $this->assertSame(DataPesanan::SELESAI, $selesai->status);
    }

    public function test_an_npk_cannot_be_charged_to_a_work_order_in_another_warehouse(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        $stok = $this->stok();
        $lain = Gudang::where('jenis', Gudang::NORMAL)->whereKeyNot($stok->gudang_id)->firstOrFail();

        $so = $this->pesanan($admin, $stok);
        $wo = app(DataPesananService::class)->rilis(
            app(DataPesananService::class)->buat($so->details->first(), [
                'tanggal' => today()->toDateString(),
                'gudang_id' => $stok->gudang_id,
                'jumlah_rencana' => 10,
            ], $admin)
        );

        $this->actingAs($admin)->post(route('pemakaian-barang.store'), [
            'kode' => 'NPK' . str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'data_pesanan_id' => $wo->id,
            'tanggal' => today()->toDateString(),
            'id_barang' => $stok->bahan_id,
            'id_gudang_asal' => $lain->id,
            'jumlah' => 1,
            'status' => 'DRAFT',
        ])->assertSessionHasErrors('data_pesanan_id');
    }

    public function test_an_accounting_manager_can_reach_and_approve_a_pending_manual_journal(): void
    {
        $akuntan = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $manajer = User::factory()->create(['type' => User::ROLE_ACCOUNTING_MANAGER]);

        $kas = BaganAkun::where('is_cash_bank', true)->where('is_postable', true)->firstOrFail();
        $lawan = BaganAkun::where('is_postable', true)->whereKeyNot($kas->id)->firstOrFail();

        $this->actingAs($akuntan)->postJson(route('jurnal.store'), [
            'no_jurnal' => '26-09-RV-IX-' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT),
            'tanggal' => today()->toDateString(),
            'details' => [
                ['coa_id' => $kas->id, 'debit' => 50000, 'kredit' => 0],
                ['coa_id' => $lawan->id, 'debit' => 0, 'kredit' => 50000],
            ],
        ])->assertCreated();

        $jurnal = Jurnal::where('sumber_transaksi', 'MANUAL')->latest('id')->firstOrFail();
        $this->actingAs($akuntan)->postJson(route('jurnal.post', $jurnal->id))->assertOk();

        $this->actingAs($manajer)->get(route('jurnal.index'))
            ->assertOk();

        $this->actingAs($manajer)->postJson(route('jurnal.approve', $jurnal->id))->assertOk();
        $this->assertSame('POSTED', $jurnal->fresh()->status);
    }

    public function test_a_return_on_an_uninvoiced_delivery_touches_no_receivable(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($admin);

        $stok = $this->stok();
        $so = $this->pesanan($admin, $stok);
        $penjualan = app(PenjualanService::class);

        $wo = app(DataPesananService::class)->rilis(
            app(DataPesananService::class)->buat($so->details->first(), [
                'tanggal' => today()->toDateString(), 'gudang_id' => $stok->gudang_id, 'jumlah_rencana' => 10,
            ], $admin)
        );
        $this->bebankanNpk($wo, $stok, $admin);
        app(DataPesananService::class)->selesaikan($wo->fresh(), 10, $admin);

        $sj = $penjualan->postingSuratJalan(
            $penjualan->buatSuratJalan($so->fresh('details'), [
                'tanggal' => today()->toDateString(),
                'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 10]],
            ], $admin),
            $admin
        );

        app(\App\Services\ReturPenjualanService::class)->buat($sj, [
            'tanggal' => today()->toDateString(),
            'alasan' => 'Pelanggan menolak sebagian barang.',
            'details' => [['surat_jalan_detail_id' => $sj->details->first()->id, 'jumlah' => 2]],
        ], $admin);

        $retur = \App\Models\ReturPenjualan::latest('id')->firstOrFail();
        $jurnal = Jurnal::where('sumber_transaksi', 'RETUR_PENJUALAN')->where('reff_id', $retur->id)->firstOrFail();
        $piutang = AccountingSetting::accountId(AccountingSetting::PIUTANG_USAHA);

        $this->assertFalse(
            JurnalDetail::where('jurnal_id', $jurnal->id)->where('coa_id', $piutang)->exists(),
            'Retur atas pengiriman yang belum difakturkan tidak boleh menyentuh piutang.'
        );
        $this->assertEqualsWithDelta((float) $jurnal->total_debit, (float) $jurnal->total_kredit, 0.01);
    }

    private function bebankanNpk(DataPesanan $wo, StokGudang $stok, User $user, float $jumlah = 4): PemakaianBarang
    {
        $bahan = Bahan::findOrFail($stok->bahan_id);

        $npk = PemakaianBarang::create([
            'kode' => 'NPK-RV-' . random_int(10000, 99999),
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

        app(\App\Services\WmsAccountingService::class)->consumeStock($npk);
        app(\App\Services\StokGudangService::class)->keluar(
            (int) $stok->gudang_id, (int) $bahan->id, (float) $npk->jumlah_stok,
            (float) $npk->fresh()->harga_satuan, 'PENGELUARAN', 'NPK', $npk->id, $npk->kode
        );
        app(\App\Services\WmsAccountingService::class)->postNpk($npk->fresh());

        return $npk->fresh();
    }

    public function test_a_second_draft_delivery_cannot_claim_quantity_the_first_already_holds(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        Auth::login($admin);

        $stok = $this->stok();
        $so = $this->pesanan($admin, $stok, 4);
        $penjualan = app(PenjualanService::class);

        $penjualan->buatSuratJalan($so->fresh('details'), [
            'tanggal' => today()->toDateString(),
            'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 4]],
        ], $admin);

        $this->expectException(RuntimeException::class);
        $penjualan->buatSuratJalan($so->fresh('details'), [
            'tanggal' => today()->toDateString(),
            'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 4]],
        ], $admin);
    }

    public function test_one_submission_cannot_repeat_the_same_order_line(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        $stok = $this->stok();
        $so = $this->pesanan($admin, $stok, 4);
        $baris = $so->details->first()->id;

        $this->actingAs($admin)->post(route('pesanan-penjualan.kirim', $so), [
            'tanggal' => today()->toDateString(),
            'details' => [
                ['pesanan_penjualan_detail_id' => $baris, 'jumlah' => 2],
                ['pesanan_penjualan_detail_id' => $baris, 'jumlah' => 2],
            ],
        ])->assertSessionHasErrors('details.1.pesanan_penjualan_detail_id');
    }

    public function test_a_landed_cost_journal_carries_the_warehouse_of_the_layers_it_capitalises(): void
    {
        $akuntan = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($akuntan);

        $layer = LayerPersediaan::where('stock_status', 'AVAILABLE')
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('gudang_id')
            ->firstOrFail();

        $lawan = BaganAkun::where('kategori_akun', 'LIABILITAS')->where('posisi_normal', 'KREDIT')
            ->where('is_postable', true)->where('is_active', true)->firstOrFail();

        $cost = BiayaTambahan::create([
            'number' => 'LC-RV-' . random_int(1000, 9999),
            'date' => today()->toDateString(),
            'description' => 'Uji dimensi gudang landed cost',
            'total_amount' => 10000,
            'credit_coa_id' => $lawan->id,
            'allocation_basis' => 'VALUE',
            'status' => 'DRAFT',
            'created_by' => $akuntan->id,
        ]);

        app(LandedCostService::class)->allocate($cost, [$layer->id]);
        $jurnal = app(LandedCostService::class)->post($cost->fresh());

        $baris = JurnalDetail::where('jurnal_id', $jurnal->id)->where('debit', '>', 0)->get();
        $this->assertGreaterThan(0, $baris->count());
        $this->assertTrue(
            $baris->every(fn ($d) => (int) $d->gudang_id === (int) $layer->gudang_id),
            'Baris debit landed cost harus membawa gudang layer yang dikapitalisasi.'
        );
    }

    public function test_a_reversing_journal_keeps_the_warehouse_dimension_of_the_original(): void
    {
        $akuntan = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($akuntan);

        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)->whereNull('data_pesanan_id')->firstOrFail();
        $asli = Jurnal::where('sumber_transaksi', 'NPK')->where('reff_id', $npk->id)->firstOrFail();
        $gudangAsli = JurnalDetail::where('jurnal_id', $asli->id)->value('gudang_id');
        $this->assertNotNull($gudangAsli);

        app(\App\Services\InventoryReversalService::class)->reverseNpk($npk, 'Uji dimensi gudang pembalikan.');

        $pembalikan = Jurnal::where('reversal_of_id', $asli->id)->firstOrFail();
        $baris = JurnalDetail::where('jurnal_id', $pembalikan->id)->get();

        $this->assertGreaterThan(0, $baris->count());
        $this->assertTrue(
            $baris->every(fn ($d) => (int) $d->gudang_id === (int) $gudangAsli),
            'Jurnal pembalikan harus membawa dimensi gudang yang sama dengan jurnal aslinya.'
        );
    }

    public function test_reusing_a_soft_deleted_customer_code_is_rejected_by_validation_not_by_the_database(): void
    {
        $akuntan = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $pelanggan = $this->pelanggan();
        $kode = $pelanggan->kode;
        $pelanggan->delete();

        $this->actingAs($akuntan)->post(route('pelanggan.store'), [
            'kode' => $kode,
            'nama' => 'Pelanggan Baru',
            'termin_hari' => 30,
        ])->assertSessionHasErrors('kode');
    }

    public function test_a_warehouse_operator_sees_the_delivery_menu_they_are_responsible_for(): void
    {
        $gudang = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);

        $this->actingAs($gudang)->get(route('surat-jalan.index'))
            ->assertOk()
            ->assertSee(route('surat-jalan.index'), false);
    }

    public function test_a_mistaken_draft_invoice_can_be_deleted_so_the_delivery_can_be_invoiced_again(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        $akuntan = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        Auth::login($admin);

        $stok = $this->stok();
        $so = $this->pesanan($admin, $stok, 3, 90000, true);
        $penjualan = app(PenjualanService::class);

        $sj = $penjualan->postingSuratJalan(
            $penjualan->buatSuratJalan($so->fresh('details'), [
                'tanggal' => today()->toDateString(),
                'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 3]],
            ], $admin),
            $admin
        );

        $service = app(FakturPenjualanService::class);
        $draft = $service->buatDariSuratJalan($sj, ['tanggal' => today()->toDateString()], $akuntan);

        $this->actingAs($akuntan)->delete(route('faktur-penjualan.destroy', $draft))->assertRedirect();
        $this->assertNull(FakturPenjualan::find($draft->id));

        $ulang = $service->buatDariSuratJalan($sj->fresh('details'), [
            'tanggal' => today()->toDateString(),
            'no_faktur_pajak' => '010.000-26.00000077',
        ], $akuntan);

        $this->assertEqualsWithDelta(3, (float) $ulang->details->sum('jumlah'), 0.000001);
    }

    public function test_lead_time_is_not_weighted_by_how_many_lines_a_receipt_has(): void
    {
        $service = app(SupplierScorecardService::class);
        $sebelum = $service->ringkasan(today()->subYears(5), today())['baris']->first();

        $lpb = PenerimaanBarang::where('document_type', 'GOODS')
            ->where('status', PenerimaanBarang::POSTED)
            ->whereNotNull('no_po')
            ->firstOrFail();
        $contoh = DB::table('wms_penerimaan_barang_detail')->where('id_lpb', $lpb->id_lpb)->first();
        $this->assertNotNull($contoh);

        foreach (range(1, 3) as $i) {
            DB::table('wms_penerimaan_barang_detail')->insert([
                'id_lpb' => $contoh->id_lpb,
                'id_bahan' => $contoh->id_bahan,
                'id_kategori' => $contoh->id_kategori,
                'jumlah_barang_diterima' => 0,
                'harga' => 0,
                'jumlah_tersisa' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sesudah = $service->ringkasan(today()->subYears(5), today())['baris']->first();

        $this->assertSame(
            $sebelum['penerimaan'],
            $sesudah['penerimaan'],
            'Menambah baris pada satu LPB tidak boleh menambah hitungan penerimaan.'
        );
        $this->assertEqualsWithDelta(
            $sebelum['lead_time_rata'],
            $sesudah['lead_time_rata'],
            0.01,
            'Lead time rata-rata tidak boleh berubah hanya karena satu LPB punya lebih banyak baris.'
        );
    }

    public function test_a_transfer_in_flight_does_not_make_the_warehouse_reconciliation_red(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        Auth::login($user);

        $service = app(RekonsiliasiGudangService::class);
        foreach ($service->nilaiPerGudang() as $baris) {
            $this->assertEqualsWithDelta(0.0, $baris['selisih'], 0.01);
        }

        $sumber = $this->stok(1);
        $tujuan = Gudang::where('jenis', Gudang::NORMAL)->whereKeyNot($sumber->gudang_id)->firstOrFail();

        $transfer = TransferGudang::create([
            'nomor_transfer' => 'TRF-RV-' . random_int(1000, 9999),
            'tanggal' => today(),
            'gudang_asal_id' => $sumber->gudang_id,
            'gudang_tujuan_id' => $tujuan->id,
            'status' => TransferGudang::DIAJUKAN,
            'dibuat_oleh' => $user->id,
        ]);
        $transfer->details()->create(['bahan_id' => $sumber->bahan_id, 'jumlah' => 1]);

        app(TransferGudangService::class)->konfirmasi($transfer);
        $this->assertSame(TransferGudang::DIKIRIM, $transfer->fresh()->status);

        foreach ($service->nilaiPerGudang() as $baris) {
            $this->assertEqualsWithDelta(
                0.0,
                $baris['selisih'],
                0.01,
                "Transfer yang masih di perjalanan membuat gudang {$baris['gudang']} tampak tidak cocok."
            );
        }
    }
}
