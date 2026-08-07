<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $guarded = [];
    protected $casts = [
        'acquisition_date' => 'date', 'depreciation_start_date' => 'date',
        'last_depreciation_date' => 'date', 'acquisition_cost' => 'decimal:2',
        'residual_value' => 'decimal:2', 'opening_accumulated_depreciation' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2', 'book_value' => 'decimal:2',
    ];

    public function category() { return $this->belongsTo(AssetCategory::class, 'asset_category_id'); }
    public function acquisitionCreditAccount() { return $this->belongsTo(ChartOfAccount::class, 'acquisition_credit_coa_id'); }
    public function depreciations() { return $this->hasMany(AssetDepreciation::class); }
    public function disposal() { return $this->hasOne(AssetDisposal::class); }

    public function suggestedMonthlyDepreciation(): float
    {
        if (!$this->useful_life_months) return 0;
        return round(max((float) $this->acquisition_cost - (float) $this->residual_value, 0) / $this->useful_life_months, 2);
    }
}
