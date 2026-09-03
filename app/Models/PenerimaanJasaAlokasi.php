<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanJasaAlokasi extends Model
{
    protected $table = 'wms_penerimaan_jasa_alokasi';
    protected $guarded = [];
    protected $casts = ['percentage' => 'decimal:4', 'amount' => 'decimal:2'];
    public function bapDetail()
    {
        return $this->belongsTo(PenerimaanJasaDetail::class, 'service_bap_detail_id');
    }
}
