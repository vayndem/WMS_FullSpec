<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\BaganAkun;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AsetDepreciationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_automatic_depreciation_posts_straight_line_amount_and_skips_the_same_period_twice(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $category = \App\Models\KategoriAset::where('code', 'EQUIPMENT')->firstOrFail();
        $asset = Aset::create([
            'nomor_aset' => 'AUTO-DEP-TEST-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Penyusutan Otomatis',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 2400000,
            'residual_value' => 0,
            'useful_life_months' => 24,
            'depreciation_method' => 'STRAIGHT_LINE',
            'accumulated_depreciation' => 0,
            'book_value' => 2400000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Penyusutan Otomatis Test',
        ])->assertOk();

        $asset->refresh();
        $this->assertEqualsWithDelta(100000.0, (float) $asset->accumulated_depreciation, 0.01);
        $this->assertSame(1, $asset->depreciations()->count());

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Penyusutan Otomatis Test',
        ])->assertOk();

        $this->assertSame(1, $asset->depreciations()->count());
        $this->assertEqualsWithDelta(100000.0, (float) $asset->fresh()->accumulated_depreciation, 0.01);
    }

    public function test_manual_depreciation_defaults_to_the_suggested_straight_line_amount_when_amount_is_blank(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $category = \App\Models\KategoriAset::where('code', 'EQUIPMENT')->firstOrFail();
        $asset = Aset::create([
            'nomor_aset' => 'MANUAL-DEP-TEST-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Penyusutan Manual',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 1200000,
            'residual_value' => 0,
            'useful_life_months' => 12,
            'depreciation_method' => 'STRAIGHT_LINE',
            'accumulated_depreciation' => 0,
            'book_value' => 1200000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $this->actingAs($accounting)->postJson(route('aset.depreciate', $asset), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Penyusutan Manual Tanpa Nominal',
        ])->assertRedirect();

        $this->assertEqualsWithDelta(100000.0, (float) $asset->fresh()->accumulated_depreciation, 0.01);
    }

    public function test_declining_balance_depreciation_charges_double_rate_on_the_running_book_value(): void
    {
        $accounting = User::factory()->create(['type' => User::ROLE_ACCOUNTING]);
        $category = \App\Models\KategoriAset::where('is_active', true)->firstOrFail();

        $asset = Aset::create([
            'nomor_aset' => 'AUTO-DEP-SALDO-1',
            'kategori_aset_id' => $category->id,
            'name' => 'Aset Uji Saldo Menurun',
            'condition' => 'BAIK',
            'acquisition_date' => today()->subYear(),
            'acquisition_type' => 'OPENING_BALANCE',
            'acquisition_credit_coa_id' => BaganAkun::where('kode_akun', '3102')->value('id'),
            'acquisition_cost' => 2400000,
            'residual_value' => 0,
            'useful_life_months' => 48,
            'depreciation_method' => Aset::DECLINING_BALANCE,
            'accumulated_depreciation' => 0,
            'book_value' => 2400000,
            'status' => 'ACTIVE',
            'created_by' => $accounting->id,
        ]);

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Saldo Menurun Bulan 1',
        ])->assertOk();

        $asset->refresh();
        $this->assertEqualsWithDelta(100000.0, (float) $asset->accumulated_depreciation, 0.01);
        $this->assertEqualsWithDelta(2300000.0, (float) $asset->book_value, 0.01);

        $this->actingAs($accounting)->postJson(route('aset.depreciate-all'), [
            'posting_date' => today()->toDateString(),
            'period_label' => 'Saldo Menurun Bulan 2',
        ])->assertOk();

        $asset->refresh();
        $this->assertEqualsWithDelta(95833.33, (float) $asset->depreciations()->latest('id')->value('amount'), 0.01);
        $this->assertEqualsWithDelta(2204166.67, (float) $asset->book_value, 0.01);
    }

}
