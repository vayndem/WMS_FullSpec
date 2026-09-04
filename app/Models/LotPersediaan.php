<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LotPersediaan extends Model
{
    use Auditable;
    protected $table = 'wms_lot_persediaan';
    protected $guarded = ['id'];
    protected $casts = ['manufactured_at' => 'date', 'expires_at' => 'date', 'blocked' => 'boolean'];
    public function bahan() { return $this->belongsTo(Bahan::class, 'bahan_id'); }
    public function serials() { return $this->hasMany(SerialPersediaan::class, 'inventory_lot_id'); }
}
