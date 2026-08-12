<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySerial extends Model
{
    protected $guarded = ['id'];
    public function lot() { return $this->belongsTo(InventoryLot::class, 'inventory_lot_id'); }
}
