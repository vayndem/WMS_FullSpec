<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananPengambilanDetail extends Model
{
    protected $table = 'wms_pesanan_pengambilan_detail';
    public $timestamps = false;
    protected $guarded = ['id'];
}
