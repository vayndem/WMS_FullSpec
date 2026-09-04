<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialPersediaan extends Model
{
    protected $table = 'wms_serial_persediaan';
    protected $guarded = ['id'];
    public function lot() { return $this->belongsTo(LotPersediaan::class, 'inventory_lot_id'); }
}
