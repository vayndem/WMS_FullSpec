<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'bahan_id',
        'system_quantity',
        'physical_quantity',
        'difference_quantity',
        'unit_cost',
        'difference_value',
        'reason',
        'notes',
        'physical_confirmed_by',
        'physical_confirmed_at',
        'valuation_confirmed_by',
        'valuation_confirmed_at',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:6',
        'physical_quantity' => 'decimal:6',
        'difference_quantity' => 'decimal:6',
        'unit_cost' => 'decimal:4',
        'difference_value' => 'decimal:2',
        'physical_confirmed_at' => 'datetime',
        'valuation_confirmed_at' => 'datetime',
    ];

    public function opname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
