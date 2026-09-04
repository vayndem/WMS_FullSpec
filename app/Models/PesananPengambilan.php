<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PesananPengambilan extends Model
{
    use Auditable;
    protected $table = 'wms_pesanan_pengambilan';
    protected $guarded = ['id'];
    protected $casts = ['picked_at' => 'datetime'];
    public function lines()
    {
        return $this->hasMany(PesananPengambilanDetail::class, 'picking_order_id');
    }
}
