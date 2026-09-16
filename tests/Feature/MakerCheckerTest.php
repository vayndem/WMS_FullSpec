<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\BaganAkun;
use App\Models\Jurnal;
use App\Models\PemakaianBarang;
use App\Models\PermintaanPersetujuan;
use App\Models\User;
use App\Services\PersetujuanOperasiService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MakerCheckerTest extends TestCase
{
    use DatabaseTransactions;

    private function akuntan(): User
    {
        return User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
    }

    private function manajer(): User
    {
        return User::factory()->create(['type' => User::ROLE_ACCOUNTING_MANAGER]);
    }

    private function buatJurnalManual(User $pembuat): Jurnal
    {
        $kas = BaganAkun::where('is_cash_bank', true)->where('is_postable', true)->firstOrFail();
        $lawan = BaganAkun::where('is_postable', true)->where('id', '!=', $kas->id)->firstOrFail();

        $this->actingAs($pembuat)->postJson(route('jurnal.store'), [
            'no_jurnal' => '26-09-MK-IX-' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT),
            'tanggal' => today()->toDateString(),
            'keterangan' => 'Uji maker checker',
            'details' => [
                ['coa_id' => $kas->id, 'debit' => 100000, 'kredit' => 0],
                ['coa_id' => $lawan->id, 'debit' => 0, 'kredit' => 100000],
            ],
        ])->assertCreated();

        return Jurnal::where('sumber_transaksi', 'MANUAL')->latest('id')->firstOrFail();
    }

    public function test_manual_journal_waits_for_approval_before_it_reaches_the_ledger(): void
    {
        $akuntan = $this->akuntan();
        $manajer = $this->manajer();
        $jurnal = $this->buatJurnalManual($akuntan);

        $this->actingAs($akuntan)->postJson(route('jurnal.post', $jurnal->id))->assertOk();

        $this->assertSame('PENDING_APPROVAL', $jurnal->fresh()->status);
        $this->assertSame(
            0,
            Jurnal::where('id', $jurnal->id)->where('status', 'POSTED')->count(),
            'Jurnal yang menunggu persetujuan tidak boleh masuk buku besar.'
        );

        $this->actingAs($manajer)->postJson(route('jurnal.approve', $jurnal->id))->assertOk();

        $segar = $jurnal->fresh();
        $this->assertSame('POSTED', $segar->status);
        $this->assertSame($manajer->id, $segar->posted_by);
    }

    public function test_accounting_cannot_approve_a_manual_journal_itself(): void
    {
        $akuntan = $this->akuntan();
        $jurnal = $this->buatJurnalManual($akuntan);

        $this->actingAs($akuntan)->postJson(route('jurnal.post', $jurnal->id))->assertOk();
        $this->actingAs($akuntan)->postJson(route('jurnal.approve', $jurnal->id))->assertForbidden();

        $this->assertSame('PENDING_APPROVAL', $jurnal->fresh()->status);
    }

    public function test_a_manager_cannot_approve_a_manual_journal_they_raised_themselves(): void
    {
        $manajer = $this->manajer();
        $jurnal = Jurnal::create([
            'no_jurnal' => '26-09-MK-IX-777',
            'tanggal' => today(),
            'keterangan' => 'Dibuat manajer sendiri',
            'sumber_transaksi' => 'MANUAL',
            'status' => 'PENDING_APPROVAL',
            'created_by' => $manajer->id,
            'total_debit' => 0,
            'total_kredit' => 0,
        ]);

        $this->actingAs($manajer)->postJson(route('jurnal.approve', $jurnal->id))->assertForbidden();
        $this->assertSame('PENDING_APPROVAL', $jurnal->fresh()->status);
    }

    public function test_rejecting_a_manual_journal_sends_it_back_to_draft(): void
    {
        $akuntan = $this->akuntan();
        $manajer = $this->manajer();
        $jurnal = $this->buatJurnalManual($akuntan);

        $this->actingAs($akuntan)->postJson(route('jurnal.post', $jurnal->id))->assertOk();
        $this->actingAs($manajer)->postJson(route('jurnal.reject', $jurnal->id))->assertOk();

        $this->assertSame('DRAFT', $jurnal->fresh()->status);
    }

    public function test_reversal_is_only_requested_over_http_and_executes_on_approval(): void
    {
        $akuntan = $this->akuntan();
        $manajer = $this->manajer();
        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)->firstOrFail();

        $this->actingAs($akuntan)
            ->post(route('wms-control.pemakaian-barang.reverse', $npk), ['reason' => 'Koreksi pencatatan pemakaian.'])
            ->assertRedirect();

        $this->assertSame(
            PemakaianBarang::POSTED,
            $npk->fresh()->status,
            'Pembalikan tidak boleh langsung jalan sebelum disetujui.'
        );

        $permintaan = PermintaanPersetujuan::pending()
            ->where('sub_jenis', PersetujuanOperasiService::REVERSE_NPK)
            ->where('referensi_id', $npk->id)
            ->firstOrFail();

        $this->actingAs($manajer)
            ->post(route('permintaan-persetujuan.approve', $permintaan))
            ->assertRedirect();

        $this->assertSame(PermintaanPersetujuan::APPROVED, $permintaan->fresh()->status);
        $this->assertSame(PemakaianBarang::REVERSED, $npk->fresh()->status);
    }

    public function test_the_same_reversal_cannot_be_queued_twice(): void
    {
        $akuntan = $this->akuntan();
        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)->firstOrFail();

        $this->actingAs($akuntan)
            ->post(route('wms-control.pemakaian-barang.reverse', $npk), ['reason' => 'Koreksi pencatatan pemakaian.'])
            ->assertRedirect();

        $this->actingAs($akuntan)
            ->post(route('wms-control.pemakaian-barang.reverse', $npk), ['reason' => 'Koreksi pencatatan pemakaian lagi.'])
            ->assertSessionHasErrors('persetujuan');

        $this->assertSame(
            1,
            PermintaanPersetujuan::where('sub_jenis', PersetujuanOperasiService::REVERSE_NPK)
                ->where('referensi_id', $npk->id)
                ->count()
        );
    }

    public function test_a_requester_can_never_approve_their_own_request_even_as_super_admin(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('wms-control.pemakaian-barang.reverse', $npk), ['reason' => 'Koreksi oleh super admin.'])
            ->assertRedirect();

        $permintaan = PermintaanPersetujuan::pending()->where('referensi_id', $npk->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('permintaan-persetujuan.approve', $permintaan))
            ->assertSessionHasErrors('persetujuan');

        $this->assertSame(PermintaanPersetujuan::PENDING, $permintaan->fresh()->status);
        $this->assertSame(PemakaianBarang::POSTED, $npk->fresh()->status);
    }

    public function test_a_decided_request_cannot_be_decided_again(): void
    {
        $akuntan = $this->akuntan();
        $manajer = $this->manajer();
        $npk = PemakaianBarang::where('status', PemakaianBarang::POSTED)->firstOrFail();

        $this->actingAs($akuntan)
            ->post(route('wms-control.pemakaian-barang.reverse', $npk), ['reason' => 'Koreksi pencatatan pemakaian.'])
            ->assertRedirect();

        $permintaan = PermintaanPersetujuan::pending()->where('referensi_id', $npk->id)->firstOrFail();

        $this->actingAs($manajer)->post(route('permintaan-persetujuan.approve', $permintaan))->assertRedirect();
        $this->actingAs($manajer)->post(route('permintaan-persetujuan.approve', $permintaan))->assertForbidden();

        $this->assertSame(PermintaanPersetujuan::APPROVED, $permintaan->fresh()->status);
    }

    public function test_changing_an_account_that_carries_journal_lines_goes_through_approval(): void
    {
        $akuntan = $this->akuntan();
        $manajer = $this->manajer();

        $akun = BaganAkun::whereHas('jurnalDetails')->firstOrFail();
        $namaLama = $akun->nama_akun;
        $namaBaru = $namaLama . ' (revisi)';

        $this->actingAs($akuntan)->putJson(route('bagan-akun.update', $akun->id), [
            'kode_akun' => $akun->kode_akun,
            'nama_akun' => $namaBaru,
            'kategori_akun' => $akun->kategori_akun,
            'posisi_normal' => $akun->posisi_normal,
            'is_active' => 1,
            'is_postable' => 1,
        ])->assertOk();

        $this->assertSame($namaLama, $akun->fresh()->nama_akun, 'Perubahan akun terpakai tidak boleh langsung berlaku.');

        $permintaan = PermintaanPersetujuan::pending()
            ->where('sub_jenis', PersetujuanOperasiService::COA_UBAH)
            ->where('referensi_id', $akun->id)
            ->firstOrFail();

        $this->actingAs($manajer)->post(route('permintaan-persetujuan.approve', $permintaan))->assertRedirect();

        $this->assertSame($namaBaru, $akun->fresh()->nama_akun);
    }

    public function test_changing_an_unused_account_still_applies_directly(): void
    {
        $akuntan = $this->akuntan();

        $akun = BaganAkun::create([
            'kode_akun' => '9901',
            'nama_akun' => 'Akun Uji Belum Dipakai',
            'kategori_akun' => 'BEBAN',
            'posisi_normal' => 'DEBIT',
            'is_active' => true,
            'is_postable' => true,
            'is_cash_bank' => false,
        ]);

        $this->actingAs($akuntan)->putJson(route('bagan-akun.update', $akun->id), [
            'kode_akun' => '9901',
            'nama_akun' => 'Akun Uji Sudah Diganti',
            'kategori_akun' => 'BEBAN',
            'posisi_normal' => 'DEBIT',
            'is_active' => 1,
            'is_postable' => 1,
        ])->assertOk();

        $this->assertSame('Akun Uji Sudah Diganti', $akun->fresh()->nama_akun);
        $this->assertSame(0, PermintaanPersetujuan::where('referensi_id', $akun->id)->count());
    }

    public function test_account_mapping_changes_wait_for_approval(): void
    {
        $akuntan = $this->akuntan();
        $manajer = $this->manajer();

        $setting = AccountingSetting::where('key', AccountingSetting::BIAYA_BANK)->firstOrFail();
        $lama = (int) $setting->coa_id;
        $baru = (int) BaganAkun::where('is_active', true)
            ->where('is_postable', true)
            ->where('kategori_akun', 'BEBAN')
            ->where('posisi_normal', 'DEBIT')
            ->where('id', '!=', $lama)
            ->firstOrFail()
            ->id;

        $global = AccountingSetting::pluck('coa_id', 'key')
            ->map(fn ($id) => (int) $id)
            ->all();
        $global[AccountingSetting::BIAYA_BANK] = $baru;

        $peran = [
            'coa_persediaan_id' => ['ASET', 'DEBIT'],
            'coa_beban_id' => ['BEBAN', 'DEBIT'],
            'coa_clearing_lpb_id' => ['LIABILITAS', 'KREDIT'],
            'coa_beban_selisih_opname_id' => ['BEBAN', 'DEBIT'],
            'coa_koreksi_opname_id' => ['PENDAPATAN', 'KREDIT'],
        ];

        $categories = \App\Models\KategoriBahan::all()
            ->filter(function ($kategori) use ($peran) {
                foreach ($peran as $kolom => $harus) {
                    $akun = BaganAkun::find($kategori->{$kolom});
                    if (!$akun || $akun->kategori_akun !== $harus[0] || $akun->posisi_normal !== $harus[1]) {
                        return false;
                    }
                }

                return true;
            })
            ->mapWithKeys(fn ($kategori) => [$kategori->id => [
                'coa_persediaan_id' => (int) $kategori->coa_persediaan_id,
                'coa_beban_id' => (int) $kategori->coa_beban_id,
                'coa_clearing_lpb_id' => (int) $kategori->coa_clearing_lpb_id,
                'coa_beban_selisih_opname_id' => (int) $kategori->coa_beban_selisih_opname_id,
                'coa_koreksi_opname_id' => (int) $kategori->coa_koreksi_opname_id,
            ]])
            ->all();

        $this->assertNotEmpty($categories, 'Butuh minimal satu kategori dengan mapping yang lolos validasi.');

        $this->actingAs($akuntan)->putJson(route('bagan-akun.mapping.update'), [
            'global' => $global,
            'categories' => $categories,
        ])->assertOk();

        $this->assertSame($lama, (int) $setting->fresh()->coa_id, 'Mapping tidak boleh berubah sebelum disetujui.');

        $permintaan = PermintaanPersetujuan::pending()
            ->where('sub_jenis', PersetujuanOperasiService::COA_MAPPING)
            ->firstOrFail();

        $this->actingAs($manajer)->post(route('permintaan-persetujuan.approve', $permintaan))->assertRedirect();

        $this->assertSame($baru, (int) $setting->fresh()->coa_id);
    }

    public function test_the_approval_queue_is_visible_to_accounting_but_closed_to_others(): void
    {
        $this->actingAs($this->akuntan())->get(route('permintaan-persetujuan.index'))->assertOk()->assertSee('Antrean Persetujuan');
        $this->actingAs($this->manajer())->get(route('permintaan-persetujuan.index'))->assertOk();

        $gudang = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);
        $this->actingAs($gudang)->get(route('permintaan-persetujuan.index'))->assertForbidden();
    }
}
