<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpkStockAllocation extends Model
{
    protected $fillable = ['npk_id', 'inventory_layer_id', 'quantity', 'unit_cost', 'total_cost'];
    protected $casts = ['quantity' => 'decimal:6', 'unit_cost' => 'decimal:4', 'total_cost' => 'decimal:2'];
}
