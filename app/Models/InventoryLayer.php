<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLayer extends Model
{
    protected $fillable = [
        'bahan_id',
        'gudang_id',
        'source_type',
        'source_id',
        'transaction_date',
        'initial_quantity',
        'remaining_quantity',
        'unit_cost',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'initial_quantity' => 'decimal:6',
        'remaining_quantity' => 'decimal:6',
        'unit_cost' => 'decimal:4',
    ];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
