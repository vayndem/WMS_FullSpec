<?php

namespace Tests\Feature;

use App\Models\FakturPembelian;
use App\Models\LampiranDokumen;
use App\Models\LogAudit;
use App\Models\PenerimaanBarang;
use App\Models\User;
use App\Notifications\PengingatTenggat;
use App\Notifications\PersetujuanMenunggu;
use App\Services\LampiranService;
use App\Services\UserAccountService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformHardeningTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdmin(): User
    {
        return User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
    }

    private function cobaMasuk(User $user, string $password)
    {
        $this->post(route('logout'));
        \Illuminate\Support\Facades\RateLimiter::clear(mb_strtolower($user->email) . '|127.0.0.1');

        return $this->post(route('login.attempt'), ['email' => $user->email, 'password' => $password]);
    }

    public function test_deactivated_account_cannot_log_in_and_the_rejection_is_audited(): void
    {
        $user = User::factory()->create([
            'type' => User::ROLE_WAREHOUSE,
            'password' => 'RahasiaKuat9',
            'is_active' => false,
        ]);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'RahasiaKuat9'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('dinonaktifkan', session('errors')->first('email'));

        $this->assertDatabaseHas('wms_log_audit', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => UserAccountService::LOGIN_DITOLAK,
        ]);
    }

    public function test_successful_login_is_audited_and_stamps_last_login(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_FINANCE, 'password' => 'RahasiaKuat9']);
        $this->assertNull($user->last_login_at);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'RahasiaKuat9'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->assertDatabaseHas('wms_log_audit', [
            'auditable_id' => $user->id,
            'event' => UserAccountService::LOGIN,
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertDatabaseHas('wms_log_audit', [
            'auditable_id' => $user->id,
            'event' => UserAccountService::LOGOUT,
        ]);
    }

    public function test_a_failed_login_is_audited_and_then_rate_limited(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_WAREHOUSE, 'password' => 'RahasiaKuat9']);
        RateLimiter::clear(mb_strtolower($user->email) . '|127.0.0.1');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'salah-sekali'])
                ->assertStatus(302);
        }

        $this->assertDatabaseHas('wms_log_audit', [
            'auditable_id' => $user->id,
            'event' => UserAccountService::LOGIN_GAGAL,
        ]);
        $this->assertSame(5, LogAudit::where('auditable_id', $user->id)
            ->where('event', UserAccountService::LOGIN_GAGAL)->count());

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'salah-sekali'])
            ->assertStatus(429);

        $this->assertSame(5, LogAudit::where('auditable_id', $user->id)
            ->where('event', UserAccountService::LOGIN_GAGAL)->count());
    }

    public function test_user_management_is_reachable_only_by_super_admin(): void
    {
        foreach ([User::ROLE_PURCHASING, User::ROLE_FINANCE, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING, User::ROLE_ACCOUNTING_MANAGER, User::ROLE_PRODUCTION] as $role) {
            $this->actingAs(User::factory()->create(['type' => $role]))
                ->get(route('user.index'))
                ->assertForbidden();
        }

        $this->actingAs($this->superAdmin())->get(route('user.index'))->assertOk();
        $this->actingAs($this->superAdmin())->get(route('user.login-audit'))->assertOk();
    }

    public function test_super_admin_creates_a_user_then_deactivates_and_reactivates_it(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('user.store'), [
            'name' => 'Operator Gudang Baru',
            'email' => 'operator.baru@example.test',
            'type' => User::ROLE_WAREHOUSE,
            'password' => 'RahasiaKuat9',
            'password_confirmation' => 'RahasiaKuat9',
        ])->assertRedirect(route('user.index'));

        $baru = User::where('email', 'operator.baru@example.test')->firstOrFail();
        $this->assertTrue($baru->is_active);

        $this->cobaMasuk($baru, 'RahasiaKuat9')->assertRedirect(route('dashboard'));

        $this->actingAs($admin)->post(route('user.deactivate', $baru))->assertRedirect(route('user.index'));
        $this->assertFalse($baru->fresh()->is_active);
        $this->assertNotNull($baru->fresh()->deactivated_at);
        $this->assertSame($admin->id, $baru->fresh()->deactivated_by);

        $this->cobaMasuk($baru, 'RahasiaKuat9')->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)->post(route('user.reactivate', $baru))->assertRedirect(route('user.index'));
        $this->assertTrue($baru->fresh()->is_active);
        $this->assertNull($baru->fresh()->deactivated_at);

        $this->cobaMasuk($baru, 'RahasiaKuat9')->assertRedirect(route('dashboard'));
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('user.deactivate', $admin))->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_the_last_active_super_admin_cannot_demote_themselves(): void
    {
        $satuSatunya = $this->superAdmin();
        User::where('type', User::ROLE_SUPER_ADMIN)
            ->where('id', '!=', $satuSatunya->id)
            ->update(['is_active' => false]);

        $this->actingAs($satuSatunya)->put(route('user.update', $satuSatunya), [
            'name' => $satuSatunya->name,
            'email' => $satuSatunya->email,
            'type' => User::ROLE_WAREHOUSE,
        ])->assertSessionHasErrors('type');

        $this->assertTrue($satuSatunya->fresh()->isSuperAdmin());
        $this->assertStringContainsString('terakhir', session('errors')->first('type'));
    }

    public function test_a_super_admin_can_be_demoted_while_another_active_one_remains(): void
    {
        $penolong = $this->superAdmin();
        $target = $this->superAdmin();

        $this->actingAs($penolong)->put(route('user.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'type' => User::ROLE_WAREHOUSE,
        ])->assertRedirect(route('user.index'));

        $this->assertFalse($target->fresh()->isSuperAdmin());
        $this->assertTrue($target->fresh()->isWarehouse());
    }

    public function test_a_user_changes_own_password_only_with_the_correct_current_one(): void
    {
        $user = User::factory()->create(['type' => User::ROLE_FINANCE, 'password' => 'RahasiaKuat9']);

        $this->actingAs($user)->put(route('profil.password'), [
            'password_lama' => 'password-salah',
            'password' => 'RahasiaBaru9',
            'password_confirmation' => 'RahasiaBaru9',
        ])->assertSessionHasErrors('password_lama');

        $this->actingAs($user)->put(route('profil.password'), [
            'password_lama' => 'RahasiaKuat9',
            'password' => 'RahasiaBaru9',
            'password_confirmation' => 'RahasiaBaru9',
        ])->assertRedirect(route('profil.show'));

        $this->post(route('logout'));
        RateLimiter::clear(mb_strtolower($user->email) . '|127.0.0.1');
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'RahasiaBaru9'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_password_reset_link_is_withheld_from_a_deactivated_account(): void
    {
        Notification::fake();

        $nonaktif = User::factory()->create(['type' => User::ROLE_WAREHOUSE, 'is_active' => false]);
        $aktif = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);

        $this->post(route('password.email'), ['email' => $nonaktif->email])->assertSessionHas('status');
        Notification::assertNothingSent();

        RateLimiter::clear(mb_strtolower($aktif->email) . '|127.0.0.1');
        $this->post(route('password.email'), ['email' => $aktif->email])->assertSessionHas('status');
        Notification::assertSentTo($aktif, \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    public function test_invoice_creation_notifies_active_accounting_managers_only(): void
    {
        Notification::fake();

        $aktif = User::factory()->create(['type' => User::ROLE_ACCOUNTING_MANAGER]);
        $nonaktif = User::factory()->create(['type' => User::ROLE_ACCOUNTING_MANAGER, 'is_active' => false]);
        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);

        app(\App\Services\NotifikasiService::class)->mintaPersetujuan(
            [User::ROLE_ACCOUNTING_MANAGER],
            'Faktur pembelian',
            'Faktur menunggu persetujuan Anda',
            'INV-UJI - Rp 1.000,00',
            route('faktur-pembelian.index'),
            'INV-UJI',
        );

        Notification::assertSentTo($aktif, PersetujuanMenunggu::class);
        Notification::assertNotSentTo($nonaktif, PersetujuanMenunggu::class);
        Notification::assertNotSentTo($finance, PersetujuanMenunggu::class);
    }

    public function test_daily_reminder_command_notifies_finance_about_an_overdue_invoice(): void
    {
        Notification::fake();

        $finance = User::factory()->create(['type' => User::ROLE_FINANCE]);
        $gudangRole = User::factory()->create(['type' => User::ROLE_WAREHOUSE]);

        FakturPembelian::where('status', '!=', FakturPembelian::VOID)
            ->where('sisa_tagihan', '>', 0)->exists()
            ?: $this->markTestSkipped('Data seed tidak memiliki invoice dengan sisa tagihan.');

        FakturPembelian::whereNotIn('status', [FakturPembelian::VOID, FakturPembelian::PENDING_APPROVAL])
            ->where('sisa_tagihan', '>', 0)
            ->limit(1)
            ->update(['tgl_deadline_pembayaran' => today()->subDays(4)]);

        $this->artisan('wms:pengingat-harian')->assertSuccessful();

        Notification::assertSentTo($finance, PengingatTenggat::class, function (PengingatTenggat $notification) use ($finance) {
            $data = $notification->toArray($finance);

            return $data['jenis'] === 'TENGGAT' && str_contains($data['ringkasan'], 'melewati tenggat');
        });
        Notification::assertNotSentTo($gudangRole, PersetujuanMenunggu::class);
    }

    public function test_the_scheduler_registers_the_operational_jobs(): void
    {
        Artisan::call('schedule:list');
        $perintah = Artisan::output();

        foreach (['wms:hitung-replenishment', 'wms:pengingat-harian', 'wms:penyusutan-bulanan'] as $signature) {
            $this->assertStringContainsString($signature, $perintah, "Perintah {$signature} harus terdaftar di scheduler.");
        }
    }

    public function test_attachment_is_stored_on_the_private_disk_and_downloaded_through_policy(): void
    {
        Storage::fake('lampiran');

        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $produksi = User::factory()->create(['type' => User::ROLE_PRODUCTION]);
        $lpb = PenerimaanBarang::query()->firstOrFail();

        $this->actingAs($purchasing)->post(route('lampiran.store'), [
            'lampiran_type' => 'penerimaan-barang',
            'lampiran_id' => $lpb->id,
            'kategori' => 'SURAT_JALAN',
            'keterangan' => 'Surat jalan supplier',
            'berkas' => UploadedFile::fake()->create('surat-jalan.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $lampiran = LampiranDokumen::where('lampiran_type', PenerimaanBarang::class)
            ->where('lampiran_id', $lpb->id)->latest('id')->firstOrFail();

        $this->assertSame('lampiran', $lampiran->disk);
        $this->assertSame('SURAT_JALAN', $lampiran->kategori);
        $this->assertSame($purchasing->id, $lampiran->user_id);
        $this->assertNotNull($lampiran->checksum);
        Storage::disk('lampiran')->assertExists($lampiran->path);
        $this->assertStringStartsWith('penerimaan-barang/' . $lpb->id . '/', $lampiran->path);

        $this->actingAs($purchasing)->get(route('lampiran.download', $lampiran))->assertOk();
        $this->actingAs($produksi)->get(route('lampiran.download', $lampiran))->assertForbidden();

        $this->actingAs($purchasing)->delete(route('lampiran.destroy', $lampiran))->assertRedirect();
        Storage::disk('lampiran')->assertMissing($lampiran->path);
        $this->assertDatabaseMissing('wms_lampiran_dokumen', ['id' => $lampiran->id]);
    }

    public function test_attachment_rejects_a_disallowed_type_and_an_oversized_file(): void
    {
        Storage::fake('lampiran');

        $purchasing = User::factory()->create(['type' => User::ROLE_PURCHASING]);
        $lpb = PenerimaanBarang::query()->firstOrFail();
        $sebelum = LampiranDokumen::count();

        $this->actingAs($purchasing)->post(route('lampiran.store'), [
            'lampiran_type' => 'penerimaan-barang',
            'lampiran_id' => $lpb->id,
            'kategori' => 'SURAT_JALAN',
            'berkas' => UploadedFile::fake()->create('skrip-jahat.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('berkas');

        $this->actingAs($purchasing)->post(route('lampiran.store'), [
            'lampiran_type' => 'penerimaan-barang',
            'lampiran_id' => $lpb->id,
            'kategori' => 'SURAT_JALAN',
            'berkas' => UploadedFile::fake()->create('raksasa.pdf', LampiranService::UKURAN_MAKS_KB + 64, 'application/pdf'),
        ])->assertSessionHasErrors('berkas');

        $this->actingAs($purchasing)->post(route('lampiran.store'), [
            'lampiran_type' => 'tabel-rahasia',
            'lampiran_id' => $lpb->id,
            'kategori' => 'SURAT_JALAN',
            'berkas' => UploadedFile::fake()->create('oke.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('lampiran_type');

        $this->assertSame($sebelum, LampiranDokumen::count());
    }
}
