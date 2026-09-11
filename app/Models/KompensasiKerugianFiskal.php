<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KompensasiKerugianFiskal extends Model
{
    protected $table = 'wms_kompensasi_kerugian_fiskal';
    protected $guarded = ['id'];
    protected $casts = ['jumlah' => 'decimal:2'];

    public function perhitungan()
    {
        return $this->belongsTo(PerhitunganPajakPenghasilan::class, 'perhitungan_id');
    }
}
