<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class InventoryLot extends Model
{
    use Auditable;
    protected $guarded = ['id'];
    protected $casts = ['manufactured_at' => 'date', 'expires_at' => 'date', 'blocked' => 'boolean'];
    public function bahan() { return $this->belongsTo(Bahan::class, 'bahan_id'); }
    public function serials() { return $this->hasMany(InventorySerial::class); }
}
