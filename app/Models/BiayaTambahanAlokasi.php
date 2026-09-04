<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiayaTambahanAlokasi extends Model
{
    protected $table = 'wms_biaya_tambahan_alokasi';
    public $timestamps = false;
    protected $guarded = ['id'];
    public function layer()
    {
        return $this->belongsTo(LayerPersediaan::class, 'inventory_layer_id');
    }
}
