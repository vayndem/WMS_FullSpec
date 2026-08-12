<?php

namespace Tests\Unit;

use App\Models\Gudang;
use App\Models\PemeriksaanConsider;
use App\Models\TransferGudang;
use App\Models\User;
use App\Policies\GudangPolicy;
use App\Policies\PemeriksaanConsiderPolicy;
use App\Policies\TransferGudangPolicy;
use Tests\TestCase;

class MultiWarehousePolicyTest extends TestCase
{
    public function test_only_warehouse_role_manages_multi_warehouse(): void
    {
        $policy = new GudangPolicy();
        $gudang = new Gudang();

        $this->assertTrue($policy->viewAny(new User(['type' => User::ROLE_WAREHOUSE])));
        $this->assertTrue($policy->update(new User(['type' => User::ROLE_WAREHOUSE]), $gudang));
        foreach ([User::ROLE_PURCHASING, User::ROLE_FINANCE, User::ROLE_ACCOUNTING, User::ROLE_PRODUCTION] as $type) {
            $this->assertFalse($policy->viewAny(new User(['type' => $type])));
        }
        $this->assertFalse($policy->delete(new User(['type' => User::ROLE_WAREHOUSE]), $gudang));
    }

    public function test_transfer_actions_follow_document_status(): void
    {
        $policy = new TransferGudangPolicy();
        $user = new User(['type' => User::ROLE_WAREHOUSE]);

        $draft = (new TransferGudang())->forceFill(['status' => TransferGudang::DRAFT]);
        $submitted = (new TransferGudang())->forceFill(['status' => TransferGudang::DIAJUKAN]);
        $posted = (new TransferGudang())->forceFill(['status' => TransferGudang::DITERIMA]);

        $this->assertTrue($policy->update($user, $draft));
        $this->assertTrue($policy->submit($user, $draft));
        $this->assertTrue($policy->confirm($user, $submitted));
        $this->assertFalse($policy->update($user, $posted));
        $this->assertFalse($policy->confirm($user, $posted));
    }

    public function test_production_role_can_access_transfer_for_assigned_warehouse(): void
    {
        $policy = new TransferGudangPolicy();
        $user = new User(['type' => User::ROLE_PRODUCTION]);
        $user->setRelation('pembagianGudangs', collect([
            new \App\Models\PembagianGudang(['gudang_id' => 10, 'boleh_transfer' => true]),
        ]));

        $assigned = (new TransferGudang())->forceFill(['status' => TransferGudang::DRAFT, 'gudang_asal_id' => 10, 'gudang_tujuan_id' => 11]);
        $outside = (new TransferGudang())->forceFill(['status' => TransferGudang::DRAFT, 'gudang_asal_id' => 12, 'gudang_tujuan_id' => 13]);

        $this->assertTrue($policy->view($user, $assigned));
        $this->assertTrue($policy->update($user, $assigned));
        $this->assertFalse($policy->view($user, $outside));
        $this->assertFalse($policy->update($user, $outside));
    }

    public function test_confirmed_consider_decision_is_final(): void
    {
        $policy = new PemeriksaanConsiderPolicy();
        $user = new User(['type' => User::ROLE_WAREHOUSE]);

        $draft = (new PemeriksaanConsider())->forceFill(['status' => PemeriksaanConsider::DRAFT]);
        $confirmed = (new PemeriksaanConsider())->forceFill(['status' => PemeriksaanConsider::DIKONFIRMASI]);

        $this->assertTrue($policy->confirm($user, $draft));
        $this->assertFalse($policy->update($user, $confirmed));
        $this->assertFalse($policy->confirm($user, $confirmed));
    }
}
