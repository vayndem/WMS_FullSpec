<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LayerPersediaan extends Model
{
    use Auditable;

    protected $table = 'wms_layer_persediaan';

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
        return $this->belongsTo(LokasiGudang::class, 'warehouse_location_id');
    }
    public function lot()
    {
        return $this->belongsTo(LotPersediaan::class, 'inventory_lot_id');
    }
}
