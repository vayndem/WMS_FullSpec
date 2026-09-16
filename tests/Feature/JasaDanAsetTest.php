<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\Aset;
use App\Models\BarangTitipan;
use App\Models\FakturPembelian;
use App\Models\KapitalisasiAset;
use App\Models\KategoriJasa;
use App\Models\PelepasanAset;
use App\Models\PembayaranFaktur;
use App\Models\PengeluaranBarang;
use App\Models\PengeluaranBarangDetail;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AsetAccountingService;
use App\Services\KapitalisasiAsetService;
use App\Services\PengeluaranBarangService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Tests\TestCase;

class JasaDanAsetTest extends TestCase
{
    use DatabaseTransactions;

    private function asetAktif(): Aset
    {
        return Aset::where('status', 'ACTIVE')->whereHas('category')->firstOrFail();
    }

    private function akuntansi(): User
    {
        return User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
    }

    public function test_output_vat_account_is_mapped_and_usable(): void
    {
        $id = AccountingSetting::accountId(AccountingSetting::PPN_KELUARAN);

        $akun = \App\Models\BaganAkun::findOrFail($id);
        $this->assertSame('LIABILITAS', $akun->kategori_akun);
        $this->assertSame('KREDIT', $akun->posisi_normal);
        $this->assertTrue($akun->is_postable && $akun->is_active);
    }

