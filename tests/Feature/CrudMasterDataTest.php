<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CrudMasterDataTest extends TestCase
{
    use DatabaseTransactions;

    private function pengguna(int $peran): User
    {
        return User::factory()->create(['type' => $peran, 'is_active' => true]);
    }

    private function dataPelanggan(array $ubah = []): array
    {
        return array_merge([
            'kode' => 'CUST-CRUD-' . random_int(1000, 9999),
            'nama' => 'PT Uji CRUD',
            'npwp' => '01.234.567.8-901.000',
            'telp' => '0211234567',
            'email' => 'uji@contoh.test',
            'alamat' => 'Jl. Uji No. 1',
            'termin_hari' => 30,
            'plafon_kredit' => 1000000,
            'is_active' => 1,
        ], $ubah);
    }

    public function test_a_customer_can_be_created_updated_and_deleted_over_http(): void
    {
        $pembuat = $this->pengguna(User::ROLE_PURCHASING);
        $akuntansi = $this->pengguna(User::ROLE_ACCOUNTING);
        $data = $this->dataPelanggan();

        $this->actingAs($pembuat)->post(route('pelanggan.store'), $data)->assertRedirect();

        $pelanggan = Pelanggan::where('kode', $data['kode'])->firstOrFail();
        $this->assertSame('PT Uji CRUD', $pelanggan->nama);

        $this->actingAs($pembuat)
            ->put(route('pelanggan.update', $pelanggan), $this->dataPelanggan([
                'kode' => $pelanggan->kode,
                'nama' => 'PT Uji CRUD Diperbarui',
            ]))
            ->assertRedirect();

        $this->assertSame('PT Uji CRUD Diperbarui', $pelanggan->fresh()->nama);

        $this->actingAs($akuntansi)->delete(route('pelanggan.destroy', $pelanggan))->assertRedirect();
        $this->assertNull(Pelanggan::find($pelanggan->id));
    }

    public function test_customer_writes_respect_the_policy_for_each_role(): void
    {
        $gudang = $this->pengguna(User::ROLE_WAREHOUSE);
        $finance = $this->pengguna(User::ROLE_FINANCE);
        $purchasing = $this->pengguna(User::ROLE_PURCHASING);

        $this->actingAs($gudang)->get(route('pelanggan.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('pelanggan.index'))->assertOk();

        $this->actingAs($finance)->post(route('pelanggan.store'), $this->dataPelanggan())->assertForbidden();

        $data = $this->dataPelanggan();
        $this->actingAs($purchasing)->post(route('pelanggan.store'), $data)->assertRedirect();

        $pelanggan = Pelanggan::where('kode', $data['kode'])->firstOrFail();

        $this->actingAs($purchasing)->delete(route('pelanggan.destroy', $pelanggan))->assertForbidden();
        $this->assertNotNull(Pelanggan::find($pelanggan->id));
    }

    public function test_a_customer_cannot_be_created_with_a_duplicate_code_or_missing_name(): void
    {
        $purchasing = $this->pengguna(User::ROLE_PURCHASING);
        $data = $this->dataPelanggan();

        $this->actingAs($purchasing)->post(route('pelanggan.store'), $data)->assertRedirect();

        $this->actingAs($purchasing)
            ->post(route('pelanggan.store'), $data)
            ->assertSessionHasErrors('kode');

        $this->actingAs($purchasing)
            ->post(route('pelanggan.store'), $this->dataPelanggan(['nama' => '']))
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Pelanggan::where('kode', $data['kode'])->count());
    }
}
