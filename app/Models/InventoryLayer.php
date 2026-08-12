<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class InventoryLayer extends Model
{
    use Auditable;

    protected $fillable = [
        'bahan_id',
        'gudang_id',
        'warehouse_location_id',
        'inventory_lot_id',
        'stock_status',
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

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }
    public function lot()
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
