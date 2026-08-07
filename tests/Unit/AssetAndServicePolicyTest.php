<?php
namespace Tests\Unit;
use App\Models\User;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\ServiceBap;
use App\Models\ServicePurchase;
use App\Policies\AssetPolicy;
use App\Policies\AssetCategoryPolicy;
use App\Policies\ServiceBapPolicy;
use App\Policies\ServicePurchasePolicy;
use Tests\TestCase;
class AssetAndServicePolicyTest extends TestCase {
    private function user(int $type): User { return new User(['id'=>$type,'name'=>"Type {$type}",'type'=>$type]); }
    public function test_asset_visibility_and_financial_access_follow_roles(): void {
        $policy=new AssetPolicy();$asset=new Asset(['status'=>'ACTIVE']);
        foreach([1,5,13,33] as $type)$this->assertTrue($policy->view($this->user($type),$asset));
        $this->assertTrue($policy->viewFinancials($this->user(5)));
        $this->assertTrue($policy->viewFinancials($this->user(33)));
        $this->assertFalse($policy->viewFinancials($this->user(13)));
        $this->assertTrue($policy->create($this->user(33)));
        $this->assertFalse($policy->create($this->user(5)));
        $this->assertTrue($policy->depreciate($this->user(33),$asset));
    }
    public function test_only_accounting_manages_asset_categories(): void {
        $policy=new AssetCategoryPolicy();
        $this->assertTrue($policy->create($this->user(33)));
        $this->assertFalse($policy->create($this->user(5)));
    }
    public function test_type_five_and_accounting_manage_services_but_finance_does_not(): void {
        $poPolicy=new ServicePurchasePolicy();$bapPolicy=new ServiceBapPolicy();
        $po=new ServicePurchase(['document_type'=>'SERVICE']);$bap=new ServiceBap(['document_type'=>'SERVICE_BAP','is_cancelled'=>false]);
        foreach([5,33] as $type){$this->assertTrue($poPolicy->view($this->user($type),$po));$this->assertTrue($bapPolicy->view($this->user($type),$bap));}
        $this->assertFalse($poPolicy->viewAny($this->user(13)));
        $this->assertFalse($bapPolicy->viewAny($this->user(13)));
    }
}
