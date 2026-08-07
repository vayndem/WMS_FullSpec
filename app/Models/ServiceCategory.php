<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    public const OPERATIONAL = 'SERVICE_OPERATIONAL';
    public const PRODUCTION = 'SERVICE_PRODUCTION';
    protected $guarded = [];
    protected $casts = [
        'requires_datapesanan' => 'boolean',
        'requires_cost_center' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function expenseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'expense_coa_id');
    }
    public function grniAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'grni_coa_id');
    }
    public function kategoriBahan(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'kategori_bahan_id');
    }
    public function poDetails(): HasMany
    {
        return $this->hasMany(ServicePoDetail::class);
    }
}
