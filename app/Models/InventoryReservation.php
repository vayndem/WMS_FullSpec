<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    use Auditable;
    protected $guarded = ['id'];
    protected $casts = ['quantity' => 'decimal:6', 'picked_quantity' => 'decimal:6', 'expires_at' => 'datetime'];
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
