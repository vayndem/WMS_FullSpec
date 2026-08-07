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
    public function test_only_type_fourteen_manages_multi_warehouse(): void
    {
        $policy = new GudangPolicy();
        $gudang = new Gudang();

        $this->assertTrue($policy->viewAny(new User(['type' => 14])));
        $this->assertTrue($policy->update(new User(['type' => 14]), $gudang));
        foreach ([5, 13, 33] as $type) {
            $this->assertFalse($policy->viewAny(new User(['type' => $type])));
        }
        $this->assertFalse($policy->delete(new User(['type' => 14]), $gudang));
    }

    public function test_transfer_actions_follow_document_status(): void
    {
        $policy = new TransferGudangPolicy();
        $user = new User(['type' => 14]);

        $draft = (new TransferGudang())->forceFill(['status' => TransferGudang::DRAFT]);
        $submitted = (new TransferGudang())->forceFill(['status' => TransferGudang::DIAJUKAN]);
        $posted = (new TransferGudang())->forceFill(['status' => TransferGudang::DIKONFIRMASI]);

        $this->assertTrue($policy->update($user, $draft));
        $this->assertTrue($policy->submit($user, $draft));
        $this->assertTrue($policy->confirm($user, $submitted));
        $this->assertFalse($policy->update($user, $posted));
        $this->assertFalse($policy->confirm($user, $posted));
    }

    public function test_confirmed_consider_decision_is_final(): void
    {
        $policy = new PemeriksaanConsiderPolicy();
        $user = new User(['type' => 14]);

        $draft = (new PemeriksaanConsider())->forceFill(['status' => PemeriksaanConsider::DRAFT]);
        $confirmed = (new PemeriksaanConsider())->forceFill(['status' => PemeriksaanConsider::DIKONFIRMASI]);

        $this->assertTrue($policy->confirm($user, $draft));
        $this->assertFalse($policy->update($user, $confirmed));
        $this->assertFalse($policy->confirm($user, $confirmed));
    }
}
