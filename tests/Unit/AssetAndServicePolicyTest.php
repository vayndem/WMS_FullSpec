<?php
namespace Tests\Unit;
use App\Models\User;
use App\Models\Aset;
use App\Models\KategoriAset;
use App\Models\ServiceBap;
use App\Models\ServicePurchase;
use App\Models\Lpb;
use App\Policies\AsetPolicy;
use App\Policies\KategoriAsetPolicy;
use App\Policies\ServiceBapPolicy;
use App\Policies\ServicePurchasePolicy;
use Tests\TestCase;
class AssetAndServicePolicyTest extends TestCase {
    private function user(int $type): User { return new User(['id'=>$type,'name'=>"Role {$type}",'type'=>$type]); }
    public function test_asset_visibility_and_financial_access_follow_roles(): void {
        $policy=new AsetPolicy();$asset=new Aset(['status'=>'ACTIVE']);
        foreach([User::ROLE_PURCHASING, User::ROLE_FINANCE, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING] as $type)$this->assertTrue($policy->view($this->user($type),$asset));
        $this->assertTrue($policy->viewFinancials($this->user(User::ROLE_PURCHASING)));
        $this->assertTrue($policy->viewFinancials($this->user(User::ROLE_ACCOUNTING)));
        $this->assertFalse($policy->viewFinancials($this->user(User::ROLE_FINANCE)));
        $this->assertTrue($policy->create($this->user(User::ROLE_ACCOUNTING)));
        $this->assertFalse($policy->create($this->user(User::ROLE_PURCHASING)));
        $this->assertTrue($policy->depreciate($this->user(User::ROLE_ACCOUNTING),$asset));
    }
    public function test_only_accounting_manages_asset_categories(): void {
        $policy=new KategoriAsetPolicy();
        $this->assertTrue($policy->create($this->user(User::ROLE_ACCOUNTING)));
        $this->assertFalse($policy->create($this->user(User::ROLE_PURCHASING)));
    }
    public function test_purchasing_and_accounting_manage_services_but_finance_does_not(): void {
        $poPolicy=new ServicePurchasePolicy();$bapPolicy=new ServiceBapPolicy();
        $po=new ServicePurchase(['document_type'=>'SERVICE']);$bap=new ServiceBap(['document_type'=>'SERVICE_BAP','status'=>Lpb::POSTED]);
        foreach([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING] as $type){$this->assertTrue($poPolicy->view($this->user($type),$po));$this->assertTrue($bapPolicy->view($this->user($type),$bap));}
        $this->assertFalse($poPolicy->viewAny($this->user(User::ROLE_FINANCE)));
        $this->assertFalse($bapPolicy->viewAny($this->user(User::ROLE_FINANCE)));
    }
}
