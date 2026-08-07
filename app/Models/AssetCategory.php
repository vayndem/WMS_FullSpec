<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function assets() { return $this->hasMany(Asset::class); }
    public function assetAccount() { return $this->belongsTo(ChartOfAccount::class, 'asset_coa_id'); }
    public function accumulatedAccount() { return $this->belongsTo(ChartOfAccount::class, 'accumulated_depreciation_coa_id'); }
    public function expenseAccount() { return $this->belongsTo(ChartOfAccount::class, 'depreciation_expense_coa_id'); }
    public function gainAccount() { return $this->belongsTo(ChartOfAccount::class, 'disposal_gain_coa_id'); }
    public function lossAccount() { return $this->belongsTo(ChartOfAccount::class, 'disposal_loss_coa_id'); }
}
