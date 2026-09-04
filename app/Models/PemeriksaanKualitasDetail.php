<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemeriksaanKualitasDetail extends Model
{
    protected $table = 'wms_pemeriksaan_kualitas_detail';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['quantity_received' => 'decimal:6', 'quantity_accepted' => 'decimal:6', 'quantity_rejected' => 'decimal:6'];
}
