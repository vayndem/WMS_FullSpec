<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use PHPUnit\Framework\TestCase;

class ChartOfAccountSafetyTest extends TestCase
{
    public function test_posting_account_must_be_active_and_postable(): void
    {
        $account = new ChartOfAccount([
            'kategori_akun' => 'BEBAN',
            'posisi_normal' => 'DEBIT',
            'is_active' => false,
            'is_postable' => true,
        ]);

        $this->assertFalse($account->isUsableFor([['BEBAN', 'DEBIT']]));

        $account->is_active = true;
        $account->is_postable = false;
        $this->assertFalse($account->isUsableFor([['BEBAN', 'DEBIT']]));
    }

    public function test_account_category_and_normal_position_must_match_mapping_role(): void
    {
        $account = new ChartOfAccount([
            'kategori_akun' => 'ASET',
            'posisi_normal' => 'DEBIT',
            'is_active' => true,
            'is_postable' => true,
            'is_cash_bank' => false,
        ]);

        $this->assertTrue($account->isUsableFor([['ASET', 'DEBIT']]));
        $this->assertFalse($account->isUsableFor([['LIABILITAS', 'KREDIT']]));
        $this->assertFalse($account->isUsableFor([['ASET', 'DEBIT']], true));
    }

    public function test_cash_bank_role_requires_cash_bank_flag(): void
    {
        $account = new ChartOfAccount([
            'kategori_akun' => 'ASET',
            'posisi_normal' => 'DEBIT',
            'is_active' => true,
            'is_postable' => true,
            'is_cash_bank' => true,
        ]);

        $this->assertTrue($account->isUsableFor([['ASET', 'DEBIT']], true));
    }

    public function test_contra_asset_can_be_validated_as_asset_credit(): void
    {
        $account = new ChartOfAccount([
            'kategori_akun' => 'ASET',
            'posisi_normal' => 'KREDIT',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->assertTrue($account->isUsableFor([['ASET', 'KREDIT']]));
        $this->assertFalse($account->isUsableFor([['ASET', 'DEBIT']]));
    }
}