    public function test_trade_in_measures_gain_against_fair_value_and_creates_a_supplier_advance(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]));

        $aset = $this->asetAktif();
        $supplier = Supplier::firstOrFail();
        $nilaiBuku = round((float) $aset->book_value, 2);
        $perolehan = round((float) $aset->acquisition_cost, 2);
        $dpp = 1_000_000.0;
        $ppn = 110_000.0;

        $pelepasan = app(AsetAccountingService::class)->dispose($aset, [
            'disposal_date' => today()->toDateString(),
            'disposal_type' => PelepasanAset::TRADE_IN,
            'proceeds' => $dpp,
            'ppn_keluaran' => $ppn,
            'supplier_id' => $supplier->id,
            'reason' => 'Tukar tambah unit baru',
        ]);

        $this->assertSame(PelepasanAset::TRADE_IN, $pelepasan->disposal_type);
        $this->assertEqualsWithDelta($dpp, (float) $pelepasan->dpp_ppn_keluaran, 0.01);
        $this->assertEqualsWithDelta($ppn, (float) $pelepasan->ppn_keluaran, 0.01);

        $this->assertEqualsWithDelta(max($dpp - $nilaiBuku, 0), (float) $pelepasan->gain_amount, 0.01,
            'Laba/rugi tukar tambah diukur terhadap DPP saja, bukan DPP + PPN.');
        $this->assertEqualsWithDelta(max($nilaiBuku - $dpp, 0), (float) $pelepasan->loss_amount, 0.01);

        $baris = $pelepasan->journal->details;
        $this->assertEqualsWithDelta((float) $baris->sum('debit'), (float) $baris->sum('kredit'), 0.01);
        $this->assertEqualsWithDelta($perolehan, (float) $baris->sum('kredit') - $ppn, 0.01,
            'Harga perolehan aset harus dihapus penuh di sisi kredit.');

        $uangMuka = $pelepasan->uangMuka;
        $this->assertNotNull($uangMuka, 'Tukar tambah harus menghasilkan uang muka supplier.');
        $this->assertNull($uangMuka->coa_kas_bank_id, 'Tidak ada kas yang benar-benar keluar pada tukar tambah.');
        $this->assertSame($supplier->id, (int) $uangMuka->supplier_id);
        $this->assertEqualsWithDelta($dpp + $ppn, (float) $uangMuka->kelebihan_pembayaran, 0.01);
        $this->assertEqualsWithDelta($dpp + $ppn, $uangMuka->sisaUangMuka(), 0.01);

        $this->assertSame('TRADED_IN', $aset->fresh()->status);
    }

    public function test_trade_in_advance_is_offered_for_the_supplier_next_purchase(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]));

        $supplier = Supplier::firstOrFail();
        $pelepasan = app(AsetAccountingService::class)->dispose($this->asetAktif(), [
            'disposal_date' => today()->toDateString(),
            'disposal_type' => PelepasanAset::TRADE_IN,
            'proceeds' => 500_000,
            'ppn_keluaran' => 55_000,
            'supplier_id' => $supplier->id,
            'reason' => 'Tukar tambah',
        ]);

        $this->actingAs(User::factory()->create(['type' => User::ROLE_FINANCE]))
            ->getJson(route('pembayaran-faktur.available-advances', $supplier->id))
            ->assertOk()
            ->assertJsonFragment(['payment_number' => $pelepasan->uangMuka->payment_number]);
    }

    public function test_trade_in_requires_a_supplier_and_a_fair_value(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]));

        $this->expectException(RuntimeException::class);
        app(AsetAccountingService::class)->dispose($this->asetAktif(), [
            'disposal_date' => today()->toDateString(),
            'disposal_type' => PelepasanAset::TRADE_IN,
            'proceeds' => 1_000_000,
            'ppn_keluaran' => 0,
            'reason' => 'Tanpa supplier',
        ]);
    }

    public function test_capitalized_service_increases_the_asset_carrying_amount_exactly_once(): void
    {
        Auth::login($this->akuntansi());

        $aset = $this->asetAktif();
        $sebelum = round((float) $aset->acquisition_cost, 2);
        $umurSebelum = (int) $aset->useful_life_months;

        $kategori = KategoriJasa::firstOrFail();
        $kategori->update(['perlakuan' => KategoriJasa::KAPITALISASI]);

        $poDetail = \App\Models\PesananJasaDetail::create([
            'pembelian_id' => \App\Models\PesananPembelian::firstOrFail()->id,
            'service_category_id' => $kategori->id,
            'aset_id' => $aset->id,
            'tambahan_umur_bulan' => 12,
            'service_type' => $kategori->code,
            'description' => 'Overhaul yang memperpanjang masa manfaat',
            'quantity' => 1,
            'unit' => 'JOB',
            'unit_price' => 2_000_000,
            'subtotal' => 2_000_000,
        ]);

        $bapDetail = \App\Models\PenerimaanJasaDetail::create([
            'lpb_id' => \App\Models\PenerimaanBarang::firstOrFail()->id,
            'service_po_detail_id' => $poDetail->id,
            'progress_percent' => 100,
            'amount' => 2_000_000,
        ]);

        $service = app(KapitalisasiAsetService::class);
        $catatan = $service->catatDariJasa($bapDetail, null, today()->toDateString());

        $this->assertNotNull($catatan);
        $this->assertEqualsWithDelta($sebelum + 2_000_000, (float) $aset->fresh()->acquisition_cost, 0.01);
        $this->assertEqualsWithDelta($sebelum, (float) $catatan->nilai_sebelum, 0.01);
        $this->assertSame($umurSebelum + 12, (int) $aset->fresh()->useful_life_months,
            'Tambahan umur manfaat harus menambah useful_life_months.');

        $this->assertNull($service->catatDariJasa($bapDetail, null, today()->toDateString()),
            'Kapitalisasi dari sumber yang sama tidak boleh terjadi dua kali.');
        $this->assertSame(1, KapitalisasiAset::where('sumber_id', $bapDetail->id)
            ->where('sumber_type', KapitalisasiAsetService::SUMBER_JASA)->count());
    }

    public function test_trade_in_advance_can_actually_be_spent_on_a_later_invoice(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]));

        $supplier = Supplier::create([
            'nama' => 'Supplier Tukar Tambah', 'alamat' => 'Jl. Barter', 'telp' => '0811000111', 'pembayaran' => 'Transfer',
        ]);

        $pelepasan = app(AsetAccountingService::class)->dispose($this->asetAktif(), [
            'disposal_date' => today()->toDateString(),
            'disposal_type' => PelepasanAset::TRADE_IN,
            'proceeds' => 1000000,
            'ppn_keluaran' => 110000,
            'supplier_id' => $supplier->id,
            'reason' => 'Tukar tambah unit baru',
        ]);

        $uangMuka = $pelepasan->uangMuka;
        $this->assertNull($uangMuka->invoice_lpb_id, 'Uang muka tukar tambah memang lahir tanpa invoice.');

        $invoice = FakturPembelian::create([
            'no_invoice' => 'TRADEIN-INV-' . now()->format('His'),
            'kode_supplier' => $supplier->id,
            'tanggal' => today(),
            'grand_total' => 2000000,
            'sisa_tagihan' => 2000000,
            'status' => FakturPembelian::UNPAID,
        ]);

        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $kas = \App\Models\BaganAkun::where('kode_akun', '1101')->firstOrFail();

        $respons = $this->actingAs($finance)->postJson(route('pembayaran-faktur.store'), [
            'payment_number' => app(\App\Services\DocumentNumberService::class)->financial('PY'),
            'invoice_lpb_id' => $invoice->id,
            'tanggal_pembayaran' => today()->toDateString(),
            'metode_pembayaran' => 'Transfer BCA',
            'coa_kas_bank_id' => $kas->id,
            'jumlah_pembayaran' => 890000,
            'uang_muka_sumber_payment_id' => $uangMuka->id,
            'uang_muka_dipakai' => 1110000,
        ]);

        $respons->assertCreated();

        $this->assertEqualsWithDelta(0.0, $uangMuka->fresh()->sisaUangMuka(), 0.01,
            'Uang muka tukar tambah harus benar-benar terpakai, bukan sekadar tampil di daftar.');
        $this->assertSame(FakturPembelian::PAID, $invoice->fresh()->status);

        $pembayaran = PembayaranFaktur::findOrFail($respons->json('data.id'));
        $this->assertSame($supplier->id, (int) $pembayaran->supplier_id,
            'Pembayaran baru wajib mencatat supplier_id, bukan mengandalkan invoice saja.');
    }

    public function test_two_capitalized_lines_on_one_asset_raise_it_by_their_sum(): void
    {
        Auth::login($this->akuntansi());

        $aset = $this->asetAktif();
        $sebelum = round((float) $aset->acquisition_cost, 2);
        $kategori = KategoriJasa::firstOrFail();
        $kategori->update(['perlakuan' => KategoriJasa::KAPITALISASI]);

        $service = app(KapitalisasiAsetService::class);
        $lpb = \App\Models\PenerimaanBarang::firstOrFail();

        foreach ([300000, 700000] as $nilai) {
            $poDetail = \App\Models\PesananJasaDetail::create([
                'pembelian_id' => \App\Models\PesananPembelian::firstOrFail()->id,
                'service_category_id' => $kategori->id,
                'aset_id' => $aset->id,
                'service_type' => $kategori->code,
                'description' => 'Baris kapitalisasi ' . $nilai,
                'quantity' => 1, 'unit' => 'JOB', 'unit_price' => $nilai, 'subtotal' => $nilai,
            ]);

            $bapDetail = \App\Models\PenerimaanJasaDetail::create([
                'lpb_id' => $lpb->id,
                'service_po_detail_id' => $poDetail->id,
                'progress_percent' => 100,
                'amount' => $nilai,
            ]);

            $service->catatDariJasa($bapDetail, null, today()->toDateString());
        }

        $this->assertEqualsWithDelta($sebelum + 1000000, (float) $aset->fresh()->acquisition_cost, 0.01,
            'Dua baris kapitalisasi ke aset yang sama harus menaikkan nilai perolehan sebesar jumlah keduanya.');
    }

    public function test_life_extension_is_applied_once_across_progress_receipts(): void
    {
        Auth::login($this->akuntansi());

        $aset = $this->asetAktif();
        $umurSebelum = (int) $aset->useful_life_months;
        $kategori = KategoriJasa::firstOrFail();
        $kategori->update(['perlakuan' => KategoriJasa::KAPITALISASI]);

        $poDetail = \App\Models\PesananJasaDetail::create([
            'pembelian_id' => \App\Models\PesananPembelian::firstOrFail()->id,
            'service_category_id' => $kategori->id,
            'aset_id' => $aset->id,
            'tambahan_umur_bulan' => 12,
            'service_type' => $kategori->code,
            'description' => 'Overhaul dua termin',
            'quantity' => 1, 'unit' => 'JOB', 'unit_price' => 2000000, 'subtotal' => 2000000,
        ]);

        $service = app(KapitalisasiAsetService::class);
        $lpb = \App\Models\PenerimaanBarang::firstOrFail();

        foreach ([50, 50] as $persen) {
            $bapDetail = \App\Models\PenerimaanJasaDetail::create([
                'lpb_id' => $lpb->id,
                'service_po_detail_id' => $poDetail->id,
                'progress_percent' => $persen,
                'amount' => 1000000,
            ]);

            $service->catatDariJasa($bapDetail, null, today()->toDateString());
        }

        $this->assertSame($umurSebelum + 12, (int) $aset->fresh()->useful_life_months,
            'Tambahan umur milik baris PO, jadi dua termin penerimaan tidak boleh menambah 24 bulan.');
    }

    public function test_capitalization_is_unwound_when_reversed(): void
    {
        Auth::login($this->akuntansi());

        $aset = $this->asetAktif();
        $sebelum = round((float) $aset->acquisition_cost, 2);
        $umurSebelum = (int) $aset->useful_life_months;
        $kategori = KategoriJasa::firstOrFail();
        $kategori->update(['perlakuan' => KategoriJasa::KAPITALISASI]);

        $poDetail = \App\Models\PesananJasaDetail::create([
            'pembelian_id' => \App\Models\PesananPembelian::firstOrFail()->id,
            'service_category_id' => $kategori->id,
            'aset_id' => $aset->id,
            'tambahan_umur_bulan' => 6,
            'service_type' => $kategori->code,
            'description' => 'Overhaul yang kemudian dibatalkan',
            'quantity' => 1, 'unit' => 'JOB', 'unit_price' => 1500000, 'subtotal' => 1500000,
        ]);

        $bapDetail = \App\Models\PenerimaanJasaDetail::create([
            'lpb_id' => \App\Models\PenerimaanBarang::firstOrFail()->id,
            'service_po_detail_id' => $poDetail->id,
            'progress_percent' => 100,
            'amount' => 1500000,
        ]);

        $service = app(KapitalisasiAsetService::class);
        $service->catatDariJasa($bapDetail, null, today()->toDateString());

        $this->assertEqualsWithDelta($sebelum + 1500000, (float) $aset->fresh()->acquisition_cost, 0.01);

        $this->assertTrue($service->batalkanDariJasa($bapDetail));

        $this->assertEqualsWithDelta($sebelum, (float) $aset->fresh()->acquisition_cost, 0.01,
            'Pembatalan harus mengembalikan nilai perolehan ke posisi semula.');
        $this->assertSame($umurSebelum, (int) $aset->fresh()->useful_life_months,
            'Tambahan umur juga harus ditarik kembali.');
        $this->assertSame(0, KapitalisasiAset::where('sumber_id', $bapDetail->id)->count());
        $this->assertFalse($service->batalkanDariJasa($bapDetail), 'Pembatalan kedua tidak boleh berefek lagi.');
    }

    public function test_gate_pass_already_at_the_vendor_cannot_be_cancelled(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_WAREHOUSE]));

        $service = app(PengeluaranBarangService::class);
        $gatePass = $service->buat([
            'tanggal' => today()->toDateString(),
            'supplier_id' => Supplier::firstOrFail()->id,
            'keperluan' => 'PERBAIKAN',
            'estimasi_kembali' => today()->subDay()->toDateString(),
            'items' => [['deskripsi' => 'Mesin di vendor', 'jumlah' => 1]],
        ]);

        $service->kirim($gatePass);

        try {
            $service->batalkan($gatePass->fresh());
            $this->fail('Gate pass yang barangnya masih di vendor seharusnya tidak dapat dibatalkan.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('sudah keluar', $e->getMessage());
        }

        $this->assertSame(PengeluaranBarang::DI_VENDOR, $gatePass->fresh()->status);
        $this->assertTrue($service->terlambat()['keluar']->contains('id', $gatePass->id),
            'Gate pass terlambat harus tetap muncul di laporan, tidak bisa disembunyikan lewat pembatalan.');
    }

    public function test_gate_pass_return_tolerates_an_empty_date_and_validates_its_rows(): void
    {
        $operator = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        Auth::login($operator);

        $service = app(PengeluaranBarangService::class);
        $gatePass = $service->buat([
            'tanggal' => today()->toDateString(),
            'supplier_id' => Supplier::firstOrFail()->id,
            'keperluan' => 'KALIBRASI',
            'items' => [['deskripsi' => 'Alat ukur', 'jumlah' => 1]],
        ]);
        $service->kirim($gatePass);
        $baris = $gatePass->details->first();

        $this->actingAs($operator)
            ->post(route('pengeluaran-barang.terima', $gatePass), ['baris' => []])
            ->assertSessionHasErrors('baris');

        $this->actingAs($operator)->post(route('pengeluaran-barang.terima', $gatePass), [
            'tanggal_kembali' => '',
            'baris' => [$baris->id => ['status' => PengeluaranBarangDetail::KEMBALI]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(PengeluaranBarang::SELESAI, $gatePass->fresh()->status);
        $this->assertNotNull($baris->fresh()->tanggal_kembali);
    }

    public function test_capitalization_refuses_an_inactive_asset(): void
    {
        Auth::login($this->akuntansi());

        $aset = $this->asetAktif();
        $aset->update(['status' => 'DISPOSED']);

        $this->expectException(RuntimeException::class);
        app(KapitalisasiAsetService::class)->assertDapatDikapitalisasi($aset->fresh());
    }

    public function test_service_category_defaults_to_expense_treatment(): void
    {
        $this->assertSame(KategoriJasa::BEBAN, KategoriJasa::firstOrFail()->perlakuan,
            'Kategori jasa yang sudah ada harus tetap dibebankan, bukan tiba-tiba dikapitalisasi.');
    }

    public function test_gate_pass_tracks_goods_out_and_back_without_touching_the_ledger(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_WAREHOUSE]));

        $jurnalSebelum = \App\Models\Jurnal::count();
        $aset = $this->asetAktif();

        $service = app(PengeluaranBarangService::class);
        $gatePass = $service->buat([
            'tanggal' => today()->toDateString(),
            'supplier_id' => Supplier::firstOrFail()->id,
            'keperluan' => 'PERBAIKAN',
            'estimasi_kembali' => today()->addDays(7)->toDateString(),
            'items' => [
                ['aset_id' => $aset->id, 'deskripsi' => 'Mesin untuk overhaul', 'jumlah' => 1],
                ['deskripsi' => 'Kabel tambahan non-aset', 'jumlah' => 2],
            ],
        ]);

        $this->assertSame(PengeluaranBarang::DRAFT, $gatePass->status);
        $this->assertSame(2, $gatePass->details->count());

        $service->kirim($gatePass);
        $this->assertSame(PengeluaranBarang::DI_VENDOR, $gatePass->fresh()->status);

        $baris = $gatePass->details;
        $service->terimaKembali($gatePass->fresh(), [$baris[0]->id => ['status' => PengeluaranBarangDetail::KEMBALI]], today()->toDateString());
        $this->assertSame(PengeluaranBarang::SEBAGIAN_KEMBALI, $gatePass->fresh()->status);

        $service->terimaKembali($gatePass->fresh(), [$baris[1]->id => ['status' => PengeluaranBarangDetail::KEMBALI]], today()->toDateString());
        $this->assertSame(PengeluaranBarang::SELESAI, $gatePass->fresh()->status);

        $this->assertSame($jurnalSebelum, \App\Models\Jurnal::count(),
            'Gate pass adalah catatan kustodian: kepemilikan tidak berpindah, jadi tidak boleh ada jurnal.');
        $this->assertSame('ACTIVE', $aset->fresh()->status,
            'Aset yang sedang diperbaiki tetap aktif dan tetap disusutkan.');
    }

    public function test_gate_pass_refuses_shipping_an_empty_or_already_sent_document(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_WAREHOUSE]));

        $service = app(PengeluaranBarangService::class);
        $gatePass = $service->buat([
            'tanggal' => today()->toDateString(),
            'supplier_id' => Supplier::firstOrFail()->id,
            'keperluan' => 'KALIBRASI',
            'items' => [['deskripsi' => 'Alat ukur', 'jumlah' => 1]],
        ]);

        $service->kirim($gatePass);

        $this->expectException(RuntimeException::class);
        $service->kirim($gatePass->fresh());
    }

    public function test_vendor_loaner_is_a_custody_record_only(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_WAREHOUSE]));

        $jurnalSebelum = \App\Models\Jurnal::count();
        $layerSebelum = \App\Models\LayerPersediaan::count();
        $asetSebelum = Aset::count();

        $service = app(PengeluaranBarangService::class);
        $titipan = $service->terimaTitipan([
            'supplier_id' => Supplier::firstOrFail()->id,
            'deskripsi' => 'Unit pengganti selama perbaikan',
            'tanggal_terima' => today()->toDateString(),
            'estimasi_kembali' => today()->addDays(14)->toDateString(),
            'nilai_taksiran' => 5_000_000,
        ]);

        $this->assertSame(BarangTitipan::DITERIMA, $titipan->status);
        $this->assertSame($jurnalSebelum, \App\Models\Jurnal::count(), 'Barang titipan vendor tidak boleh membuat jurnal.');
        $this->assertSame($layerSebelum, \App\Models\LayerPersediaan::count(), 'Barang titipan vendor tidak boleh masuk persediaan.');
        $this->assertSame($asetSebelum, Aset::count(), 'Barang titipan vendor tidak boleh menjadi aset kita.');

        $service->selesaikanTitipan($titipan, BarangTitipan::DIKEMBALIKAN, today()->toDateString());
        $this->assertSame(BarangTitipan::DIKEMBALIKAN, $titipan->fresh()->status);

        $this->expectException(RuntimeException::class);
        $service->selesaikanTitipan($titipan->fresh(), BarangTitipan::DIKEMBALIKAN, today()->toDateString());
    }

    public function test_overdue_gate_pass_and_loaner_are_reported(): void
    {
        Auth::login(User::factory()->create(['type' => User::ROLE_WAREHOUSE]));

        $service = app(PengeluaranBarangService::class);
        $gatePass = $service->buat([
            'tanggal' => today()->subDays(30)->toDateString(),
            'supplier_id' => Supplier::firstOrFail()->id,
            'keperluan' => 'PERBAIKAN',
            'estimasi_kembali' => today()->subDays(10)->toDateString(),
            'items' => [['deskripsi' => 'Mesin terlambat', 'jumlah' => 1]],
        ]);
        $service->kirim($gatePass);

        $titipan = $service->terimaTitipan([
            'supplier_id' => Supplier::firstOrFail()->id,
            'deskripsi' => 'Loaner terlambat',
            'tanggal_terima' => today()->subDays(30)->toDateString(),
            'estimasi_kembali' => today()->subDays(5)->toDateString(),
        ]);

        $terlambat = $service->terlambat();

        $this->assertTrue($terlambat['keluar']->contains('id', $gatePass->id));
        $this->assertTrue($terlambat['titipan']->contains('id', $titipan->id));
        $this->assertTrue($gatePass->fresh()->terlambat());
        $this->assertTrue($titipan->fresh()->terlambat());
    }
}
